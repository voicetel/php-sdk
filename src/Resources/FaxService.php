<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Resources;

use VoiceTel\Sdk\Transport;

/**
 * FaxService sends faxes from the account's fax-capable numbers and reads
 * their job history and rendered documents.
 *
 * `send()` posts a multipart form — `fromNumber` and `toNumber` as text
 * fields plus one to ten document file parts (PDF/DOC/DOCX, magic-byte
 * checked server-side, 8 MiB each and in total) — and is idempotent per the
 * client-generated UUID Idempotency-Key: resubmitting the same documents
 * with the same key returns the original job instead of sending twice.
 * Rate-limited to 10 sends per minute per account.
 */
final class FaxService
{
    /** @var array<string, string> extension → MIME type for the accepted fax document types */
    private const CONTENT_TYPES = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    public function __construct(private readonly Transport $t)
    {
    }

    /**
     * GET /v2.2/numbers/fax — every fax-capable number on the account, with
     * the email address faxes to each number deliver to (destinationId 0
     * means the account email).
     *
     * @return array<string, mixed>
     */
    public function numbers(): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/numbers/fax');
        return $r;
    }

    /**
     * POST /v2.2/faxes — submit a fax for delivery (HTTP 201, status "queued").
     *
     * @param string     $fromNumber    fax number on this account, 10 digits
     * @param string     $toNumber      US/CA destination, 10 digits
     * @param array<int, array{name: string, contents: string, contentType?: string}|string> $documents
     *        one to ten documents, each either a part array (name, contents
     *        as raw bytes, optional contentType — guessed from the name when
     *        omitted) or a bare local file path
     * @param string|null $idempotencyKey client-generated UUID identifying the
     *        submission; a fresh UUID v4 is generated when omitted. Pass the
     *        same key with the same documents to avoid sending twice.
     *
     * @return array<string, mixed> the queued job: id, fromNumber, toNumber,
     *         status, documents ([{name, size, contentType}])
     */
    public function send(string $fromNumber, string $toNumber, array $documents, ?string $idempotencyKey = null): array
    {
        $multipart = [
            ['name' => 'fromNumber', 'contents' => $fromNumber],
            ['name' => 'toNumber', 'contents' => $toNumber],
        ];
        foreach ($documents as $document) {
            if (is_string($document)) {
                $document = ['name' => basename($document), 'contents' => self::fileBytes($document)];
            }
            $contentType = $document['contentType'] ?? self::contentTypeFor($document['name']);
            $multipart[] = [
                'name' => 'documents',
                'contents' => $document['contents'],
                'filename' => $document['name'],
                'headers' => ['Content-Type' => $contentType],
            ];
        }
        /** @var array<string, mixed> $r */
        $r = $this->t->request(
            'POST',
            '/v2.2/faxes',
            idempotencyKey: $idempotencyKey ?? self::uuid4(),
            multipart: $multipart,
        );
        return $r;
    }

    /**
     * GET /v2.2/faxes — history for one fax number, newest first.
     *
     * @param string      $number a fax number on the account (required)
     * @param int|null    $from   newer edge of the range, unix seconds (defaults to now)
     * @param int|null    $to     older edge of the range, unix seconds (defaults to 24h ago)
     * @param int|null    $limit  page size, 1..100 (defaults to 50)
     * @param string|null $cursor paging cursor from a previous response's nextCursor
     * @param string|null $status all|active|queued|sending|retry_pending|sent|failed
     *
     * @return array<string, mixed> number, fromTs, toTs, faxes (jobs, newest
     *         first), nextCursor (string|null)
     */
    public function history(
        string $number,
        ?int $from = null,
        ?int $to = null,
        ?int $limit = null,
        ?string $cursor = null,
        ?string $status = null,
    ): array {
        $q = ['number' => $number];
        if ($from !== null) {
            $q['from'] = $from;
        }
        if ($to !== null) {
            $q['to'] = $to;
        }
        if ($limit !== null) {
            $q['limit'] = $limit;
        }
        if ($cursor !== null && $cursor !== '') {
            $q['cursor'] = $cursor;
        }
        if ($status !== null && $status !== '') {
            $q['status'] = $status;
        }
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/faxes', query: $q);
        return $r;
    }

    /**
     * GET /v2.2/faxes/{id} — one fax job. Poll until status is "sent" or "failed".
     *
     * @return array<string, mixed> the job: id, fromNumber, toNumber,
     *         clientToken, status, submittedAt/startedAt/completedAt (ISO-8601
     *         UTC, nullable), attempts, failureReason, documents,
     *         documentAvailable, documentName, documentSize
     */
    public function get(string $id): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/faxes/' . rawurlencode($id));
        return $r;
    }

    /**
     * GET /v2.2/faxes/{id}/document — the rendered PDF, as raw bytes
     * (binary response; not JSON-decoded). Served when the job reports
     * documentAvailable true; 404 otherwise.
     */
    public function document(string $id): string
    {
        /** @var string $r */
        $r = $this->t->request('GET', '/v2.2/faxes/' . rawurlencode($id) . '/document', rawResponse: true);
        return $r;
    }

    /** @throws \RuntimeException when the file cannot be read */
    private static function fileBytes(string $path): string
    {
        $bytes = @file_get_contents($path);
        if ($bytes === false) {
            throw new \RuntimeException("voicetel: cannot read fax document: {$path}");
        }
        return $bytes;
    }

    private static function contentTypeFor(string $name): string
    {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        return self::CONTENT_TYPES[$ext] ?? 'application/octet-stream';
    }

    /** RFC 4122 version 4 UUID, for the send idempotency key. */
    private static function uuid4(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }
}

<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Tests\Resources;

use GuzzleHttp\Psr7\Response;
use VoiceTel\Sdk\ApiError;
use VoiceTel\Sdk\ErrorKind;
use VoiceTel\Sdk\Tests\TestCase;

final class FaxServiceTest extends TestCase
{
    private const UUID_V4 = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';

    public function testNumbers(): void
    {
        $c = $this->makeClient([$this->envelope([
            'numbers' => [
                ['number' => '2015550123', 'destinationId' => 7, 'email' => 'fax@example.com'],
                ['number' => '2015550199', 'destinationId' => 0, 'email' => 'account@example.com'],
            ],
        ])]);
        $r = $c->fax->numbers();
        $this->assertSame('2015550123', $r['numbers'][0]['number']);
        $this->assertSame(7, $r['numbers'][0]['destinationId']);
        $req = $this->lastRequest();
        $this->assertSame('GET', $req->getMethod());
        $this->assertSame('/v2.2/numbers/fax', $req->getUri()->getPath());
    }

    public function testSendBuildsMultipartFormWithUuidIdempotencyKey(): void
    {
        $c = $this->makeClient([$this->envelope([
            'id' => '9f8c2b41a',
            'fromNumber' => '2015550123',
            'toNumber' => '2125550123',
            'status' => 'queued',
            'documents' => [['name' => 'contract.pdf', 'size' => 12, 'contentType' => 'application/pdf']],
        ], status: 201)]);
        $r = $c->fax->send('2015550123', '2125550123', [
            ['name' => 'contract.pdf', 'contents' => "%PDF-1.4 bytes", 'contentType' => 'application/pdf'],
        ]);
        $this->assertSame('9f8c2b41a', $r['id']);
        $this->assertSame('queued', $r['status']);

        $req = $this->lastRequest();
        $this->assertSame('POST', $req->getMethod());
        $this->assertSame('/v2.2/faxes', $req->getUri()->getPath());
        $this->assertSame('', $req->getUri()->getQuery());

        $contentType = $req->getHeaderLine('Content-Type');
        $this->assertMatchesRegularExpression('/^multipart\/form-data; boundary=/', $contentType);
        $boundary = substr($contentType, (int) strpos($contentType, 'boundary='));
        $this->assertStringContainsString('--' . substr($boundary, strlen('boundary=')), (string) $req->getBody());

        $this->assertMatchesRegularExpression(self::UUID_V4, $req->getHeaderLine('Idempotency-Key'));

        $body = (string) $req->getBody();
        $this->assertStringContainsString('Content-Disposition: form-data; name="fromNumber"', $body);
        $this->assertStringContainsString("\r\n\r\n2015550123\r\n", $body);
        $this->assertStringContainsString('Content-Disposition: form-data; name="toNumber"', $body);
        $this->assertStringContainsString("\r\n\r\n2125550123\r\n", $body);
        $this->assertStringContainsString('Content-Disposition: form-data; name="documents"; filename="contract.pdf"', $body);
        $this->assertStringContainsString("Content-Type: application/pdf\r\n", $body);
        $this->assertStringContainsString("\r\n\r\n%PDF-1.4 bytes\r\n", $body);
    }

    public function testSendWithMultipleDocumentsUsesRepeatedParts(): void
    {
        $c = $this->makeClient([$this->envelope(['id' => 'x', 'status' => 'queued', 'documents' => []], status: 201)]);
        $c->fax->send('2015550123', '2125550123', [
            ['name' => 'a.pdf', 'contents' => 'pdf-a'],
            ['name' => 'b.docx', 'contents' => 'docx-b'],
        ]);
        $body = (string) $this->lastRequest()->getBody();
        $this->assertSame(2, substr_count($body, 'name="documents"'));
        $this->assertStringContainsString('filename="a.pdf"', $body);
        $this->assertStringContainsString('filename="b.docx"', $body);
        // Content type guessed from the extension when omitted.
        $this->assertStringContainsString("Content-Type: application/pdf\r\n", $body);
        $this->assertStringContainsString("\r\n\r\npdf-a\r\n", $body);
        $this->assertStringContainsString(
            "Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document\r\n",
            $body,
        );
        $this->assertStringContainsString("\r\n\r\ndocx-b\r\n", $body);
    }

    public function testSendAcceptsFilePaths(): void
    {
        $path = sys_get_temp_dir() . '/voicetel-fax-test-' . getmypid() . '.pdf';
        file_put_contents($path, 'file-pdf-bytes');
        try {
            $c = $this->makeClient([$this->envelope(['id' => 'x', 'status' => 'queued'], status: 201)]);
            $c->fax->send('2015550123', '2125550123', [$path]);
            $body = (string) $this->lastRequest()->getBody();
            $this->assertStringContainsString('filename="' . basename($path) . '"', $body);
            $this->assertStringContainsString("Content-Type: application/pdf\r\n", $body);
            $this->assertStringContainsString("\r\n\r\nfile-pdf-bytes\r\n", $body);
        } finally {
            @unlink($path);
        }
    }

    public function testSendHonorsProvidedIdempotencyKey(): void
    {
        $key = '123e4567-e89b-42d3-a456-426614174000';
        $c = $this->makeClient([$this->envelope(['id' => 'x', 'status' => 'queued'], status: 201)]);
        $c->fax->send('2015550123', '2125550123', [
            ['name' => 'contract.pdf', 'contents' => 'pdf'],
        ], idempotencyKey: $key);
        $this->assertSame($key, $this->lastRequest()->getHeaderLine('Idempotency-Key'));
    }

    public function testSendErrorEnvelopeWithServerTimeIsParsed(): void
    {
        $body = [
            'status' => 'error',
            'error' => ['code' => 429, 'message' => 'Too many faxes. Please wait a minute and try again.'],
            'serverTime' => 1795100000,
        ];
        $c = $this->makeClient([
            new Response(429, ['Content-Type' => 'application/json', 'Retry-After' => '60'], json_encode($body)),
            new Response(429, ['Content-Type' => 'application/json', 'Retry-After' => '60'], json_encode($body)),
            new Response(429, ['Content-Type' => 'application/json', 'Retry-After' => '60'], json_encode($body)),
        ], maxRetries: 2);
        try {
            $c->fax->send('2015550123', '2125550123', [['name' => 'c.pdf', 'contents' => 'pdf']]);
            $this->fail('expected ApiError');
        } catch (ApiError $e) {
            $this->assertSame(ErrorKind::RateLimit, $e->kind);
            $this->assertSame('429', $e->code());
            $this->assertStringContainsString('Too many faxes', $e->getMessage());
        }
    }

    public function testSendMapsInsufficientBalanceAndPayloadTooLarge(): void
    {
        $cases = [
            [402, ErrorKind::InsufficientBalance, 'Insufficient balance to send fax.'],
            [413, ErrorKind::PayloadTooLarge, 'Document exceeds the 8 MiB limit.'],
        ];
        foreach ($cases as [$status, $kind, $message]) {
            $c = $this->makeClient([
                new Response($status, ['Content-Type' => 'application/json'], json_encode([
                    'status' => 'error',
                    'error' => ['code' => $status, 'message' => $message],
                ], JSON_THROW_ON_ERROR)),
            ]);
            try {
                $c->fax->send('2015550123', '2125550123', [['name' => 'c.pdf', 'contents' => 'pdf']]);
                $this->fail("expected ApiError for HTTP {$status}");
            } catch (ApiError $e) {
                $this->assertSame($kind, $e->kind, "HTTP {$status}");
                $this->assertSame($status, $e->statusCode);
                $this->assertStringContainsString($message, $e->getMessage());
            }
        }
    }

    public function testHistorySendsExactQueryNames(): void
    {
        $c = $this->makeClient([$this->envelope([
            'number' => '2015550123',
            'fromTs' => 1795100000,
            'toTs' => 1795013600,
            'faxes' => [],
            'nextCursor' => null,
        ])]);
        $r = $c->fax->history('2015550123', from: 1795100000, to: 1795013600, limit: 100, cursor: 'cur.1', status: 'failed');
        $this->assertSame('2015550123', $r['number']);
        $this->assertNull($r['nextCursor']);

        $req = $this->lastRequest();
        $this->assertSame('GET', $req->getMethod());
        $this->assertSame('/v2.2/faxes', $req->getUri()->getPath());
        $query = $req->getUri()->getQuery();
        $this->assertSame(
            'number=2015550123&from=1795100000&to=1795013600&limit=100&cursor=cur.1&status=failed',
            $query,
        );
    }

    public function testHistoryOmitsOptionalParams(): void
    {
        $c = $this->makeClient([$this->envelope(['number' => '2015550123', 'faxes' => [], 'nextCursor' => null])]);
        $c->fax->history('2015550123');
        $this->assertSame('number=2015550123', $this->lastRequest()->getUri()->getQuery());
    }

    public function testGet(): void
    {
        $c = $this->makeClient([$this->envelope([
            'id' => '9f8c2b41a',
            'status' => 'sent',
            'documents' => [['name' => 'contract.pdf', 'size' => 123456, 'contentType' => 'application/pdf']],
            'documentAvailable' => true,
            'documentName' => 'contract.pdf',
            'documentSize' => 123456,
        ])]);
        $r = $c->fax->get('9f8c2b41a');
        $this->assertSame('sent', $r['status']);
        $this->assertTrue($r['documentAvailable']);
        $req = $this->lastRequest();
        $this->assertSame('GET', $req->getMethod());
        $this->assertSame('/v2.2/faxes/9f8c2b41a', $req->getUri()->getPath());
    }

    public function testDocumentReturnsRawPdfBytes(): void
    {
        $pdf = "%PDF-1.4\nbinary\x00\x01\x02bytes";
        $c = $this->makeClient([
            new Response(200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="contract.pdf"'], $pdf),
        ]);
        $r = $c->fax->document('9f8c2b41a');
        $this->assertSame($pdf, $r);
        $req = $this->lastRequest();
        $this->assertSame('GET', $req->getMethod());
        $this->assertSame('/v2.2/faxes/9f8c2b41a/document', $req->getUri()->getPath());
    }

    public function testDocumentMissingMapsToNotFound(): void
    {
        $body = [
            'status' => 'error',
            'error' => ['code' => 404, 'message' => 'Not found'],
            'serverTime' => 1795100000,
        ];
        $c = $this->makeClient([
            new Response(404, ['Content-Type' => 'application/json'], json_encode($body)),
        ], maxRetries: 0);
        try {
            $c->fax->document('9f8c2b41a');
            $this->fail('expected ApiError');
        } catch (ApiError $e) {
            $this->assertSame(ErrorKind::NotFound, $e->kind);
        }
    }
}

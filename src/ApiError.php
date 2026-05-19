<?php

declare(strict_types=1);

namespace VoiceTel\Sdk;

use RuntimeException;
use Throwable;

/**
 * ApiError is thrown whenever the VoiceTel API responds with a non-2xx status,
 * or when the transport itself fails (in which case statusCode is 0).
 *
 * The body field preserves the raw response payload — useful for 409 conflicts
 * where the server returns structured detail about partial successes.
 */
final class ApiError extends RuntimeException
{
    /**
     * @param ErrorKind $kind        classification (see ErrorKind)
     * @param int       $statusCode  HTTP status code; 0 for transport-level failures
     * @param string    $errorCode   server-supplied error code, if any
     * @param mixed     $body        decoded response body, or raw string
     */
    public readonly ErrorKind $kind;
    public readonly int $statusCode;
    public readonly string $errorCode;
    public readonly mixed $body;

    public function __construct(
        ErrorKind $kind = ErrorKind::Unknown,
        int $statusCode = 0,
        string $code = '',
        mixed $body = null,
        string $message = '',
        ?Throwable $previous = null,
    ) {
        $this->kind = $kind;
        $this->statusCode = $statusCode;
        $this->errorCode = $code;
        $this->body = $body;
        if ($message === '') {
            $message = $statusCode > 0
                ? sprintf('voicetel: HTTP %d%s', $statusCode, $code !== '' ? ' ' . $code : '')
                : 'voicetel: transport error';
        }
        parent::__construct($message, $statusCode, $previous);
    }

    /** Server-supplied error code, if any. (Renamed from `$code` to avoid clashing with Exception::$code.) */
    public function code(): string
    {
        return $this->errorCode;
    }

    /** True when this error has kind = RateLimit. */
    public function isRateLimit(): bool
    {
        return $this->kind === ErrorKind::RateLimit;
    }

    /** True when this error has kind = NotFound. */
    public function isNotFound(): bool
    {
        return $this->kind === ErrorKind::NotFound;
    }

    /** True when this error has kind = Authentication. */
    public function isAuthentication(): bool
    {
        return $this->kind === ErrorKind::Authentication;
    }

    /** True when this error has kind = Conflict. */
    public function isConflict(): bool
    {
        return $this->kind === ErrorKind::Conflict;
    }
}

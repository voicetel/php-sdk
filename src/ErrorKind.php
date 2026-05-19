<?php

declare(strict_types=1);

namespace VoiceTel\Sdk;

/**
 * ErrorKind classifies a VoiceTel API error so callers can switch on it
 * without having to inspect HTTP status codes.
 *
 * The string values are stable and may be used in logs / metrics.
 */
enum ErrorKind: string
{
    /** Catch-all for unmapped statuses or transport failures. */
    case Unknown = 'unknown';
    /** HTTP 400 — server-side validation failure. */
    case BadRequest = 'bad_request';
    /** HTTP 401 — bearer token missing, expired, or invalid. */
    case Authentication = 'authentication';
    /** HTTP 403 — authenticated but not allowed. */
    case PermissionDenied = 'permission_denied';
    /** HTTP 404 — resource does not exist. */
    case NotFound = 'not_found';
    /** HTTP 409 — request conflicts with current state. */
    case Conflict = 'conflict';
    /** HTTP 429 — exceeded the 6/hour/IP cap on account/* endpoints. */
    case RateLimit = 'rate_limit';
    /** Any HTTP 5xx. */
    case Server = 'server';

    /**
     * Map an HTTP status code to its ErrorKind.
     */
    public static function fromStatus(int $status): self
    {
        return match (true) {
            $status === 400 => self::BadRequest,
            $status === 401 => self::Authentication,
            $status === 403 => self::PermissionDenied,
            $status === 404 => self::NotFound,
            $status === 409 => self::Conflict,
            $status === 429 => self::RateLimit,
            $status >= 500 && $status < 600 => self::Server,
            default => self::Unknown,
        };
    }
}

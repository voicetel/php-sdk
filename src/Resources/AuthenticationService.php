<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Resources;

use VoiceTel\Sdk\Transport;

/**
 * AuthenticationService manages SIP/HTTP auth mode + password.
 *
 * `authType`: 0 = Digest, 1 = IP Auth, 2 = Digest OR IP, 3 = Digest AND IP.
 */
final class AuthenticationService
{
    public const AUTH_TYPE_DIGEST = 0;
    public const AUTH_TYPE_IP_AUTH = 1;
    public const AUTH_TYPE_DIGEST_OR_IP = 2;
    public const AUTH_TYPE_DIGEST_AND_IP = 3;

    public function __construct(private readonly Transport $t)
    {
    }

    /**
     * GET /v2.2/auth — return current auth mode + allowlist.
     *
     * @return array<string, mixed>
     */
    public function get(): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/auth');
        return $r;
    }

    /**
     * PUT /v2.2/auth — set authType and/or password.
     *
     * @param array<string, mixed> $body  optional keys: authType (int 0..3), password (6-10 alphanumeric)
     * @return array<string, mixed>
     */
    public function update(array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('PUT', '/v2.2/auth', body: $body);
        return $r;
    }
}

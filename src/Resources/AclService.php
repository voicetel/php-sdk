<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Resources;

use VoiceTel\Sdk\Transport;

/**
 * AclService manages the IP allowlist (CIDR entries) bound to the account.
 *
 * 409 responses from add/remove include the structured AclConflictData
 * payload on the resulting ApiError->body — `added`, `removed`, `failed`.
 */
final class AclService
{
    public function __construct(private readonly Transport $t)
    {
    }

    /**
     * GET /v2.2/acl — return the current allowlist.
     *
     * @return array<string, mixed>
     */
    public function list(): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/acl');
        return $r;
    }

    /**
     * POST /v2.2/acl — append CIDR entries to the allowlist.
     *
     * @param array<string, mixed> $body  shape: ["acl" => [["cidr" => "1.2.3.0/24"], ...]]
     * @return array<string, mixed>
     */
    public function add(array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('POST', '/v2.2/acl', body: $body);
        return $r;
    }

    /**
     * DELETE /v2.2/acl — remove CIDR entries from the allowlist.
     *
     * Unlike most DELETE endpoints, this one returns 200 with a body.
     *
     * @param array<string, mixed> $body  shape: ["acl" => [["cidr" => "1.2.3.0/24"], ...]]
     * @return array<string, mixed>
     */
    public function remove(array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('DELETE', '/v2.2/acl', body: $body);
        return $r;
    }
}

<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Resources;

use VoiceTel\Sdk\Transport;

/**
 * GatewaysService manages outbound termination gateways on the account.
 */
final class GatewaysService
{
    public function __construct(private readonly Transport $t)
    {
    }

    /**
     * GET /v2.2/gateways — list every gateway.
     *
     * @return array<string, mixed>
     */
    public function list(): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/gateways');
        return $r;
    }

    /**
     * POST /v2.2/gateways — create a new gateway.
     *
     * Required: gateway. Optional: prefix, limit (1..1000, default 23).
     *
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    public function add(array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('POST', '/v2.2/gateways', body: $body);
        return $r;
    }

    /**
     * GET /v2.2/gateways/{id} — fetch a single gateway by id.
     *
     * @return array<string, mixed>
     */
    public function get(int $id): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/gateways/' . $id);
        return $r;
    }

    /**
     * PUT /v2.2/gateways/{id} — partial-update a gateway.
     *
     * @param array<string, mixed> $body  optional: gateway, prefix, limit
     * @return array<string, mixed>
     */
    public function update(int $id, array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('PUT', '/v2.2/gateways/' . $id, body: $body);
        return $r;
    }

    /**
     * DELETE /v2.2/gateways/{id} — delete a gateway.
     *
     * Returns 204 No Content on success.
     */
    public function remove(int $id): void
    {
        $this->t->request('DELETE', '/v2.2/gateways/' . $id, expectNoBody: true);
    }

    /**
     * GET /v2.2/gateways/{id}/numbers — list every number routed through this gateway.
     *
     * @return array<string, mixed>
     */
    public function numbers(int $id): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/gateways/' . $id . '/numbers');
        return $r;
    }
}

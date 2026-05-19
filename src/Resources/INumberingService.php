<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Resources;

use VoiceTel\Sdk\Transport;

/**
 * INumberingService covers inventory searches, number orders, and port-ins.
 *
 * Port availability data (v2.2.10) includes `localRoutingNumber` and
 * `rateCenterTier` alongside the original `number`, `portable`,
 * `losingCarrier`, and `reason` fields.
 */
final class INumberingService
{
    public function __construct(private readonly Transport $t)
    {
    }

    /**
     * GET /v2.2/inventory — search available TNs.
     *
     * Recognised keys in $query (any combination): npa, nxx, state, ratecenter,
     * contains, endswith, limit.
     *
     * @param array<string, scalar> $query
     * @return array<string, mixed>
     */
    public function searchInventory(array $query = []): array
    {
        $q = [];
        foreach ($query as $k => $v) {
            if ($v !== '' && $v !== 0 && $v !== null) {
                $q[$k] = (string) $v;
            }
        }
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/inventory', query: $q);
        return $r;
    }

    /**
     * GET /v2.2/inventory/coverage — aggregated availability buckets.
     *
     * Recognised keys: state, ratecenter.
     *
     * @param array<string, scalar> $query
     * @return array<string, mixed>
     */
    public function coverage(array $query = []): array
    {
        $q = [];
        foreach ($query as $k => $v) {
            if ($v !== '' && $v !== null) {
                $q[$k] = (string) $v;
            }
        }
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/inventory/coverage', query: $q);
        return $r;
    }

    /**
     * POST /v2.2/orders — purchase one or more TNs.
     *
     * `numbers` may contain plain string TNs or {"number", "route"} objects.
     *
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    public function order(array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('POST', '/v2.2/orders', body: $body);
        return $r;
    }

    /**
     * GET /v2.2/ports — list every port-in record on the account.
     *
     * @return array<string, mixed>
     */
    public function ports(): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/ports');
        return $r;
    }

    /**
     * GET /v2.2/ports/{id} — fetch detail for one port-in by id.
     *
     * @return array<string, mixed>
     */
    public function port(int $id): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/ports/' . $id);
        return $r;
    }

    /**
     * POST /v2.2/ports — submit a port-in order.
     *
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    public function submitPort(array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('POST', '/v2.2/ports', body: $body);
        return $r;
    }

    /**
     * GET /v2.2/ports/availability/{number} — check whether a TN can be ported in.
     *
     * Response data shape (v2.2.10+):
     *  - number (string)
     *  - portable (bool)
     *  - losingCarrier (string|null)
     *  - localRoutingNumber (string|null)
     *  - rateCenterTier (string|null)
     *  - reason (string|null)
     *
     * @return array<string, mixed>
     */
    public function portAvailability(string $number): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/ports/availability/' . rawurlencode($number));
        return $r;
    }
}

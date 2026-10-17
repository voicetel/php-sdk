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
     * @param string      $tnMask     prefix or wildcard pattern: `201`, `201555`, `201555*` (required)
     * @param int|null    $quantity   max results, 1-1000 (server default 10)
     * @param string|null $province   2-letter US state or Canadian province code
     * @param string|null $rateCenter rate-center name
     * @param string|null $lata       LATA code
     * @return array<string, mixed>
     */
    public function searchInventory(
        string $tnMask,
        ?int $quantity = null,
        ?string $province = null,
        ?string $rateCenter = null,
        ?string $lata = null,
    ): array {
        $q = ['tnMask' => $tnMask];
        if ($quantity !== null) {
            $q['quantity'] = $quantity;
        }
        if ($province !== null && $province !== '') {
            $q['province'] = $province;
        }
        if ($rateCenter !== null && $rateCenter !== '') {
            $q['rateCenter'] = $rateCenter;
        }
        if ($lata !== null) {
            $q['lata'] = $lata;
        }
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/inventory', query: $q);
        return $r;
    }

    /**
     * GET /v2.2/inventory/coverage — aggregated availability buckets.
     *
     * @param string      $countBy  aggregation dimension: "state" | "city" | "rateCenter" | "npanxx" | "block" (required)
     * @param string|null $locState state/province code filter (e.g. NJ)
     * @param string|null $city     city name filter
     * @param string|null $rcAbbre  rate-center abbreviation filter
     * @param string|null $npa      area-code filter (e.g. 201)
     * @param string|null $lata     LATA code filter
     * @return array<string, mixed>
     */
    public function coverage(
        string $countBy,
        ?string $locState = null,
        ?string $city = null,
        ?string $rcAbbre = null,
        ?string $npa = null,
        ?string $lata = null,
    ): array {
        $q = ['countBy' => $countBy];
        if ($locState !== null && $locState !== '') {
            $q['locState'] = $locState;
        }
        if ($city !== null && $city !== '') {
            $q['city'] = $city;
        }
        if ($rcAbbre !== null && $rcAbbre !== '') {
            $q['rcAbbre'] = $rcAbbre;
        }
        if ($npa !== null && $npa !== '') {
            $q['npa'] = $npa;
        }
        if ($lata !== null) {
            $q['lata'] = $lata;
        }
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/inventory/coverage', query: $q);
        return $r;
    }

    /**
     * POST /v2.2/orders — purchase one or more TNs.
     *
     * `numbers` may contain plain string TNs or {"number", "route"} objects,
     * where route is the global route type: 1 USER, 2 T30, 3 VOICE_BRIDGE,
     * 4 DID (default), 5 T38, 6 VOICEML, 8 SDN, 9 MOBILE.
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
    public function port(string $id): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/ports/' . rawurlencode($id));
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

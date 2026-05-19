<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Resources;

use VoiceTel\Sdk\Transport;

/**
 * LookupsService provides CNAM and LRN dips. Each call costs money; rate
 * them per call rather than fanning out blindly.
 */
final class LookupsService
{
    public function __construct(private readonly Transport $t)
    {
    }

    /**
     * GET /v2.2/cnam/{number} — perform a CNAM dip on a 10-digit TN.
     *
     * @return array<string, mixed>
     */
    public function cnam(string $number): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/cnam/' . rawurlencode($number));
        return $r;
    }

    /**
     * GET /v2.2/lrn/{number}/{ani} — perform an LRN dip.
     *
     * `$ani` is the presented ANI (10-digit TN) used only for billing/auth —
     * it is not echoed back.
     *
     * @return array<string, mixed>
     */
    public function lrn(string $number, string $ani): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/lrn/' . rawurlencode($number) . '/' . rawurlencode($ani));
        return $r;
    }
}

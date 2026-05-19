<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Resources;

use VoiceTel\Sdk\Transport;

/**
 * E911Service manages e911 records and address validation.
 *
 * Note the asymmetric `dn` formats: requests take a 10-digit TN; responses
 * return the 11-digit E.164 US form (country code 1 prepended).
 */
final class E911Service
{
    public function __construct(private readonly Transport $t)
    {
    }

    /**
     * GET /v2.2/e911 — list every e911 record on the account.
     *
     * @return array<string, mixed>
     */
    public function list(): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/e911');
        return $r;
    }

    /**
     * POST /v2.2/e911 — validate + provision in one call.
     *
     * Required: dn, callername, address1, city, state, zip. Optional: address2.
     *
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    public function create(array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('POST', '/v2.2/e911', body: $body);
        return $r;
    }

    /**
     * POST /v2.2/e911/validations — validate an address, returning an addressid.
     *
     * @param array<string, mixed> $body  required: address1, city, state, zip; optional: address2
     * @return array<string, mixed>
     */
    public function validate(array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('POST', '/v2.2/e911/validations', body: $body);
        return $r;
    }

    /**
     * GET /v2.2/e911/{dn} — fetch the e911 record for $dn (10-digit TN).
     *
     * @return array<string, mixed>
     */
    public function getRecord(string $dn): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/e911/' . rawurlencode($dn));
        return $r;
    }

    /**
     * PUT /v2.2/e911/{dn} — provision e911 for $dn using a previously-validated addressid.
     *
     * @param array<string, mixed> $body  required: callername, addressid
     * @return array<string, mixed>
     */
    public function provision(string $dn, array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('PUT', '/v2.2/e911/' . rawurlencode($dn), body: $body);
        return $r;
    }

    /**
     * DELETE /v2.2/e911/{dn} — remove the e911 record for $dn.
     *
     * Returns 204 No Content on success.
     */
    public function remove(string $dn): void
    {
        $this->t->request('DELETE', '/v2.2/e911/' . rawurlencode($dn), expectNoBody: true);
    }
}

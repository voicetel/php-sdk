<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Resources;

use VoiceTel\Sdk\Transport;

/**
 * NumbersService is the entry point for every operation on a telephone number
 * owned by the account.
 *
 * DELETE endpoints generally return 204 No Content; the methods here return
 * `void`. The two exceptions:
 *  - {@see unassignCampaign()} and {@see bulkUnassignCampaign()} return 200 with a body.
 */
final class NumbersService
{
    public function __construct(private readonly Transport $t)
    {
    }

    /**
     * GET /v2.2/numbers — list every TN on the account.
     *
     * @return array<string, mixed>
     */
    public function list(): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/numbers');
        return $r;
    }

    /**
     * POST /v2.2/numbers — attach a TN to the account.
     *
     * Required body: number. Optional: route (gateway id; default 4 = DID).
     *
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    public function add(array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('POST', '/v2.2/numbers', body: $body);
        return $r;
    }

    /**
     * GET /v2.2/numbers/{number} — fetch one TN.
     *
     * @return array<string, mixed>
     */
    public function get(string $number): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/numbers/' . rawurlencode($number));
        return $r;
    }

    /** DELETE /v2.2/numbers/{number} — detach a TN. Returns 204 No Content. */
    public function remove(string $number): void
    {
        $this->t->request('DELETE', '/v2.2/numbers/' . rawurlencode($number), expectNoBody: true);
    }

    /**
     * PATCH /v2.2/numbers/{number} — transfer a TN to another account in the org.
     *
     * Required body: accountId, route.
     *
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    public function move(string $number, array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('PATCH', '/v2.2/numbers/' . rawurlencode($number), body: $body);
        return $r;
    }

    /** POST /v2.2/numbers/{number}/release — return a TN to the network. Returns 204. */
    public function release(string $number): void
    {
        $this->t->request('POST', '/v2.2/numbers/' . rawurlencode($number) . '/release', expectNoBody: true);
    }

    /**
     * PUT /v2.2/numbers/{number}/route — update outbound route.
     *
     * @param array<string, mixed> $body  required: route (int)
     * @return array<string, mixed>
     */
    public function setRoute(string $number, array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('PUT', '/v2.2/numbers/' . rawurlencode($number) . '/route', body: $body);
        return $r;
    }

    /**
     * PUT /v2.2/numbers/{number}/translation — update DNIS translation.
     *
     * @param array<string, mixed> $body  required: translation
     * @return array<string, mixed>
     */
    public function setTranslation(string $number, array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('PUT', '/v2.2/numbers/' . rawurlencode($number) . '/translation', body: $body);
        return $r;
    }

    /**
     * PUT /v2.2/numbers/{number}/cnam — toggle inbound CNAM lookup.
     *
     * @param array<string, mixed> $body  required: enabled (bool)
     * @return array<string, mixed>
     */
    public function setCnam(string $number, array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('PUT', '/v2.2/numbers/' . rawurlencode($number) . '/cnam', body: $body);
        return $r;
    }

    /**
     * PUT /v2.2/numbers/{number}/lidb — update outbound caller name (LIDB).
     *
     * @param array<string, mixed> $body  required: cnam (<=15 chars)
     * @return array<string, mixed>
     */
    public function setLidb(string $number, array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('PUT', '/v2.2/numbers/' . rawurlencode($number) . '/lidb', body: $body);
        return $r;
    }

    /**
     * GET /v2.2/numbers/{number}/fax — read fax-to-email routing.
     *
     * @return array<string, mixed>
     */
    public function getFax(string $number): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/numbers/' . rawurlencode($number) . '/fax');
        return $r;
    }

    /**
     * PUT /v2.2/numbers/{number}/fax — enable fax-to-email routing.
     *
     * @param array<string, mixed> $body  required: email
     * @return array<string, mixed>
     */
    public function setFax(string $number, array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('PUT', '/v2.2/numbers/' . rawurlencode($number) . '/fax', body: $body);
        return $r;
    }

    /** DELETE /v2.2/numbers/{number}/fax — disable fax-to-email. Returns 204. */
    public function removeFax(string $number): void
    {
        $this->t->request('DELETE', '/v2.2/numbers/' . rawurlencode($number) . '/fax', expectNoBody: true);
    }

    /**
     * PUT /v2.2/numbers/{number}/forward — enable call forwarding.
     *
     * @param array<string, mixed> $body  required: destination (10-digit int TN)
     * @return array<string, mixed>
     */
    public function setForward(string $number, array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('PUT', '/v2.2/numbers/' . rawurlencode($number) . '/forward', body: $body);
        return $r;
    }

    /** DELETE /v2.2/numbers/{number}/forward — disable call forwarding. Returns 204. */
    public function removeForward(string $number): void
    {
        $this->t->request('DELETE', '/v2.2/numbers/' . rawurlencode($number) . '/forward', expectNoBody: true);
    }

    /**
     * GET /v2.2/numbers/{number}/sms — read SMS routing.
     *
     * @return array<string, mixed>
     */
    public function getSms(string $number): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/numbers/' . rawurlencode($number) . '/sms');
        return $r;
    }

    /**
     * PUT /v2.2/numbers/{number}/sms — configure SMS routing.
     *
     * @param array<string, mixed> $body  required: type ("email"|"webhook"|"sip"), resource
     * @return array<string, mixed>
     */
    public function setSms(string $number, array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('PUT', '/v2.2/numbers/' . rawurlencode($number) . '/sms', body: $body);
        return $r;
    }

    /** DELETE /v2.2/numbers/{number}/sms — clear SMS routing. Returns 204. */
    public function removeSms(string $number): void
    {
        $this->t->request('DELETE', '/v2.2/numbers/' . rawurlencode($number) . '/sms', expectNoBody: true);
    }

    /**
     * GET /v2.2/numbers/{number}/messaging — messaging state for one TN.
     *
     * @return array<string, mixed>
     */
    public function getMessaging(string $number): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/numbers/' . rawurlencode($number) . '/messaging');
        return $r;
    }

    /**
     * PATCH /v2.2/numbers/{number}/messaging — update inbound/outbound routing.
     *
     * At least one of routeIn / routeOut must be set.
     *
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    public function patchMessaging(string $number, array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('PATCH', '/v2.2/numbers/' . rawurlencode($number) . '/messaging', body: $body);
        return $r;
    }

    /**
     * PUT /v2.2/numbers/{number}/messaging-campaign — bind a 10DLC campaign to a TN.
     *
     * @param array<string, mixed> $body  required: campaignId (7-char alphanumeric upper)
     * @return array<string, mixed>
     */
    public function assignCampaign(string $number, array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request(
            'PUT',
            '/v2.2/numbers/' . rawurlencode($number) . '/messaging-campaign',
            body: $body,
        );
        return $r;
    }

    /**
     * DELETE /v2.2/numbers/{number}/messaging-campaign — remove campaign binding.
     *
     * Unusually for a DELETE, this returns 200 with a body.
     *
     * @return array<string, mixed>
     */
    public function unassignCampaign(string $number): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('DELETE', '/v2.2/numbers/' . rawurlencode($number) . '/messaging-campaign');
        return $r;
    }

    /**
     * DELETE /v2.2/numbers/messaging-campaign — bulk-remove campaign binding from many TNs.
     *
     * Unusually for a DELETE, this returns 200 with a body.
     *
     * @param array<int, string> $numbers
     * @return array<string, mixed>
     */
    public function bulkUnassignCampaign(array $numbers): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request(
            'DELETE',
            '/v2.2/numbers/messaging-campaign',
            body: ['numbers' => $numbers],
        );
        return $r;
    }

    /**
     * PATCH /v2.2/numbers/{number}/port-out-pin — set the port-out PIN for a TN.
     *
     * @param array<string, mixed> $body  required: pin (4-digit numeric)
     * @return array<string, mixed>
     */
    public function setPortOutPin(string $number, array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request(
            'PATCH',
            '/v2.2/numbers/' . rawurlencode($number) . '/port-out-pin',
            body: $body,
        );
        return $r;
    }
}

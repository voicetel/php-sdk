<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Resources;

use VoiceTel\Sdk\Transport;

/**
 * AccountService groups every operation under the Account tag.
 *
 * Note: `cdr`, `recurringCharges`, `payments`, `registration`, `info`, and
 * `apiKey` (the login exchange) share a 6 req/hour/IP rate limit. Bursting
 * will trigger 429s — the transport auto-retries with backoff (default 2).
 */
final class AccountService
{
    public function __construct(private readonly Transport $t)
    {
    }

    /**
     * GET /v2.2/account — returns the authenticated account's profile.
     *
     * @return array<string, mixed>
     */
    public function get(): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/account');
        return $r;
    }

    /**
     * PUT /v2.2/account — partial-update account settings. Only supplied keys are sent.
     *
     * Recognised keys: notify, notifyThreshold, timezone, callerId, e911,
     * intl, sms, mms, ccs. Admin-only fields are flagged on
     * voicetel.com/docs/api/v2.2/.
     *
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    public function update(array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('PUT', '/v2.2/account', body: $body);
        return $r;
    }

    /**
     * POST /v2.2/account — create a sub-account (admin only).
     *
     * Required keys: username (int), name, email. Optional: masterAccount (int).
     *
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    public function add(array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('POST', '/v2.2/account', body: $body);
        return $r;
    }

    /**
     * POST /v2.2/accounts — public sign-up flow.
     *
     * Required keys: name, email. Optional: promo.
     *
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    public function signup(array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('POST', '/v2.2/accounts', body: $body);
        return $r;
    }

    /**
     * GET /v2.2/account/cdr — call detail records in [$start, $end] Unix seconds.
     *
     * Rate-limited: 6 req/hour/IP.
     *
     * @return array<string, mixed>
     */
    public function cdr(int $start = 0, int $end = 0): array
    {
        $q = [];
        if ($start !== 0) {
            $q['start'] = (string) $start;
        }
        if ($end !== 0) {
            $q['end'] = (string) $end;
        }
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/account/cdr', query: $q);
        return $r;
    }

    /**
     * GET /v2.2/account/credits — full credit history, newest first.
     *
     * @return array<string, mixed>
     */
    public function credits(): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/account/credits');
        return $r;
    }

    /**
     * GET /v2.2/account/recurring-charges — active monthly-recurring charges.
     *
     * Rate-limited: 6 req/hour/IP.
     *
     * @return array<string, mixed>
     */
    public function recurringCharges(): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/account/recurring-charges');
        return $r;
    }

    /**
     * GET /v2.2/account/payments — full payment history, newest first.
     *
     * Rate-limited: 6 req/hour/IP.
     *
     * @return array<string, mixed>
     */
    public function payments(): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/account/payments');
        return $r;
    }

    /**
     * GET /v2.2/account/registration — current SIP registration.
     *
     * Rate-limited: 6 req/hour/IP.
     *
     * @return array<string, mixed>
     */
    public function registration(): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/account/registration');
        return $r;
    }

    /**
     * POST /v2.2/account/recovery — start the password recovery flow.
     *
     * No auth required.
     *
     * @param array<string, mixed> $body  required key: email
     * @return array<string, mixed>
     */
    public function recover(array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('POST', '/v2.2/account/recovery', body: $body, requireAuth: false);
        return $r;
    }
}

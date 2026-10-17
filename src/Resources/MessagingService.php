<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Resources;

use VoiceTel\Sdk\Transport;

/**
 * MessagingService handles SMS/MMS sending and 10DLC brand/campaign registration.
 *
 * `send()` request body uses the wire field names `fromNumber` and `toNumber`.
 */
final class MessagingService
{
    public function __construct(private readonly Transport $t)
    {
    }

    /**
     * GET /v2.2/messages — message history for one number.
     *
     * @param string      $number 10-digit TN whose messages are returned (required)
     * @param int|null    $from   unix timestamp range start
     * @param int|null    $to     unix timestamp range end
     * @param string|null $type   "sms" | "mms" | "dlr"
     * @return array<string, mixed>
     */
    public function history(string $number, ?int $from = null, ?int $to = null, ?string $type = null): array
    {
        $q = ['number' => $number];
        if ($from !== null) {
            $q['from'] = $from;
        }
        if ($to !== null) {
            $q['to'] = $to;
        }
        if ($type !== null && $type !== '') {
            $q['type'] = $type;
        }
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/messages', query: $q);
        return $r;
    }

    /**
     * POST /v2.2/messages — send an SMS or MMS.
     *
     * Required body keys: fromNumber, toNumber, text. Optional: subject, mediaUrls
     * (presence of mediaUrls switches the message to MMS).
     *
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    public function send(array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('POST', '/v2.2/messages', body: $body);
        return $r;
    }

    /**
     * POST /v2.2/messaging/brands — register a 10DLC brand.
     *
     * Required body keys: messagingBrandId (starts with B), messagingBrandName.
     * Optional: messagingBrandDescription.
     *
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    public function createBrand(array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('POST', '/v2.2/messaging/brands', body: $body);
        return $r;
    }

    /**
     * GET /v2.2/messaging/campaigns — current 10DLC campaign statuses.
     *
     * Each item in `campaigns` has the shape (v2.2.14+):
     *  - id (string)
     *  - status (string) — CAMPAIGN_DCA_COMPLETE (ready: carrier review
     *    finished, numbers assigned), CAMPAIGN_DCA_COMPLETE_NO_NUMBERS_ASSIGNED,
     *    CAMPAIGN_PENDING_DCA (active at the campaign registry, but carrier
     *    (DCA) review is not finished yet; numbers cannot be enabled until it
     *    is), CAMPAIGN_DCA_REJECTED (a carrier's DCA rejected or suspended the
     *    campaign; see dca.carriers), CAMPAIGN_<registry status>, or
     *    CAMPAIGN_INVALID_ID
     *  - numbers (string[])
     *  - dca (array|null) — carrier (DCA) review progress; null for
     *    CAMPAIGN_INVALID_ID:
     *    - electionComplete (bool) — every carrier has a DCA assigned to
     *      review the campaign
     *    - electedCarriers (string[]|null) — carriers with a DCA assigned so
     *      far (null when unavailable)
     *    - carriers (array[]) — each carrier DCA decision received, latest
     *      per carrier: {carrier, status ("ACTIVE"|"REJECTED"|"SUSPENDED"|
     *      "UNSUSPENDED"|"TERMINATED"), detail, updatedAt}
     *
     * @param array<int, string>|string $ids campaign ids (max 100) — an array of
     *        ids or a comma-separated string
     * @return array<string, mixed>
     */
    public function campaignStatus(array|string $ids): array
    {
        $q = ['ids' => is_array($ids) ? implode(',', $ids) : $ids];
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/messaging/campaigns', query: $q);
        return $r;
    }

    /**
     * POST /v2.2/messaging/campaigns — register a 10DLC campaign.
     *
     * Body is passed through to the campaign registration service.
     * campaignClassName (from the campaign registry) and campaignStartDate
     * (today) are auto-populated if omitted.
     *
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    public function createCampaign(array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('POST', '/v2.2/messaging/campaigns', body: $body);
        return $r;
    }

    /**
     * GET /v2.2/numbers/messaging — messaging state for a set of numbers.
     *
     * @param array<int, string> $numbers list of 10-digit TNs (max 1000)
     * @return array<string, mixed>
     */
    public function numbersState(array $numbers): array
    {
        $q = ['numbers' => implode(',', $numbers)];
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/numbers/messaging', query: $q);
        return $r;
    }

    /**
     * GET /v2.2/messaging/health — account-wide messaging health overview.
     *
     * Mirrors the portal Messaging tab: summary counts (numbers, messaging on,
     * healthy, needing attention, in progress), campaigns with carrier-review
     * progress, numbers needing attention (each with a plain-language title,
     * detail and the actions that fix it: assign, disable, samples), 14 days of
     * delivery counts (delivered, failed, blocked as spam, refused because the
     * number is not enabled), and the alert-email setting. Updated hourly.
     *
     * The API spec defines no response schema for this endpoint, so the decoded
     * JSON object is returned as-is.
     *
     * @return array<string, mixed>
     */
    public function health(): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/messaging/health');
        return $r;
    }

    /**
     * GET /v2.2/messaging/health/alerts — the alert-email setting: whether new
     * messaging problems are emailed to the account.
     *
     * The spec defines no response schema, so the decoded JSON object is returned as-is.
     *
     * @return array<string, mixed>
     */
    public function healthAlerts(): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/messaging/health/alerts');
        return $r;
    }

    /**
     * PUT /v2.2/messaging/health/alerts — turn alert emails on or off.
     *
     * When enabled, the account is emailed as new messaging problems are found
     * (at most one digest about every 20 hours).
     *
     * The spec defines no response schema, so the decoded JSON object is returned as-is.
     *
     * @return array<string, mixed>
     */
    public function setHealthAlerts(bool $enabled): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('PUT', '/v2.2/messaging/health/alerts', body: ['enabled' => $enabled]);
        return $r;
    }

    /**
     * POST /v2.2/messaging/health/check — live health check of one number.
     *
     * Reports in service, messaging enabled, campaign approved, assigned to the
     * campaign by the messaging provider, delivery failures in the last 24 hours,
     * and enablement in progress. Limited to 30 checks per hour per account.
     *
     * The spec defines no response schema, so the decoded JSON object is returned as-is.
     *
     * @param string $number 10-digit number on the account
     * @return array<string, mixed>
     */
    public function healthCheck(string $number): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('POST', '/v2.2/messaging/health/check', body: ['number' => $number]);
        return $r;
    }

    /**
     * GET /v2.2/messaging/health/samples — recent messages blocked as spam.
     *
     * Up to 3 recent outbound messages from the number that carriers blocked as
     * spam (time, masked recipient, text, reason), to help review content.
     *
     * The spec defines no response schema, so the decoded JSON object is returned as-is.
     *
     * @param string $number 10-digit number on the account
     * @return array<string, mixed>
     */
    public function healthSamples(string $number): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/messaging/health/samples', query: ['number' => $number]);
        return $r;
    }
}

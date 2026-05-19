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
     * GET /v2.2/messages — message history.
     *
     * Optional query keys: number, start (unix ts), end (unix ts), type ("sms" | "mms" | "dlr").
     *
     * @param array<string, scalar> $query
     * @return array<string, mixed>
     */
    public function history(array $query = []): array
    {
        $q = [];
        foreach ($query as $k => $v) {
            if ($v !== '' && $v !== 0 && $v !== null) {
                $q[$k] = (string) $v;
            }
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
     * @return array<string, mixed>
     */
    public function campaignStatus(): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/messaging/campaigns');
        return $r;
    }

    /**
     * POST /v2.2/messaging/campaigns — register a 10DLC campaign.
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
     * GET /v2.2/numbers/messaging — messaging state for many numbers at once.
     *
     * Pass an empty array (the default) for "all numbers on the account".
     *
     * @param array<int, string> $numbers list of 10-digit TNs
     * @return array<string, mixed>
     */
    public function numbersState(array $numbers = []): array
    {
        $q = [];
        if ($numbers !== []) {
            $q['numbers'] = implode(',', $numbers);
        }
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/numbers/messaging', query: $q);
        return $r;
    }
}

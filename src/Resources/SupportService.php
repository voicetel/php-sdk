<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Resources;

use VoiceTel\Sdk\Transport;

/**
 * SupportService manages support tickets (create, read, update, delete, reply).
 *
 * Note: the wire field "number" on a conversation is a ticket sequence number
 * (e.g. 1015), NOT a phone number. Read it from the response as
 * `$conversation['number']` and treat it as a ticket reference.
 */
final class SupportService
{
    public function __construct(private readonly Transport $t)
    {
    }

    /**
     * GET /v2.2/support/tickets — list every ticket on the account.
     *
     * @return array<string, mixed>
     */
    public function list(): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/support/tickets');
        return $r;
    }

    /**
     * POST /v2.2/support/tickets — open a new support ticket.
     *
     * Required body: subject, message. Optional: email (admin only — create on
     * behalf of this customer).
     *
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    public function create(array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('POST', '/v2.2/support/tickets', body: $body);
        return $r;
    }

    /**
     * GET /v2.2/support/tickets/{id} — fetch one ticket by id.
     *
     * @return array<string, mixed>
     */
    public function get(int $id): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/support/tickets/' . $id);
        return $r;
    }

    /**
     * PUT /v2.2/support/tickets/{id} — change a ticket's status.
     *
     * @param array<string, mixed> $body  required: status ("active"|"pending"|"closed"|"spam")
     * @return array<string, mixed>
     */
    public function update(int $id, array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('PUT', '/v2.2/support/tickets/' . $id, body: $body);
        return $r;
    }

    /** DELETE /v2.2/support/tickets/{id} — remove a ticket. Admin only. Returns 204. */
    public function delete(int $id): void
    {
        $this->t->request('DELETE', '/v2.2/support/tickets/' . $id, expectNoBody: true);
    }

    /**
     * GET /v2.2/support/tickets/{id}/messages — list every thread on a ticket.
     *
     * @return array<string, mixed>
     */
    public function messages(int $id): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('GET', '/v2.2/support/tickets/' . $id . '/messages');
        return $r;
    }

    /**
     * POST /v2.2/support/tickets/{id}/replies — add a reply to a ticket.
     *
     * @param array<string, mixed> $body  required: message
     * @return array<string, mixed>
     */
    public function reply(int $id, array $body): array
    {
        /** @var array<string, mixed> $r */
        $r = $this->t->request('POST', '/v2.2/support/tickets/' . $id . '/replies', body: $body);
        return $r;
    }
}

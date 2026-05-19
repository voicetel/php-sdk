<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Tests\Resources;

use GuzzleHttp\Psr7\Response;
use VoiceTel\Sdk\Tests\TestCase;

final class SupportServiceTest extends TestCase
{
    public function testList(): void
    {
        $c = $this->makeClient([$this->envelope(['tickets' => []])]);
        $c->support->list();
        $this->assertSame('/v2.2/support/tickets', $this->lastRequest()->getUri()->getPath());
    }

    public function testCreate(): void
    {
        $c = $this->makeClient([$this->envelope(['ticket' => ['id' => 1, 'number' => 1015, 'status' => 'active']])]);
        $r = $c->support->create(['subject' => 'help', 'message' => 'pls']);
        // confirm the "number" field is the ticket sequence number, not a phone
        $this->assertSame(1015, $r['ticket']['number']);
    }

    public function testGet(): void
    {
        $c = $this->makeClient([$this->envelope(['ticket' => ['id' => 1, 'status' => 'active']])]);
        $c->support->get(1);
        $this->assertSame('/v2.2/support/tickets/1', $this->lastRequest()->getUri()->getPath());
    }

    public function testUpdate(): void
    {
        $c = $this->makeClient([$this->envelope(['id' => 1, 'status' => 'success'])]);
        $r = $c->support->update(1, ['status' => 'closed']);
        $this->assertSame('success', $r['status']);
        $this->assertSame('PUT', $this->lastRequest()->getMethod());
    }

    public function testDelete(): void
    {
        $c = $this->makeClient([new Response(204)]);
        $c->support->delete(1);
        $this->assertSame('DELETE', $this->lastRequest()->getMethod());
    }

    public function testMessages(): void
    {
        $c = $this->makeClient([$this->envelope(['messages' => [['id' => 5, 'status' => 'active']]])]);
        $r = $c->support->messages(1);
        $this->assertCount(1, $r['messages']);
        $this->assertSame('/v2.2/support/tickets/1/messages', $this->lastRequest()->getUri()->getPath());
    }

    public function testReply(): void
    {
        $c = $this->makeClient([$this->envelope(['message' => 'Reply added'])]);
        $r = $c->support->reply(1, ['message' => 'thanks']);
        $this->assertSame('Reply added', $r['message']);
        $this->assertSame('POST', $this->lastRequest()->getMethod());
    }
}

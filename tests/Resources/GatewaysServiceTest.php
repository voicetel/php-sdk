<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Tests\Resources;

use VoiceTel\Sdk\Tests\TestCase;

final class GatewaysServiceTest extends TestCase
{
    public function testList(): void
    {
        $c = $this->makeClient([$this->envelope(['gateways' => [['id' => 1, 'gateway' => '203.0.113.1']]])]);
        $r = $c->gateways->list();
        $this->assertSame(1, $r['gateways'][0]['id']);
    }

    public function testAdd(): void
    {
        $c = $this->makeClient([$this->envelope(['id' => 7, 'gateway' => '203.0.113.7'])]);
        $r = $c->gateways->add(['gateway' => '203.0.113.7', 'limit' => 100]);
        $this->assertSame(7, $r['id']);
    }

    public function testGet(): void
    {
        $c = $this->makeClient([$this->envelope(['id' => 7])]);
        $r = $c->gateways->get(7);
        $this->assertSame(7, $r['id']);
        $this->assertSame('/v2.2/gateways/7', $this->lastRequest()->getUri()->getPath());
    }

    public function testUpdate(): void
    {
        $c = $this->makeClient([$this->envelope(['id' => 7, 'limit' => 50])]);
        $r = $c->gateways->update(7, ['limit' => 50]);
        $this->assertSame(50, $r['limit']);
        $this->assertSame('PUT', $this->lastRequest()->getMethod());
    }

    public function testRemove(): void
    {
        $c = $this->makeClient([new \GuzzleHttp\Psr7\Response(204)]);
        $c->gateways->remove(7);
        $this->assertSame('DELETE', $this->lastRequest()->getMethod());
    }

    public function testNumbers(): void
    {
        $c = $this->makeClient([$this->envelope(['numbers' => [['number' => '2015551234']]])]);
        $r = $c->gateways->numbers(7);
        $this->assertCount(1, $r['numbers']);
        $this->assertSame('/v2.2/gateways/7/numbers', $this->lastRequest()->getUri()->getPath());
    }
}

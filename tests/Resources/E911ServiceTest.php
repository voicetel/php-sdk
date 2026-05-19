<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Tests\Resources;

use VoiceTel\Sdk\ApiError;
use VoiceTel\Sdk\ErrorKind;
use VoiceTel\Sdk\Tests\TestCase;

final class E911ServiceTest extends TestCase
{
    public function testList(): void
    {
        $c = $this->makeClient([$this->envelope(['records' => [['dn' => '12015551234']]])]);
        $r = $c->e911->list();
        $this->assertSame('12015551234', $r['records'][0]['dn']);
    }

    public function testCreate(): void
    {
        $c = $this->makeClient([$this->envelope(['record' => ['dn' => '12015551234']])]);
        $r = $c->e911->create([
            'dn' => '2015551234', 'callername' => 'Jane',
            'address1' => '1 Way', 'city' => 'Newark', 'state' => 'NJ', 'zip' => '07101',
        ]);
        $this->assertSame('12015551234', $r['record']['dn']);
        $this->assertSame('POST', $this->lastRequest()->getMethod());
    }

    public function testValidate(): void
    {
        $c = $this->makeClient([$this->envelope(['address' => ['addressid' => 42]])]);
        $r = $c->e911->validate(['address1' => '1 Way', 'city' => 'Newark', 'state' => 'NJ', 'zip' => '07101']);
        $this->assertSame(42, $r['address']['addressid']);
        $this->assertSame('/v2.2/e911/validations', $this->lastRequest()->getUri()->getPath());
    }

    public function testGet(): void
    {
        $c = $this->makeClient([$this->envelope(['record' => ['dn' => '12015551234']])]);
        $r = $c->e911->getRecord('2015551234');
        $this->assertSame('12015551234', $r['record']['dn']);
        $this->assertSame('/v2.2/e911/2015551234', $this->lastRequest()->getUri()->getPath());
    }

    public function testProvision(): void
    {
        $c = $this->makeClient([$this->envelope(['record' => ['dn' => '12015551234']])]);
        $c->e911->provision('2015551234', ['callername' => 'Jane', 'addressid' => 42]);
        $this->assertSame('PUT', $this->lastRequest()->getMethod());
    }

    public function testRemoveReturnsVoid(): void
    {
        $c = $this->makeClient([new \GuzzleHttp\Psr7\Response(204)]);
        $c->e911->remove('2015551234');
        $this->assertSame('DELETE', $this->lastRequest()->getMethod());
    }

    public function testNotFoundError(): void
    {
        $c = $this->makeClient([new \GuzzleHttp\Psr7\Response(404, [], '{"message":"no such dn"}')], maxRetries: 0);
        try {
            $c->e911->getRecord('2015551234');
            $this->fail('expected ApiError');
        } catch (ApiError $e) {
            $this->assertSame(ErrorKind::NotFound, $e->kind);
            $this->assertTrue($e->isNotFound());
        }
    }
}

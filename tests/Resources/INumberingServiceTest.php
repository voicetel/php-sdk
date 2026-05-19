<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Tests\Resources;

use VoiceTel\Sdk\Tests\TestCase;

final class INumberingServiceTest extends TestCase
{
    public function testSearchInventoryAppendsQuery(): void
    {
        $c = $this->makeClient([$this->envelope(['numbers' => []])]);
        $c->iNumbering->searchInventory(['npa' => 201, 'state' => 'NJ', 'limit' => 25]);
        $q = $this->lastRequest()->getUri()->getQuery();
        $this->assertStringContainsString('npa=201', $q);
        $this->assertStringContainsString('state=NJ', $q);
        $this->assertStringContainsString('limit=25', $q);
    }

    public function testCoverage(): void
    {
        $c = $this->makeClient([$this->envelope(['coverage' => [['count' => 10]]])]);
        $r = $c->iNumbering->coverage(['state' => 'NJ']);
        $this->assertSame(10, $r['coverage'][0]['count']);
        $this->assertSame('/v2.2/inventory/coverage', $this->lastRequest()->getUri()->getPath());
    }

    public function testOrder(): void
    {
        $c = $this->makeClient([$this->envelope(['orderId' => 'ord_1', 'amountCharged' => 1.0, 'numbersOrdered' => ['2015551234']])]);
        $r = $c->iNumbering->order(['numbers' => ['2015551234']]);
        $this->assertSame('ord_1', $r['orderId']);
    }

    public function testPorts(): void
    {
        $c = $this->makeClient([$this->envelope(['ports' => []])]);
        $r = $c->iNumbering->ports();
        $this->assertSame([], $r['ports']);
    }

    public function testPort(): void
    {
        $c = $this->makeClient([$this->envelope(['port' => ['status' => 'in_progress']])]);
        $r = $c->iNumbering->port(99);
        $this->assertSame('in_progress', $r['port']['status']);
        $this->assertSame('/v2.2/ports/99', $this->lastRequest()->getUri()->getPath());
    }

    public function testSubmitPort(): void
    {
        $c = $this->makeClient([$this->envelope(['pid' => 'ABCDE', 'ticket' => 1234, 'message' => 'ok', 'loaUrl' => '', 'portUrl' => ''])]);
        $r = $c->iNumbering->submitPort(['did' => ['2015551234']]);
        $this->assertSame('ABCDE', $r['pid']);
    }

    public function testPortAvailabilityHasV2210Fields(): void
    {
        $c = $this->makeClient([$this->envelope([
            'number' => '2015551234',
            'portable' => true,
            'losingCarrier' => 'AcmeTel',
            'localRoutingNumber' => '2015550000',
            'rateCenterTier' => 'tier1',
            'reason' => null,
        ])]);
        $r = $c->iNumbering->portAvailability('2015551234');
        $this->assertTrue($r['portable']);
        $this->assertSame('2015550000', $r['localRoutingNumber']);
        $this->assertSame('tier1', $r['rateCenterTier']);
        $this->assertNull($r['reason']);
    }
}

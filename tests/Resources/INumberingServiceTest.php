<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Tests\Resources;

use VoiceTel\Sdk\Tests\TestCase;

final class INumberingServiceTest extends TestCase
{
    public function testSearchInventoryAppendsQuery(): void
    {
        $c = $this->makeClient([$this->envelope(['numbers' => []])]);
        $c->iNumbering->searchInventory('201555', quantity: 25, province: 'NJ', rateCenter: 'NEWARK', lata: '224');
        $q = $this->lastRequest()->getUri()->getQuery();
        $this->assertStringContainsString('tnMask=201555', $q);
        $this->assertStringContainsString('quantity=25', $q);
        $this->assertStringContainsString('province=NJ', $q);
        $this->assertStringContainsString('rateCenter=NEWARK', $q);
        $this->assertStringContainsString('lata=224', $q);
    }

    public function testCoverage(): void
    {
        $c = $this->makeClient([$this->envelope(['coverage' => [['count' => 10]]])]);
        $r = $c->iNumbering->coverage('npanxx', npa: '201');
        $this->assertSame(10, $r['coverage'][0]['count']);
        $this->assertSame('/v2.2/inventory/coverage', $this->lastRequest()->getUri()->getPath());
        $q = $this->lastRequest()->getUri()->getQuery();
        $this->assertStringContainsString('countBy=npanxx', $q);
        $this->assertStringContainsString('npa=201', $q);
    }

    public function testOrder(): void
    {
        $c = $this->makeClient([$this->envelope(['orderId' => 'ord_1', 'amountCharged' => 1.0, 'numbersOrdered' => ['2015550123']])]);
        $r = $c->iNumbering->order(['numbers' => ['2015550123']]);
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
        $r = $c->iNumbering->port('99');
        $this->assertSame('in_progress', $r['port']['status']);
        $this->assertSame('/v2.2/ports/99', $this->lastRequest()->getUri()->getPath());
    }

    public function testSubmitPort(): void
    {
        $c = $this->makeClient([$this->envelope(['pid' => 'ABCDE', 'ticket' => 1234, 'message' => 'ok', 'loaUrl' => '', 'portUrl' => ''])]);
        $r = $c->iNumbering->submitPort(['did' => ['2015550123']]);
        $this->assertSame('ABCDE', $r['pid']);
    }

    public function testPortAvailabilityHasV2210Fields(): void
    {
        $c = $this->makeClient([$this->envelope([
            'number' => '2015550123',
            'portable' => true,
            'losingCarrier' => 'AcmeTel',
            'localRoutingNumber' => '2015550000',
            'rateCenterTier' => 'tier1',
            'reason' => null,
        ])]);
        $r = $c->iNumbering->portAvailability('2015550123');
        $this->assertTrue($r['portable']);
        $this->assertSame('2015550000', $r['localRoutingNumber']);
        $this->assertSame('tier1', $r['rateCenterTier']);
        $this->assertNull($r['reason']);
    }
}

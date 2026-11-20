<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Tests\Resources;

use VoiceTel\Sdk\Tests\TestCase;

final class LookupsServiceTest extends TestCase
{
    public function testCnam(): void
    {
        $c = $this->makeClient([$this->envelope(['number' => '2015550123', 'cnam' => 'JANE DOE'])]);
        $r = $c->lookups->cnam('2015550123');
        $this->assertSame('JANE DOE', $r['cnam']);
        $this->assertSame('/v2.2/cnam/2015550123', $this->lastRequest()->getUri()->getPath());
    }

    public function testLrn(): void
    {
        $c = $this->makeClient([$this->envelope(['ani' => '2125550000', 'destination' => '2015550123', 'lrn' => ['lrn' => '2015550000', 'state' => 'NJ']])]);
        $r = $c->lookups->lrn('2015550123', '2125550000');
        $this->assertSame('2015550000', $r['lrn']['lrn']);
        $this->assertSame('/v2.2/lrn/2015550123/2125550000', $this->lastRequest()->getUri()->getPath());
    }

    public function testCarrierUsesUpstreamSnakeCaseFieldNames(): void
    {
        $c = $this->makeClient([$this->envelope([
            'number' => '2015550123',
            'carrier' => [
                'name' => 'EXAMPLE CARRIER',
                'type' => 'voip',
                'mobile_country_code' => null,
                'mobile_network_code' => null,
                'error_code' => null,
            ],
        ])]);
        $r = $c->lookups->carrier('2015550123');
        $this->assertSame('EXAMPLE CARRIER', $r['carrier']['name']);
        $this->assertSame('voip', $r['carrier']['type']);
        $this->assertNull($r['carrier']['mobile_country_code']);
        $this->assertNull($r['carrier']['mobile_network_code']);
        $this->assertNull($r['carrier']['error_code']);
        $req = $this->lastRequest();
        $this->assertSame('GET', $req->getMethod());
        $this->assertSame('/v2.2/carrier/2015550123', $req->getUri()->getPath());
    }

    public function testCarrierMobileFields(): void
    {
        $c = $this->makeClient([$this->envelope([
            'number' => '2015550123',
            'carrier' => [
                'name' => 'EXAMPLE MOBILE',
                'type' => 'mobile',
                'mobile_country_code' => '310',
                'mobile_network_code' => '456',
                'error_code' => null,
            ],
        ])]);
        $r = $c->lookups->carrier('2015550123');
        $this->assertSame('310', $r['carrier']['mobile_country_code']);
        $this->assertSame('456', $r['carrier']['mobile_network_code']);
    }
}

<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Tests\Resources;

use VoiceTel\Sdk\Tests\TestCase;

final class LookupsServiceTest extends TestCase
{
    public function testCnam(): void
    {
        $c = $this->makeClient([$this->envelope(['number' => '2015551234', 'cnam' => 'JANE DOE'])]);
        $r = $c->lookups->cnam('2015551234');
        $this->assertSame('JANE DOE', $r['cnam']);
        $this->assertSame('/v2.2/cnam/2015551234', $this->lastRequest()->getUri()->getPath());
    }

    public function testLrn(): void
    {
        $c = $this->makeClient([$this->envelope(['ani' => '2125550000', 'destination' => '2015551234', 'lrn' => ['lrn' => '2015550000', 'state' => 'NJ']])]);
        $r = $c->lookups->lrn('2015551234', '2125550000');
        $this->assertSame('2015550000', $r['lrn']['lrn']);
        $this->assertSame('/v2.2/lrn/2015551234/2125550000', $this->lastRequest()->getUri()->getPath());
    }
}

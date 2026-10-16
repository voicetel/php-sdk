<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Tests\Resources;

use VoiceTel\Sdk\Tests\TestCase;

final class MessagingServiceTest extends TestCase
{
    public function testHistoryWithFilters(): void
    {
        $c = $this->makeClient([$this->envelope(['messages' => [], 'number' => '2015550123', 'type' => 'sms', 'fromTs' => 0, 'toTs' => 0])]);
        $c->messaging->history(['number' => '2015550123', 'type' => 'sms', 'start' => 100]);
        $q = $this->lastRequest()->getUri()->getQuery();
        $this->assertStringContainsString('number=2015550123', $q);
        $this->assertStringContainsString('type=sms', $q);
        $this->assertStringContainsString('start=100', $q);
    }

    public function testSendUsesWireFieldNames(): void
    {
        $c = $this->makeClient([$this->envelope(['id' => 'msg_1', 'type' => 'sms', 'fromNumber' => '1', 'toNumber' => '2', 'parts' => 1])]);
        $c->messaging->send([
            'fromNumber' => '2015550123',
            'toNumber' => '2125559999',
            'text' => 'hi',
        ]);
        $sent = json_decode((string) $this->lastRequest()->getBody(), true);
        $this->assertSame('2015550123', $sent['fromNumber']);
        $this->assertSame('2125559999', $sent['toNumber']);
        $this->assertArrayNotHasKey('from_number', $sent);
    }

    public function testCreateBrand(): void
    {
        $c = $this->makeClient([$this->envelope(['result' => ['statusCode' => '200', 'status' => 'Success']])]);
        $r = $c->messaging->createBrand(['messagingBrandId' => 'B1234', 'messagingBrandName' => 'Acme']);
        $this->assertSame('Success', $r['result']['status']);
    }

    public function testCampaignStatus(): void
    {
        $c = $this->makeClient([$this->envelope(['campaigns' => []])]);
        $r = $c->messaging->campaignStatus();
        $this->assertSame([], $r['campaigns']);
    }

    public function testCampaignStatusIncludesDca(): void
    {
        $c = $this->makeClient([$this->envelope(['campaigns' => [
            [
                'id' => 'CAMP002',
                'status' => 'CAMPAIGN_DCA_REJECTED',
                'numbers' => ['2015550123'],
                'dca' => [
                    'electionComplete' => true,
                    'electedCarriers' => ['AT&T', 'T-Mobile', 'Verizon Wireless'],
                    'carriers' => [
                        [
                            'carrier' => 'T-Mobile',
                            'status' => 'REJECTED',
                            'detail' => 'Campaign rejected',
                            'updatedAt' => '2026-01-02T03:04:05Z',
                        ],
                    ],
                ],
            ],
            ['id' => 'NOPE', 'status' => 'CAMPAIGN_INVALID_ID', 'numbers' => [], 'dca' => null],
        ]])]);
        $r = $c->messaging->campaignStatus();
        $this->assertSame('CAMPAIGN_DCA_REJECTED', $r['campaigns'][0]['status']);
        $this->assertTrue($r['campaigns'][0]['dca']['electionComplete']);
        $this->assertSame('T-Mobile', $r['campaigns'][0]['dca']['carriers'][0]['carrier']);
        $this->assertSame('REJECTED', $r['campaigns'][0]['dca']['carriers'][0]['status']);
        $this->assertNull($r['campaigns'][1]['dca']);
    }

    public function testCreateCampaign(): void
    {
        $c = $this->makeClient([$this->envelope(['result' => ['statusCode' => '200', 'status' => 'Success']])]);
        $c->messaging->createCampaign(['messagingBrandId' => 'B1', 'externalCampaignId' => 'C1', 'campaignDescription' => 'd']);
        $this->assertSame('POST', $this->lastRequest()->getMethod());
    }

    public function testNumbersStateAll(): void
    {
        $c = $this->makeClient([$this->envelope(['numbers' => []])]);
        $c->messaging->numbersState();
        $this->assertSame('', $this->lastRequest()->getUri()->getQuery());
    }

    public function testNumbersStateFilter(): void
    {
        $c = $this->makeClient([$this->envelope(['numbers' => []])]);
        $c->messaging->numbersState(['2015550123', '2125550000']);
        $q = $this->lastRequest()->getUri()->getQuery();
        $this->assertStringContainsString('numbers=2015550123%2C2125550000', $q);
    }

    // v2.2.15: messaging-health endpoints have no response schema in the spec, so the
    // SDK returns the decoded JSON as an array verbatim; bodies/query are still shaped.
    public function testHealthReturnsRawPayload(): void
    {
        $c = $this->makeClient([$this->envelope(['summary' => ['numbers' => 3, 'healthy' => 2]])]);
        $r = $c->messaging->health();
        $this->assertSame(3, $r['summary']['numbers']);
        $this->assertSame('GET', $this->lastRequest()->getMethod());
        $this->assertSame('/v2.2/messaging/health', $this->lastRequest()->getUri()->getPath());
    }

    public function testHealthAlertsGetAndSet(): void
    {
        $c = $this->makeClient([
            $this->envelope(['enabled' => true]),
            $this->envelope(['enabled' => false]),
        ]);
        $get = $c->messaging->healthAlerts();
        $this->assertTrue($get['enabled']);
        $this->assertSame('/v2.2/messaging/health/alerts', $this->lastRequest()->getUri()->getPath());

        $set = $c->messaging->setHealthAlerts(false);
        $this->assertFalse($set['enabled']);
        $this->assertSame('PUT', $this->lastRequest()->getMethod());
        $sent = json_decode((string) $this->lastRequest()->getBody(), true);
        $this->assertFalse($sent['enabled']);
    }

    public function testHealthCheck(): void
    {
        $c = $this->makeClient([$this->envelope(['number' => '2125550123', 'inService' => true])]);
        $r = $c->messaging->healthCheck('2125550123');
        $this->assertTrue($r['inService']);
        $this->assertSame('POST', $this->lastRequest()->getMethod());
        $this->assertSame('/v2.2/messaging/health/check', $this->lastRequest()->getUri()->getPath());
        $sent = json_decode((string) $this->lastRequest()->getBody(), true);
        $this->assertSame('2125550123', $sent['number']);
    }

    public function testHealthSamples(): void
    {
        $c = $this->makeClient([$this->envelope(['samples' => [['reason' => 'spam']]])]);
        $r = $c->messaging->healthSamples('2125550123');
        $this->assertSame('spam', $r['samples'][0]['reason']);
        $this->assertSame('/v2.2/messaging/health/samples', $this->lastRequest()->getUri()->getPath());
        $this->assertStringContainsString('number=2125550123', $this->lastRequest()->getUri()->getQuery());
    }
}

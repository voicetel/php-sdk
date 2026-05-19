<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Tests\Resources;

use VoiceTel\Sdk\Tests\TestCase;

final class MessagingServiceTest extends TestCase
{
    public function testHistoryWithFilters(): void
    {
        $c = $this->makeClient([$this->envelope(['messages' => [], 'number' => '2015551234', 'type' => 'sms', 'fromTs' => 0, 'toTs' => 0])]);
        $c->messaging->history(['number' => '2015551234', 'type' => 'sms', 'start' => 100]);
        $q = $this->lastRequest()->getUri()->getQuery();
        $this->assertStringContainsString('number=2015551234', $q);
        $this->assertStringContainsString('type=sms', $q);
        $this->assertStringContainsString('start=100', $q);
    }

    public function testSendUsesWireFieldNames(): void
    {
        $c = $this->makeClient([$this->envelope(['id' => 'msg_1', 'type' => 'sms', 'fromNumber' => '1', 'toNumber' => '2', 'parts' => 1])]);
        $c->messaging->send([
            'fromNumber' => '2015551234',
            'toNumber' => '2125559999',
            'text' => 'hi',
        ]);
        $sent = json_decode((string) $this->lastRequest()->getBody(), true);
        $this->assertSame('2015551234', $sent['fromNumber']);
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
        $c->messaging->numbersState(['2015551234', '2125550000']);
        $q = $this->lastRequest()->getUri()->getQuery();
        $this->assertStringContainsString('numbers=2015551234%2C2125550000', $q);
    }
}

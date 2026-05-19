<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Tests\Resources;

use GuzzleHttp\Psr7\Response;
use VoiceTel\Sdk\Tests\TestCase;

final class NumbersServiceTest extends TestCase
{
    private const TN = '2015551234';

    public function testList(): void
    {
        $c = $this->makeClient([$this->envelope(['numbers' => []])]);
        $c->numbers->list();
        $this->assertSame('/v2.2/numbers', $this->lastRequest()->getUri()->getPath());
    }

    public function testAdd(): void
    {
        $c = $this->makeClient([$this->envelope(['number' => self::TN, 'route' => 4])]);
        $r = $c->numbers->add(['number' => self::TN]);
        $this->assertSame(4, $r['route']);
    }

    public function testGet(): void
    {
        $c = $this->makeClient([$this->envelope(['number' => self::TN])]);
        $c->numbers->get(self::TN);
        $this->assertSame('/v2.2/numbers/' . self::TN, $this->lastRequest()->getUri()->getPath());
    }

    public function testRemoveReturnsVoid(): void
    {
        $c = $this->makeClient([new Response(204)]);
        $c->numbers->remove(self::TN);
        $this->assertSame('DELETE', $this->lastRequest()->getMethod());
    }

    public function testMove(): void
    {
        $c = $this->makeClient([$this->envelope(['number' => self::TN, 'accountId' => 9, 'route' => 4])]);
        $c->numbers->move(self::TN, ['accountId' => 9, 'route' => 4]);
        $this->assertSame('PATCH', $this->lastRequest()->getMethod());
    }

    public function testRelease(): void
    {
        $c = $this->makeClient([new Response(204)]);
        $c->numbers->release(self::TN);
        $this->assertSame('POST', $this->lastRequest()->getMethod());
        $this->assertSame('/v2.2/numbers/' . self::TN . '/release', $this->lastRequest()->getUri()->getPath());
    }

    public function testSetRoute(): void
    {
        $c = $this->makeClient([$this->envelope(['number' => self::TN, 'route' => 7])]);
        $r = $c->numbers->setRoute(self::TN, ['route' => 7]);
        $this->assertSame(7, $r['route']);
    }

    public function testSetTranslation(): void
    {
        $c = $this->makeClient([$this->envelope(['number' => self::TN, 'translation' => '1#'])]);
        $c->numbers->setTranslation(self::TN, ['translation' => '1#']);
        $this->assertSame('PUT', $this->lastRequest()->getMethod());
    }

    public function testSetCnam(): void
    {
        $c = $this->makeClient([$this->envelope(['number' => self::TN, 'cnam' => true])]);
        $c->numbers->setCnam(self::TN, ['enabled' => true]);
        $this->assertSame('PUT', $this->lastRequest()->getMethod());
    }

    public function testSetLidb(): void
    {
        $c = $this->makeClient([$this->envelope(['number' => self::TN, 'cnam' => 'JANE DOE', 'customerOrderReference' => 'X', 'carrierStatus' => 'Success'])]);
        $r = $c->numbers->setLidb(self::TN, ['cnam' => 'JANE DOE']);
        $this->assertSame('Success', $r['carrierStatus']);
    }

    public function testGetFax(): void
    {
        $c = $this->makeClient([$this->envelope(['number' => self::TN, 'email' => 'fax@x.y'])]);
        $r = $c->numbers->getFax(self::TN);
        $this->assertSame('fax@x.y', $r['email']);
    }

    public function testSetFax(): void
    {
        $c = $this->makeClient([$this->envelope(['number' => self::TN, 'email' => 'fax@x.y'])]);
        $c->numbers->setFax(self::TN, ['email' => 'fax@x.y']);
        $this->assertSame('PUT', $this->lastRequest()->getMethod());
    }

    public function testRemoveFax(): void
    {
        $c = $this->makeClient([new Response(204)]);
        $c->numbers->removeFax(self::TN);
        $this->assertSame('DELETE', $this->lastRequest()->getMethod());
    }

    public function testSetForward(): void
    {
        $c = $this->makeClient([$this->envelope(['number' => self::TN, 'forwardTo' => '2125551234'])]);
        $c->numbers->setForward(self::TN, ['destination' => 2125551234]);
        $this->assertSame('PUT', $this->lastRequest()->getMethod());
    }

    public function testRemoveForward(): void
    {
        $c = $this->makeClient([new Response(204)]);
        $c->numbers->removeForward(self::TN);
        $this->assertSame('DELETE', $this->lastRequest()->getMethod());
    }

    public function testGetSms(): void
    {
        $c = $this->makeClient([$this->envelope(['number' => self::TN, 'type' => 'webhook', 'resource' => 'https://x'])]);
        $r = $c->numbers->getSms(self::TN);
        $this->assertSame('webhook', $r['type']);
    }

    public function testSetSms(): void
    {
        $c = $this->makeClient([$this->envelope(['number' => self::TN, 'type' => 'webhook', 'resource' => 'https://x'])]);
        $c->numbers->setSms(self::TN, ['type' => 'webhook', 'resource' => 'https://x']);
        $this->assertSame('PUT', $this->lastRequest()->getMethod());
    }

    public function testRemoveSms(): void
    {
        $c = $this->makeClient([new Response(204)]);
        $c->numbers->removeSms(self::TN);
        $this->assertSame('DELETE', $this->lastRequest()->getMethod());
    }

    public function testGetMessaging(): void
    {
        $c = $this->makeClient([$this->envelope(['number' => self::TN, 'enabled' => true])]);
        $r = $c->numbers->getMessaging(self::TN);
        $this->assertTrue($r['enabled']);
    }

    public function testPatchMessaging(): void
    {
        $c = $this->makeClient([$this->envelope(['number' => self::TN, 'updated' => ['routeIn']])]);
        $c->numbers->patchMessaging(self::TN, ['routeIn' => 42]);
        $this->assertSame('PATCH', $this->lastRequest()->getMethod());
    }

    public function testAssignCampaign(): void
    {
        $c = $this->makeClient([$this->envelope(['number' => self::TN, 'campaignId' => 'CXXXXXX', 'carrier' => 17])]);
        $r = $c->numbers->assignCampaign(self::TN, ['campaignId' => 'CXXXXXX']);
        $this->assertSame('CXXXXXX', $r['campaignId']);
    }

    public function testUnassignCampaignReturns200Body(): void
    {
        $c = $this->makeClient([$this->envelope(['number' => self::TN, 'campaignId' => 'CXXXXXX', 'unassigned' => true])]);
        $r = $c->numbers->unassignCampaign(self::TN);
        $this->assertTrue($r['unassigned']);
        $this->assertSame('DELETE', $this->lastRequest()->getMethod());
    }

    public function testBulkUnassignCampaignReturns200Body(): void
    {
        $c = $this->makeClient([$this->envelope(['campaignId' => 'C', 'unassignedNumbers' => [self::TN]])]);
        $r = $c->numbers->bulkUnassignCampaign([self::TN]);
        $this->assertContains(self::TN, $r['unassignedNumbers']);
        $this->assertSame('DELETE', $this->lastRequest()->getMethod());
        $sent = json_decode((string) $this->lastRequest()->getBody(), true);
        $this->assertSame([self::TN], $sent['numbers']);
    }

    public function testSetPortOutPin(): void
    {
        $c = $this->makeClient([$this->envelope(['number' => self::TN, 'portOutPin' => '1234'])]);
        $r = $c->numbers->setPortOutPin(self::TN, ['pin' => '1234']);
        $this->assertSame('1234', $r['portOutPin']);
        $this->assertSame('PATCH', $this->lastRequest()->getMethod());
    }
}

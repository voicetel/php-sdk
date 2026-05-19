<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Tests\Resources;

use VoiceTel\Sdk\ApiError;
use VoiceTel\Sdk\ErrorKind;
use VoiceTel\Sdk\Tests\TestCase;

final class AclServiceTest extends TestCase
{
    public function testList(): void
    {
        $c = $this->makeClient([$this->envelope(['acl' => [['cidr' => '1.2.3.0/24']]])]);
        $r = $c->acl->list();
        $this->assertSame('1.2.3.0/24', $r['acl'][0]['cidr']);
        $this->assertSame('GET', $this->lastRequest()->getMethod());
    }

    public function testAdd(): void
    {
        $c = $this->makeClient([$this->envelope(['added' => [['cidr' => '1.2.3.0/24']]])]);
        $r = $c->acl->add(['acl' => [['cidr' => '1.2.3.0/24']]]);
        $this->assertCount(1, $r['added']);
        $this->assertSame('POST', $this->lastRequest()->getMethod());
    }

    public function testRemoveReturnsBody(): void
    {
        $c = $this->makeClient([$this->envelope(['removed' => [['cidr' => '1.2.3.0/24']]])]);
        $r = $c->acl->remove(['acl' => [['cidr' => '1.2.3.0/24']]]);
        $this->assertCount(1, $r['removed']);
        $this->assertSame('DELETE', $this->lastRequest()->getMethod());
    }

    public function testConflictExposesFailedEntriesOnError(): void
    {
        $body = [
            'status' => 'failure',
            'data' => [
                'added' => [['cidr' => '1.2.3.0/24']],
                'failed' => [['cidr' => 'bogus', 'reason' => 'Invalid mask: must be /8, /16, /24, or /32']],
            ],
            'message' => 'partial',
        ];
        $c = $this->makeClient([
            new \GuzzleHttp\Psr7\Response(409, ['Content-Type' => 'application/json'], json_encode($body)),
        ], maxRetries: 0);
        try {
            $c->acl->add(['acl' => [['cidr' => 'bogus']]]);
            $this->fail('expected ApiError');
        } catch (ApiError $e) {
            $this->assertSame(ErrorKind::Conflict, $e->kind);
            $this->assertIsArray($e->body);
        }
    }
}

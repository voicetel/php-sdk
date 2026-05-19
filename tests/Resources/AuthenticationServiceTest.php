<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Tests\Resources;

use VoiceTel\Sdk\Resources\AuthenticationService;
use VoiceTel\Sdk\Tests\TestCase;

final class AuthenticationServiceTest extends TestCase
{
    public function testGet(): void
    {
        $c = $this->makeClient([$this->envelope(['authType' => 0, 'authTypeDescription' => 'Digest', 'acl' => []])]);
        $r = $c->authentication->get();
        $this->assertSame(0, $r['authType']);
        $this->assertSame('Digest', $r['authTypeDescription']);
        $this->assertSame('/v2.2/auth', $this->lastRequest()->getUri()->getPath());
    }

    public function testUpdate(): void
    {
        $c = $this->makeClient([$this->envelope(['updated' => [['field' => 'authType', 'value' => 1]]])]);
        $c->authentication->update(['authType' => AuthenticationService::AUTH_TYPE_IP_AUTH]);
        $this->assertSame('PUT', $this->lastRequest()->getMethod());
        $sent = json_decode((string) $this->lastRequest()->getBody(), true);
        $this->assertSame(1, $sent['authType']);
    }

    public function testAuthTypeConstants(): void
    {
        $this->assertSame(0, AuthenticationService::AUTH_TYPE_DIGEST);
        $this->assertSame(1, AuthenticationService::AUTH_TYPE_IP_AUTH);
        $this->assertSame(2, AuthenticationService::AUTH_TYPE_DIGEST_OR_IP);
        $this->assertSame(3, AuthenticationService::AUTH_TYPE_DIGEST_AND_IP);
    }
}

<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Tests\Resources;

use VoiceTel\Sdk\ApiError;
use VoiceTel\Sdk\ErrorKind;
use VoiceTel\Sdk\Tests\TestCase;

final class AccountServiceTest extends TestCase
{
    public function testGet(): void
    {
        $c = $this->makeClient([$this->envelope(['username' => 'a', 'cash' => 1.5])]);
        $r = $c->account->get();
        $this->assertSame('a', $r['username']);
        $this->assertSame('GET', $this->lastRequest()->getMethod());
        $this->assertSame('/v2.2/account', $this->lastRequest()->getUri()->getPath());
    }

    public function testUpdate(): void
    {
        $c = $this->makeClient([$this->envelope(['updated' => ['timezone']])]);
        $r = $c->account->update(['timezone' => 'America/Chicago']);
        $this->assertSame(['timezone'], $r['updated']);
        $this->assertSame('PUT', $this->lastRequest()->getMethod());
        $sent = json_decode((string) $this->lastRequest()->getBody(), true);
        $this->assertSame('America/Chicago', $sent['timezone']);
    }

    public function testAdd(): void
    {
        $c = $this->makeClient([$this->envelope(['username' => '5', 'password' => 'auto'])]);
        $r = $c->account->add(['username' => 5, 'name' => 'Sub', 'email' => 'x@y.z']);
        $this->assertSame('auto', $r['password']);
    }

    public function testSignup(): void
    {
        $c = $this->makeClient([$this->envelope(['username' => '1', 'password' => 'pw'])]);
        $r = $c->account->signup(['name' => 'N', 'email' => 'e@f.g']);
        $this->assertSame('pw', $r['password']);
        $this->assertSame('/v2.2/accounts', $this->lastRequest()->getUri()->getPath());
    }

    public function testCdrWithRange(): void
    {
        $c = $this->makeClient([$this->envelope(['cdr' => [], 'start' => 100, 'end' => 200])]);
        $c->account->cdr(100, 200);
        $q = $this->lastRequest()->getUri()->getQuery();
        $this->assertStringContainsString('start=100', $q);
        $this->assertStringContainsString('end=200', $q);
    }

    public function testCdrWithoutArgsOmitsQuery(): void
    {
        $c = $this->makeClient([$this->envelope(['cdr' => []])]);
        $c->account->cdr();
        $this->assertSame('', $this->lastRequest()->getUri()->getQuery());
    }

    public function testCredits(): void
    {
        $c = $this->makeClient([$this->envelope(['credits' => []])]);
        $r = $c->account->credits();
        $this->assertArrayHasKey('credits', $r);
    }

    public function testRecurringCharges(): void
    {
        $c = $this->makeClient([$this->envelope(['charges' => [], 'total' => 12.5])]);
        $r = $c->account->recurringCharges();
        $this->assertSame(12.5, $r['total']);
        $this->assertSame('/v2.2/account/recurring-charges', $this->lastRequest()->getUri()->getPath());
    }

    public function testPayments(): void
    {
        $c = $this->makeClient([$this->envelope(['payments' => []])]);
        $r = $c->account->payments();
        $this->assertSame([], $r['payments']);
    }

    public function testRegistration(): void
    {
        $c = $this->makeClient([$this->envelope(['agent' => 'X-UA', 'uri' => 'sip:x', 'expires' => 3600])]);
        $r = $c->account->registration();
        $this->assertSame('X-UA', $r['agent']);
    }

    public function testRecoverDoesNotRequireAuth(): void
    {
        $c = $this->makeClient([$this->envelope(['message' => 'sent'])], apiKey: '');
        $r = $c->account->recover(['email' => 'a@b.c']);
        $this->assertSame('sent', $r['message']);
        $this->assertFalse($this->lastRequest()->hasHeader('Authorization'));
    }

    public function testRateLimitErrorPath(): void
    {
        $c = $this->makeClient([
            new \GuzzleHttp\Psr7\Response(429, [], '{"message":"too many"}'),
        ], maxRetries: 0);
        try {
            $c->account->cdr();
            $this->fail('expected ApiError');
        } catch (ApiError $e) {
            $this->assertSame(ErrorKind::RateLimit, $e->kind);
            $this->assertTrue($e->isRateLimit());
        }
    }
}

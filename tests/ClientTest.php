<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Tests;

use VoiceTel\Sdk\ApiError;
use VoiceTel\Sdk\Client;
use VoiceTel\Sdk\ErrorKind;
use VoiceTel\Sdk\Resources\AccountService;
use VoiceTel\Sdk\Resources\AclService;
use VoiceTel\Sdk\Resources\AuthenticationService;
use VoiceTel\Sdk\Resources\E911Service;
use VoiceTel\Sdk\Resources\GatewaysService;
use VoiceTel\Sdk\Resources\INumberingService;
use VoiceTel\Sdk\Resources\LookupsService;
use VoiceTel\Sdk\Resources\MessagingService;
use VoiceTel\Sdk\Resources\NumbersService;
use VoiceTel\Sdk\Resources\SupportService;
use VoiceTel\Sdk\Version;

final class ClientTest extends TestCase
{
    public function testExposesAllTenResourceServices(): void
    {
        $c = $this->makeClient();
        $this->assertInstanceOf(AccountService::class, $c->account);
        $this->assertInstanceOf(AclService::class, $c->acl);
        $this->assertInstanceOf(AuthenticationService::class, $c->authentication);
        $this->assertInstanceOf(E911Service::class, $c->e911);
        $this->assertInstanceOf(GatewaysService::class, $c->gateways);
        $this->assertInstanceOf(INumberingService::class, $c->iNumbering);
        $this->assertInstanceOf(LookupsService::class, $c->lookups);
        $this->assertInstanceOf(MessagingService::class, $c->messaging);
        $this->assertInstanceOf(NumbersService::class, $c->numbers);
        $this->assertInstanceOf(SupportService::class, $c->support);
    }

    public function testDefaultBaseUrl(): void
    {
        $c = new Client();
        $this->assertSame('https://api.voicetel.com', $c->baseUrl());
        $this->assertSame('', $c->apiKey());
    }

    public function testBaseUrlIsConfigurable(): void
    {
        $c = $this->makeClient();
        $this->assertSame('https://api.voicetel.test', $c->baseUrl());
        $this->assertSame('test-key', $c->apiKey());
    }

    public function testLoginExchangesPasswordForApiKeyAndInstallsIt(): void
    {
        $c = $this->makeClient([
            $this->envelope(['apikey' => 'aabbccddeeff112233445566778899aa']),
        ], apiKey: '');

        $key = $c->login(1000000001, 'hunter2');

        $this->assertSame('aabbccddeeff112233445566778899aa', $key);
        $this->assertSame('aabbccddeeff112233445566778899aa', $c->apiKey());

        $req = $this->lastRequest();
        $this->assertSame('POST', $req->getMethod());
        $this->assertSame('/v2.2/account/api-key', $req->getUri()->getPath());
        $this->assertFalse($req->hasHeader('Authorization'), 'login must not send a bearer');
        $decoded = json_decode((string) $req->getBody(), true);
        $this->assertSame(1000000001, $decoded['username']);
        $this->assertSame('hunter2', $decoded['password']);
    }

    public function testLoginRaisesAuthenticationErrorIfApiKeyMissing(): void
    {
        $c = $this->makeClient([$this->envelope(['apikey' => ''])], apiKey: '');
        try {
            $c->login(1, 'pw');
            $this->fail('expected ApiError');
        } catch (ApiError $e) {
            $this->assertSame(ErrorKind::Authentication, $e->kind);
        }
    }

    public function testRequestWithoutApiKeyThrowsAuthenticationError(): void
    {
        $c = $this->makeClient([], apiKey: '');
        try {
            $c->account->get();
            $this->fail('expected ApiError');
        } catch (ApiError $e) {
            $this->assertSame(ErrorKind::Authentication, $e->kind);
            $this->assertSame(0, $e->statusCode);
        }
    }

    public function testUserAgentHeaderSent(): void
    {
        $c = $this->makeClient([$this->envelope(['username' => '1'])]);
        $c->account->get();
        $this->assertStringStartsWith(
            'voicetel-php-sdk/' . Version::SDK_VERSION,
            $this->lastRequest()->getHeaderLine('User-Agent'),
        );
        $this->assertSame('Bearer test-key', $this->lastRequest()->getHeaderLine('Authorization'));
    }
}

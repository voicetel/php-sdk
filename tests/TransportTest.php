<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Tests;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response;
use VoiceTel\Sdk\ApiError;
use VoiceTel\Sdk\ErrorKind;

final class TransportTest extends TestCase
{
    public function testStripsSuccessEnvelope(): void
    {
        $c = $this->makeClient([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'status' => 'success',
                'data' => ['username' => 'abc', 'cash' => 12.5],
            ])),
        ]);
        $out = $c->account->get();
        $this->assertSame('abc', $out['username']);
        $this->assertSame(12.5, $out['cash']);
        $this->assertSame('gzip', $this->history[0]['request']->getHeaderLine('Accept-Encoding'));
    }

    public function testDecodesGzipResponse(): void
    {
        $payload = json_encode([
            'status' => 'success',
            'data' => ['username' => 'gzip-user'],
        ], JSON_THROW_ON_ERROR);
        $c = $this->makeClient([
            new Response(200, [
                'Content-Type' => 'application/json',
                'Content-Encoding' => 'gzip',
            ], gzencode($payload)),
        ]);
        $out = $c->account->get();
        $this->assertSame('gzip-user', $out['username']);
    }

    public function testRawJsonWithoutEnvelopeIsPassedThrough(): void
    {
        $c = $this->makeClient([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['x' => 1])),
        ]);
        $out = $c->account->get();
        $this->assertSame(1, $out['x']);
    }

    public function testRetryOn429HonorsRetryAfter(): void
    {
        $c = $this->makeClient([
            new Response(429, ['Retry-After' => '0'], '{"message":"slow down"}'),
            new Response(429, ['Retry-After' => '0'], '{"message":"slow down"}'),
            $this->envelope(['username' => 'ok']),
        ], maxRetries: 2);

        $out = $c->account->get();
        $this->assertSame('ok', $out['username']);
        $this->assertCount(3, $this->history);
    }

    public function testRetriesExhaustedOn429YieldRateLimitError(): void
    {
        $c = $this->makeClient([
            new Response(429, [], '{"message":"limited"}'),
            new Response(429, [], '{"message":"limited"}'),
            new Response(429, [], '{"message":"limited"}'),
        ], maxRetries: 2);
        try {
            $c->account->get();
            $this->fail('expected ApiError');
        } catch (ApiError $e) {
            $this->assertSame(ErrorKind::RateLimit, $e->kind);
            $this->assertSame(429, $e->statusCode);
        }
    }

    public function testRetryOn5xx(): void
    {
        $c = $this->makeClient([
            new Response(503, [], '{"message":"oops"}'),
            $this->envelope(['username' => 'after-retry']),
        ], maxRetries: 1);

        $out = $c->account->get();
        $this->assertSame('after-retry', $out['username']);
    }

    public function testHttpErrorMapping(): void
    {
        $mapping = [
            400 => ErrorKind::BadRequest,
            401 => ErrorKind::Authentication,
            403 => ErrorKind::PermissionDenied,
            404 => ErrorKind::NotFound,
            409 => ErrorKind::Conflict,
            500 => ErrorKind::Server,
            418 => ErrorKind::Unknown,
        ];

        foreach ($mapping as $code => $kind) {
            // 5xx will be retried by default — disable retries.
            $c = $this->makeClient([new Response($code, [], '{"message":"x","code":"E_X"}')], maxRetries: 0);
            try {
                $c->account->get();
                $this->fail("expected ApiError for $code");
            } catch (ApiError $e) {
                $this->assertSame($kind, $e->kind, "wrong kind for $code");
                $this->assertSame($code, $e->statusCode);
                $this->assertSame('E_X', $e->code());
            }
        }
    }

    public function testNon2xxWithNonJsonBodyKeepsRawString(): void
    {
        $c = $this->makeClient([new Response(500, [], '<html>bad</html>')], maxRetries: 0);
        try {
            $c->account->get();
            $this->fail('expected ApiError');
        } catch (ApiError $e) {
            $this->assertSame('<html>bad</html>', $e->body);
        }
    }

    public function testTransportErrorRetriesThenFails(): void
    {
        $req = new Psr7Request('GET', 'https://api.voicetel.test/v2.2/account');
        $c = $this->makeClient([
            new ConnectException('dns fail', $req),
            new ConnectException('dns fail', $req),
        ], maxRetries: 1);

        try {
            $c->account->get();
            $this->fail('expected ApiError');
        } catch (ApiError $e) {
            $this->assertSame(ErrorKind::Unknown, $e->kind);
            $this->assertSame(0, $e->statusCode);
            $this->assertStringContainsString('transport error', $e->getMessage());
        }
    }

    public function testQueryParametersAreUrlEncoded(): void
    {
        $c = $this->makeClient([$this->envelope(['cdr' => [], 'start' => 1, 'end' => 2])]);
        $c->account->cdr(1, 2);
        $uri = $this->lastRequest()->getUri();
        $this->assertSame('/v2.2/account/cdr', $uri->getPath());
        $this->assertStringContainsString('start=1', $uri->getQuery());
        $this->assertStringContainsString('end=2', $uri->getQuery());
    }

    public function testJsonContentTypeAndAcceptHeaders(): void
    {
        $c = $this->makeClient([$this->envelope(['updated' => []])]);
        $c->account->update(['timezone' => 'UTC']);
        $req = $this->lastRequest();
        $this->assertSame('application/json', $req->getHeaderLine('Content-Type'));
        $this->assertSame('application/json', $req->getHeaderLine('Accept'));
        $this->assertSame('PUT', $req->getMethod());
    }
}

<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase as BaseTestCase;
use VoiceTel\Sdk\Client;

/**
 * Shared test plumbing — builds a Client whose Guzzle handler is a MockHandler
 * we can queue responses against, plus a captured-request log.
 */
abstract class TestCase extends BaseTestCase
{
    protected MockHandler $mock;
    /** @var array<int, array{request: Request, response: Response|\Throwable|null}> */
    protected array $history = [];

    /**
     * Build a configured Client backed by a MockHandler.
     *
     * @param array<int, Response|\Throwable> $queue
     */
    protected function makeClient(array $queue = [], int $maxRetries = 2, ?string $apiKey = 'test-key'): Client
    {
        $this->mock = new MockHandler($queue);
        $stack = HandlerStack::create($this->mock);
        $this->history = [];
        $stack->push(Middleware::history($this->history));
        $http = new GuzzleClient(['handler' => $stack, 'http_errors' => false]);

        $client = new Client(
            apiKey: $apiKey,
            baseUrl: 'https://api.voicetel.test',
            maxRetries: $maxRetries,
            timeout: 5.0,
            http: $http,
        );
        // No real sleeping during tests.
        $client->transport()->sleeper = static function (float $s): void {};
        return $client;
    }

    /**
     * Build a JSON success response with the standard {status, data} envelope.
     */
    protected function envelope(mixed $data, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode([
            'status' => 'success',
            'data' => $data,
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * Build a JSON error response.
     *
     * @param array<string, mixed> $body
     */
    protected function errorJson(int $status, array $body = [], array $headers = []): Response
    {
        return new Response(
            $status,
            array_merge(['Content-Type' => 'application/json'], $headers),
            json_encode($body ?: ['message' => sprintf('HTTP %d test error', $status)], JSON_THROW_ON_ERROR),
        );
    }

    protected function lastRequest(): Request
    {
        $last = end($this->history);
        $this->assertNotFalse($last, 'no captured request');
        /** @var Request $req */
        $req = $last['request'];
        return $req;
    }
}

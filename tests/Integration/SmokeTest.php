<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Tests\Integration;

use PHPUnit\Framework\TestCase;
use VoiceTel\Sdk\Client;

/**
 * Read-only integration smoke test.
 *
 * Set VOICETEL_USERNAME and VOICETEL_PASSWORD (and optionally VOICETEL_BASE_URL)
 * to run. By default the entire class is skipped — production credentials
 * should never be exercised by `composer test`.
 */
final class SmokeTest extends TestCase
{
    protected function setUp(): void
    {
        $username = getenv('VOICETEL_USERNAME');
        $password = getenv('VOICETEL_PASSWORD');
        if ($username === false || $username === '' || $password === false || $password === '') {
            $this->markTestSkipped('VOICETEL_USERNAME / VOICETEL_PASSWORD not set');
        }
    }

    public function testLoginAndAccountRead(): void
    {
        $base = getenv('VOICETEL_BASE_URL');
        $c = new Client(baseUrl: is_string($base) && $base !== '' ? $base : null);
        $key = $c->login((int) getenv('VOICETEL_USERNAME'), (string) getenv('VOICETEL_PASSWORD'));
        $this->assertNotEmpty($key);

        $profile = $c->account->get();
        $this->assertArrayHasKey('username', $profile);
    }

    public function testListNumbersReadOnly(): void
    {
        $base = getenv('VOICETEL_BASE_URL');
        $c = new Client(baseUrl: is_string($base) && $base !== '' ? $base : null);
        $c->login((int) getenv('VOICETEL_USERNAME'), (string) getenv('VOICETEL_PASSWORD'));
        $numbers = $c->numbers->list();
        $this->assertArrayHasKey('numbers', $numbers);
    }
}

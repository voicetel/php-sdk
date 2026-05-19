<?php

declare(strict_types=1);

namespace VoiceTel\Sdk\Tests;

use PHPUnit\Framework\TestCase;
use VoiceTel\Sdk\ApiError;
use VoiceTel\Sdk\ErrorKind;

final class ApiErrorTest extends TestCase
{
    public function testCarriesStructuredFields(): void
    {
        $e = new ApiError(ErrorKind::NotFound, 404, 'E_NF', ['k' => 'v'], 'gone');
        $this->assertSame(ErrorKind::NotFound, $e->kind);
        $this->assertSame(404, $e->statusCode);
        $this->assertSame('E_NF', $e->code());
        $this->assertSame(['k' => 'v'], $e->body);
        $this->assertSame('gone', $e->getMessage());
    }

    public function testHelperPredicates(): void
    {
        $this->assertTrue((new ApiError(ErrorKind::NotFound))->isNotFound());
        $this->assertTrue((new ApiError(ErrorKind::Conflict))->isConflict());
        $this->assertTrue((new ApiError(ErrorKind::Authentication))->isAuthentication());
        $this->assertTrue((new ApiError(ErrorKind::RateLimit))->isRateLimit());
        $this->assertFalse((new ApiError(ErrorKind::Server))->isRateLimit());
    }

    public function testKindFromStatus(): void
    {
        $this->assertSame(ErrorKind::BadRequest, ErrorKind::fromStatus(400));
        $this->assertSame(ErrorKind::Authentication, ErrorKind::fromStatus(401));
        $this->assertSame(ErrorKind::PermissionDenied, ErrorKind::fromStatus(403));
        $this->assertSame(ErrorKind::NotFound, ErrorKind::fromStatus(404));
        $this->assertSame(ErrorKind::Conflict, ErrorKind::fromStatus(409));
        $this->assertSame(ErrorKind::RateLimit, ErrorKind::fromStatus(429));
        $this->assertSame(ErrorKind::Server, ErrorKind::fromStatus(503));
        $this->assertSame(ErrorKind::Unknown, ErrorKind::fromStatus(418));
    }

    public function testDefaultMessageWhenStatusGreaterThanZero(): void
    {
        $e = new ApiError(ErrorKind::BadRequest, 400);
        $this->assertSame('voicetel: HTTP 400', $e->getMessage());
    }

    public function testDefaultMessageWithTransportError(): void
    {
        $e = new ApiError();
        $this->assertStringContainsString('transport error', $e->getMessage());
    }
}

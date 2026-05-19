<?php

declare(strict_types=1);

namespace VoiceTel\Sdk;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Request;
use JsonException;
use Psr\Http\Message\ResponseInterface;

/**
 * Transport is the low-level HTTP client used by every resource service.
 *
 * Handles:
 *  - JSON encode/decode (with JSON_THROW_ON_ERROR)
 *  - the {"status":"success","data":...} envelope (stripped before decode)
 *  - bearer-token injection
 *  - auto-retry on 429 / 5xx with exponential backoff + Retry-After
 *  - mapping non-2xx responses to ApiError with a typed ErrorKind
 *
 * @internal
 */
final class Transport
{
    /** @var array<int, true> */
    private const RETRYABLE = [429 => true, 500 => true, 502 => true, 503 => true, 504 => true];

    private string $baseUrl;
    private string $apiKey;
    private string $userAgent;
    private int $maxRetries;
    private float $timeout;
    private ClientInterface $http;

    /** Hook for tests — swap in a closure that sleeps without blocking. */
    public \Closure $sleeper;

    public function __construct(
        string $baseUrl,
        string $apiKey,
        string $userAgent,
        int $maxRetries,
        float $timeout,
        ?ClientInterface $http = null,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->userAgent = $userAgent;
        $this->maxRetries = max(0, $maxRetries);
        $this->timeout = $timeout;
        $this->http = $http ?? new GuzzleClient([
            'timeout' => $timeout,
            'connect_timeout' => $timeout,
            'http_errors' => false,
        ]);
        // Default sleeper — real time. Tests override via $transport->sleeper = fn($s) => null;.
        $this->sleeper = static function (float $seconds): void {
            if ($seconds > 0) {
                usleep((int) round($seconds * 1_000_000));
            }
        };
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    public function apiKey(): string
    {
        return $this->apiKey;
    }

    public function setBearer(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function maxRetries(): int
    {
        return $this->maxRetries;
    }

    public function timeout(): float
    {
        return $this->timeout;
    }

    /**
     * Perform an HTTP request and decode the JSON response.
     *
     * @param string                $method        HTTP method
     * @param string                $path          path with leading slash, e.g. "/v2.2/numbers"
     * @param array<string, scalar> $query         URL query parameters (scalars stringified)
     * @param mixed                 $body          request body, json_encoded as-is. null = no body.
     * @param bool                  $requireAuth   prepend Authorization: Bearer
     * @param bool                  $expectNoBody  true for DELETE/204 endpoints
     *
     * @return mixed decoded inner data (after envelope unwrap), or null for 204 / no-body responses
     *
     * @throws ApiError on non-2xx or transport failure
     */
    public function request(
        string $method,
        string $path,
        array $query = [],
        mixed $body = null,
        bool $requireAuth = true,
        bool $expectNoBody = false,
    ): mixed {
        if ($requireAuth && $this->apiKey === '') {
            throw new ApiError(
                kind: ErrorKind::Authentication,
                message: 'voicetel: no api key set; call $client->login() or pass apiKey to the Client constructor',
            );
        }

        $url = $this->baseUrl . $path;
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        $headers = [
            'User-Agent' => $this->userAgent,
            'Accept' => 'application/json',
        ];
        $payload = null;
        if ($body !== null) {
            try {
                $payload = json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } catch (JsonException $e) {
                throw new ApiError(message: 'voicetel: marshal request body: ' . $e->getMessage(), previous: $e);
            }
            $headers['Content-Type'] = 'application/json';
        }
        if ($requireAuth) {
            $headers['Authorization'] = 'Bearer ' . $this->apiKey;
        }

        $request = new Request($method, $url, $headers, $payload);

        $lastErr = null;
        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            try {
                $response = $this->http->send($request, ['http_errors' => false]);
            } catch (ConnectException | TransferException | GuzzleException $e) {
                // Cast network-level failures into ApiError, retry where possible.
                $lastErr = $e;
                if ($attempt >= $this->maxRetries) {
                    throw new ApiError(
                        message: sprintf(
                            'voicetel: transport error after %d attempt(s): %s',
                            $attempt + 1,
                            $e->getMessage(),
                        ),
                        previous: $e,
                    );
                }
                ($this->sleeper)($this->backoffDelay($attempt, null));
                continue;
            }

            $status = $response->getStatusCode();
            if (isset(self::RETRYABLE[$status]) && $attempt < $this->maxRetries) {
                ($this->sleeper)($this->backoffDelay($attempt, $response));
                continue;
            }

            return $this->decode($response, $expectNoBody);
        }

        // Defensive — loop always returns or throws.
        throw new ApiError(
            message: 'voicetel: retry loop exhausted',
            previous: $lastErr instanceof \Throwable ? $lastErr : null,
        );
    }

    /**
     * @return mixed decoded inner data, or null for empty / no-body responses
     */
    private function decode(ResponseInterface $resp, bool $expectNoBody): mixed
    {
        $status = $resp->getStatusCode();
        $raw = (string) $resp->getBody();

        if ($status >= 200 && $status < 300) {
            if ($expectNoBody || $raw === '' || $status === 204) {
                return null;
            }
            try {
                $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new ApiError(
                    statusCode: $status,
                    body: $raw,
                    message: 'voicetel: decode response body: ' . $e->getMessage(),
                    previous: $e,
                );
            }
            return $this->unwrap($decoded);
        }

        // Error path.
        $body = $raw;
        $code = '';
        $message = '';
        if ($raw !== '') {
            try {
                $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $body = $decoded;
                    if (isset($decoded['code']) && is_string($decoded['code'])) {
                        $code = $decoded['code'];
                    } elseif (isset($decoded['error']) && is_string($decoded['error'])) {
                        $code = $decoded['error'];
                    }
                    if (isset($decoded['message']) && is_string($decoded['message'])) {
                        $message = $decoded['message'];
                    } elseif (isset($decoded['error']) && is_string($decoded['error'])) {
                        $message = $decoded['error'];
                    }
                }
            } catch (JsonException) {
                // Leave $body as raw string.
            }
        }
        if ($message === '') {
            $message = sprintf('HTTP %d', $status);
        }
        throw new ApiError(
            kind: ErrorKind::fromStatus($status),
            statusCode: $status,
            code: $code,
            body: $body,
            message: sprintf('voicetel: HTTP %d%s: %s', $status, $code !== '' ? ' ' . $code : '', $message),
        );
    }

    /**
     * Strip the {status, data} envelope when present; otherwise return the input.
     */
    private function unwrap(mixed $decoded): mixed
    {
        if (is_array($decoded) && array_key_exists('status', $decoded) && array_key_exists('data', $decoded)) {
            return $decoded['data'];
        }
        return $decoded;
    }

    /**
     * Compute the delay before the next retry. Honors a Retry-After header
     * (integer seconds) if present; otherwise uses exponential backoff capped at 8s.
     */
    private function backoffDelay(int $attempt, ?ResponseInterface $resp): float
    {
        if ($resp !== null) {
            $h = $resp->getHeaderLine('Retry-After');
            if ($h !== '' && ctype_digit($h)) {
                return (float) $h;
            }
        }
        $base = 0.5;
        $d = $base * (2 ** $attempt);
        return min($d, 8.0);
    }
}

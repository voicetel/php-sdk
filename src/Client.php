<?php

declare(strict_types=1);

namespace VoiceTel\Sdk;

use GuzzleHttp\ClientInterface;
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

/**
 * Client is the entry point for the VoiceTel API.
 *
 * Reach the API through its resource properties — for example
 * `$client->numbers->list()` or `$client->messaging->send([...])`.
 *
 * ```
 * $client = new \VoiceTel\Sdk\Client();
 * $client->login(1000000001, 'hunter2');
 * print_r($client->account->get());
 * ```
 */
final class Client
{
    public readonly AccountService $account;
    public readonly AclService $acl;
    public readonly AuthenticationService $authentication;
    public readonly E911Service $e911;
    public readonly GatewaysService $gateways;
    public readonly INumberingService $iNumbering;
    public readonly LookupsService $lookups;
    public readonly MessagingService $messaging;
    public readonly NumbersService $numbers;
    public readonly SupportService $support;

    private Transport $transport;

    /**
     * @param string|null          $apiKey     installed bearer token. Omit and call {@see login()} to populate.
     * @param string|null          $baseUrl    override the API endpoint (defaults to https://api.voicetel.com).
     * @param int                  $maxRetries number of 429/5xx retries (default 2; total attempts is N+1).
     * @param float                $timeout    per-request timeout in seconds (default 30).
     * @param string|null          $userAgent  override the User-Agent header.
     * @param ClientInterface|null $http       injectable Guzzle client (mainly for tests).
     */
    public function __construct(
        ?string $apiKey = null,
        ?string $baseUrl = null,
        int $maxRetries = 2,
        float $timeout = 30.0,
        ?string $userAgent = null,
        ?ClientInterface $http = null,
    ) {
        $this->transport = new Transport(
            baseUrl: $baseUrl ?? Version::DEFAULT_BASE_URL,
            apiKey: $apiKey ?? '',
            userAgent: $userAgent ?? Version::DEFAULT_USER_AGENT,
            maxRetries: $maxRetries,
            timeout: $timeout,
            http: $http,
        );

        $this->account = new AccountService($this->transport);
        $this->acl = new AclService($this->transport);
        $this->authentication = new AuthenticationService($this->transport);
        $this->e911 = new E911Service($this->transport);
        $this->gateways = new GatewaysService($this->transport);
        $this->iNumbering = new INumberingService($this->transport);
        $this->lookups = new LookupsService($this->transport);
        $this->messaging = new MessagingService($this->transport);
        $this->numbers = new NumbersService($this->transport);
        $this->support = new SupportService($this->transport);
    }

    /** Returns the currently configured API endpoint. */
    public function baseUrl(): string
    {
        return $this->transport->baseUrl();
    }

    /** Returns the installed bearer token (empty string before login). */
    public function apiKey(): string
    {
        return $this->transport->apiKey();
    }

    /**
     * Exchange username + password for a 32-hex bearer token, install it on
     * this client, and return it.
     *
     * The exchange counts against the 6 req/hour/IP rate limit shared by every
     * account/* endpoint (cdr, mrc, payments, registration, api-key).
     *
     * @throws ApiError on a non-2xx response or transport error
     */
    public function login(int $username, string $password): string
    {
        $data = $this->transport->request(
            method: 'POST',
            path: '/v2.2/account/api-key',
            body: ['username' => $username, 'password' => $password],
            requireAuth: false,
        );
        if (!is_array($data) || !isset($data['apikey']) || !is_string($data['apikey']) || $data['apikey'] === '') {
            throw new ApiError(
                kind: ErrorKind::Authentication,
                message: 'voicetel: api-key response did not contain data.apikey',
                body: $data,
            );
        }
        $this->transport->setBearer($data['apikey']);
        return $data['apikey'];
    }

    /**
     * Exposes the transport for tests. Not part of the public API.
     *
     * @internal
     */
    public function transport(): Transport
    {
        return $this->transport;
    }
}

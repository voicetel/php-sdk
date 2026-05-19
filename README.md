# 📞 VoiceTel PHP SDK

The official PHP client for the [VoiceTel REST API](https://voicetel.com/docs/api/v2.2/) — provision numbers, place orders, validate e911, send messages, and manage your account, with modern PHP 8.1+ ergonomics and battle-tested Guzzle transport.

![Version](https://img.shields.io/badge/version-2.2.10-blue)
![PHP](https://img.shields.io/badge/php-%E2%89%A58.1-blue)
![License](https://img.shields.io/badge/license-MIT-green)
![Coverage](https://img.shields.io/badge/coverage-94%25-brightgreen)

## 📚 Table of Contents

- [Features](#-features)
- [Installation](#-installation)
- [Quickstart](#-quickstart)
- [Authentication](#-authentication)
- [Resource Reference](#-resource-reference)
- [Error Handling](#-error-handling)
- [Rate Limits](#-rate-limits)
- [Development](#-development)
- [API Documentation](#-api-documentation)
- [Contributors](#-contributors)
- [Sponsors](#-sponsors)
- [License](#-license)

## ✨ Features

### 🛡️ Modern PHP, Strictly Typed
- **PHP 8.1+** end-to-end — readonly properties, native enums, named arguments, typed everywhere.
- **`ErrorKind` enum** for HTTP error classification: `BadRequest`, `Authentication`, `PermissionDenied`, `NotFound`, `Conflict`, `RateLimit`, `Server`, `Unknown`.
- **PSR-4 autoloading** under `VoiceTel\Sdk\`, plays nicely with Symfony, Laravel, and every other Composer-aware framework.
- **PHPStan level 8 clean** across the entire `src/` tree.

### 🔁 Production-Grade Transport
- Built on **Guzzle 7** — the de-facto HTTP client in the PHP ecosystem.
- **Automatic retry** with exponential backoff on 429 / 5xx — honors `Retry-After` headers, capped at 8s.
- **Configurable timeout** per client (defaults to 30s).
- **Bearer auth** managed for you; the password → key exchange is one method call (`$client->login(...)`).
- **Structured `ApiError`** with typed `kind` so you can `match ($e->kind) { ErrorKind::RateLimit => ... }` without parsing HTTP status codes.
- **Envelope-aware** — strips the `{"status":"success","data": ...}` wrapper before returning the inner payload.

### 📞 Complete API Coverage (73 operations)
- **Numbers** — list, get, add, remove, route, translate, CNAM, LIDB, fax, forward, SMS, messaging campaigns, port-out PIN, account moves.
- **Account** — profile, sub-accounts, CDRs, credits, payments, MRC, registration, password recovery.
- **e911** — record provisioning, address validation, lookup, removal.
- **Gateways** — list, create, update, delete, view bound numbers.
- **Messaging** — SMS & MMS sending, message history, 10DLC brand and campaign registration, per-number messaging state.
- **Lookups** — CNAM and LRN dips.
- **iNumbering** — inventory search, coverage queries, number orders, port-in submissions, port-out availability checks.
- **Support** — ticket create / read / update / delete, threaded messages, replies.
- **ACL** — IP allowlist management with structured 409 conflict bodies.
- **Authentication** — switch between Digest, IP-only, or hybrid modes; rotate passwords.

### 🧪 Battle-Tested
- **101 unit tests** at **94%+ line coverage** with PHPUnit 10.
- **Mocked Guzzle** via `GuzzleHttp\Handler\MockHandler` — every method and every error path exercised, no network in CI.
- **Read-only integration scaffolding** gated by `VOICETEL_USERNAME` / `VOICETEL_PASSWORD` env vars.

### 📦 Clean Distribution
- Zero codegen footprint — every byte hand-written.
- Single Composer package, single namespace, no surprise transitive deps beyond Guzzle.

## 🚀 Installation

```bash
composer require voicetel/sdk
```

Requires PHP 8.1 or later and the `json` extension (bundled with PHP).

## 🏁 Quickstart

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use VoiceTel\Sdk\Client;

$client = new Client();

// Exchange username + password for an API key (one-time per session)
$client->login(1000000001, 'hunter2');

// Typed responses — arrays with documented shapes.
$me = $client->account->get();
printf("Balance: \$%.2f  |  Caller ID: %s\n", $me['cash'], $me['callerId']);

// List your numbers
$list = $client->numbers->list();
foreach ($list['numbers'] as $n) {
    printf(
        "%s  route=%d  cnam=%s  sms=%s\n",
        $n['number'],
        $n['route'],
        $n['cnam'] ? 'yes' : 'no',
        $n['smsEnabled'] ? 'yes' : 'no',
    );
}
```

Or, if you already have an API key, skip `login()` and pass it straight to the constructor:

```php
$client = new Client(apiKey: getenv('VOICETEL_API_KEY'));

$coverage = $client->iNumbering->coverage(['state' => 'NJ']);
foreach ($coverage['coverage'] as $bucket) {
    printf("%s-%s: %d TNs available\n", $bucket['npa'], $bucket['nxx'], $bucket['count']);
}
```

## 🔑 Authentication

Every endpoint requires `Authorization: Bearer <apikey>` **except** `POST /v2.2/account/api-key`, which exchanges username + password for a fresh key. `$client->login()` handles the exchange and installs the returned key on the client.

Re-fetch the API key after any password change — the old one is invalidated.

> Don't have credentials yet? Get them at **[voicetel.com/docs/api/v2.2/credentials](https://voicetel.com/docs/api/v2.2/credentials/)**.

```php
$client = new Client();
$key = $client->login(1000000001, 'hunter2');
// $key is the new 32-hex bearer; the client already has it installed.
```

## 🗺️ Resource Reference

| Resource | Property on Client | Example |
|---|---|---|
| Account | `$client->account` | `$client->account->cdr(1700000000, 1700100000)` |
| ACL | `$client->acl` | `$client->acl->add(['acl' => [['cidr' => '1.2.3.0/24']]])` |
| Authentication | `$client->authentication` | `$client->authentication->update(['authType' => 1])` |
| e911 | `$client->e911` | `$client->e911->validate(['address1' => '1 Way', ...])` |
| Gateways | `$client->gateways` | `$client->gateways->list()` |
| iNumbering | `$client->iNumbering` | `$client->iNumbering->searchInventory(['npa' => 201])` |
| Lookups | `$client->lookups` | `$client->lookups->lrn('2015551234', '2125550000')` |
| Messaging | `$client->messaging` | `$client->messaging->send(['fromNumber' => ..., 'toNumber' => ..., 'text' => ...])` |
| Numbers | `$client->numbers` | `$client->numbers->assignCampaign('2015551234', ['campaignId' => 'CXXXXXX'])` |
| Support | `$client->support` | `$client->support->create(['subject' => '...', 'message' => '...'])` |

### Notes on payload shape

- `messaging->send()` uses the wire field names `fromNumber` and `toNumber` — pass them exactly as shown.
- `support->create()` / `support->get()` return a conversation whose `number` field is a **ticket sequence integer** (e.g. 1015), **not** a phone number — keep that distinction in mind everywhere else in this API where `number` is a 10-digit TN.
- LIDB endpoints (`numbers->setLidb()`) use the spelling `Lidb` consistently — the legacy `Libd` typo from earlier spec drafts has been corrected.
- `iNumbering->portAvailability()` includes the v2.2.10 fields `localRoutingNumber` and `rateCenterTier` alongside the original `number`, `portable`, `losingCarrier`, and `reason`.
- `DELETE` endpoints generally return `204 No Content` — the corresponding methods return `void`. Three endpoints intentionally return a body and an `array`: `acl->remove()`, `numbers->unassignCampaign()`, and `numbers->bulkUnassignCampaign()`.

## 🚨 Error Handling

All HTTP errors throw `VoiceTel\Sdk\ApiError`. Inspect `kind`, `statusCode`, `code()`, or `body`:

| Kind | HTTP status |
|---|---|
| `ErrorKind::BadRequest` | 400 |
| `ErrorKind::Authentication` | 401 |
| `ErrorKind::PermissionDenied` | 403 |
| `ErrorKind::NotFound` | 404 |
| `ErrorKind::Conflict` | 409 |
| `ErrorKind::RateLimit` | 429 |
| `ErrorKind::Server` | 5xx |
| `ErrorKind::Unknown` | other / transport |

```php
use VoiceTel\Sdk\ApiError;
use VoiceTel\Sdk\ErrorKind;

try {
    $n = $client->numbers->get('9999999999');
} catch (ApiError $e) {
    match ($e->kind) {
        ErrorKind::NotFound  => print "That number isn't on your account.\n",
        ErrorKind::RateLimit => print "Slow down — backoff and retry.\n",
        default              => throw $e,
    };
}
```

Or use the helper predicates:

```php
catch (ApiError $e) {
    if ($e->isNotFound())   { /* ... */ }
    if ($e->isRateLimit())  { /* ... */ }
    if ($e->isConflict())   {
        // $e->body is the structured AclConflictData / AuthPutConflictData payload.
    }
}
```

## ⏱️ Rate Limits

These endpoints are limited to **6 requests per hour per IP**:

- `account/info` (`$client->account->get()`)
- `account/cdr`
- `account/recurring-charges`
- `account/payments`
- `account/registration`
- `account/api-key` (`$client->login()`)

The SDK automatically retries 429 responses with `Retry-After` honored, up to `maxRetries` (default 2). To bump it:

```php
$client = new Client(
    apiKey: getenv('VOICETEL_API_KEY'),
    maxRetries: 4,
    timeout: 60.0,
);
```

## 🛠️ Development

```bash
git clone https://github.com/voicetel/php-sdk
cd php-sdk

# Install dependencies
composer install

# Run unit tests
vendor/bin/phpunit --testsuite unit

# With coverage (needs Xdebug or PCOV)
XDEBUG_MODE=coverage vendor/bin/phpunit --testsuite unit --coverage-text

# Static analysis
vendor/bin/phpstan analyse

# Run read-only integration tests against a real account
cp .env.example .env  # populate VOICETEL_USERNAME / VOICETEL_PASSWORD
set -a; source .env; set +a
vendor/bin/phpunit --testsuite integration
```

## 📖 API Documentation

- **Reference docs:** [voicetel.com/docs/api/v2.2/](https://voicetel.com/docs/api/v2.2/)
- **Interactive playground:** [voicetel.com/docs/api/v2.2/playground/](https://voicetel.com/docs/api/v2.2/playground/) — try the API in your browser without writing any code
- **API credentials:** [voicetel.com/docs/api/v2.2/credentials/](https://voicetel.com/docs/api/v2.2/credentials/)

## 🙌 Contributors

- [Michael Mavroudis](https://github.com/mavroudis) — Lead Developer

Contributions welcome. Open an issue describing the change, or send a pull request against `main`.

## 💖 Sponsors

| Sponsor | Contribution |
|---------|--------------|
| [VoiceTel Communications](https://voicetel.com) | Primary development and production hosting |

## 📄 License

This project is licensed under the MIT License — see the [LICENSE](LICENSE) file for details.

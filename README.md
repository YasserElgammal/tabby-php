# Tabby PHP SDK

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Tests](https://github.com/YasserElgammal/tabby-php/actions/workflows/tests.yml/badge.svg)](https://github.com/YasserElgammal/tabby-php/actions/workflows/tests.yml)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

A production-ready, framework-agnostic PHP SDK for the Tabby API.

Build Tabby checkout, payment, capture, refund, and webhook integrations without coupling your application to a specific framework.

## Table of Contents

- [Tabby PHP SDK](#tabby-php-sdk)
  - [Table of Contents](#table-of-contents)
  - [Features](#features)
  - [Requirements](#requirements)
  - [Installation](#installation)
  - [Quick Start](#quick-start)
  - [Configuration](#configuration)
    - [Environments](#environments)
    - [Custom HTTP client](#custom-http-client)
  - [Security](#security)
  - [API Overview](#api-overview)
    - [CheckoutResource](#checkoutresource)
    - [PaymentResource](#paymentresource)
    - [WebhookResource](#webhookresource)
    - [WebhookVerifier](#webhookverifier)
  - [Payment Lifecycle](#payment-lifecycle)
  - [Services and Builders](#services-and-builders)
    - [API Services](#api-services)
    - [Fluent Builders](#fluent-builders)
  - [Checkout](#checkout)
    - [Checkout with multiple items](#checkout-with-multiple-items)
  - [Payments](#payments)
    - [Capture and refund](#capture-and-refund)
  - [Idempotency](#idempotency)
  - [Webhooks](#webhooks)
    - [Register a webhook](#register-a-webhook)
    - [List and retrieve webhooks](#list-and-retrieve-webhooks)
    - [Update and delete a webhook](#update-and-delete-a-webhook)
    - [Verify the webhook secret](#verify-the-webhook-secret)
  - [Error Handling](#error-handling)
    - [Validation example](#validation-example)
  - [Testing](#testing)
  - [Contributing](#contributing)
  - [Changelog](#changelog)
  - [Links](#links)
  - [License](#license)

## Features

- Framework agnostic
- Strongly typed DTOs
- Fluent checkout and item builders
- Checkout API
- Payments API
- Payment capture and refund
- Webhook management and secret verification
- Local input validation
- Structured API, validation, and transport exceptions
- Injectable HTTP client

## Requirements

| Requirement | Version |
| --- | --- |
| PHP | 8.2+ |
| Guzzle | ^7.0 |
| ext-json | Required |

## Installation

Install the package via Composer:

```bash
composer require yasser-elgammal/tabby-php
```

## Quick Start

Configure the client, create a checkout, and redirect the customer to the URL returned by Tabby:

```php
use YasserElgammal\TabbyPhp\Client\Configuration;
use YasserElgammal\TabbyPhp\Client\TabbyClient;
use YasserElgammal\TabbyPhp\DTOs\TabbyAddressData;
use YasserElgammal\TabbyPhp\DTOs\TabbyBuyerData;

$tabby = new TabbyClient(new Configuration(
    secretKey: $_ENV['TABBY_SECRET_KEY'],
    baseUrl: $_ENV['TABBY_BASE_URL'],
));

$checkout = $tabby->checkout()
    ->amount('250.00')
    ->currency('SAR')
    ->referenceId('order-1')
    ->buyer(new TabbyBuyerData('+966500000000', 'buyer@example.com', 'Buyer Name'))
    ->shippingAddress(new TabbyAddressData('Riyadh', 'Street 1'))
    ->item($tabby->item()
        ->title('Product Name')
        ->quantity(1)
        ->unitPrice('250.00')
        ->referenceId('product-1')
        ->build())
    ->merchantUrls(
        'https://shop.test/success',
        'https://shop.test/cancel',
        'https://shop.test/failure',
    )
    ->build();

$response = $tabby->checkouts()->create($checkout);

if ($response->isAvailable()) {
    header('Location: ' . $response->url());
    exit;
}
```

## Configuration

Create a `Configuration` and pass it to `TabbyClient`. The merchant code and webhook secret are optional and only need to be supplied when your integration uses them.

```php
use YasserElgammal\TabbyPhp\Client\Configuration;
use YasserElgammal\TabbyPhp\Client\TabbyClient;

$config = new Configuration(
    secretKey: $_ENV['TABBY_SECRET_KEY'],
    baseUrl: $_ENV['TABBY_BASE_URL'],
    merchantCode: $_ENV['TABBY_MERCHANT_CODE'] ?? null,
    webhookSecret: $_ENV['TABBY_WEBHOOK_SECRET'] ?? null,
);

$tabby = new TabbyClient($config);
```

### Environments

Configure the API base URL according to the environment and region provided by Tabby for your merchant account. Keep it in server-side configuration, for example as `TABBY_BASE_URL`, rather than hard-coding a regional endpoint in your application.

### Custom HTTP client

The client accepts any implementation of `HttpClientInterface`, which makes the transport replaceable and integrations straightforward to test:

```php
use YasserElgammal\TabbyPhp\Client\TabbyClient;
use YasserElgammal\TabbyPhp\Client\Http\HttpClientInterface;

/** @var HttpClientInterface $httpClient */
$tabby = new TabbyClient($config, $httpClient);
```

## Security

Never expose your Tabby secret key or webhook secret in frontend or mobile applications.

All SDK operations must be executed from your backend. Store credentials in environment variables or a secrets manager, exclude them from source control, and verify the webhook secret before processing webhook data.

## API Overview

### CheckoutResource

- `create(TabbyCheckoutData $checkout)`
- `createFromArray(array $payload)`

### PaymentResource

- `get(string $paymentId)`
- `getResponse(string $paymentId)`
- `all(?PaymentListFilters $filters = null)`
- `update(string $paymentId, string|PaymentUpdateData $referenceId)`
- `capture(string $paymentId, string $referenceId, string|int|float|null $amount = null)`
- `refund(string $paymentId, string $referenceId, string|int|float $amount, ?string $reason = null)`
- `close(string $paymentId)`

### WebhookResource

- `create(string $url, bool $test = true, array $headers = [])`
- `all()`
- `get(string $id)`
- `getResponse(string $id)`
- `update(string $id, string $url, bool $test = true)`
- `delete(string $id)`

### WebhookVerifier

- `verify(?string $providedSecret)`

## Payment Lifecycle

```text
Create Checkout
    ↓
Redirect Customer
    ↓
Customer completes checkout
    ↓
Retrieve Payment
    ↓
Capture Payment
    ↓
Refund / Close when needed
```

Use the payment ID returned in `CheckoutResponse::$paymentId` to retrieve and manage the payment after checkout.

## Services and Builders

API services send requests to Tabby and return API responses:

### API Services

```php
$tabby->checkouts();
$tabby->payments();
$tabby->webhooks();
```

Fluent builders construct and locally validate the DTOs passed to those services. They do not send API requests:

### Fluent Builders

```php
$tabby->checkout();
$tabby->item();
```

Webhook verification is available separately through `$tabby->webhookVerifier()`.

## Checkout

### Checkout with multiple items

Build multiple items and add them to a checkout using `items()`:

```php
use YasserElgammal\TabbyPhp\DTOs\TabbyAddressData;
use YasserElgammal\TabbyPhp\DTOs\TabbyBuyerData;

$firstItem = $tabby->item()
    ->title('Product 1')
    ->quantity(1)
    ->unitPrice('150.00')
    ->referenceId('prod-1')
    ->build();

$secondItem = $tabby->item()
    ->title('Product 2')
    ->quantity(2)
    ->unitPrice('50.00')
    ->referenceId('prod-2')
    ->build();

$checkout = $tabby->checkout()
    ->amount('250.00')
    ->currency('SAR')
    ->referenceId('order-1')
    ->buyer(new TabbyBuyerData('+966500000000', 'buyer@example.com', 'Buyer Name'))
    ->shippingAddress(new TabbyAddressData('Riyadh', 'Street 1'))
    ->items([$firstItem, $secondItem])
    ->merchantUrls(
        'https://shop.test/success',
        'https://shop.test/cancel',
        'https://shop.test/failure',
    )
    ->build();

$response = $tabby->checkouts()->create($checkout);
```

## Payments

```php
use YasserElgammal\TabbyPhp\DTOs\PaymentListFilters;
use YasserElgammal\TabbyPhp\DTOs\PaymentUpdateData;

// Retrieve a payment as an array.
$payment = $tabby->payments()->get('payment-id');

// Retrieve a strongly typed payment response.
$payment = $tabby->payments()->getResponse('payment-id');
echo $payment->status;
echo $payment->amount;
echo $payment->raw['currency'] ?? '';

// Update the merchant order reference associated with the payment.
$tabby->payments()->update(
    'payment-id',
    'new-order-id',
);

// The same update can be expressed explicitly with a DTO.
$tabby->payments()->update(
    'payment-id',
    new PaymentUpdateData(referenceId: 'new-order-id'),
);

// List payments with filters.
$payments = $tabby->payments()->all(new PaymentListFilters(
    createdAtGte: '2025-01-01',
    limit: 20,
));

// Close a payment.
$tabby->payments()->close('payment-id');
```

### Capture and refund

```php
// Capture the complete remaining amount.
$tabby->payments()->capture('payment-id', 'capture-ref-123');

// Capture a specific amount.
$tabby->payments()->capture('payment-id', 'capture-ref-124', '100.00');

// Issue a refund.
$tabby->payments()->refund(
    'payment-id',
    'refund-ref-456',
    '50.00',
    'Customer requested refund',
);
```

## Idempotency

Capture, refund, and other mutation operations should use unique reference IDs.

Do not automatically retry mutation requests unless the same idempotent reference is reused. Reusing the same reference preserves the identity of the original operation and helps prevent accidental duplicate mutations.

## Webhooks

Configure `webhookSecret` when constructing the client if you want to verify the secret received with webhook requests.

### Register a webhook

```php
$webhook = $tabby->webhooks()->create('https://shop.example/webhooks/tabby');

// Optionally register a custom header to be sent with webhook requests.
$webhook = $tabby->webhooks()->create(
    'https://shop.example/webhooks/tabby',
    headers: ['title' => 'X-Webhook-Token', 'value' => 'expected-value'],
);
```

### List and retrieve webhooks

```php
$webhooks = $tabby->webhooks()->all();
$webhook = $tabby->webhooks()->get('webhook-id');
$webhookResponse = $tabby->webhooks()->getResponse('webhook-id');
```

### Update and delete a webhook

```php
$tabby->webhooks()->update('webhook-id', 'https://shop.example/webhooks/tabby');
$tabby->webhooks()->delete('webhook-id');
```

### Verify the webhook secret

Pass the secret value received with the webhook request to the verifier before processing the request body. The SDK performs a timing-safe comparison with the configured `webhookSecret`.

```php
$providedSecret = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? null;

if (! $tabby->webhookVerifier()->verify($providedSecret)) {
    http_response_code(401);
    exit('Invalid webhook secret');
}

// The secret is valid; process the webhook body now.
$payload = json_decode(file_get_contents('php://input'), true, flags: JSON_THROW_ON_ERROR);
```

The verifier validates a shared secret value; it does not calculate an HMAC signature from the request payload. Read the header name and delivery requirements from the webhook configuration supplied for your Tabby integration.

## Error Handling

All SDK errors inherit from `TabbyException`.

| Exception | Meaning |
| --- | --- |
| `ValidationException` | Invalid input detected locally before a request |
| `ApiException` | Tabby API returned an error response |
| `TransportException` | Network or connection failure |
| `TabbyException` | Base type for any other SDK error |

```php
use YasserElgammal\TabbyPhp\Exceptions\ApiException;
use YasserElgammal\TabbyPhp\Exceptions\TabbyException;
use YasserElgammal\TabbyPhp\Exceptions\TransportException;
use YasserElgammal\TabbyPhp\Exceptions\ValidationException;

try {
    $tabby->payments()->get('invalid-id');
} catch (ValidationException $e) {
    // Local validation failure.
} catch (ApiException $e) {
    // Tabby API error.
    echo $e->getMessage();
    echo $e->getCode();
    print_r($e->response);
} catch (TransportException $e) {
    // Network error.
} catch (TabbyException $e) {
    // Any other SDK error.
}
```

### Validation example

Builders validate input locally when `build()` is called, before any request is sent:

```php
use YasserElgammal\TabbyPhp\Exceptions\ValidationException;

try {
    $checkout = $tabby->checkout()
        ->amount('-10')
        ->build();
} catch (ValidationException $e) {
    echo $e->getMessage();
}
```

## Testing

The SDK includes a PHPUnit test suite. Inject a mock of `HttpClientInterface` to test your integration without calling the real API.

```php
use YasserElgammal\TabbyPhp\Client\Configuration;
use YasserElgammal\TabbyPhp\Client\Http\HttpClientInterface;
use YasserElgammal\TabbyPhp\Client\Http\Response;
use YasserElgammal\TabbyPhp\Client\TabbyClient;

$httpClient = $this->createMock(HttpClientInterface::class);
$httpClient->method('request')->willReturn(
    new Response(200, [], '{"id":"payment-id"}'),
);

$tabby = new TabbyClient(
    new Configuration(secretKey: 'test-secret'),
    $httpClient,
);

$payment = $tabby->payments()->get('payment-id');

$this->assertSame('payment-id', $payment['id']);
```

Run the suite:

```bash
./vendor/bin/phpunit
```

## Contributing

Contributions are welcome.

Please open an issue before submitting major changes.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for release history.

## Links

- [Tabby API Documentation](https://docs.tabby.ai/)
- [Changelog](CHANGELOG.md)

## License

This SDK is open-source software licensed under the [MIT License](LICENSE).

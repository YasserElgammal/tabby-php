# Changelog

All notable changes to `tabby-php` will be documented in this file.

## [2.0.0] - 2026-11-25

### Changed

- Validate client configuration, checkout metadata, merchant URLs, and item invariants earlier.
- Retry transient GET responses and `TransportException` with exponential backoff and jitter. Wait up to two seconds per retry and return the API error immediately when `Retry-After` exceeds that limit.
- Preserve precision when subtracting decimal amounts larger than native PHP integers.
- Normalize malformed API JSON responses into `ApiException` instances.
- Add PHP 8.5 CI coverage, PHPStan level 8, PHP CS Fixer, and expanded edge-case tests.

### Removed

- Remove the deprecated webhook `$test` argument from registration and updates. The secret key selects the environment. See the [1.x to 2.x call examples](README.md#register-a-webhook).

## [1.0.0] - 2026-10-03

### Added

- Framework-agnostic client for the Tabby API.
- Fluent builders and strongly typed DTOs for checkout data.
- Checkout creation with typed checkout responses.
- Payment retrieval, listing, updating, capture, refund, and close operations.
- Webhook registration, listing, retrieval, updating, deletion, and shared-secret verification.
- Support for injectable HTTP clients.
- Local input validation with structured API, transport, and validation exceptions.
- Comprehensive test coverage for API requests, checkout, payments, webhooks, DTOs, validation, retries, and transport errors.
- Documentation for usage, security, payment lifecycle, idempotency, testing, and Laravel integration.

<?php

namespace YasserElgammal\TabbyPhp\Resources;

use YasserElgammal\TabbyPhp\Client\ApiClient;
use YasserElgammal\TabbyPhp\DTOs\PaymentListFilters;
use YasserElgammal\TabbyPhp\DTOs\PaymentResponse;
use YasserElgammal\TabbyPhp\DTOs\PaymentUpdateData;
use YasserElgammal\TabbyPhp\Exceptions\ApiException;
use YasserElgammal\TabbyPhp\Exceptions\ValidationException;
use YasserElgammal\TabbyPhp\Support\Decimal;
use YasserElgammal\TabbyPhp\Support\ValidatesInput;

final class PaymentResource
{
    public function __construct(private readonly ApiClient $client) {}

    /** @return array<string, mixed> */
    public function get(string $paymentId): array
    {
        $data = $this->client->get('/api/v2/payments/' . ValidatesInput::id($paymentId, 'payment ID'));
        if (! isset($data['id'])) {
            throw new ApiException('Tabby returned an invalid payment response.', 200, $data);
        }
        return $data;
    }

    public function getResponse(string $paymentId): PaymentResponse
    {
        return PaymentResponse::fromArray($this->get($paymentId));
    }

    public function update(string $paymentId, string|PaymentUpdateData $referenceId): array
    {
        $data = $referenceId instanceof PaymentUpdateData ? $referenceId : new PaymentUpdateData($referenceId);
        return $this->client->put('/api/v2/payments/' . ValidatesInput::id($paymentId, 'payment ID'), $data->toArray());
    }

    public function all(?PaymentListFilters $filters = null): array
    {
        return $this->client->get('/api/v2/payments', ($filters ?? new PaymentListFilters())->toArray());
    }

    /** @return array<string, mixed> */
    public function capture(string $paymentId, string $referenceId, string|int|float|null $amount = null): array
    {
        $this->ensureReferenceId($referenceId);

        if ($amount === null) {
            $payment = $this->get($paymentId);
            if (! isset($payment['amount'])) {
                throw new ValidationException('The payment response does not contain an amount to capture.');
            }
            $amount = $this->remainingAmount((string) $payment['amount'], $payment['captures'] ?? []);
        }

        $amount = Decimal::positive($amount);

        return $this->client->post('/api/v2/payments/' . ValidatesInput::id($paymentId, 'payment ID') . '/captures', [
            'amount' => $amount,
            'reference_id' => $referenceId,
        ]);
    }

    /** @return array<string, mixed> */
    public function refund(string $paymentId, string $referenceId, string|int|float $amount, ?string $reason = null): array
    {
        $this->ensureReferenceId($referenceId);
        $amount = Decimal::positive($amount);

        return $this->client->post('/api/v2/payments/' . ValidatesInput::id($paymentId, 'payment ID') . '/refunds', array_filter([
            'amount' => $amount,
            'reference_id' => $referenceId,
            'reason' => $reason,
        ], fn ($value) => $value !== null));
    }

    /** @return array<string, mixed> */
    public function close(string $paymentId): array
    {
        return $this->client->post('/api/v2/payments/' . ValidatesInput::id($paymentId, 'payment ID') . '/close');
    }

    private function ensureReferenceId(string $referenceId): void
    {
        if (trim($referenceId) === '') {
            throw new ValidationException('A non-empty reference ID is required for payment operations.');
        }
    }

    private function remainingAmount(string $amount, mixed $captures): string
    {
        $capturedAmounts = [];
        if (is_array($captures)) {
            foreach ($captures as $capture) {
                if (is_array($capture) && isset($capture['amount'])) {
                    $capturedAmounts[] = (string) $capture['amount'];
                }
            }
        }
        return Decimal::subtract($amount, $capturedAmounts, 'remaining capture amount');
    }
}

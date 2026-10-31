<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use Illuminate\Support\Facades\Http;

/**
 * Read-only Bakong payment-status lookup, NOT a checkout or fulfillment path.
 * The MD5 must have originated from a trusted, order-specific dynamic KHQR.
 * Never accept customer-submitted hash or screenshot as payment evidence.
 */
final class BakongStatusGateway
{
    public function configured(): bool
    {
        $settings = config('payments.bakong', []);
        return ($settings['enabled'] ?? false) === true
            && ($settings['base_url'] ?? null) === 'https://sit-api-bakong.nbc.gov.kh'
            && is_string($settings['token'] ?? null)
            && strlen($settings['token']) >= 16
            && is_string($settings['receiver_account'] ?? null)
            && preg_match('/^[A-Za-z0-9_.-]+@[A-Za-z0-9_.-]+$/D', $settings['receiver_account']) === 1;
    }

    /**
     * Returns a MATCHED status signal, not a payment entitlement.
     * Dynamic QR issuance and order binding require a licensed provider and
     * must be built/tested before any KHQR payment activation.
     */
    public function verifyTrustedMd5(string $md5, string $currency, string $expectedAmount): array
    {
        abort_unless($this->configured(), 503, 'Bakong verification is not configured.');
        abort_unless(preg_match('/^[a-fA-F0-9]{32}$/D', $md5) === 1, 422);
        abort_unless(in_array($currency, ['USD', 'KHR'], true), 422);
        abort_unless(preg_match('/^(0|[1-9]\d{0,8})(\.\d{1,2})?$/D', $expectedAmount), 422);

        $response = Http::withToken((string) config('payments.bakong.token'))
            ->acceptJson()->timeout(12)
            ->post('https://sit-api-bakong.nbc.gov.kh/v1/check_transaction_by_md5', [
                'md5' => strtolower($md5),
            ]);
        abort_unless($response->successful() && is_array($response->json()), 502);

        $result = $response->json();
        $data = $result['data'] ?? null;
        if (($result['responseCode'] ?? null) !== 0 || ! is_array($data)) {
            return ['matched' => false];
        }

        $amount = $data['amount'] ?? null;
        $matches = is_int($amount) || is_float($amount) || is_string($amount);
        $amount = $matches ? (string) $amount : '';
        $matches = $matches && preg_match('/^(0|[1-9]\d{0,8})(\.\d{1,2})?$/D', $amount) === 1;

        // Use decimal-string comparison only. No float equality or FX guessing.
        $normal = static function (string $value): string {
            [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
            return ltrim($whole, '0').'|'.str_pad($fraction, 2, '0');
        };

        return [
            'matched' => $matches
                && $normal($amount) === $normal($expectedAmount)
                && ($data['currency'] ?? null) === $currency
                && ($data['toAccountId'] ?? null) === config('payments.bakong.receiver_account')
                && ($data['trackingStatus'] ?? null) === 'RECEIVE_AT_RECEIVER_BANK'
                && is_string($data['hash'] ?? null)
                && preg_match('/^[a-fA-F0-9]{64}$/D', $data['hash']) === 1,
            'transaction_hash' => is_string($data['hash'] ?? null)
                ? $data['hash'] : null,
        ];
    }
}

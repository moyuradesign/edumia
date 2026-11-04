<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Models\PaymentProviderCheck;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Throwable;

final class PaymentProviderReadinessService
{
    public function summary(): array
    {
        $mode = (string) config('payments.stripe.mode', 'test');
        $key = (string) config('payments.stripe.secret_key', '');
        $secret = (string) config('payments.stripe.webhook_secret', '');
        $https = str_starts_with((string) config('app.url'), 'https://');

        return [
            'mode' => $mode,
            'enabled' => (bool) config('payments.stripe.enabled', false),
            'live_approved' => (bool) config('payments.stripe.live_approved', false),
            'key_valid_for_mode' => in_array($mode, ['test','live'], true)
                && str_starts_with($key, 'sk_'.$mode.'_') && strlen($key) >= 15,
            'webhook_configured' => str_starts_with($secret, 'whsec_') && strlen($secret) >= 15,
            'https' => $https,
            'bakong_enabled' => (bool) config('payments.bakong.enabled', false),
            'test_connectivity_available' => $mode === 'test'
                && str_starts_with($key, 'sk_test_') && strlen($key) >= 15,
        ];
    }

    public function checkStripeTestAccount(User $actor): PaymentProviderCheck
    {
        abort_unless($actor->isAdmin(), 403);
        $summary = $this->summary();
        abort_unless($summary['test_connectivity_available'], 422,
            'Configure a valid Stripe test API key in the private environment first.');

        $result = 'unreachable';
        $country = null;
        $chargesEnabled = null;
        try {
            $response = Http::withToken((string) config('payments.stripe.secret_key'))
                ->acceptJson()->timeout(10)->get('https://api.stripe.com/v1/account');

            if ($response->successful()
                && is_string($response->json('id'))
                && str_starts_with($response->json('id'), 'acct_')) {
                $result = 'connected';
                $countryValue = $response->json('country');
                $country = is_string($countryValue) && preg_match('/^[A-Z]{2}$/D', $countryValue)
                    ? $countryValue : null;
                $value = $response->json('charges_enabled');
                $chargesEnabled = is_bool($value) ? $value : null;
            } else {
                $result = 'rejected';
            }
        } catch (Throwable) {
            // No tokens, request bodies, provider errors or account identifiers
            // enter the audit table or user-visible output.
            $result = 'unreachable';
        }

        return PaymentProviderCheck::query()->create([
            'actor_id' => $actor->getKey(),
            'provider' => 'stripe',
            'mode' => 'test',
            'result' => $result,
            'country' => $country,
            'charges_enabled' => $chargesEnabled,
            'checked_at' => now(),
        ]);
    }
}

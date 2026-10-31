<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class StripeCheckoutGateway
{
    public function configured(): bool
    {
        $settings = config('payments.stripe', []);
        $mode = $settings['mode'] ?? 'test';
        $secret = (string) ($settings['secret_key'] ?? '');
        $webhook = (string) ($settings['webhook_secret'] ?? '');

        return ($settings['enabled'] ?? false) === true
            && in_array($mode, ['test', 'live'], true)
            && str_starts_with($secret, 'sk_'.$mode.'_')
            && str_starts_with($webhook, 'whsec_')
            && strlen($secret) >= 15 && strlen($webhook) >= 15;
    }

    public function assertConfigured(): void
    {
        abort_unless($this->configured(), 503, 'Secure payments are not configured.');
        // Do not send a customer back to an HTTP site outside isolated tests.
        abort_unless(app()->environment('testing')
            || str_starts_with((string) config('app.url'), 'https://'), 503, 'HTTPS is required.');
    }

    public function createSession(Order $order): array
    {
        $this->assertConfigured();
        abort_unless($order->currency_code === 'USD', 422, 'This gateway currently supports USD only.');
        abort_unless($order->items()->count() === 1, 422);
        $item = $order->items()->firstOrFail();
        $amount = $this->cents($order->total);
        abort_unless($amount >= 50 && $amount <= 99999999 && $item->line_total === $order->total, 422);

        $response = Http::asForm()->withToken((string) config('payments.stripe.secret_key'))
            ->withHeaders(['Idempotency-Key' => 'edumia-order-'.$order->reference])
            ->timeout(15)->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'payment_method_types' => ['card'],
                'client_reference_id' => $order->reference,
                'metadata' => ['order_reference' => $order->reference],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'unit_amount' => $amount,
                        'product_data' => ['name' => $item->title],
                    ],
                    'quantity' => 1,
                ]],
                'expires_at' => now()->addMinutes(55)->timestamp,
                'success_url' => route('checkout.orders.show', $order).'?payment=return',
                'cancel_url' => route('checkout.orders.show', $order).'?payment=cancel',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Stripe checkout could not be created (HTTP '.$response->status().').');
        }

        $session = $response->json();
        abort_unless(is_array($session)
            && is_string($session['id'] ?? null)
            && str_starts_with($session['id'], 'cs_')
            && is_string($session['url'] ?? null)
            && $this->checkoutUrlSafe($session['url'])
            && ($session['client_reference_id'] ?? null) === $order->reference
            && ($session['metadata']['order_reference'] ?? null) === $order->reference
            && ($session['currency'] ?? null) === 'usd'
            && ($session['amount_total'] ?? null) === $amount
            && ($session['mode'] ?? null) === 'payment'
            && ($session['livemode'] ?? null) === (config('payments.stripe.mode') === 'live'),
            502, 'Gateway checkout validation failed.');

        return ['id' => $session['id'], 'url' => $session['url']];
    }

    public function retrieveSession(string $sessionId): array
    {
        $this->assertConfigured();
        abort_unless(preg_match('/^cs_[A-Za-z0-9_]{8,180}$/D', $sessionId), 400);

        $response = Http::withToken((string) config('payments.stripe.secret_key'))
            ->timeout(15)->get('https://api.stripe.com/v1/checkout/sessions/'.$sessionId);

        abort_unless($response->successful() && is_array($response->json()),
            502, 'Payment verification could not be completed.');

        return $response->json();
    }

    public function checkoutUrlSafe(string $url): bool
    {
        $parts = parse_url($url);

        return is_array($parts)
            && ($parts['scheme'] ?? null) === 'https'
            && ($parts['host'] ?? null) === 'checkout.stripe.com'
            && ! isset($parts['user']) && ! isset($parts['pass']);
    }

    public function cents(string $amount): int
    {
        abort_unless(preg_match('/^(0|[1-9]\d{0,6})\.(\d{2})$/D', $amount, $matches), 422);

        return ((int) $matches[1] * 100) + (int) $matches[2];
    }
}

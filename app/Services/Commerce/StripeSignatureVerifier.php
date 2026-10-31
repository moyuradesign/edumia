<?php

declare(strict_types=1);

namespace App\Services\Commerce;

final class StripeSignatureVerifier
{
    public function valid(string $payload, ?string $header, string $secret, int $now): bool
    {
        if ($header === null || $header === '' || $secret === '' || strlen($payload) > 262144) {
            return false;
        }

        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            if ($key === 't' && ctype_digit($value)) {
                $timestamp = (int) $value;
            }
            if ($key === 'v1' && preg_match('/^[a-f0-9]{64}$/D', $value)) {
                $signatures[] = $value;
            }
        }

        if ($timestamp === null || abs($now - $timestamp) > 300 || $signatures === []) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }
}

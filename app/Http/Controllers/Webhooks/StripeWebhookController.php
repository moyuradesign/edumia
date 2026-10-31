<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Services\Commerce\PaymentFulfillmentService;
use App\Services\Commerce\StripeCheckoutGateway;
use App\Services\Commerce\StripeSignatureVerifier;
use App\Services\Commerce\StripeWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class StripeWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        StripeCheckoutGateway $gateway,
        StripeSignatureVerifier $verifier,
        StripeWebhookService $webhooks,
        PaymentFulfillmentService $fulfillment
    ): JsonResponse {
        $webhooks->handle($request->getContent(), $request->header('Stripe-Signature'),
            $gateway, $verifier, $fulfillment);

        return response()->json(['received' => true]);
    }
}

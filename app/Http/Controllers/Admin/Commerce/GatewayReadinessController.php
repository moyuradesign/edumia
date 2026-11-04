<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Commerce;

use App\Models\PaymentProviderCheck;
use App\Services\Commerce\PaymentProviderReadinessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class GatewayReadinessController extends Controller
{
    public function index(PaymentProviderReadinessService $readiness): View
    {
        return view('admin.commerce.gateway-readiness.index', [
            'readiness' => $readiness->summary(),
            'checks' => PaymentProviderCheck::query()->where('provider','stripe')
                ->orderByDesc('id')->limit(15)->get(),
        ]);
    }

    public function verify(Request $request, PaymentProviderReadinessService $readiness): RedirectResponse
    {
        $result = $readiness->checkStripeTestAccount($request->user());

        return to_route('admin.commerce.gateway-readiness.index')->with(
            'success',
            $result->result === 'connected'
                ? 'Stripe test API connection succeeded. Checkout and webhook acceptance remain unverified.'
                : 'Stripe test account connectivity could not be verified. Review private provider configuration.'
        );
    }
}

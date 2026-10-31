<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Requests\Student\SubmitRefundRequest;
use App\Models\Order;
use App\Services\Commerce\RefundRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class RefundRequestController extends Controller
{
    public function store(SubmitRefundRequest $request, Order $order, RefundRequestService $service): RedirectResponse
    {
        $service->create($request->user(), $order, $request->validated('reason'));

        return to_route('checkout.orders.show', $order)->with('success',
            'Refund review submitted. No refund has been issued yet.');
    }
}

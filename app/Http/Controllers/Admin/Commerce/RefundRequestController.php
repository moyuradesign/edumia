<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Commerce;

use App\Http\Requests\Admin\ReviewRefundRequest;
use App\Models\CommerceRefundRequest;
use App\Services\Commerce\RefundRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class RefundRequestController extends Controller
{
    public function index(): View
    {
        return view('admin.commerce.refunds.index', [
            'requests' => CommerceRefundRequest::query()
                ->with(['order', 'user'])->latest()->paginate(25),
        ]);
    }

    public function update(ReviewRefundRequest $request, CommerceRefundRequest $refundRequest,
        RefundRequestService $service): RedirectResponse
    {
        $service->review($request->user(), $refundRequest,
            $request->validated('status'), $request->validated('review_note'));

        return to_route('admin.commerce.refunds.index')
            ->with('success', 'Review updated. Provider refund processing is separate.');
    }
}

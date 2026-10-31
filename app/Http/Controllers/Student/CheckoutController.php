<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Models\Course;
use App\Models\Order;
use App\Services\Commerce\CourseCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class CheckoutController extends Controller
{
    public function index(Request $request): View
    {
        return view('student.checkout.index', [
            'orders' => Order::query()
                ->where('user_id', $request->user()->getKey())
                ->with('items')->latest()->paginate(15),
        ]);
    }

    public function store(Request $request, Course $course, CourseCheckoutService $checkout): RedirectResponse
    {
        $order = $checkout->prepare($request->user(), $course);

        return to_route('checkout.orders.show', $order);
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless((int) $order->user_id === (int) $request->user()->getKey(), 404);

        return view('student.checkout.show', [
            'order' => $order->load('items'),
        ]);
    }

    public function cancel(Request $request, Order $order, CourseCheckoutService $checkout): RedirectResponse
    {
        $checkout->cancel($request->user(), $order);

        return to_route('checkout.orders.show', $order)
            ->with('success', 'The pending order was cancelled. No payment was taken.');
    }
}

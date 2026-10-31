<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Models\Bootcamp;
use App\Models\Course;
use App\Models\Ebook;
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
            'orders' => Order::query()->where('user_id', $request->user()->getKey())
                ->with('items')->latest()->paginate(15),
        ]);
    }

    public function store(Request $request, Course $course, CourseCheckoutService $checkout): RedirectResponse
    {
        return to_route('checkout.orders.show', $checkout->prepare($request->user(), $course));
    }

    public function ebook(Request $request, Ebook $ebook, CourseCheckoutService $checkout): RedirectResponse
    {
        return to_route('checkout.orders.show', $checkout->prepareEbook($request->user(), $ebook));
    }

    public function bootcamp(Request $request, Bootcamp $bootcamp, CourseCheckoutService $checkout): RedirectResponse
    {
        return to_route('checkout.orders.show', $checkout->prepareBootcamp($request->user(), $bootcamp));
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless((int) $order->user_id === (int) $request->user()->getKey(), 404);

        return view('student.checkout.show', ['order' => $order->load('items')]);
    }

    public function cancel(Request $request, Order $order, CourseCheckoutService $checkout): RedirectResponse
    {
        $checkout->cancel($request->user(), $order);

        return to_route('checkout.orders.show', $order)
            ->with('success', 'The pending order was cancelled. No payment was taken.');
    }
}

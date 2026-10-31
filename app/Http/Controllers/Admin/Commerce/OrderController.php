<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Commerce;

use App\Models\Order;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class OrderController extends Controller
{
    public function index(): View
    {
        return view('admin.commerce.orders.index', [
            'orders' => Order::query()->with(['user', 'items'])->latest()->paginate(25),
        ]);
    }
}

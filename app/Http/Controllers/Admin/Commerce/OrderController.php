<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Commerce;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->query('q');
        $search = is_string($query) ? mb_substr(trim($query), 0, 100) : '';

        $requestedStatus = $request->query('status');
        $statuses = ['all', 'pending', 'paid', 'partially_refunded', 'refunded', 'disputed', 'expired'];
        $status = is_string($requestedStatus) && in_array($requestedStatus, $statuses, true)
            ? $requestedStatus : 'all';

        $orders = Order::query()->with(['user', 'items'])->latest('created_at')->latest('id');

        if ($search !== '') {
            $orders->where(static function (Builder $builder) use ($search): void {
                $builder->where('reference', 'like', '%'.$search.'%')
                    ->orWhereHas('user', static function (Builder $user) use ($search): void {
                        $user->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    });
            });
        }

        if ($status === 'pending' || $status === 'expired') {
            $orders->where('status', 'pending')->whereNull('paid_at');
            if ($status === 'expired') {
                $orders->where('expires_at', '<=', now());
            } else {
                $orders->where(static function (Builder $builder): void {
                    $builder->whereNull('expires_at')->orWhere('expires_at', '>', now());
                });
            }
        } elseif ($status === 'paid') {
            $orders->where('status', 'paid')->whereNotNull('paid_at');
        } elseif ($status !== 'all') {
            $orders->where('status', $status);
        }

        return view('admin.commerce.orders.index', [
            'orders' => $orders->paginate(25)->withQueryString(),
            'search' => $search,
            'status' => $status,
            'totalOrders' => Order::query()->count(),
            'pendingOrders' => Order::query()->where('status', 'pending')->whereNull('paid_at')
                ->where(static function (Builder $builder): void {
                    $builder->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })->count(),
            'verifiedOrders' => Order::query()->where('status', 'paid')->whereNotNull('paid_at')->count(),
            'exceptionOrders' => Order::query()
                ->whereIn('status', ['partially_refunded', 'refunded', 'disputed'])->count(),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Commerce;

use App\Models\Order;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class AccountingController extends Controller
{
    public function index(): View
    {
        $paid = Order::query()->whereNotNull('paid_at');
        $missingInvoices = (clone $paid)->whereNotIn('id', DB::table('commerce_invoices')->select('order_id'))->count();
        $missingPayments = (clone $paid)->whereNotIn('gateway_payment_reference',
            DB::table('payment_histories')->select('reference'))->count();
        $missingEarnings = (clone $paid)->whereNotIn('id',
            DB::table('instructor_earning_entries')->where('entry_type', 'sale')->select('order_id'))->count();

        return view('admin.commerce.accounting.index', [
            'invoices' => DB::table('commerce_invoices')->orderByDesc('id')->paginate(15),
            'earnings' => DB::table('instructor_earning_entries')
                ->select('instructor_id', 'currency_code')
                ->selectRaw('SUM(instructor_minor) AS net_held_minor')
                ->groupBy('instructor_id', 'currency_code')
                ->orderBy('instructor_id')->get(),
            'payouts' => DB::table('instructor_payout_records')->latest()->limit(20)->get(),
            'issues' => [
                'paid_missing_invoice' => $missingInvoices,
                'paid_missing_payment_record' => $missingPayments,
                'paid_missing_earning_entry' => $missingEarnings,
                'open_disputes' => DB::table('commerce_disputes')->count(),
            ],
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class InvoiceController extends Controller
{
    public function show(Request $request, Order $order): View
    {
        abort_unless((int) $order->user_id === (int) $request->user()->getKey(), 404);
        $invoice = DB::table('commerce_invoices')->where('order_id', $order->getKey())->firstOrFail();
        return view('student.checkout.invoice', [
            'invoice' => $invoice,
            'order' => $order->load('items'),
        ]);
    }
}

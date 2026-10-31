<?php

declare(strict_types=1);

namespace App\Http\Controllers\Instructor;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class EarningsController extends Controller
{
    public function index(Request $request): View
    {
        $instructorId = $request->user()->getKey();

        return view('instructor.earnings.index', [
            'entries' => DB::table('instructor_earning_entries')
                ->where('instructor_id', $instructorId)->orderByDesc('id')->paginate(20),
            'balances' => DB::table('instructor_earning_entries')
                ->where('instructor_id', $instructorId)
                ->select('currency_code')
                ->selectRaw('SUM(instructor_minor) AS net_held_minor')
                ->groupBy('currency_code')->get(),
            'payouts' => DB::table('instructor_payout_records')
                ->where('instructor_id', $instructorId)->latest()->limit(20)->get(),
        ]);
    }
}

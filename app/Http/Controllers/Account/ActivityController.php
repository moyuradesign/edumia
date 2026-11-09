<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class ActivityController extends Controller
{
    public const ACTIONS = [
        'account.name_updated' => 'Display name changed',
        'account.locale_updated' => 'Language preference changed',
        'notifications.preference_changed' => 'Notification preferences changed',
    ];

    public function index(Request $request): View
    {
        $requested = $request->query('type', 'all');
        $type = is_string($requested)
            && ($requested === 'all' || array_key_exists($requested, self::ACTIONS))
                ? $requested : 'all';

        // The subject and actor must both be this account. Never load audit
        // values or sensitive metadata into an account-facing activity list.
        $userId = $request->user()->getKey();
        $owned = AuditEvent::query()
            ->where('actor_id', $userId)
            ->where('subject_type', User::class)
            ->where('subject_id', $userId)
            ->whereIn('action', array_keys(self::ACTIONS));

        $totals = (clone $owned)->select('action')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('action')->pluck('total', 'action');
        $counts = ['all' => (int) $totals->sum()];
        foreach (self::ACTIONS as $key => $label) {
            $counts[$key] = (int) ($totals[$key] ?? 0);
        }

        $query = clone $owned;
        if ($type !== 'all') {
            $query->where('action', $type);
        }

        return view('account.activity.index', [
            'activity' => $query->select(['id', 'action', 'created_at'])
                ->orderByDesc('id')->paginate(10)
                ->appends($type === 'all' ? [] : ['type' => $type]),
            'types' => ['all' => 'All activity'] + self::ACTIONS,
            'type' => $type,
            'counts' => $counts,
        ]);
    }
}

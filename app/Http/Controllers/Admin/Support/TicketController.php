<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Support;

use App\Http\Requests\Admin\UpdateTicketStatusRequest;
use App\Models\Ticket;
use App\Services\Support\TicketService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->query('q');
        $search = is_string($query) ? mb_substr(trim($query), 0, 100) : '';
        $requestedStatus = $request->query('status');
        $status = is_string($requestedStatus) && in_array($requestedStatus, ['all', 'open', 'closed'], true)
            ? $requestedStatus : 'all';

        $tickets = Ticket::query()->with(['requester', 'status'])->latest('updated_at')->latest('id');

        if ($search !== '') {
            $tickets->where(static function (Builder $builder) use ($search): void {
                $builder->where('subject', 'like', '%'.$search.'%')
                    ->orWhereHas('requester', static function (Builder $member) use ($search): void {
                        $member->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    });
            });
        }

        if ($status === 'closed') {
            $tickets->whereHas('status', static fn (Builder $builder) => $builder->where('is_closed', true));
        } elseif ($status === 'open') {
            $tickets->where(static function (Builder $builder): void {
                $builder->whereDoesntHave('status')
                    ->orWhereHas('status', static fn (Builder $value) => $value->where('is_closed', false));
            });
        }

        return view('admin.support.tickets.index', [
            'tickets' => $tickets->paginate(25)->withQueryString(),
            'search' => $search,
            'status' => $status,
            'allTickets' => Ticket::query()->count(),
            'openTickets' => Ticket::query()->where(static function (Builder $builder): void {
                $builder->whereDoesntHave('status')
                    ->orWhereHas('status', static fn (Builder $value) => $value->where('is_closed', false));
            })->count(),
            'closedTickets' => Ticket::query()
                ->whereHas('status', static fn (Builder $builder) => $builder->where('is_closed', true))->count(),
            'recentTickets' => Ticket::query()->where('created_at', '>=', now()->subDays(7))->count(),
        ]);
    }

    public function show(Ticket $ticket): View
    {
        return view('admin.support.tickets.show', [
            'ticket' => $ticket->load(['requester', 'status', 'messages.sender']),
        ]);
    }

    public function update(
        UpdateTicketStatusRequest $request,
        Ticket $ticket,
        TicketService $tickets
    ): RedirectResponse {
        $tickets->changeStatus($request->user(), $ticket, $request->validated('status'));

        return to_route('admin.tickets.show', $ticket)->with('success', 'Ticket status updated.');
    }
}

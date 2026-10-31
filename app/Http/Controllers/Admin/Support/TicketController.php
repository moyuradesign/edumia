<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Support;

use App\Http\Requests\Admin\UpdateTicketStatusRequest;
use App\Models\Ticket;
use App\Services\Support\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class TicketController extends Controller
{
    public function index(): View
    {
        return view('admin.support.tickets.index', [
            'tickets' => Ticket::query()->with(['requester', 'status'])->latest()->paginate(25),
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

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student\Support;

use App\Http\Requests\Student\CreateTicketRequest;
use App\Http\Requests\Student\ReplyTicketRequest;
use App\Models\Ticket;
use App\Services\Support\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class TicketController extends Controller
{
    public function index(Request $request): View
    {
        return view('student.support.tickets.index', [
            'tickets' => Ticket::query()->with('status')
                ->where('requester_id', $request->user()->getKey())->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('student.support.tickets.form');
    }

    public function store(CreateTicketRequest $request, TicketService $tickets): RedirectResponse
    {
        $ticket = $tickets->create($request->user(), $request->validated());

        return to_route('support.tickets.show', $ticket)->with('success', 'Your ticket was created.');
    }

    public function show(Request $request, Ticket $ticket, TicketService $tickets): View
    {
        $actor = $request->user();
        $tickets->assertCanRead($actor, $ticket);

        $ticket->load(['requester', 'status', 'messages' => function ($query) use ($actor) {
            if (! $actor->isAdmin()) {
                $query->where('is_internal', false);
            }
            $query->with('sender');
        }]);

        return view('student.support.tickets.show', ['ticket' => $ticket]);
    }

    public function reply(ReplyTicketRequest $request, Ticket $ticket, TicketService $tickets): RedirectResponse
    {
        $tickets->reply($request->user(), $ticket, $request->validated('body'));

        return to_route('support.tickets.show', $ticket)->with('success', 'Your reply was added.');
    }
}

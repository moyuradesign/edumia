<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Support;

use App\Http\Requests\Admin\ContactStatusRequest;
use App\Models\Contact;
use App\Services\Support\ContactManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class ContactController extends Controller
{
    public function index(): View
    {
        return view('admin.support.contacts.index', [
            'contacts' => Contact::query()->latest()->paginate(20),
        ]);
    }

    public function show(Contact $contact): View
    {
        return view('admin.support.contacts.show', ['contact' => $contact]);
    }

    public function update(
        ContactStatusRequest $request,
        Contact $contact,
        ContactManagementService $management
    ): RedirectResponse {
        $management->updateStatus($request->user(), $contact, $request->validated('status'));

        return to_route('admin.contacts.show', $contact)->with('success', 'Contact status updated.');
    }
}

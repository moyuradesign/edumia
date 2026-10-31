<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Helpers\Localization;
use App\Http\Requests\Frontend\ContactRequest;
use App\Models\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class ContactController extends Controller
{
    public function create(): View
    {
        return view('frontend.default.contact.index', [
            'locale' => 'en',
            'languages' => Localization::LANGUAGES,
        ]);
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        Contact::create($request->validated());

        return to_route('contact.create')->with(
            'success',
            'Your message has been received. Please keep a copy for your records.'
        );
    }
}

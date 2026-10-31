<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Helpers\Localization;
use App\Http\Requests\Frontend\ContactRequest;
use App\Models\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

final class ContactController extends Controller
{
    public function create(Request $request, ?string $locale = null): View
    {
        $locale = Localization::resolve($locale);
        App::setLocale($locale);

        return view('frontend.default.contact.index', [
            'locale' => $locale, 'languages' => Localization::LANGUAGES,
        ]);
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        Contact::create($request->validated());

        $locale = Localization::resolve($request->route('locale'));

        return redirect()->to(Localization::url($locale, 'contact'))->with(
            'success',
            'Your message has been received. Please keep a copy for your records.'
        );
    }
}

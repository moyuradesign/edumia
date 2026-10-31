<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Requests\Frontend\NewsletterSubscriptionRequest;
use App\Models\NewsletterSubscriber;
use App\Services\Marketing\NewsletterSubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class NewsletterController extends Controller
{
    public function store(
        NewsletterSubscriptionRequest $request,
        NewsletterSubscriptionService $subscription
    ): RedirectResponse {
        $subscription->requestConfirmation(
            $request->validated('email'),
            $request->validated('name')
        );

        // Avoid revealing subscriber status or account presence.
        return back()->with('success', 'If eligible, a confirmation email will be sent to that address.');
    }

    public function confirm(NewsletterSubscriber $subscriber, NewsletterSubscriptionService $subscription): RedirectResponse
    {
        $subscription->confirm($subscriber);

        return to_route('home')->with('success', 'Your newsletter subscription is confirmed.');
    }

    public function unsubscribe(NewsletterSubscriber $subscriber, NewsletterSubscriptionService $subscription): RedirectResponse
    {
        $subscription->unsubscribe($subscriber);

        return to_route('home')->with('success', 'Your newsletter subscription has been cancelled.');
    }
}

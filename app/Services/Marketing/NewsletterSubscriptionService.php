<?php

declare(strict_types=1);

namespace App\Services\Marketing;

use App\Mail\NewsletterConfirmation;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

final class NewsletterSubscriptionService
{
    public function requestConfirmation(string $email, ?string $name): void
    {
        $email = mb_strtolower(trim($email));

        $subscriber = DB::transaction(function () use ($email, $name): ?NewsletterSubscriber {
            $existing = NewsletterSubscriber::query()->where('email', $email)
                ->lockForUpdate()->first();

            if ($existing && $existing->isSubscribed()) {
                return null;
            }

            $subscriber = $existing ?: new NewsletterSubscriber(['email' => $email]);
            $subscriber->name = $name ? trim($name) : $subscriber->name;
            // A former subscriber must reconfirm consent before any campaign.
            $subscriber->verified_at = null;
            $subscriber->unsubscribed_at = null;
            $subscriber->save();

            return $subscriber;
        });

        if ($subscriber === null) {
            return;
        }

        Mail::to($subscriber->email)->send(new NewsletterConfirmation(
            URL::temporarySignedRoute(
                'newsletter.confirm', now()->addHours(48), ['subscriber' => $subscriber->getKey()]
            ),
            URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $subscriber->getKey()])
        ));
    }

    public function confirm(NewsletterSubscriber $subscriber): void
    {
        DB::transaction(function () use ($subscriber): void {
            $record = NewsletterSubscriber::query()->lockForUpdate()->findOrFail($subscriber->getKey());
            abort_if($record->unsubscribed_at !== null, 403);

            $record->verified_at ??= now();
            $record->save();
        });
    }

    public function unsubscribe(NewsletterSubscriber $subscriber): void
    {
        DB::transaction(function () use ($subscriber): void {
            $record = NewsletterSubscriber::query()->lockForUpdate()->findOrFail($subscriber->getKey());
            $record->unsubscribed_at = now();
            $record->save();
        });
    }
}

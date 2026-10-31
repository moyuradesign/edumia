<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\NewsletterConfirmation;
use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class NewsletterConsentTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_requires_explicit_consent_and_signed_email_verification(): void
    {
        Mail::fake();
        $this->post('/newsletter/subscribe', [
            'email' => 'reader@example.test', 'name' => 'Reader',
        ])->assertSessionHasErrors('consent');
        $this->assertDatabaseCount('newsletter_subscribers', 0);

        $this->post('/newsletter/subscribe', [
            'email' => 'Reader@Example.test', 'name' => 'Reader', 'consent' => 1,
        ])->assertRedirect();

        $subscriber = NewsletterSubscriber::firstOrFail();
        $this->assertSame('reader@example.test', $subscriber->email);
        $this->assertFalse($subscriber->isSubscribed());
        Mail::assertSent(NewsletterConfirmation::class, 1);
        $mail = Mail::assertSent(NewsletterConfirmation::class)->first();
        $this->get($mail->confirmationUrl)->assertRedirect(route('home'));
        $this->assertTrue($subscriber->fresh()->isSubscribed());

        $this->post('/newsletter/subscribe', [
            'email' => 'reader@example.test', 'consent' => 1,
        ])->assertRedirect();
        Mail::assertSent(NewsletterConfirmation::class, 1);
    }

    public function test_explicit_unsubscribe_revokes_consent_and_rejoin_reconfirms(): void
    {
        Mail::fake();
        $this->post('/newsletter/subscribe', [
            'email' => 'learner@example.test', 'consent' => 1,
        ])->assertRedirect();
        $mail = Mail::assertSent(NewsletterConfirmation::class)->first();
        $this->get($mail->confirmationUrl)->assertRedirect();
        $subscriber = NewsletterSubscriber::firstOrFail();
        $this->assertTrue($subscriber->fresh()->isSubscribed());

        $this->get($mail->unsubscribeUrl)->assertRedirect(route('home'));
        $this->assertFalse($subscriber->fresh()->isSubscribed());
        $this->get($mail->confirmationUrl)->assertForbidden();

        $this->post('/newsletter/subscribe', [
            'email' => 'learner@example.test', 'consent' => 1,
        ])->assertRedirect();
        $this->assertFalse($subscriber->fresh()->isSubscribed());
        Mail::assertSent(NewsletterConfirmation::class, 2);
    }

    public function test_unsigned_links_cannot_modify_subscription(): void
    {
        Mail::fake();
        $this->post('/newsletter/subscribe', [
            'email' => 'learner@example.test', 'consent' => 1,
        ])->assertRedirect();
        $subscriber = NewsletterSubscriber::firstOrFail();

        $this->get('/newsletter/confirm/'.$subscriber->id)->assertForbidden();
        $this->get('/newsletter/unsubscribe/'.$subscriber->id)->assertForbidden();
        $this->assertFalse($subscriber->fresh()->isSubscribed());
        $this->assertNull($subscriber->fresh()->unsubscribed_at);
    }
}

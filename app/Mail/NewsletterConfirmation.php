<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

final class NewsletterConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $confirmationUrl,
        public readonly string $unsubscribeUrl,
    ) {}

    public function build(): self
    {
        return $this->subject('Confirm your Edumia newsletter subscription')
            ->view('emails.newsletter-confirmation');
    }
}

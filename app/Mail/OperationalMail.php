<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

final class OperationalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $headline,
        public readonly string $summary,
        public readonly string $destination,
    ) {}

    public function build(): self
    {
        return $this->subject('Edumia account update: '.mb_substr($this->headline, 0, 100))
            ->markdown('emails.operational');
    }
}

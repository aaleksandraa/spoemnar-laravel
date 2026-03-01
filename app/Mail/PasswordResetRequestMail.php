<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $fullName,
        public readonly string $mailLocale,
        public readonly string $resetUrl,
    ) {
    }

    public function build(): self
    {
        return $this->subject(trans('mail.password_reset.subject', [], $this->mailLocale))
            ->view('emails.password-reset-request')
            ->with([
                'fullName' => $this->fullName,
                'locale' => $this->mailLocale,
                'resetUrl' => $this->resetUrl,
                'appName' => (string) config('app.name', 'Spomenar'),
            ]);
    }
}

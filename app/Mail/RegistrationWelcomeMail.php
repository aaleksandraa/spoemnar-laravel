<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegistrationWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $fullName,
        public readonly string $mailLocale,
        public readonly string $dashboardUrl,
    ) {
    }

    public function build(): self
    {
        return $this->subject(trans('mail.registration.subject', [], $this->mailLocale))
            ->view('emails.registration-welcome')
            ->with([
                'fullName' => $this->fullName,
                'locale' => $this->mailLocale,
                'dashboardUrl' => $this->dashboardUrl,
                'appName' => (string) config('app.name', 'Spomenar'),
            ]);
    }
}

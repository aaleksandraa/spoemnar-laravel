<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MemorialTributeNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $ownerName,
        public readonly string $memorialName,
        public readonly string $authorName,
        public readonly string $authorEmail,
        public readonly string $tributeMessage,
        public readonly string $memorialUrl,
        public readonly string $mailLocale,
    ) {
    }

    public function build(): self
    {
        return $this->subject(trans('mail.tribute_notification.subject', [
            'memorial' => $this->memorialName,
        ], $this->mailLocale))
            ->view('emails.memorial-tribute-notification')
            ->with([
                'ownerName' => $this->ownerName,
                'memorialName' => $this->memorialName,
                'authorName' => $this->authorName,
                'authorEmail' => $this->authorEmail,
                'tributeMessage' => $this->tributeMessage,
                'memorialUrl' => $this->memorialUrl,
                'locale' => $this->mailLocale,
                'appName' => (string) config('app.name', 'Spomenar'),
            ]);
    }
}

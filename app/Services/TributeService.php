<?php

namespace App\Services;

use App\Mail\MemorialTributeNotificationMail;
use App\Models\Memorial;
use App\Models\Tribute;
use App\Support\LocaleResolver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TributeService
{
    public function createForMemorial(
        Memorial $memorial,
        string $authorName,
        string $authorEmail,
        string $message,
        ?string $mailLocale = null,
    ): Tribute {
        $tribute = $memorial->tributes()->create([
            'author_name' => $authorName,
            'author_email' => $authorEmail,
            'message' => $message,
        ]);

        $this->sendOwnerNotification($memorial, $tribute, $mailLocale);

        return $tribute;
    }

    private function sendOwnerNotification(Memorial $memorial, Tribute $tribute, ?string $mailLocale): void
    {
        $memorial->loadMissing('user.profile');

        $ownerEmail = trim((string) ($memorial->user?->profile?->email ?: $memorial->user?->email ?: ''));
        if ($ownerEmail === '') {
            return;
        }

        if ($this->emailsMatch($ownerEmail, (string) $tribute->author_email)) {
            return;
        }

        $resolvedLocale = $this->resolveMailLocale(
            (string) ($memorial->user?->profile?->preferred_locale ?? ''),
            $mailLocale,
        );
        $ownerName = trim((string) ($memorial->user?->profile?->full_name ?: $memorial->user?->email ?: $ownerEmail));
        $memorialName = trim($memorial->first_name.' '.$memorial->last_name);
        $memorialUrl = route('memorial.profile', [
            'locale' => $resolvedLocale,
            'slug' => $memorial->slug,
        ]);

        try {
            Mail::to($ownerEmail)->send(new MemorialTributeNotificationMail(
                ownerName: $ownerName,
                memorialName: $memorialName,
                authorName: (string) $tribute->author_name,
                authorEmail: (string) $tribute->author_email,
                tributeMessage: (string) $tribute->message,
                memorialUrl: $memorialUrl,
                mailLocale: $resolvedLocale,
            ));
        } catch (\Throwable $exception) {
            Log::warning('Memorial tribute notification email failed.', [
                'memorial_id' => $memorial->id,
                'memorial_slug' => $memorial->slug,
                'tribute_id' => $tribute->id,
                'owner_user_id' => $memorial->user?->id,
                'owner_email' => $ownerEmail,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function resolveMailLocale(?string ...$candidates): string
    {
        foreach ($candidates as $candidate) {
            $normalizedCandidate = LocaleResolver::normalizeLocale((string) $candidate);
            if (LocaleResolver::isSupported($normalizedCandidate)) {
                return $normalizedCandidate;
            }
        }

        $appLocale = LocaleResolver::normalizeLocale((string) app()->getLocale());
        if (LocaleResolver::isSupported($appLocale)) {
            return $appLocale;
        }

        return 'bs';
    }

    private function emailsMatch(string $left, string $right): bool
    {
        return mb_strtolower(trim($left)) === mb_strtolower(trim($right));
    }
}

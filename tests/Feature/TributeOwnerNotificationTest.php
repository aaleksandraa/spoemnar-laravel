<?php

use App\Mail\MemorialTributeNotificationMail;
use App\Models\Memorial;
use App\Models\User;
use App\Support\TributeMathChallenge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function createMemorialForTributeOwnerNotificationTest(
    string $userEmail = 'owner-user@example.com',
    string $profileEmail = 'owner-profile@example.com',
    string $fullName = 'Memorial Owner',
    ?string $preferredLocale = null,
): Memorial {
    $owner = User::factory()->create([
        'email' => $userEmail,
    ]);

    $owner->profile()->create([
        'email' => $profileEmail,
        'full_name' => $fullName,
        'preferred_locale' => $preferredLocale,
    ]);

    return Memorial::factory()->create([
        'user_id' => $owner->id,
        'is_public' => true,
    ]);
}

function extractTributeOwnerNotificationMathAnswer(string $payload): string
{
    $decoded = json_decode(Crypt::decryptString($payload), true, 512, JSON_THROW_ON_ERROR);

    expect($decoded)->toBeArray();
    expect($decoded)->toHaveKey('answer');

    return (string) $decoded['answer'];
}

/**
 * @return array{session_id: string, form_rendered_at: int, form_signature: string, math_challenge_payload: string, math_answer: string}
 */
function issueTributeOwnerNotificationSecurityFields(Memorial $memorial): array
{
    $session = app('session.store');
    $session->start();
    $session->put('tribute_math_seed', true);
    $session->save();

    $sessionId = $session->getId();
    $formRenderedAt = now()->subSeconds(5)->timestamp;
    $mathChallenge = TributeMathChallenge::issue((string) $memorial->id, $sessionId, $formRenderedAt);

    return [
        'session_id' => $sessionId,
        'form_rendered_at' => $formRenderedAt,
        'form_signature' => hash_hmac(
            'sha256',
            $formRenderedAt.'|'.$memorial->id.'|'.$sessionId,
            (string) config('app.key')
        ),
        'math_challenge_payload' => $mathChallenge['payload'],
        'math_answer' => extractTributeOwnerNotificationMathAnswer($mathChallenge['payload']),
    ];
}

it('prefers the memorial owners saved locale for notification emails', function () {
    Mail::fake();

    $memorial = createMemorialForTributeOwnerNotificationTest(
        preferredLocale: 'sr',
    );

    $response = $this->postJson("/api/v1/memorials/{$memorial->id}/tributes?lang=de", [
        'author_name' => 'Visitor Person',
        'author_email' => 'visitor@example.com',
        'message' => 'This is a respectful tribute message for API notification testing.',
        'honeypot' => '',
        'timestamp' => now()->subMinutes(5)->timestamp,
    ]);

    $response->assertCreated();

    $this->assertDatabaseHas('tributes', [
        'memorial_id' => $memorial->id,
        'author_email' => 'visitor@example.com',
    ]);

    Mail::assertSent(MemorialTributeNotificationMail::class, function (MemorialTributeNotificationMail $mail) use ($memorial) {
        expect($mail->ownerName)->toBe('Memorial Owner');
        expect($mail->memorialName)->toBe(trim($memorial->first_name.' '.$memorial->last_name));
        expect($mail->authorName)->toBe('Visitor Person');
        expect($mail->authorEmail)->toBe('visitor@example.com');
        expect($mail->mailLocale)->toBe('sr');
        expect($mail->memorialUrl)->toBe(route('memorial.profile', [
            'locale' => 'sr',
            'slug' => $memorial->slug,
        ]));

        return $mail->hasTo('owner-profile@example.com');
    });
});

it('sends the notification from the public tribute form using the memorial locale', function () {
    Mail::fake();

    $memorial = createMemorialForTributeOwnerNotificationTest(
        userEmail: 'owner-login@example.com',
        profileEmail: 'owner-public@example.com',
        fullName: 'Vlasnik Memorijala',
    );
    $profileUrl = route('memorial.profile', ['locale' => 'hr', 'slug' => $memorial->slug]);
    $securityFields = issueTributeOwnerNotificationSecurityFields($memorial);

    $response = $this
        ->withCookie((string) config('session.cookie'), $securityFields['session_id'])
        ->from($profileUrl)
        ->post(route('tributes.store', [
            'locale' => 'hr',
            'memorial' => $memorial,
        ]), [
            'author_name' => 'Posjetilac',
            'author_email' => 'Visitor@Example.com',
            'message' => 'Ovo je iskrena i dovoljno duga poruka sjecanja za web formu.',
            'website' => '',
            'form_rendered_at' => $securityFields['form_rendered_at'],
            'form_signature' => $securityFields['form_signature'],
            'math_challenge_payload' => $securityFields['math_challenge_payload'],
            'math_answer' => $securityFields['math_answer'],
        ]);

    $response->assertRedirect($profileUrl);
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('tributes', [
        'memorial_id' => $memorial->id,
        'author_email' => 'visitor@example.com',
    ]);

    Mail::assertSent(MemorialTributeNotificationMail::class, function (MemorialTributeNotificationMail $mail) use ($memorial) {
        expect($mail->ownerName)->toBe('Vlasnik Memorijala');
        expect($mail->authorName)->toBe('Posjetilac');
        expect($mail->authorEmail)->toBe('visitor@example.com');
        expect($mail->mailLocale)->toBe('hr');
        expect($mail->memorialUrl)->toBe(route('memorial.profile', [
            'locale' => 'hr',
            'slug' => $memorial->slug,
        ]));

        return $mail->hasTo('owner-public@example.com');
    });
});

it('does not notify the owner when the tribute author email matches the owner email', function () {
    Mail::fake();

    $memorial = createMemorialForTributeOwnerNotificationTest();

    $response = $this->postJson("/api/v1/memorials/{$memorial->id}/tributes", [
        'author_name' => 'Owner Again',
        'author_email' => 'OWNER-PROFILE@example.com',
        'message' => 'This tribute should be stored without sending a self notification email.',
        'honeypot' => '',
        'timestamp' => now()->subMinutes(2)->timestamp,
    ]);

    $response->assertCreated();

    $this->assertDatabaseHas('tributes', [
        'memorial_id' => $memorial->id,
        'author_email' => 'OWNER-PROFILE@example.com',
    ]);

    Mail::assertNotSent(MemorialTributeNotificationMail::class);
});

<?php

use App\Models\Memorial;
use App\Models\User;
use App\Support\TributeMathChallenge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

function createPublicTributeMemorial(): Memorial
{
    $owner = User::factory()->create();
    $owner->profile()->create(['email' => $owner->email]);

    return Memorial::factory()->create([
        'user_id' => $owner->id,
        'is_public' => true,
    ]);
}

function extractTributeMathAnswer(string $payload): string
{
    $decoded = json_decode(Crypt::decryptString($payload), true, 512, JSON_THROW_ON_ERROR);

    expect($decoded)->toBeArray();
    expect($decoded)->toHaveKey('answer');

    return (string) $decoded['answer'];
}

/**
 * @return array{session_id: string, form_rendered_at: int, form_signature: string, math_challenge_payload: string, math_answer: string}
 */
function issueTributeSecurityFields(Memorial $memorial): array
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
        'math_answer' => extractTributeMathAnswer($mathChallenge['payload']),
    ];
}

describe('Tribute Math Challenge', function () {
    it('renders a math challenge on the public tribute form', function () {
        $memorial = createPublicTributeMemorial();
        $profileUrl = route('memorial.profile', ['locale' => 'bs', 'slug' => $memorial->slug]);

        $response = $this->get($profileUrl);

        $response->assertOk();
        $response->assertSee('name="math_answer"', false);
        $response->assertSee('name="math_challenge_payload"', false);
        $response->assertSee('data:image/svg+xml;base64,', false);
    });

    it('accepts a tribute when the math challenge is solved correctly', function () {
        $memorial = createPublicTributeMemorial();
        $profileUrl = route('memorial.profile', ['locale' => 'bs', 'slug' => $memorial->slug]);
        $securityFields = issueTributeSecurityFields($memorial);

        $response = $this
            ->withCookie((string) config('session.cookie'), $securityFields['session_id'])
            ->from($profileUrl)
            ->post(route('tributes.store', [
                'locale' => 'bs',
                'memorial' => $memorial,
            ]), [
                'author_name' => 'Test Korisnik',
                'author_email' => 'test@example.com',
                'message' => 'Ovo je iskrena poruka sjecanja za testiranje forme.',
                'honeypot' => '',
                'form_rendered_at' => $securityFields['form_rendered_at'],
                'form_signature' => $securityFields['form_signature'],
                'math_challenge_payload' => $securityFields['math_challenge_payload'],
                'math_answer' => $securityFields['math_answer'],
            ]);

        $response->assertRedirect($profileUrl);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tributes', [
            'memorial_id' => $memorial->id,
            'author_email' => 'test@example.com',
        ]);
    });

    it('rejects a tribute when the math challenge answer is wrong', function () {
        $memorial = createPublicTributeMemorial();
        $profileUrl = route('memorial.profile', ['locale' => 'bs', 'slug' => $memorial->slug]);
        $securityFields = issueTributeSecurityFields($memorial);

        $response = $this
            ->withCookie((string) config('session.cookie'), $securityFields['session_id'])
            ->from($profileUrl)
            ->post(route('tributes.store', [
                'locale' => 'bs',
                'memorial' => $memorial,
            ]), [
                'author_name' => 'Spam Bot',
                'author_email' => 'wrong-answer@example.com',
                'message' => 'Ovo je poruka koja ne bi trebala proci math challenge.',
                'honeypot' => '',
                'form_rendered_at' => $securityFields['form_rendered_at'],
                'form_signature' => $securityFields['form_signature'],
                'math_challenge_payload' => $securityFields['math_challenge_payload'],
                'math_answer' => '999',
            ]);

        $response->assertRedirect($profileUrl);
        $response->assertSessionHasErrors([
            'message' => __('ui.memorial.messages.math_failed'),
        ]);

        $this->assertDatabaseMissing('tributes', [
            'memorial_id' => $memorial->id,
            'author_email' => 'wrong-answer@example.com',
        ]);
    });
});

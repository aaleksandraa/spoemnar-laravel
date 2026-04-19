<?php

use App\Models\Memorial;
use App\Models\Tribute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders tribute delete controls as hidden by default on the public memorial page', function () {
    $owner = User::factory()->create();
    $owner->profile()->create(['email' => $owner->email]);

    $memorial = Memorial::factory()->create([
        'user_id' => $owner->id,
        'is_public' => true,
        'slug' => 'skriveno-dugme-test',
    ]);

    Tribute::factory()->create([
        'memorial_id' => $memorial->id,
    ]);

    $response = $this->get(route('memorial.profile', [
        'locale' => 'bs',
        'slug' => $memorial->slug,
    ]));

    $response->assertOk();

    $content = $response->getContent();

    expect($content)->toContain('data-tribute-delete');
    expect($content)->toMatch('/<button[^>]*(?:hidden[^>]*data-tribute-delete|data-tribute-delete[^>]*hidden)[^>]*>/s');
});

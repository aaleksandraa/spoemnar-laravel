<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders account navigation and logout state sync hooks in the header', function () {
    $response = $this->get(route('dashboard', ['locale' => 'bs']));

    $response->assertOk();
    $response->assertSee(route('account', ['locale' => 'bs']), false);
    $response->assertSee(__('ui.dashboard.profile'));
    $response->assertSee("window.addEventListener('pageshow', syncHeaderState);", false);
    $response->assertSee('clearClientAuthState();', false);
});

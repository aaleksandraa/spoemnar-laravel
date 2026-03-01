<?php

use App\Models\Memorial;

// Feature: laravel-migration, Property 10: Slug generation format
// Validates: Requirements 3.2

it('generates valid slug format for any name combination', function () {
    $firstNames = [
        'John',
        'Марко',
        'Đorđe',
        'Željko',
        'Šaban',
        'Čedomir',
        'Ćamil',
        'Jean-Pierre',
        'O\'Brien',
        'José',
        'Müller',
        'Søren',
    ];

    $lastNames = [
        'Smith',
        'Петровић',
        'Đurić',
        'Živković',
        'Šabić',
        'Čolić',
        'Ćosić',
        'De La Cruz',
        'O\'Connor',
        'García',
        'Müller',
        'Jørgensen',
    ];

    foreach ($firstNames as $firstName) {
        foreach ($lastNames as $lastName) {
            $slug = Memorial::generateSlug($firstName, $lastName);

            // Slug should only contain lowercase alphanumeric characters and dots
            expect($slug)->toMatch('/^[a-z0-9.]+$/');

            // Slug should not contain consecutive dots
            expect($slug)->not->toContain('..');

            // Slug should not start with a dot
            expect($slug)->not->toStartWith('.');

            // Slug should not end with a dot
            expect($slug)->not->toEndWith('.');

            // Slug should not be empty
            expect($slug)->not->toBeEmpty();
        }
    }
})->repeat(100);

it('transliterates special Balkan characters correctly', function () {
    $testCases = [
        ['Đorđe', 'Đurić', 'djordje.djuric'],
        ['Željko', 'Živković', 'zeljko.zivkovic'],
        ['Šaban', 'Šabić', 'saban.sabic'],
        ['Čedomir', 'Čolić', 'cedomir.colic'],
        ['Ćamil', 'Ćosić', 'camil.cosic'],
    ];

    foreach ($testCases as [$firstName, $lastName, $expectedSlug]) {
        $slug = Memorial::generateSlug($firstName, $lastName);
        expect($slug)->toBe($expectedSlug);
    }
});

it('handles names with spaces and special characters', function () {
    $testCases = [
        ['Jean-Pierre', 'De La Cruz', 'jean.pierre.de.la.cruz'],
        ['Mary Jane', 'O\'Connor', 'mary.jane.o.connor'],
        ['José María', 'García López', 'jose.maria.garcia.lopez'],
    ];

    foreach ($testCases as [$firstName, $lastName, $expectedSlug]) {
        $slug = Memorial::generateSlug($firstName, $lastName);
        expect($slug)->toBe($expectedSlug);
    }
});

it('removes diacritics from characters', function () {
    $testCases = [
        ['José', 'García', 'jose.garcia'],
        ['François', 'Müller', 'francois.muller'],
        ['Søren', 'Jørgensen', 'soren.jorgensen'],
    ];

    foreach ($testCases as [$firstName, $lastName, $expectedSlug]) {
        $slug = Memorial::generateSlug($firstName, $lastName);
        expect($slug)->toBe($expectedSlug);
    }
});

it('handles edge cases with multiple consecutive special characters', function () {
    $testCases = [
        ['A!!!B', 'C???D', 'a.b.c.d'],
        ['Test---Name', 'Test___Last', 'test.name.test.last'],
        ['...Start', 'End...', 'start.end'],
    ];

    foreach ($testCases as [$firstName, $lastName, $expectedSlug]) {
        $slug = Memorial::generateSlug($firstName, $lastName);
        expect($slug)->toBe($expectedSlug);
    }
});

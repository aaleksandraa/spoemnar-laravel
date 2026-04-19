<?php

use App\Models\Country;
use App\Models\Place;
use Database\Seeders\LocationCatalogExpansionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds the expanded location catalog for existing and new countries', function () {
    $this->seed(LocationCatalogExpansionSeeder::class);

    expect(Country::whereIn('code', ['BA', 'RS', 'HR', 'IT', 'DE', 'AT', 'ME', 'MK', 'SI'])->count())
        ->toBe(9);

    $bosnia = Country::where('code', 'BA')->firstOrFail();
    $montenegro = Country::where('code', 'ME')->firstOrFail();
    $macedonia = Country::where('code', 'MK')->firstOrFail();
    $slovenia = Country::where('code', 'SI')->firstOrFail();

    $this->assertDatabaseHas('places', [
        'country_id' => $bosnia->id,
        'name' => 'Gradiska',
        'type' => 'town',
    ]);

    $this->assertDatabaseHas('places', [
        'country_id' => $montenegro->id,
        'name' => 'Sveti Stefan',
        'type' => 'village',
    ]);

    $this->assertDatabaseHas('places', [
        'country_id' => $macedonia->id,
        'name' => 'Skopje',
        'type' => 'city',
    ]);

    $this->assertDatabaseHas('places', [
        'country_id' => $macedonia->id,
        'name' => 'Lazaropole',
        'type' => 'village',
    ]);

    $this->assertDatabaseHas('places', [
        'country_id' => $slovenia->id,
        'name' => 'Ljubljana',
        'type' => 'city',
    ]);

    $this->assertDatabaseHas('places', [
        'country_id' => $slovenia->id,
        'name' => 'Kranjska Gora',
        'type' => 'town',
    ]);
});

it('is idempotent when the expansion seeder is executed multiple times', function () {
    $this->seed(LocationCatalogExpansionSeeder::class);
    $this->seed(LocationCatalogExpansionSeeder::class);

    $macedonia = Country::where('code', 'MK')->firstOrFail();

    expect(
        Place::where('country_id', $macedonia->id)
            ->where('name', 'Skopje')
            ->count()
    )->toBe(1);

    expect(
        Place::where('country_id', $macedonia->id)
            ->where('name', 'Lazaropole')
            ->count()
    )->toBe(1);
});

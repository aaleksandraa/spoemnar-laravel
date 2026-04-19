<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Place;
use Database\Seeders\Support\ExpandedLocationCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationCatalogExpansionSeeder extends Seeder
{
    /**
     * Seed an expanded catalog of countries, cities, towns and villages
     * without changing the existing location seeder.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (ExpandedLocationCatalog::countries() as $code => $countryData) {
                $country = Country::firstOrCreate(
                    ['code' => $code],
                    [
                        'name' => $countryData['name'],
                        'is_active' => true,
                    ]
                );

                foreach ($countryData['places'] as [$placeName, $type]) {
                    Place::firstOrCreate(
                        [
                            'country_id' => $country->id,
                            'name' => $placeName,
                        ],
                        [
                            'type' => $type,
                            'is_active' => true,
                        ]
                    );
                }
            }
        });
    }
}

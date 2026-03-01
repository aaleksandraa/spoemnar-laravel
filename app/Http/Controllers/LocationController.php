<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Place;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    /**
     * Public country list for memorial form selectors.
     */
    public function countries(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        $query = Country::query()
            ->where('is_active', true)
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($innerQuery) use ($search) {
                $innerQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'data' => $query->get()->map(static function (Country $country): array {
                return [
                    'id' => $country->id,
                    'code' => $country->code,
                    'name' => $country->name,
                ];
            })->values(),
        ]);
    }

    /**
     * Public places list for selected country.
     */
    public function countryPlaces(Request $request, Country $country): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        $type = trim((string) $request->query('type', ''));

        $query = $country->places()
            ->where('is_active', true)
            ->orderBy('name');

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($type !== '') {
            $query->where('type', $this->normalizePlaceType($type));
        }

        return response()->json([
            'data' => $query->get()->map(static function (Place $place): array {
                return [
                    'id' => $place->id,
                    'countryId' => $place->country_id,
                    'name' => $place->name,
                    'type' => $place->type,
                ];
            })->values(),
        ]);
    }

    /**
     * Admin country listing with place counters.
     */
    public function adminCountries(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        $query = Country::query()
            ->withCount('places')
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($innerQuery) use ($search) {
                $innerQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'data' => $query->get()->map(static function (Country $country): array {
                return [
                    'id' => $country->id,
                    'code' => $country->code,
                    'name' => $country->name,
                    'isActive' => $country->is_active,
                    'placesCount' => $country->places_count ?? 0,
                ];
            })->values(),
        ]);
    }

    /**
     * Admin place listing.
     */
    public function adminPlaces(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        $countryId = $request->query('country_id');

        $query = Place::query()
            ->with('country')
            ->orderBy('name');

        if (is_numeric($countryId)) {
            $query->where('country_id', (int) $countryId);
        }

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        return response()->json([
            'data' => $query->limit(300)->get()->map(static function (Place $place): array {
                return [
                    'id' => $place->id,
                    'countryId' => $place->country_id,
                    'countryName' => $place->country?->name,
                    'countryCode' => $place->country?->code,
                    'name' => $place->name,
                    'type' => $place->type,
                    'isActive' => $place->is_active,
                ];
            })->values(),
        ]);
    }

    /**
     * Admin single country create.
     */
    public function storeCountry(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:2', 'alpha', Rule::unique('countries', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', new \App\Rules\StrictBooleanRule()],
        ]);

        $country = Country::create([
            'code' => strtoupper($validated['code']),
            'name' => trim($validated['name']),
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
        ]);

        return response()->json([
            'message' => 'Country created successfully.',
            'data' => [
                'id' => $country->id,
                'code' => $country->code,
                'name' => $country->name,
                'isActive' => $country->is_active,
            ],
        ], 201);
    }

    /**
     * Admin single place create.
     */
    public function storePlace(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:32'],
            'is_active' => ['nullable', new \App\Rules\StrictBooleanRule()],
        ]);

        $place = Place::create([
            'country_id' => (int) $validated['country_id'],
            'name' => trim($validated['name']),
            'type' => $this->normalizePlaceType((string) ($validated['type'] ?? 'city')),
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
        ]);

        $place->load('country');

        return response()->json([
            'message' => 'Place created successfully.',
            'data' => [
                'id' => $place->id,
                'countryId' => $place->country_id,
                'countryName' => $place->country?->name,
                'countryCode' => $place->country?->code,
                'name' => $place->name,
                'type' => $place->type,
                'isActive' => $place->is_active,
            ],
        ], 201);
    }

    /**
     * Admin bulk import for countries and places.
     */
    public function import(Request $request): JsonResponse
    {
        // Authorization check: Only admin users can import locations
        $this->authorize('import', Country::class);

        $validated = $request->validate([
            'country_lines' => ['nullable', 'string'],
            'place_lines' => ['nullable', 'string'],
        ]);

        $countryLines = $this->normalizeImportLines((string) ($validated['country_lines'] ?? ''));
        $placeLines = $this->normalizeImportLines((string) ($validated['place_lines'] ?? ''));

        $countryImported = 0;
        $placeImported = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($countryLines as $lineNumber => $line) {
                [$code, $name] = $this->parseCountryLine($line);
                if ($code === '' || $name === '') {
                    $errors[] = "Country line {$lineNumber}: invalid format.";
                    continue;
                }

                Country::updateOrCreate(
                    ['code' => $code],
                    ['name' => $name, 'is_active' => true]
                );
                $countryImported++;
            }

            foreach ($placeLines as $lineNumber => $line) {
                [$countryCode, $name, $type] = $this->parsePlaceLine($line);
                if ($countryCode === '' || $name === '') {
                    $errors[] = "Place line {$lineNumber}: invalid format.";
                    continue;
                }

                $country = Country::where('code', $countryCode)->first();
                if (!$country) {
                    $errors[] = "Place line {$lineNumber}: country {$countryCode} not found.";
                    continue;
                }

                Place::updateOrCreate(
                    [
                        'country_id' => $country->id,
                        'name' => $name,
                    ],
                    [
                        'type' => $type,
                        'is_active' => true,
                    ]
                );
                $placeImported++;
            }

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();

            return response()->json([
                'message' => 'Import failed.',
                'error' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Import finished.',
            'summary' => [
                'countries_imported' => $countryImported,
                'places_imported' => $placeImported,
                'errors_count' => count($errors),
            ],
            'errors' => $errors,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function normalizeImportLines(string $raw): array
    {
        $rows = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $normalized = [];

        foreach ($rows as $row) {
            $trimmed = trim($row);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            $normalized[] = $trimmed;
        }

        return array_values($normalized);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseCountryLine(string $line): array
    {
        $parts = preg_split('/\s*[|;,]\s*/', $line) ?: [];
        $code = strtoupper(trim((string) ($parts[0] ?? '')));
        $name = trim((string) ($parts[1] ?? ''));

        return [$code, $name];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function parsePlaceLine(string $line): array
    {
        $parts = preg_split('/\s*[|;,]\s*/', $line) ?: [];
        $countryCode = strtoupper(trim((string) ($parts[0] ?? '')));
        $name = trim((string) ($parts[1] ?? ''));
        $type = $this->normalizePlaceType((string) ($parts[2] ?? 'city'));

        return [$countryCode, $name, $type];
    }

    private function normalizePlaceType(string $type): string
    {
        $normalized = strtolower(trim($type));
        $allowed = ['city', 'town', 'village', 'settlement'];

        if (!in_array($normalized, $allowed, true)) {
            return 'city';
        }

        return $normalized;
    }
}


@extends('layouts.app')

@section('title', __('ui.search.title'))
@section('meta_description', __('ui.search.meta_description'))

@section('head')
    {{-- Breadcrumb Structured Data --}}
    <x-seo.structured-data
        type="breadcrumb"
        :breadcrumbs="[
            ['name' => __('ui.home.title'), 'url' => route('home', ['locale' => app()->getLocale()])],
            ['name' => __('ui.search.title'), 'url' => route('search.page', ['locale' => app()->getLocale()])]
        ]"
    />
@endsection

@section('content')
<main class="flex-1 bg-gradient-hero py-10 md:py-14">
    <div class="container mx-auto px-4 max-w-7xl space-y-8">
        <section class="rounded-2xl border border-border bg-card shadow-elegant p-6 md:p-8">
            <h1 class="text-3xl md:text-4xl font-serif font-bold text-primary">{{ __('ui.search.page_title') }}</h1>
            <p class="text-muted-foreground mt-2 max-w-3xl">{{ __('ui.search.page_subtitle') }}</p>
        </section>

        <section class="rounded-2xl border border-border bg-card shadow-elegant p-6 md:p-8">
            <h2 class="text-2xl font-serif font-semibold text-primary mb-5">{{ __('ui.search.filters_title') }}</h2>

            <form method="GET" action="{{ route('search.page') }}" class="space-y-6">
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div class="md:col-span-2 lg:col-span-4 space-y-2">
                        <label for="search-q" class="block text-sm font-medium">{{ __('ui.search.keyword') }}</label>
                        <input
                            id="search-q"
                            type="text"
                            name="q"
                            value="{{ $filters['q'] }}"
                            placeholder="{{ __('ui.search.keyword_placeholder') }}"
                            class="w-full h-11 px-3 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                    </div>

                    <div class="space-y-2">
                        <label for="search-birth-country-id" class="block text-sm font-medium">{{ __('ui.memorial_form.birth_country') }}</label>
                        <select
                            id="search-birth-country-id"
                            name="birth_country_id"
                            class="w-full h-11 px-3 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option value="">{{ __('ui.memorial_form.select_country') }}</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}" {{ (int) ($filters['birth_country_id'] ?? 0) === (int) $country->id ? 'selected' : '' }}>
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="search-birth-place-id" class="block text-sm font-medium">{{ __('ui.memorial_form.birth_place') }}</label>
                        @php
                            $birthPlaces = collect($placesByCountry[$filters['birth_country_id']] ?? []);
                        @endphp
                        <select
                            id="search-birth-place-id"
                            name="birth_place_id"
                            class="w-full h-11 px-3 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                            {{ $filters['birth_country_id'] ? '' : 'disabled' }}
                        >
                            <option value="">{{ $filters['birth_country_id'] ? __('ui.memorial_form.select_place') : __('ui.memorial_form.select_country_first') }}</option>
                            @foreach($birthPlaces as $place)
                                <option value="{{ $place['id'] }}" {{ (int) ($filters['birth_place_id'] ?? 0) === (int) $place['id'] ? 'selected' : '' }}>
                                    {{ $place['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="search-death-country-id" class="block text-sm font-medium">{{ __('ui.memorial_form.death_country') }}</label>
                        <select
                            id="search-death-country-id"
                            name="death_country_id"
                            class="w-full h-11 px-3 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option value="">{{ __('ui.memorial_form.select_country') }}</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}" {{ (int) ($filters['death_country_id'] ?? 0) === (int) $country->id ? 'selected' : '' }}>
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="search-death-place-id" class="block text-sm font-medium">{{ __('ui.memorial_form.death_place') }}</label>
                        @php
                            $deathPlaces = collect($placesByCountry[$filters['death_country_id']] ?? []);
                        @endphp
                        <select
                            id="search-death-place-id"
                            name="death_place_id"
                            class="w-full h-11 px-3 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                            {{ $filters['death_country_id'] ? '' : 'disabled' }}
                        >
                            <option value="">{{ $filters['death_country_id'] ? __('ui.memorial_form.select_place') : __('ui.memorial_form.select_country_first') }}</option>
                            @foreach($deathPlaces as $place)
                                <option value="{{ $place['id'] }}" {{ (int) ($filters['death_place_id'] ?? 0) === (int) $place['id'] ? 'selected' : '' }}>
                                    {{ $place['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="search-birth-year-from" class="block text-sm font-medium">{{ __('ui.search.birth_year_from') }}</label>
                        <input
                            id="search-birth-year-from"
                            type="number"
                            name="birth_year_from"
                            min="1800"
                            max="{{ $maxYear }}"
                            value="{{ $filters['birth_year_from'] ?? '' }}"
                            class="w-full h-11 px-3 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                    </div>

                    <div class="space-y-2">
                        <label for="search-birth-year-to" class="block text-sm font-medium">{{ __('ui.search.birth_year_to') }}</label>
                        <input
                            id="search-birth-year-to"
                            type="number"
                            name="birth_year_to"
                            min="1800"
                            max="{{ $maxYear }}"
                            value="{{ $filters['birth_year_to'] ?? '' }}"
                            class="w-full h-11 px-3 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                    </div>

                    <div class="space-y-2">
                        <label for="search-death-year-from" class="block text-sm font-medium">{{ __('ui.search.death_year_from') }}</label>
                        <input
                            id="search-death-year-from"
                            type="number"
                            name="death_year_from"
                            min="1800"
                            max="{{ $maxYear }}"
                            value="{{ $filters['death_year_from'] ?? '' }}"
                            class="w-full h-11 px-3 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                    </div>

                    <div class="space-y-2">
                        <label for="search-death-year-to" class="block text-sm font-medium">{{ __('ui.search.death_year_to') }}</label>
                        <input
                            id="search-death-year-to"
                            type="number"
                            name="death_year_to"
                            min="1800"
                            max="{{ $maxYear }}"
                            value="{{ $filters['death_year_to'] ?? '' }}"
                            class="w-full h-11 px-3 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                    </div>

                    <div class="md:col-span-2 space-y-2">
                        <label for="search-sort" class="block text-sm font-medium">{{ __('ui.search.sort') }}</label>
                        <select
                            id="search-sort"
                            name="sort"
                            class="w-full h-11 px-3 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option value="newest" {{ $filters['sort'] === 'newest' ? 'selected' : '' }}>{{ __('ui.search.sort_newest') }}</option>
                            <option value="oldest" {{ $filters['sort'] === 'oldest' ? 'selected' : '' }}>{{ __('ui.search.sort_oldest') }}</option>
                            <option value="name_asc" {{ $filters['sort'] === 'name_asc' ? 'selected' : '' }}>{{ __('ui.search.sort_name_asc') }}</option>
                            <option value="name_desc" {{ $filters['sort'] === 'name_desc' ? 'selected' : '' }}>{{ __('ui.search.sort_name_desc') }}</option>
                            <option value="death_desc" {{ $filters['sort'] === 'death_desc' ? 'selected' : '' }}>{{ __('ui.search.sort_death_desc') }}</option>
                        </select>
                    </div>
                </div>

                <div class="grid gap-3 md:grid-cols-3">
                    <label class="flex items-center gap-3 rounded-lg border border-border bg-background px-3 py-2">
                        <input
                            type="checkbox"
                            name="has_profile_image"
                            value="1"
                            class="h-4 w-4 rounded border-border"
                            {{ $filters['has_profile_image'] ? 'checked' : '' }}
                        />
                        <span class="text-sm">{{ __('ui.search.with_profile_image') }}</span>
                    </label>
                    <label class="flex items-center gap-3 rounded-lg border border-border bg-background px-3 py-2">
                        <input
                            type="checkbox"
                            name="has_gallery"
                            value="1"
                            class="h-4 w-4 rounded border-border"
                            {{ $filters['has_gallery'] ? 'checked' : '' }}
                        />
                        <span class="text-sm">{{ __('ui.search.with_gallery') }}</span>
                    </label>
                    <label class="flex items-center gap-3 rounded-lg border border-border bg-background px-3 py-2">
                        <input
                            type="checkbox"
                            name="has_video"
                            value="1"
                            class="h-4 w-4 rounded border-border"
                            {{ $filters['has_video'] ? 'checked' : '' }}
                        />
                        <span class="text-sm">{{ __('ui.search.with_video') }}</span>
                    </label>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center px-6 h-11 rounded-lg bg-gradient-accent text-accent-foreground font-semibold hover:opacity-90 transition-opacity"
                    >
                        {{ __('ui.search.apply_filters') }}
                    </button>
                    <a
                        href="{{ route('search.page') }}"
                        class="inline-flex items-center justify-center px-6 h-11 rounded-lg border border-border hover:bg-muted transition-colors font-medium"
                    >
                        {{ __('ui.search.clear_filters') }}
                    </a>
                </div>
            </form>
        </section>

        @php
            $activeFilters = [];

            if ($filters['q'] !== '') {
                $activeFilters[] = __('ui.search.keyword').': '.$filters['q'];
            }
            if ($filters['birth_country_id'] !== null) {
                $activeFilters[] = __('ui.memorial_form.birth_country').': '.($countryNames[$filters['birth_country_id']] ?? $filters['birth_country_id']);
            }
            if ($filters['birth_place_id'] !== null) {
                $activeFilters[] = __('ui.memorial_form.birth_place').': '.($placeNames[$filters['birth_place_id']] ?? $filters['birth_place_id']);
            }
            if ($filters['death_country_id'] !== null) {
                $activeFilters[] = __('ui.memorial_form.death_country').': '.($countryNames[$filters['death_country_id']] ?? $filters['death_country_id']);
            }
            if ($filters['death_place_id'] !== null) {
                $activeFilters[] = __('ui.memorial_form.death_place').': '.($placeNames[$filters['death_place_id']] ?? $filters['death_place_id']);
            }
            if ($filters['birth_year_from'] !== null) {
                $activeFilters[] = __('ui.search.birth_year_from').': '.$filters['birth_year_from'];
            }
            if ($filters['birth_year_to'] !== null) {
                $activeFilters[] = __('ui.search.birth_year_to').': '.$filters['birth_year_to'];
            }
            if ($filters['death_year_from'] !== null) {
                $activeFilters[] = __('ui.search.death_year_from').': '.$filters['death_year_from'];
            }
            if ($filters['death_year_to'] !== null) {
                $activeFilters[] = __('ui.search.death_year_to').': '.$filters['death_year_to'];
            }
            if ($filters['has_profile_image']) {
                $activeFilters[] = __('ui.search.with_profile_image');
            }
            if ($filters['has_gallery']) {
                $activeFilters[] = __('ui.search.with_gallery');
            }
            if ($filters['has_video']) {
                $activeFilters[] = __('ui.search.with_video');
            }
        @endphp

        @if($activeFilters !== [])
            <section class="rounded-2xl border border-border bg-card p-5">
                <h2 class="text-sm uppercase tracking-wide text-muted-foreground mb-3">{{ __('ui.search.active_filters') }}</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach($activeFilters as $activeFilter)
                        <span class="inline-flex items-center px-3 h-8 rounded-full bg-muted text-sm">{{ $activeFilter }}</span>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="rounded-2xl border border-border bg-card shadow-elegant p-6 md:p-8">
            <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
                <h2 class="text-2xl md:text-3xl font-serif font-semibold text-primary">{{ __('ui.search.results_title') }}</h2>
                <p class="text-sm text-muted-foreground">{{ __('ui.search.results_count', ['count' => $memorials->total()]) }}</p>
            </header>

            @if($memorials->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach($memorials as $memorial)
                        @php
                            $birthYear = $memorial->birth_date ? \Illuminate\Support\Carbon::parse($memorial->birth_date)->format('Y') : __('ui.search.empty_value');
                            $deathYear = $memorial->death_date ? \Illuminate\Support\Carbon::parse($memorial->death_date)->format('Y') : __('ui.search.empty_value');
                            $memorialProfileImageUrl = \App\Support\MediaUrl::normalize($memorial->profile_image_url);
                        @endphp
                        <a href="{{ route('memorial.profile', ['slug' => $memorial->slug]) }}" class="group block h-full">
                            <article class="h-full rounded-xl border border-border bg-background overflow-hidden hover:shadow-elegant transition-all duration-300 hover:-translate-y-1">
                                <div class="aspect-square bg-muted" style="aspect-ratio: 1 / 1;">
                                    @if($memorialProfileImageUrl)
                                        <img
                                            src="{{ $memorialProfileImageUrl }}"
                                            alt="{{ $memorial->first_name }} {{ $memorial->last_name }}"
                                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                        />
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-muted-foreground text-sm font-medium">
                                            {{ __('ui.search.badges.profile_image') }}
                                        </div>
                                    @endif
                                </div>
                                <div class="p-4 space-y-3">
                                    <h3 class="text-lg font-serif font-semibold text-primary line-clamp-2">
                                        {{ $memorial->first_name }} {{ $memorial->last_name }}
                                    </h3>

                                    <p class="text-sm text-muted-foreground">
                                        {{ __('ui.search.born') }}: {{ $birthYear }} • {{ __('ui.search.died') }}: {{ $deathYear }}
                                    </p>

                                    @if($memorial->birth_place || $memorial->death_place)
                                        <div class="text-sm text-muted-foreground space-y-1">
                                            @if($memorial->birth_place)
                                                <p>{{ __('ui.search.birth_place') }}: {{ $memorial->birth_place }}</p>
                                            @endif
                                            @if($memorial->death_place)
                                                <p>{{ __('ui.search.death_place') }}: {{ $memorial->death_place }}</p>
                                            @endif
                                        </div>
                                    @endif

                                    @if($memorial->biography)
                                        <p class="text-sm text-foreground/90 leading-relaxed">
                                            {{ \Illuminate\Support\Str::limit((string) $memorial->biography, 130) }}
                                        </p>
                                    @endif

                                    <div class="flex flex-wrap gap-2 pt-1">
                                        @if($memorialProfileImageUrl)
                                            <span class="inline-flex items-center px-2.5 h-7 rounded-full bg-muted text-xs">{{ __('ui.search.badges.profile_image') }}</span>
                                        @endif
                                        @if(($memorial->images_count ?? 0) > 0)
                                            <span class="inline-flex items-center px-2.5 h-7 rounded-full bg-muted text-xs">{{ __('ui.search.badges.gallery') }}</span>
                                        @endif
                                        @if(($memorial->videos_count ?? 0) > 0)
                                            <span class="inline-flex items-center px-2.5 h-7 rounded-full bg-muted text-xs">{{ __('ui.search.badges.videos') }}</span>
                                        @endif
                                    </div>

                                    <span class="inline-flex items-center justify-center w-full px-4 h-10 rounded-lg border border-border hover:bg-muted transition-colors text-sm font-medium">
                                        {{ __('ui.search.open_profile') }}
                                    </span>
                                </div>
                            </article>
                        </a>
                    @endforeach
                </div>

                @if($memorials->hasPages())
                    <div class="mt-8 pt-6 border-t border-border flex items-center justify-between gap-3">
                        @if($memorials->onFirstPage())
                            <span class="inline-flex items-center justify-center px-4 h-10 rounded-lg border border-border text-sm text-muted-foreground opacity-60">
                                {{ __('ui.search.pagination.previous') }}
                            </span>
                        @else
                            <a href="{{ $memorials->previousPageUrl() }}" class="inline-flex items-center justify-center px-4 h-10 rounded-lg border border-border hover:bg-muted transition-colors text-sm font-medium">
                                {{ __('ui.search.pagination.previous') }}
                            </a>
                        @endif

                        <p class="text-sm text-muted-foreground text-center">
                            {{ __('ui.search.pagination.page', ['current' => $memorials->currentPage(), 'last' => $memorials->lastPage()]) }}
                        </p>

                        @if($memorials->hasMorePages())
                            <a href="{{ $memorials->nextPageUrl() }}" class="inline-flex items-center justify-center px-4 h-10 rounded-lg border border-border hover:bg-muted transition-colors text-sm font-medium">
                                {{ __('ui.search.pagination.next') }}
                            </a>
                        @else
                            <span class="inline-flex items-center justify-center px-4 h-10 rounded-lg border border-border text-sm text-muted-foreground opacity-60">
                                {{ __('ui.search.pagination.next') }}
                            </span>
                        @endif
                    </div>
                @endif
            @else
                <div class="rounded-xl border border-dashed border-border bg-background p-10 text-center">
                    <p class="text-lg font-medium text-foreground">{{ __('ui.search.no_results') }}</p>
                    <p class="text-sm text-muted-foreground mt-2">{{ __('ui.search.reset_hint') }}</p>
                    <a
                        href="{{ route('search.page') }}"
                        class="inline-flex items-center justify-center mt-5 px-5 h-10 rounded-lg border border-border hover:bg-muted transition-colors text-sm font-medium"
                    >
                        {{ __('ui.search.clear_filters') }}
                    </a>
                </div>
            @endif
        </section>
    </div>
</main>
@push('scripts')
<script>
    // Track search query
    if (window.eventTracker) {
        const searchTerm = @json($filters['q'] ?? '');
        const resultsCount = @json($memorials->total());

        if (searchTerm || resultsCount > 0) {
            window.eventTracker.trackSearch({
                search_term: searchTerm || 'all',
                results_count: resultsCount,
                locale: @json(app()->getLocale())
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const placesByCountry = @json($placesByCountry);
        const labels = {
            selectPlace: @json(__('ui.memorial_form.select_place')),
            selectCountryFirst: @json(__('ui.memorial_form.select_country_first')),
        };

        const birthCountrySelect = document.getElementById('search-birth-country-id');
        const birthPlaceSelect = document.getElementById('search-birth-place-id');
        const deathCountrySelect = document.getElementById('search-death-country-id');
        const deathPlaceSelect = document.getElementById('search-death-place-id');

        if (!birthCountrySelect || !birthPlaceSelect || !deathCountrySelect || !deathPlaceSelect) {
            return;
        }

        function renderPlaceOptions(placeSelect, countryId, selectedValue) {
            const normalizedCountryId = String(countryId || '').trim();
            const placeOptions = placesByCountry[normalizedCountryId] || [];

            placeSelect.innerHTML = '';

            const placeholderOption = document.createElement('option');
            placeholderOption.value = '';
            placeholderOption.textContent = normalizedCountryId === '' ? labels.selectCountryFirst : labels.selectPlace;
            placeSelect.appendChild(placeholderOption);

            placeOptions.forEach((place) => {
                const option = document.createElement('option');
                option.value = String(place.id);
                option.textContent = String(place.name || '');
                placeSelect.appendChild(option);
            });

            placeSelect.disabled = normalizedCountryId === '';

            const normalizedSelectedValue = String(selectedValue || '').trim();
            if (normalizedSelectedValue !== '') {
                placeSelect.value = normalizedSelectedValue;
            }
        }

        renderPlaceOptions(birthPlaceSelect, birthCountrySelect.value, birthPlaceSelect.value);
        renderPlaceOptions(deathPlaceSelect, deathCountrySelect.value, deathPlaceSelect.value);

        birthCountrySelect.addEventListener('change', function () {
            renderPlaceOptions(birthPlaceSelect, birthCountrySelect.value, '');
        });

        deathCountrySelect.addEventListener('change', function () {
            renderPlaceOptions(deathPlaceSelect, deathCountrySelect.value, '');
        });
    });
</script>
@endpush
@endsection

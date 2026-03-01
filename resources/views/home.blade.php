@extends('layouts.app')

@section('title', __('ui.home.title'))

@section('head')
    {{-- Organization Structured Data --}}
    <x-seo.structured-data type="organization" />

    {{-- WebSite Structured Data with SearchAction --}}
    <x-seo.structured-data type="website" />
@endsection

@section('content')
<style>
.hero-section {
    background-position: 75% center;
}
@media (min-width: 768px) {
    .hero-section {
        background-position: center center;
    }
}
</style>
<section class="hero-section relative h-screen flex items-center justify-center bg-cover bg-no-repeat overflow-hidden bg-gray-900" style="background-image: url('/spomenar-pozadina.jpg'); min-height: 100vh; height: 100vh; width: 100%; background-size: cover;">
    <div class="absolute inset-0 bg-black/30 z-0"></div>

    <div class="container mx-auto px-4 relative z-10 pt-20">
        <div class="max-w-4xl mx-auto text-center animate-fade-in">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 border border-white/20 mb-8 backdrop-blur-sm">
                <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                <span class="text-sm font-medium text-white">{{ __('ui.digital_memorials') }}</span>
            </div>

            <h1 class="text-4xl md:text-5xl lg:text-6xl font-serif font-bold mb-6 text-white leading-tight drop-shadow-lg">
                {{ __('ui.home.hero_title') }}
            </h1>
            <p class="text-lg md:text-xl text-white/90 mb-10 max-w-2xl mx-auto leading-relaxed drop-shadow-md">
                {{ __('ui.home.hero_subtitle') }}
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a
                    href="{{ $heroSettings->cta_button_link ?? route('register') }}"
                    class="inline-flex items-center justify-center bg-gradient-accent hover:opacity-90 text-accent-foreground shadow-gold px-8 h-12 text-base rounded-lg font-semibold transition-opacity"
                >
                    {{ __('ui.home.cta_start') }}
                </a>
                <a
                    href="{{ $heroSettings->secondary_button_link ?? '#features' }}"
                    class="inline-flex items-center justify-center px-8 h-12 text-base border-2 border-white text-white rounded-lg font-semibold hover:bg-white/10 transition-colors backdrop-blur-sm"
                >
                    {{ __('ui.home.cta_more') }}
                </a>
            </div>
        </div>
    </div>
</section>

    <section id="features" class="py-24 bg-background relative" aria-labelledby="features-heading">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <p class="text-sm font-semibold text-accent uppercase tracking-wider mb-3">{{ __('ui.home.features_label') }}</p>
                <h2 id="features-heading" class="text-3xl md:text-4xl font-serif font-bold text-primary">
                    {{ __('ui.home.features_title') }}
                </h2>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <article class="border border-border/50 hover:shadow-elegant hover:border-accent/30 transition-all duration-300 group bg-card/50 backdrop-blur-sm rounded-lg">
                    <div class="pt-8 pb-6 px-6">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-accent flex items-center justify-center mb-5 shadow-gold group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-7 h-7 text-accent-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h4V3H4v4zm6 14h4v-4h-4v4zm-6 0h4v-4H4v4zm12 0h4v-4h-4v4zm0-10h4V7h-4v4zm0-8v2m-8 0V3m-2 8h12M5 21h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="font-serif font-semibold text-xl mb-3 text-foreground">{{ __('ui.home.feature_1_title') }}</h3>
                        <p class="text-muted-foreground leading-relaxed">
                            {{ __('ui.home.feature_1_desc') }}
                        </p>
                    </div>
                </article>

                <article class="border border-border/50 hover:shadow-elegant hover:border-accent/30 transition-all duration-300 group bg-card/50 backdrop-blur-sm rounded-lg">
                    <div class="pt-8 pb-6 px-6">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-accent flex items-center justify-center mb-5 shadow-gold group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-7 h-7 text-accent-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <h3 class="font-serif font-semibold text-xl mb-3 text-foreground">{{ __('ui.home.feature_2_title') }}</h3>
                        <p class="text-muted-foreground leading-relaxed">
                            {{ __('ui.home.feature_2_desc') }}
                        </p>
                    </div>
                </article>

                <article class="border border-border/50 hover:shadow-elegant hover:border-accent/30 transition-all duration-300 group bg-card/50 backdrop-blur-sm rounded-lg">
                    <div class="pt-8 pb-6 px-6">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-accent flex items-center justify-center mb-5 shadow-gold group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-7 h-7 text-accent-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                            </svg>
                        </div>
                        <h3 class="font-serif font-semibold text-xl mb-3 text-foreground">{{ __('ui.home.feature_3_title') }}</h3>
                        <p class="text-muted-foreground leading-relaxed">
                            {{ __('ui.home.feature_3_desc') }}
                        </p>
                    </div>
                </article>

                <article class="border border-border/50 hover:shadow-elegant hover:border-accent/30 transition-all duration-300 group bg-card/50 backdrop-blur-sm rounded-lg">
                    <div class="pt-8 pb-6 px-6">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-accent flex items-center justify-center mb-5 shadow-gold group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-7 h-7 text-accent-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.178 8.586a4 4 0 00-5.656 0L12 9.107l-.522-.521a4 4 0 10-5.656 5.656l.522.522L12 20.42l5.656-5.656.522-.522a4 4 0 000-5.656z" />
                            </svg>
                        </div>
                        <h3 class="font-serif font-semibold text-xl mb-3 text-foreground">{{ __('ui.home.feature_4_title') }}</h3>
                        <p class="text-muted-foreground leading-relaxed">
                            {{ __('ui.home.feature_4_desc') }}
                        </p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="py-24 bg-muted/30 relative overflow-hidden" aria-labelledby="how-it-works-heading">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-accent/5 via-transparent to-transparent pointer-events-none"></div>
        <div class="container mx-auto px-4 relative">
            <div class="text-center mb-16">
                <p class="text-sm font-semibold text-accent uppercase tracking-wider mb-3">{{ __('ui.home.how_label') }}</p>
                <h2 id="how-it-works-heading" class="text-3xl md:text-4xl font-serif font-bold text-primary">
                    {{ __('ui.home.how_title') }}
                </h2>
            </div>

            <ol class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto relative">
                <div class="hidden md:block absolute top-8 left-1/6 right-1/6 h-0.5 bg-gradient-to-r from-transparent via-accent/30 to-transparent"></div>

                <li class="text-center relative">
                    <div class="w-16 h-16 rounded-full bg-gradient-accent text-accent-foreground flex items-center justify-center text-2xl font-bold mx-auto mb-6 shadow-gold relative z-10 ring-4 ring-background">1</div>
                    <h3 class="font-serif font-semibold text-xl mb-3">{{ __('ui.home.step_1_title') }}</h3>
                    <p class="text-muted-foreground leading-relaxed">{{ __('ui.home.step_1_desc') }}</p>
                </li>
                <li class="text-center relative">
                    <div class="w-16 h-16 rounded-full bg-gradient-accent text-accent-foreground flex items-center justify-center text-2xl font-bold mx-auto mb-6 shadow-gold relative z-10 ring-4 ring-background">2</div>
                    <h3 class="font-serif font-semibold text-xl mb-3">{{ __('ui.home.step_2_title') }}</h3>
                    <p class="text-muted-foreground leading-relaxed">{{ __('ui.home.step_2_desc') }}</p>
                </li>
                <li class="text-center relative">
                    <div class="w-16 h-16 rounded-full bg-gradient-accent text-accent-foreground flex items-center justify-center text-2xl font-bold mx-auto mb-6 shadow-gold relative z-10 ring-4 ring-background">3</div>
                    <h3 class="font-serif font-semibold text-xl mb-3">{{ __('ui.home.step_3_title') }}</h3>
                    <p class="text-muted-foreground leading-relaxed">{{ __('ui.home.step_3_desc') }}</p>
                </li>
            </ol>
        </div>
    </section>

    <section class="py-24 bg-gradient-soft text-foreground relative overflow-hidden border-y border-border/60">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_bottom_right,_var(--tw-gradient-stops))] from-accent/12 via-transparent to-transparent pointer-events-none"></div>
        <div class="container mx-auto px-4 text-center relative">
            <h2 class="text-3xl md:text-4xl lg:text-5xl font-serif font-bold text-primary mb-6">{{ __('ui.home.cta_title') }}</h2>
            <p class="text-lg md:text-xl mb-10 text-muted-foreground max-w-2xl mx-auto">
                {{ __('ui.home.cta_desc') }}
            </p>
            <a href="{{ route('register') }}" class="inline-flex items-center justify-center shadow-gold px-10 h-12 text-base font-semibold hover:scale-105 transition-transform bg-gradient-accent text-accent-foreground rounded-lg">
                {{ __('ui.home.cta_button') }}
            </a>
        </div>
    </section>

    <section class="py-24 bg-background" aria-labelledby="recent-memorials-heading">
        <div class="container mx-auto px-4" x-data="{ tab: 'recent' }">
            <div class="flex justify-center mb-12">
                <div class="grid w-full max-w-md grid-cols-2 h-12 p-1 bg-muted/50 rounded-md">
                    <button
                        type="button"
                        @click="tab = 'recent'"
                        class="inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium transition-all"
                        :class="tab === 'recent' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground'"
                    >
                        {{ __('ui.home.recent_tab') }}
                    </button>
                    <a
                        href="{{ route('search.page') }}"
                        class="inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
                    >
                        {{ __('ui.home.search_tab') }}
                    </a>
                </div>
            </div>

            <div x-show="tab === 'recent'" x-cloak>
                <div class="text-center mb-8">
                    <h2 id="recent-memorials-heading" class="text-3xl md:text-4xl font-serif font-bold text-primary">
                        {{ __('ui.home.recent_title') }}
                    </h2>
                </div>

                @if($recentMemorials && $recentMemorials->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5">
                        @foreach($recentMemorials as $memorial)
                            @php
                                $memorialProfileImageUrl = \App\Support\MediaUrl::normalize($memorial->profile_image_url);
                                $memorialPlace = $memorial->death_place ?: $memorial->birth_place;
                            @endphp
                            <a href="{{ route('memorial.profile', ['slug' => $memorial->slug]) }}" class="group">
                                <article class="h-full rounded-xl border border-border/60 bg-card p-4 hover:shadow-elegant transition-all duration-300 hover:-translate-y-1">
                                    <div class="flex items-center gap-4">
                                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-lg overflow-hidden bg-muted shrink-0">
                                            @if($memorialProfileImageUrl)
                                                <img
                                                    src="{{ $memorialProfileImageUrl }}"
                                                    alt="{{ $memorial->first_name }} {{ $memorial->last_name }}"
                                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                                />
                                            @else
                                                <div class="w-full h-full flex items-center justify-center text-muted-foreground">
                                                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <h3 class="text-xl sm:text-2xl font-serif font-semibold text-primary leading-tight line-clamp-2">
                                                {{ $memorial->first_name }} {{ $memorial->last_name }}
                                            </h3>

                                            <div class="mt-2 space-y-1.5 text-sm sm:text-base text-muted-foreground">
                                                <p class="flex items-center gap-2">
                                                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                    <span>{{ \Carbon\Carbon::parse($memorial->birth_date)->format('Y') }} - {{ \Carbon\Carbon::parse($memorial->death_date)->format('Y') }}</span>
                                                </p>
                                                @if($memorialPlace)
                                                    <p class="flex items-center gap-2">
                                                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0L6.343 16.657a8 8 0 1111.314 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        </svg>
                                                        <span class="line-clamp-1">{{ $memorialPlace }}</span>
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="border border-dashed rounded-lg py-16 text-center border-border">
                        <p class="text-muted-foreground">{{ __('ui.home.no_memorials') }}</p>
                    </div>
                @endif
            </div>

            <div x-show="tab === 'search'" x-cloak class="max-w-3xl mx-auto">
                <form method="GET" action="{{ route('search.page') }}" class="mb-8">
                    <label for="memorial-search" class="sr-only">{{ __('ui.home.search_tab') }}</label>
                    <div class="flex gap-3">
                        <input
                            id="memorial-search"
                            type="text"
                            name="q"
                            value="{{ $searchQuery }}"
                            placeholder="{{ __('ui.home.search_placeholder') }}"
                            class="flex-1 px-4 h-12 border border-border rounded-lg bg-card focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center px-6 h-12 rounded-lg bg-gradient-accent text-accent-foreground font-semibold hover:opacity-90 transition-opacity"
                        >
                            {{ __('ui.buttons.search') }}
                        </button>
                    </div>
                </form>

                @if($searchQuery === '')
                    <div class="text-center py-10">
                        <p class="text-muted-foreground">{{ __('ui.home.search_prompt') }}</p>
                    </div>
                @elseif($searchResults->count() > 0)
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 md:gap-6">
                        @foreach($searchResults as $memorial)
                            @php
                                $searchProfileImageUrl = \App\Support\MediaUrl::normalize($memorial->profile_image_url);
                            @endphp
                            <a href="{{ route('memorial.profile', ['slug' => $memorial->slug]) }}" class="group">
                                <article class="h-full hover:shadow-elegant transition-all duration-300 hover:-translate-y-2 border border-border/50 rounded-lg overflow-hidden">
                                    <div class="relative w-full aspect-square overflow-hidden" style="aspect-ratio: 1 / 1;">
                                        @if($searchProfileImageUrl)
                                            <img
                                                src="{{ $searchProfileImageUrl }}"
                                                alt="{{ $memorial->first_name }} {{ $memorial->last_name }}"
                                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                            />
                                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                                            <div class="absolute bottom-0 left-0 right-0 p-3 md:p-4 text-white">
                                                <h3 class="text-sm md:text-lg font-serif font-semibold line-clamp-2 drop-shadow-md">
                                                    {{ $memorial->first_name }} {{ $memorial->last_name }}
                                                </h3>
                                                <div class="flex items-center gap-1.5 text-xs text-white/90 mt-1">
                                                    <svg class="h-3 w-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                    <span>{{ \Carbon\Carbon::parse($memorial->birth_date)->format('Y') }} - {{ \Carbon\Carbon::parse($memorial->death_date)->format('Y') }}</span>
                                                </div>
                                            </div>
                                        @else
                                            <div class="p-4 h-full flex flex-col justify-end">
                                                <h3 class="text-sm md:text-lg font-serif font-semibold mb-2 text-primary line-clamp-2">
                                                    {{ $memorial->first_name }} {{ $memorial->last_name }}
                                                </h3>
                                                <div class="text-xs text-muted-foreground">
                                                    {{ \Carbon\Carbon::parse($memorial->birth_date)->format('Y') }} - {{ \Carbon\Carbon::parse($memorial->death_date)->format('Y') }}
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </article>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="border border-dashed rounded-lg py-16 text-center border-border">
                        <p class="text-muted-foreground">{{ __('ui.home.no_results', ['query' => $searchQuery]) }}</p>
                    </div>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection

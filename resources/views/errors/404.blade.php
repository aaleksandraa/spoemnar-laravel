@extends('layouts.app')

@section('title', __('ui.errors.404.title'))

@section('head')
    {{-- Set meta robots to noindex, nofollow for 404 pages --}}
    <meta name="robots" content="noindex, nofollow">
@endsection

@section('content')
<div class="min-h-screen flex flex-col">
    <section class="py-24 bg-background relative overflow-hidden flex-1 flex items-center">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-accent/5 via-transparent to-transparent pointer-events-none"></div>

        <div class="container mx-auto px-4 relative">
            <div class="max-w-3xl mx-auto text-center">
                {{-- 404 Number --}}
                <div class="mb-8">
                    <h1 class="text-8xl md:text-9xl font-serif font-bold text-accent/20 select-none">404</h1>
                </div>

                {{-- Error Message --}}
                <h2 class="text-3xl md:text-4xl font-serif font-bold text-primary mb-4">
                    {{ __('ui.errors.404.heading') }}
                </h2>
                <p class="text-lg text-muted-foreground mb-10 max-w-xl mx-auto">
                    {{ __('ui.errors.404.message') }}
                </p>

                {{-- Search Box --}}
                <div class="mb-12">
                    <form method="GET" action="{{ route('search.page', ['locale' => app()->getLocale()]) }}" class="max-w-2xl mx-auto">
                        <label for="error-search" class="sr-only">{{ __('ui.errors.404.search_label') }}</label>
                        <div class="flex gap-3">
                            <input
                                id="error-search"
                                type="text"
                                name="q"
                                placeholder="{{ __('ui.errors.404.search_placeholder') }}"
                                class="flex-1 px-4 h-12 border border-border rounded-lg bg-card focus:outline-none focus:ring-2 focus:ring-ring"
                                autofocus
                            />
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center px-6 h-12 rounded-lg bg-gradient-accent text-accent-foreground font-semibold hover:opacity-90 transition-opacity"
                            >
                                {{ __('ui.buttons.search') }}
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Quick Links --}}
                <div class="space-y-6">
                    <h3 class="text-xl font-serif font-semibold text-primary">
                        {{ __('ui.errors.404.suggestions') }}
                    </h3>

                    <div class="flex flex-wrap justify-center gap-3">
                        <a
                            href="{{ route('home', ['locale' => app()->getLocale()]) }}"
                            class="inline-flex items-center gap-2 px-6 h-10 border border-border rounded-lg hover:bg-muted/50 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            {{ __('ui.nav.home') }}
                        </a>

                        <a
                            href="{{ route('search.page', ['locale' => app()->getLocale()]) }}"
                            class="inline-flex items-center gap-2 px-6 h-10 border border-border rounded-lg hover:bg-muted/50 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            {{ __('ui.errors.404.browse_memorials') }}
                        </a>

                        <a
                            href="{{ route('about', ['locale' => app()->getLocale()]) }}"
                            class="inline-flex items-center gap-2 px-6 h-10 border border-border rounded-lg hover:bg-muted/50 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ __('ui.nav.about') }}
                        </a>

                        <a
                            href="{{ route('contact', ['locale' => app()->getLocale()]) }}"
                            class="inline-flex items-center gap-2 px-6 h-10 border border-border rounded-lg hover:bg-muted/50 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            {{ __('ui.nav.contact') }}
                        </a>
                    </div>
                </div>

                {{-- Popular Memorials Section --}}
                @php
                    $popularMemorials = \App\Models\Memorial::where('is_public', true)
                        ->orderBy('created_at', 'desc')
                        ->limit(3)
                        ->get();
                @endphp

                @if($popularMemorials->count() > 0)
                    <div class="mt-16 pt-12 border-t border-border">
                        <h3 class="text-xl font-serif font-semibold text-primary mb-6">
                            {{ __('ui.errors.404.popular_memorials') }}
                        </h3>

                        <div class="grid md:grid-cols-3 gap-4">
                            @foreach($popularMemorials as $memorial)
                                @php
                                    $memorialProfileImageUrl = \App\Support\MediaUrl::normalize($memorial->profile_image_url);
                                @endphp
                                <a href="{{ route('memorial.profile', ['locale' => app()->getLocale(), 'slug' => $memorial->slug]) }}" class="group">
                                    <article class="h-full rounded-lg border border-border/60 bg-card p-4 hover:shadow-elegant transition-all duration-300 hover:-translate-y-1">
                                        <div class="flex items-center gap-3">
                                            <div class="w-12 h-12 rounded-lg overflow-hidden bg-muted shrink-0">
                                                @if($memorialProfileImageUrl)
                                                    <img
                                                        src="{{ $memorialProfileImageUrl }}"
                                                        alt="{{ $memorial->first_name }} {{ $memorial->last_name }}"
                                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                                    />
                                                @else
                                                    <div class="w-full h-full flex items-center justify-center text-muted-foreground">
                                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="min-w-0 flex-1 text-left">
                                                <h4 class="text-sm font-serif font-semibold text-primary leading-tight line-clamp-1">
                                                    {{ $memorial->first_name }} {{ $memorial->last_name }}
                                                </h4>
                                                <p class="text-xs text-muted-foreground mt-1">
                                                    {{ \Carbon\Carbon::parse($memorial->birth_date)->format('Y') }} - {{ \Carbon\Carbon::parse($memorial->death_date)->format('Y') }}
                                                </p>
                                            </div>
                                        </div>
                                    </article>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection

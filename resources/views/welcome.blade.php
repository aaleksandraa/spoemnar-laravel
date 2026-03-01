@extends('layouts.app')

@section('title', __('ui.welcome.title'))

@section('content')
<main class="flex-1">
    <section class="relative overflow-hidden bg-gradient-hero py-24 md:py-32">
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute -top-16 -left-10 h-72 w-72 rounded-full bg-accent/20 blur-3xl"></div>
            <div class="absolute -bottom-24 -right-10 h-80 w-80 rounded-full bg-primary/15 blur-3xl"></div>
        </div>

        <div class="container mx-auto px-4 relative z-10">
            <div class="mx-auto max-w-3xl text-center">
                <span class="inline-flex items-center rounded-full border border-accent/30 bg-accent/10 px-4 py-2 text-sm font-medium text-accent">
                    {{ __('ui.digital_memorials') }}
                </span>
                <h1 class="mt-6 text-4xl font-serif font-bold text-primary md:text-5xl lg:text-6xl">
                    {{ __('ui.welcome.hero_title') }}
                </h1>
                <p class="mt-5 text-lg leading-relaxed text-muted-foreground md:text-xl">
                    {{ __('ui.welcome.hero_desc') }}
                </p>
                <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
                    <a
                        href="{{ route('home') }}"
                        class="inline-flex h-12 items-center justify-center rounded-lg bg-gradient-accent px-8 font-semibold text-accent-foreground shadow-gold transition-opacity hover:opacity-90"
                    >
                        {{ __('ui.welcome.open_home') }}
                    </a>
                    <a
                        href="{{ route('register') }}"
                        class="inline-flex h-12 items-center justify-center rounded-lg border border-border bg-card px-8 font-semibold transition-colors hover:bg-muted"
                    >
                        {{ __('ui.welcome.register') }}
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-background py-16 md:py-24">
        <div class="container mx-auto px-4">
            <div class="grid gap-6 md:grid-cols-3">
                <article class="rounded-xl border border-border bg-card p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-elegant">
                    <h2 class="font-serif text-2xl font-semibold text-primary">{{ __('ui.welcome.card_1_title') }}</h2>
                    <p class="mt-3 text-muted-foreground">
                        {{ __('ui.welcome.card_1_desc') }}
                    </p>
                </article>
                <article class="rounded-xl border border-border bg-card p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-elegant">
                    <h2 class="font-serif text-2xl font-semibold text-primary">{{ __('ui.welcome.card_2_title') }}</h2>
                    <p class="mt-3 text-muted-foreground">
                        {{ __('ui.welcome.card_2_desc') }}
                    </p>
                </article>
                <article class="rounded-xl border border-border bg-card p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-elegant">
                    <h2 class="font-serif text-2xl font-semibold text-primary">{{ __('ui.welcome.card_3_title') }}</h2>
                    <p class="mt-3 text-muted-foreground">
                        {{ __('ui.welcome.card_3_desc') }}
                    </p>
                </article>
            </div>
        </div>
    </section>
</main>
@endsection

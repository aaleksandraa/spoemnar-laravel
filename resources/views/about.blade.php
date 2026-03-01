@extends('layouts.app')

@section('title', __('ui.about.title'))

@section('content')
<main class="flex-1">
    <section class="relative bg-gradient-hero py-24 md:py-32 overflow-hidden">
        <div class="absolute inset-0 opacity-10 pointer-events-none">
            <div class="absolute -top-10 -left-10 w-72 h-72 rounded-full blur-3xl bg-accent/30"></div>
            <div class="absolute -bottom-16 -right-10 w-80 h-80 rounded-full blur-3xl bg-primary/20"></div>
        </div>
        <div class="container mx-auto px-4 relative z-10">
            <div class="max-w-3xl mx-auto text-center">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-card/80 border border-border mb-6">
                    <span class="w-2 h-2 rounded-full bg-accent"></span>
                    <span class="text-sm font-medium text-accent">{{ __('ui.digital_memorials') }}</span>
                </div>
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-serif font-bold text-primary mb-6">
                    {{ __('ui.about.page_title') }}
                </h1>
                <p class="text-lg md:text-xl text-muted-foreground leading-relaxed">
                    {{ __('ui.about.hero_desc') }}
                </p>
            </div>
        </div>
    </section>

    <section class="py-16 md:py-24 bg-background">
        <div class="container mx-auto px-4 max-w-6xl">
            <div class="grid lg:grid-cols-2 gap-10 items-center">
                <div class="order-2 lg:order-1">
                    <div class="aspect-video rounded-2xl bg-gradient-accent shadow-elegant flex items-center justify-center">
                        <svg class="w-24 h-24 text-accent-foreground/80" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5A5.5 5.5 0 017.5 3 5.98 5.98 0 0112 5.09 5.98 5.98 0 0116.5 3 5.5 5.5 0 0122 8.5c0 3.78-3.4 6.86-8.55 11.54z"/>
                        </svg>
                    </div>
                </div>
                <div class="order-1 lg:order-2">
                    <p class="text-sm font-semibold uppercase tracking-wider text-accent mb-3">{{ __('ui.about.mission_label') }}</p>
                    <h2 class="text-3xl md:text-4xl font-serif font-bold text-primary mb-6">
                        {{ __('ui.about.mission_title') }}
                    </h2>
                    <p class="text-foreground leading-relaxed mb-4">
                        {{ __('ui.about.mission_text_1') }}
                    </p>
                    <p class="text-muted-foreground leading-relaxed">
                        {{ __('ui.about.mission_text_2') }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 md:py-24 bg-muted/30">
        <div class="container mx-auto px-4 max-w-6xl">
            <div class="text-center mb-12">
                <p class="text-sm font-semibold uppercase tracking-wider text-accent mb-3">{{ __('ui.about.features_label') }}</p>
                <h2 class="text-3xl md:text-4xl font-serif font-bold text-primary mb-4">
                    {{ __('ui.about.features_title') }}
                </h2>
                <p class="text-lg text-muted-foreground max-w-2xl mx-auto">
                    {{ __('ui.about.features_desc') }}
                </p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <article class="rounded-xl border border-border bg-card p-6 hover:shadow-elegant transition-all duration-300">
                    <div class="w-12 h-12 rounded-xl bg-gradient-accent text-accent-foreground flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h4V3H4v4zm6 14h4v-4h-4v4zm-6 0h4v-4H4v4zm12 0h4v-4h-4v4zm0-10h4V7h-4v4zm0-8v2m-8 0V3m-2 8h12M5 21h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="font-serif font-semibold text-xl text-primary mb-2">{{ __('ui.about.f1_title') }}</h3>
                    <p class="text-muted-foreground">{{ __('ui.about.f1_desc') }}</p>
                </article>

                <article class="rounded-xl border border-border bg-card p-6 hover:shadow-elegant transition-all duration-300">
                    <div class="w-12 h-12 rounded-xl bg-gradient-accent text-accent-foreground flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <h3 class="font-serif font-semibold text-xl text-primary mb-2">{{ __('ui.about.f2_title') }}</h3>
                    <p class="text-muted-foreground">{{ __('ui.about.f2_desc') }}</p>
                </article>

                <article class="rounded-xl border border-border bg-card p-6 hover:shadow-elegant transition-all duration-300">
                    <div class="w-12 h-12 rounded-xl bg-gradient-accent text-accent-foreground flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                    </div>
                    <h3 class="font-serif font-semibold text-xl text-primary mb-2">{{ __('ui.about.f3_title') }}</h3>
                    <p class="text-muted-foreground">{{ __('ui.about.f3_desc') }}</p>
                </article>

                <article class="rounded-xl border border-border bg-card p-6 hover:shadow-elegant transition-all duration-300">
                    <div class="w-12 h-12 rounded-xl bg-gradient-accent text-accent-foreground flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 1.343-3 3 0 1.306.835 2.417 2 2.83V17h2v-3.17c1.165-.413 2-1.524 2-2.83 0-1.657-1.343-3-3-3z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 9a7 7 0 1114 0c0 7-7 11-7 11S5 16 5 9z"/>
                        </svg>
                    </div>
                    <h3 class="font-serif font-semibold text-xl text-primary mb-2">{{ __('ui.about.f4_title') }}</h3>
                    <p class="text-muted-foreground">{{ __('ui.about.f4_desc') }}</p>
                </article>
            </div>
        </div>
    </section>

    <section class="py-20 md:py-28 bg-gradient-soft text-foreground relative overflow-hidden border-y border-border/60">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_bottom_right,_var(--tw-gradient-stops))] from-accent/12 via-transparent to-transparent pointer-events-none"></div>
        <div class="container mx-auto px-4 text-center relative z-10">
            <h2 class="text-3xl md:text-4xl lg:text-5xl font-serif font-bold text-primary mb-6">{{ __('ui.about.cta_title') }}</h2>
            <p class="text-lg md:text-xl text-muted-foreground max-w-2xl mx-auto mb-10">
                {{ __('ui.about.cta_desc') }}
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-8 h-12 rounded-lg bg-gradient-accent text-accent-foreground font-semibold hover:scale-105 transition-transform shadow-gold">
                    {{ __('ui.about.cta_register') }}
                </a>
                <a href="{{ route('contact') }}" class="inline-flex items-center justify-center px-8 h-12 rounded-lg border border-border bg-card font-semibold hover:bg-muted transition-colors">
                    {{ __('ui.nav.contact') }}
                </a>
            </div>
        </div>
    </section>
</main>
@endsection

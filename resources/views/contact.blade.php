@extends('layouts.app')

@section('title', __('ui.contact.title'))
@section('meta_description', __('ui.contact.meta_description'))

@section('content')
<main class="flex-1">
    <section class="relative bg-gradient-hero py-24 md:py-32 overflow-hidden">
        <div class="container mx-auto px-4 relative z-10">
            <div class="max-w-3xl mx-auto text-center">
                <h1 class="text-4xl md:text-5xl font-serif font-bold text-primary mb-6">
                    {{ __('ui.contact.page_title') }}
                </h1>
                <p class="text-lg md:text-xl text-muted-foreground leading-relaxed">
                    {{ __('ui.contact.hero_desc') }}
                </p>
            </div>
        </div>
    </section>

    <section class="py-16 md:py-20 bg-background">
        <div class="container mx-auto px-4 max-w-3xl">
            <article class="rounded-2xl border border-border bg-card p-8 md:p-10 shadow-elegant text-center">
                <h2 class="text-2xl md:text-3xl font-serif font-bold text-primary mb-4">
                    {{ __('ui.contact.support_title') }}
                </h2>
                <p class="text-muted-foreground leading-relaxed max-w-2xl mx-auto">
                    {{ __('ui.contact.support_desc') }}
                </p>

                <div class="mt-8">
                    <a
                        href="mailto:info@spomenar.com"
                        class="inline-flex items-center justify-center px-6 py-3 rounded-xl border border-accent/30 bg-accent/5 text-lg font-semibold text-accent hover:bg-accent/10 transition-colors"
                    >
                        info@spomenar.com
                    </a>
                </div>
            </article>
        </div>
    </section>
</main>
@endsection

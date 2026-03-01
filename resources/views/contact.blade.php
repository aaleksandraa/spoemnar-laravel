@extends('layouts.app')

@section('title', __('ui.contact.title'))

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
        <div class="container mx-auto px-4 max-w-6xl">
            <div class="grid lg:grid-cols-5 gap-8">
                <article class="lg:col-span-3 rounded-xl border border-border bg-card p-6 md:p-8 shadow-elegant">
                    <h2 class="text-2xl md:text-3xl font-serif font-bold text-primary mb-2">
                        {{ __('ui.contact.form_title') }}
                    </h2>
                    <p class="text-muted-foreground mb-6">
                        {{ __('ui.contact.form_desc') }}
                    </p>

                    @if(session('success'))
                        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('contact.submit') }}" method="POST" class="space-y-5" id="contactForm">
                        @csrf

                        <div class="grid sm:grid-cols-2 gap-4">
                            <div class="space-y-2 sm:col-span-2">
                                <label for="name" class="block text-sm font-medium text-foreground">{{ __('ui.contact.name') }} *</label>
                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    value="{{ old('name') }}"
                                    required
                                    class="w-full h-12 px-4 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                                    placeholder="{{ __('ui.contact.name') }}"
                                />
                                @error('name')
                                    <p class="text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-2">
                                <label for="email" class="block text-sm font-medium text-foreground">{{ __('ui.contact.email') }} *</label>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    class="w-full h-12 px-4 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                                    placeholder="you@email.com"
                                />
                                @error('email')
                                    <p class="text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-2">
                                <label for="subject" class="block text-sm font-medium text-foreground">{{ __('ui.contact.subject') }} *</label>
                                <input
                                    type="text"
                                    id="subject"
                                    name="subject"
                                    value="{{ old('subject') }}"
                                    required
                                    class="w-full h-12 px-4 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                                    placeholder="{{ __('ui.contact.subject') }}"
                                />
                                @error('subject')
                                    <p class="text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label for="message" class="block text-sm font-medium text-foreground">{{ __('ui.contact.message') }} *</label>
                            <textarea
                                id="message"
                                name="message"
                                rows="6"
                                required
                                class="w-full px-4 py-3 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring resize-y"
                                placeholder="{{ __('ui.contact.message') }}..."
                            >{{ old('message') }}</textarea>
                            <p class="text-xs text-muted-foreground">{{ __('ui.contact.message_hint') }}</p>
                            @error('message')
                                <p class="text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <button
                            type="submit"
                            id="contactSubmitBtn"
                            class="inline-flex items-center justify-center px-6 h-12 rounded-lg bg-gradient-accent text-accent-foreground font-semibold hover:opacity-90 transition-opacity"
                        >
                            {{ __('ui.contact.submit') }}
                        </button>
                    </form>
                </article>

                <aside class="lg:col-span-2 space-y-4">
                    <article class="rounded-xl border border-border bg-card p-5">
                        <h3 class="font-serif font-semibold text-xl text-primary mb-2">{{ __('ui.contact.email') }}</h3>
                        <a href="mailto:info@spomenar.com" class="block text-muted-foreground hover:text-accent transition-colors">info@spomenar.com</a>
                        <a href="mailto:podrska@spomenar.com" class="block text-muted-foreground hover:text-accent transition-colors">podrska@spomenar.com</a>
                    </article>

                    <article class="rounded-xl border border-border bg-card p-5">
                        <h3 class="font-serif font-semibold text-xl text-primary mb-2">{{ __('ui.contact.phone') }}</h3>
                        <a href="tel:+381111234567" class="block text-muted-foreground hover:text-accent transition-colors">+381 11 123 4567</a>
                        <p class="text-sm text-muted-foreground mt-1">{{ __('ui.contact.working_hours') }}</p>
                    </article>

                    <article class="rounded-xl border border-border bg-card p-5">
                        <h3 class="font-serif font-semibold text-xl text-primary mb-2">{{ __('ui.contact.address_title') }}</h3>
                        <p class="text-muted-foreground">Kneza Milosa 10</p>
                        <p class="text-muted-foreground">11000 Beograd, Srbija</p>
                    </article>

                    <article class="rounded-xl border border-accent/30 bg-accent/5 p-5">
                        <h3 class="font-semibold text-primary mb-2">{{ __('ui.contact.support_title') }}</h3>
                        <p class="text-sm text-muted-foreground">
                            {{ __('ui.contact.support_desc') }}
                        </p>
                    </article>
                </aside>
            </div>
        </div>
    </section>
</main>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('contactForm');
        const submitBtn = document.getElementById('contactSubmitBtn');
        if (!form || !submitBtn) return;

        form.addEventListener('submit', function () {
            // Track form submission
            if (window.eventTracker) {
                window.eventTracker.trackFormSubmit({
                    form_type: 'contact',
                    locale: @json(app()->getLocale()),
                    success: true
                });
            }

            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
            submitBtn.textContent = @json(__('ui.contact.sending'));
        });
    });
</script>
@endpush

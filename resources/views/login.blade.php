@extends('layouts.app')

@section('title', __('ui.auth.login_title'))

@section('content')
<main class="flex-1 bg-gradient-hero py-12 md:py-20">
    <div class="container mx-auto px-4 max-w-md">
        <article class="rounded-2xl border border-border bg-card shadow-elegant p-6 md:p-8">
            <header class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-accent/15 text-accent mb-4">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <h1 class="text-3xl md:text-4xl font-serif font-bold text-primary mb-2">{{ __('ui.auth.login_title') }}</h1>
                <p class="text-muted-foreground">{{ __('ui.auth.login_desc') }}</p>
            </header>

            <div id="loginMessage" class="hidden mb-6 rounded-lg border p-4 text-sm"></div>

            <form id="loginForm" class="space-y-5" novalidate>
                @csrf
                <div class="space-y-2">
                    <label for="email" class="block text-sm font-medium text-foreground">{{ __('ui.auth.email') }}</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        required
                        autocomplete="email"
                        class="w-full h-12 px-4 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        placeholder="you@email.com"
                    />
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-3">
                        <label for="password" class="block text-sm font-medium text-foreground">{{ __('ui.auth.password') }}</label>
                        <a href="{{ route('password.forgot', ['locale' => app()->getLocale()]) }}" class="text-xs text-accent hover:underline">{{ __('ui.auth.forgot_password') }}</a>
                    </div>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="w-full h-12 px-4 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        placeholder="{{ __('ui.auth.password') }}"
                    />
                </div>

                <div class="flex items-center justify-between">
                    <label class="inline-flex items-center gap-2 text-sm text-muted-foreground">
                        <input type="checkbox" id="remember" class="rounded border-border">
                        {{ __('ui.auth.remember_me') }}
                    </label>
                    <a href="{{ route('contact') }}" class="text-sm text-accent hover:underline">{{ __('ui.auth.need_help') }}</a>
                </div>

                <button
                    type="submit"
                    id="loginSubmit"
                    class="w-full h-12 rounded-lg bg-gradient-accent text-accent-foreground font-semibold hover:opacity-90 transition-opacity"
                >
                    {{ __('ui.auth.login_button') }}
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-muted-foreground">
                {{ __('ui.auth.no_account') }}
                <a href="{{ route('register') }}" class="text-accent font-medium hover:underline">{{ __('ui.auth.go_register') }}</a>
            </p>
        </article>
    </div>
</main>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('loginForm');
        const submit = document.getElementById('loginSubmit');
        const message = document.getElementById('loginMessage');

        function showMessage(type, text) {
            message.classList.remove('hidden', 'border-red-200', 'bg-red-50', 'text-red-700', 'border-green-200', 'bg-green-50', 'text-green-700');
            if (type === 'error') {
                message.classList.add('border-red-200', 'bg-red-50', 'text-red-700');
            } else {
                message.classList.add('border-green-200', 'bg-green-50', 'text-green-700');
            }
            message.textContent = text;
        }

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            submit.disabled = true;
            submit.classList.add('opacity-70', 'cursor-not-allowed');
            submit.textContent = @json(__('ui.auth.logging_in'));
            message.classList.add('hidden');

            const payload = {
                email: document.getElementById('email').value.trim(),
                password: document.getElementById('password').value,
            };

            try {
                const response = await fetch('/api/v1/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    credentials: 'include',
                    body: JSON.stringify(payload),
                });

                const data = await response.json();
                if (!response.ok) {
                    const errorText = data?.message || (data?.errors ? Object.values(data.errors).flat().join(' ') : @json(__('ui.auth.login_failed')));
                    showMessage('error', errorText);
                    return;
                }

                showMessage('success', @json(__('ui.auth.login_success')));
                setTimeout(() => {
                    window.location.href = @json(route('dashboard'));
                }, 700);
            } catch (_error) {
                showMessage('error', @json(__('ui.auth.connection_error')));
            } finally {
                submit.disabled = false;
                submit.classList.remove('opacity-70', 'cursor-not-allowed');
                submit.textContent = @json(__('ui.auth.login_button'));
            }
        });
    });
</script>
@endpush

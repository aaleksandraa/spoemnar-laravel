@extends('layouts.app')

@section('title', __('ui.auth.reset_password_title'))
@section('meta_description', __('ui.auth.reset_password_desc'))

@php
    $currentLocale = app()->getLocale();
@endphp

@section('content')
<main class="flex-1 bg-gradient-hero py-12 md:py-20">
    <div class="container mx-auto px-4 max-w-md">
        <article class="rounded-2xl border border-border bg-card shadow-elegant p-6 md:p-8">
            <header class="text-center mb-8">
                <h1 class="text-3xl md:text-4xl font-serif font-bold text-primary mb-2">{{ __('ui.auth.reset_password_title') }}</h1>
                <p class="text-muted-foreground">{{ __('ui.auth.reset_password_desc') }}</p>
            </header>

            <div id="resetPasswordMessage" class="hidden mb-6 rounded-lg border p-4 text-sm"></div>

            <form id="resetPasswordForm" class="space-y-5" novalidate>
                @csrf
                <input type="hidden" id="resetToken" value="{{ $token ?? '' }}">

                <div class="space-y-2">
                    <label for="resetEmail" class="block text-sm font-medium text-foreground">{{ __('ui.auth.email') }}</label>
                    <input
                        type="email"
                        id="resetEmail"
                        name="email"
                        required
                        autocomplete="email"
                        value="{{ $email ?? '' }}"
                        class="w-full h-12 px-4 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        placeholder="you@email.com"
                    />
                </div>

                <div class="space-y-2">
                    <label for="resetPassword" class="block text-sm font-medium text-foreground">{{ __('ui.auth.password') }}</label>
                    <input
                        type="password"
                        id="resetPassword"
                        name="password"
                        required
                        minlength="12"
                        autocomplete="new-password"
                        class="w-full h-12 px-4 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        placeholder="{{ __('ui.auth.password_min') }}"
                    />
                </div>

                <div class="space-y-2">
                    <label for="resetPasswordConfirm" class="block text-sm font-medium text-foreground">{{ __('ui.auth.password_confirm') }}</label>
                    <input
                        type="password"
                        id="resetPasswordConfirm"
                        name="password_confirmation"
                        required
                        minlength="12"
                        autocomplete="new-password"
                        class="w-full h-12 px-4 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        placeholder="{{ __('ui.auth.password_confirm') }}"
                    />
                </div>

                <button
                    type="submit"
                    id="resetPasswordSubmit"
                    class="w-full h-12 rounded-lg bg-gradient-accent text-accent-foreground font-semibold hover:opacity-90 transition-opacity"
                >
                    {{ __('ui.auth.reset_password_button') }}
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-muted-foreground">
                <a href="{{ route('login', ['locale' => $currentLocale]) }}" class="text-accent font-medium hover:underline">{{ __('ui.auth.back_to_login') }}</a>
            </p>
        </article>
    </div>
</main>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('resetPasswordForm');
        const submit = document.getElementById('resetPasswordSubmit');
        const message = document.getElementById('resetPasswordMessage');
        const loginUrl = @json(route('login', ['locale' => $currentLocale]));
        const locale = @json($currentLocale);

        function showMessage(type, text) {
            message.classList.remove('hidden', 'border-red-200', 'bg-red-50', 'text-red-700', 'border-green-200', 'bg-green-50', 'text-green-700');
            if (type === 'error') {
                message.classList.add('border-red-200', 'bg-red-50', 'text-red-700');
            } else {
                message.classList.add('border-green-200', 'bg-green-50', 'text-green-700');
            }
            // Preserve line breaks in error messages
            message.style.whiteSpace = 'pre-line';
            message.textContent = text;
        }

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            submit.disabled = true;
            submit.classList.add('opacity-70', 'cursor-not-allowed');
            message.classList.add('hidden');

            const password = document.getElementById('resetPassword').value;
            const passwordConfirmation = document.getElementById('resetPasswordConfirm').value;
            if (password !== passwordConfirmation) {
                showMessage('error', @json(__('ui.auth.password_mismatch')));
                submit.disabled = false;
                submit.classList.remove('opacity-70', 'cursor-not-allowed');
                return;
            }

            try {
                const response = await fetch('/api/v1/reset-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        token: document.getElementById('resetToken').value,
                        email: document.getElementById('resetEmail').value.trim(),
                        password,
                        password_confirmation: passwordConfirmation,
                        locale,
                    }),
                });

                const data = await response.json();
                if (!response.ok) {
                    let errorText = data?.message || @json(__('ui.auth.password_reset_failed'));

                    // If there are validation errors, display them as a list
                    if (data?.errors) {
                        const errorMessages = [];
                        for (const field in data.errors) {
                            const fieldErrors = data.errors[field];
                            if (Array.isArray(fieldErrors)) {
                                errorMessages.push(...fieldErrors);
                            } else {
                                errorMessages.push(fieldErrors);
                            }
                        }
                        if (errorMessages.length > 0) {
                            errorText = '- ' + errorMessages.join('\n- ');
                        }
                    }

                    showMessage('error', errorText);
                    return;
                }

                showMessage('success', data?.message || @json(__('ui.auth.password_reset_success')));
                setTimeout(() => {
                    window.location.href = loginUrl;
                }, 900);
            } catch (_error) {
                showMessage('error', @json(__('ui.auth.connection_error')));
            } finally {
                submit.disabled = false;
                submit.classList.remove('opacity-70', 'cursor-not-allowed');
            }
        });
    });
</script>
@endpush

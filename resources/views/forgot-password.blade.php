@extends('layouts.app')

@section('title', __('ui.auth.forgot_password_title'))
@section('meta_description', __('ui.auth.forgot_password_desc'))

@php
    $currentLocale = app()->getLocale();
@endphp

@section('content')
<main class="flex-1 bg-gradient-hero py-12 md:py-20">
    <div class="container mx-auto px-4 max-w-md">
        <article class="rounded-2xl border border-border bg-card shadow-elegant p-6 md:p-8">
            <header class="text-center mb-8">
                <h1 class="text-3xl md:text-4xl font-serif font-bold text-primary mb-2">{{ __('ui.auth.forgot_password_title') }}</h1>
                <p class="text-muted-foreground">{{ __('ui.auth.forgot_password_desc') }}</p>
            </header>

            <div id="forgotPasswordMessage" class="hidden mb-6 rounded-lg border p-4 text-sm"></div>

            <form id="forgotPasswordForm" class="space-y-5" novalidate>
                @csrf
                <div class="space-y-2">
                    <label for="forgotEmail" class="block text-sm font-medium text-foreground">{{ __('ui.auth.email') }}</label>
                    <input
                        type="email"
                        id="forgotEmail"
                        name="email"
                        required
                        autocomplete="email"
                        class="w-full h-12 px-4 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        placeholder="you@email.com"
                    />
                </div>

                <button
                    type="submit"
                    id="forgotPasswordSubmit"
                    class="w-full h-12 rounded-lg bg-gradient-accent text-accent-foreground font-semibold hover:opacity-90 transition-opacity"
                >
                    {{ __('ui.auth.send_reset_link') }}
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
        const form = document.getElementById('forgotPasswordForm');
        const submit = document.getElementById('forgotPasswordSubmit');
        const message = document.getElementById('forgotPasswordMessage');
        const locale = @json($currentLocale);

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
            message.classList.add('hidden');

            try {
                const response = await fetch('/api/v1/forgot-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        email: document.getElementById('forgotEmail').value.trim(),
                        locale,
                    }),
                });

                const data = await response.json();
                if (!response.ok) {
                    const errorText = data?.message || (data?.errors ? Object.values(data.errors).flat().join(' ') : @json(__('ui.auth.reset_link_failed')));
                    showMessage('error', errorText);
                    return;
                }

                showMessage('success', data?.message || @json(__('ui.auth.reset_link_sent')));
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

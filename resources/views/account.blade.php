@extends('layouts.app')

@section('title', __('ui.account.title'))
@section('meta_description', __('ui.account.meta_description'))

@php
    $currentLocale = app()->getLocale();
    $loginUrl = route('login', ['locale' => $currentLocale]);
    $dashboardUrl = route('dashboard', ['locale' => $currentLocale]);
    $baseUrl = url('/');
    $availableLocales = [
        'sr' => __('ui.languages.sr'),
        'hr' => __('ui.languages.hr'),
        'bs' => __('ui.languages.bs'),
        'de' => __('ui.languages.de'),
        'en' => __('ui.languages.en'),
        'it' => __('ui.languages.it'),
    ];
@endphp

@section('content')
<main class="flex-1 bg-gradient-hero py-10 md:py-14">
    <div class="container mx-auto px-4 max-w-5xl space-y-8">
        <section class="rounded-2xl border border-border bg-card shadow-elegant p-6 md:p-8">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5">
                <div class="space-y-3">
                    <p class="inline-flex items-center px-3 py-1 rounded-full bg-accent/10 text-accent text-xs font-semibold uppercase tracking-[0.18em]">
                        {{ __('ui.nav.profile') }}
                    </p>
                    <div>
                        <h1 class="text-3xl md:text-4xl font-serif font-bold text-primary">{{ __('ui.account.title') }}</h1>
                        <p class="text-muted-foreground mt-2">{{ __('ui.account.subtitle') }}</p>
                    </div>
                    <p id="accountIdentity" class="hidden text-sm text-muted-foreground"></p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ $dashboardUrl }}" class="inline-flex items-center justify-center px-5 h-11 rounded-lg border border-border hover:bg-muted transition-colors">
                        {{ __('ui.account.back_to_dashboard') }}
                    </a>
                </div>
            </div>
        </section>

        <div id="accountAlert" class="hidden rounded-xl border p-4 text-sm"></div>

        <form id="accountForm" class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]" novalidate>
            <section class="rounded-2xl border border-border bg-card shadow-elegant p-6 md:p-8">
                <header class="mb-6">
                    <h2 class="text-2xl font-serif font-semibold text-primary">{{ __('ui.account.overview_title') }}</h2>
                    <p class="text-sm text-muted-foreground mt-2">{{ __('ui.account.overview_desc') }}</p>
                </header>

                <div class="space-y-5">
                    <div class="space-y-2">
                        <label for="accountFullName" class="block text-sm font-medium text-foreground">{{ __('ui.auth.full_name') }}</label>
                        <input
                            type="text"
                            id="accountFullName"
                            name="full_name"
                            autocomplete="name"
                            class="w-full h-12 px-4 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                    </div>

                    <div class="space-y-2">
                        <label for="accountEmail" class="block text-sm font-medium text-foreground">{{ __('ui.auth.email') }}</label>
                        <input
                            type="email"
                            id="accountEmail"
                            name="email"
                            autocomplete="email"
                            class="w-full h-12 px-4 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                        <p class="text-xs text-muted-foreground">{{ __('ui.account.email_help') }}</p>
                    </div>

                    <div class="space-y-2">
                        <label for="accountPreferredLocale" class="block text-sm font-medium text-foreground">{{ __('ui.account.preferred_language') }}</label>
                        <select
                            id="accountPreferredLocale"
                            name="preferred_locale"
                            class="w-full h-12 px-4 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            @foreach($availableLocales as $localeCode => $localeLabel)
                                <option value="{{ $localeCode }}">{{ $localeLabel }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-muted-foreground">{{ __('ui.account.locale_help') }}</p>
                    </div>

                    <div class="pt-2">
                        <button
                            type="submit"
                            id="accountSubmit"
                            class="inline-flex items-center justify-center px-5 h-11 rounded-lg bg-gradient-accent text-accent-foreground font-semibold hover:opacity-90 transition-opacity"
                        >
                            {{ __('ui.account.save') }}
                        </button>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-border bg-card shadow-elegant p-6 md:p-8">
                <header class="mb-6">
                    <h2 class="text-2xl font-serif font-semibold text-primary">{{ __('ui.account.security_title') }}</h2>
                    <p class="text-sm text-muted-foreground mt-2">{{ __('ui.account.security_desc') }}</p>
                </header>

                <div class="space-y-5">
                    <div class="space-y-2">
                        <label for="accountCurrentPassword" class="block text-sm font-medium text-foreground">{{ __('ui.account.current_password') }}</label>
                        <input
                            type="password"
                            id="accountCurrentPassword"
                            name="current_password"
                            autocomplete="current-password"
                            class="w-full h-12 px-4 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                        <p class="text-xs text-muted-foreground">{{ __('ui.account.current_password_hint') }}</p>
                    </div>

                    <div class="space-y-2">
                        <label for="accountNewPassword" class="block text-sm font-medium text-foreground">{{ __('ui.account.new_password') }}</label>
                        <input
                            type="password"
                            id="accountNewPassword"
                            name="new_password"
                            autocomplete="new-password"
                            class="w-full h-12 px-4 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                    </div>

                    <div class="space-y-2">
                        <label for="accountNewPasswordConfirmation" class="block text-sm font-medium text-foreground">{{ __('ui.account.new_password_confirm') }}</label>
                        <input
                            type="password"
                            id="accountNewPasswordConfirmation"
                            name="new_password_confirmation"
                            autocomplete="new-password"
                            class="w-full h-12 px-4 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                        <p class="text-xs text-muted-foreground">{{ __('ui.account.password_hint') }}</p>
                    </div>
                </div>
            </section>
        </form>
    </div>
</main>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const token = localStorage.getItem('auth_token') || '';
        const currentLocale = @json($currentLocale);
        const loginUrl = @json($loginUrl);
        const baseUrl = @json($baseUrl);
        const labels = {
            loadError: @json(__('ui.account.load_error')),
            saveSuccess: @json(__('ui.account.save_success')),
            redirectingLocale: @json(__('ui.account.redirecting_locale')),
        };

        const alertBox = document.getElementById('accountAlert');
        const identityEl = document.getElementById('accountIdentity');
        const form = document.getElementById('accountForm');
        const submit = document.getElementById('accountSubmit');
        const fullNameInput = document.getElementById('accountFullName');
        const emailInput = document.getElementById('accountEmail');
        const preferredLocaleInput = document.getElementById('accountPreferredLocale');
        const currentPasswordInput = document.getElementById('accountCurrentPassword');
        const newPasswordInput = document.getElementById('accountNewPassword');
        const newPasswordConfirmationInput = document.getElementById('accountNewPasswordConfirmation');

        function showAlert(type, text) {
            alertBox.classList.remove('hidden', 'border-red-200', 'bg-red-50', 'text-red-700', 'border-green-200', 'bg-green-50', 'text-green-700');
            if (type === 'error') {
                alertBox.classList.add('border-red-200', 'bg-red-50', 'text-red-700');
            } else {
                alertBox.classList.add('border-green-200', 'bg-green-50', 'text-green-700');
            }
            alertBox.style.whiteSpace = 'pre-line';
            alertBox.textContent = text;
        }

        function hideAlert() {
            alertBox.classList.add('hidden');
        }

        function localizedAccountUrl(locale) {
            return `${baseUrl}/${encodeURIComponent(locale)}/account`;
        }

        function isAuthStatus(status) {
            return Number(status) === 401 || Number(status) === 403;
        }

        async function apiRequest(url, options = {}) {
            const headers = Object.assign(
                {
                    Accept: 'application/json',
                    Authorization: `Bearer ${token}`,
                },
                options.headers || {}
            );

            if (!(options.body instanceof FormData)) {
                headers['Content-Type'] = headers['Content-Type'] || 'application/json';
            }

            const response = await fetch(url, Object.assign({}, options, { headers }));
            let payload = null;

            try {
                payload = await response.json();
            } catch (_error) {
                payload = null;
            }

            if (!response.ok) {
                const validationErrors = payload?.errors ? Object.values(payload.errors).flat().join('\n') : '';
                const message = validationErrors || payload?.message || labels.loadError;
                const error = new Error(message);
                error.status = response.status;
                throw error;
            }

            return payload;
        }

        function populateForm(user) {
            const profile = user?.profile || {};

            fullNameInput.value = String(profile?.full_name || '');
            emailInput.value = String(user?.email || '');
            preferredLocaleInput.value = String(profile?.preferred_locale || currentLocale || 'bs');
            currentPasswordInput.value = '';
            newPasswordInput.value = '';
            newPasswordConfirmationInput.value = '';

            const identity = String(profile?.full_name || user?.email || '').trim();
            if (identity !== '') {
                identityEl.classList.remove('hidden');
                identityEl.textContent = identity;
            }
        }

        async function initializeAccount() {
            if (!token) {
                window.location.href = loginUrl;
                return;
            }

            try {
                const response = await apiRequest(`/api/v1/account?lang=${encodeURIComponent(currentLocale)}`);
                populateForm(response?.user || null);
            } catch (error) {
                if (isAuthStatus(error?.status)) {
                    localStorage.removeItem('auth_token');
                    localStorage.removeItem('user');
                    window.location.href = loginUrl;
                    return;
                }

                showAlert('error', error.message || labels.loadError);
            }
        }

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            hideAlert();

            submit.disabled = true;
            submit.classList.add('opacity-70', 'cursor-not-allowed');
            submit.textContent = @json(__('ui.account.saving'));

            const nextLocale = String(preferredLocaleInput.value || currentLocale || 'bs').trim();
            const normalizedEmail = String(emailInput.value || '').trim().toLowerCase();
            const payload = {
                locale: currentLocale,
                full_name: String(fullNameInput.value || '').trim(),
                email: normalizedEmail,
                preferred_locale: nextLocale,
            };

            if (String(currentPasswordInput.value || '') !== '') {
                payload.current_password = currentPasswordInput.value;
            }

            if (String(newPasswordInput.value || '') !== '') {
                payload.new_password = newPasswordInput.value;
                payload.new_password_confirmation = newPasswordConfirmationInput.value;
            }

            try {
                const response = await apiRequest('/api/v1/account', {
                    method: 'PUT',
                    body: JSON.stringify(payload),
                });

                const user = response?.user || null;
                if (user) {
                    localStorage.setItem('user', JSON.stringify(user));
                    populateForm(user);
                }

                const localeChanged = nextLocale !== '' && nextLocale !== currentLocale;
                showAlert('success', localeChanged ? `${labels.saveSuccess}\n${labels.redirectingLocale}` : labels.saveSuccess);

                if (localeChanged) {
                    setTimeout(function () {
                        window.location.href = localizedAccountUrl(nextLocale);
                    }, 700);
                }
            } catch (error) {
                if (isAuthStatus(error?.status)) {
                    localStorage.removeItem('auth_token');
                    localStorage.removeItem('user');
                    window.location.href = loginUrl;
                    return;
                }

                showAlert('error', error.message || labels.loadError);
            } finally {
                submit.disabled = false;
                submit.classList.remove('opacity-70', 'cursor-not-allowed');
                submit.textContent = @json(__('ui.account.save'));
            }
        });

        initializeAccount();
    });
</script>
@endpush

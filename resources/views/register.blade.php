@extends('layouts.app')

@section('title', __('ui.auth.register_title'))

@section('content')
<main class="flex-1 bg-gradient-hero py-12 md:py-20">
    <div class="container mx-auto px-4 max-w-lg">
        <article class="rounded-2xl border border-border bg-card shadow-elegant p-6 md:p-8">
            <header class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-accent/15 text-accent mb-4">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
                <h1 class="text-3xl md:text-4xl font-serif font-bold text-primary mb-2">{{ __('ui.auth.register_title') }}</h1>
                <p class="text-muted-foreground">{{ __('ui.auth.register_desc') }}</p>
            </header>

            <div id="registerMessage" class="hidden mb-6 rounded-lg border p-4 text-sm"></div>

            <form id="registerForm" class="space-y-5" novalidate>
                @csrf
                <div class="space-y-2">
                    <label for="full_name" class="block text-sm font-medium text-foreground">{{ __('ui.auth.full_name') }}</label>
                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        autocomplete="name"
                        class="w-full h-12 px-4 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        placeholder="{{ __('ui.auth.full_name') }}"
                    />
                </div>

                <div class="space-y-2">
                    <label for="email" class="block text-sm font-medium text-foreground">{{ __('ui.auth.email') }} *</label>
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
                    <label for="password" class="block text-sm font-medium text-foreground">{{ __('ui.auth.password') }} *</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        minlength="12"
                        autocomplete="new-password"
                        class="w-full h-12 px-4 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        placeholder="{{ __('ui.auth.password_min') }}"
                    />
                    <p id="passwordStrength" class="text-xs text-muted-foreground">{{ __('ui.auth.password_strength') }}: {{ __('ui.auth.strength_none') }}</p>
                </div>

                <div class="space-y-2">
                    <label for="password_confirmation" class="block text-sm font-medium text-foreground">{{ __('ui.auth.password_confirm') }} *</label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        required
                        minlength="12"
                        autocomplete="new-password"
                        class="w-full h-12 px-4 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        placeholder="{{ __('ui.auth.password_confirm') }}"
                    />
                </div>

                <label class="inline-flex items-start gap-2 text-sm text-muted-foreground">
                    <input type="checkbox" id="terms" required class="mt-0.5 rounded border-border">
                    <span>
                        {{ __('ui.auth.terms_text') }}
                        <a href="{{ route('terms') }}" target="_blank" rel="noopener noreferrer" class="text-accent hover:underline">{{ __('ui.footer.terms') }}</a>
                        /
                        <a href="{{ route('privacy') }}" target="_blank" rel="noopener noreferrer" class="text-accent hover:underline">{{ __('ui.footer.privacy') }}</a>
                    </span>
                </label>

                <button
                    type="submit"
                    id="registerSubmit"
                    class="w-full h-12 rounded-lg bg-gradient-accent text-accent-foreground font-semibold hover:opacity-90 transition-opacity"
                >
                    {{ __('ui.auth.register_button') }}
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-muted-foreground">
                {{ __('ui.auth.have_account') }}
                <a href="{{ route('login') }}" class="text-accent font-medium hover:underline">{{ __('ui.auth.go_login') }}</a>
            </p>
        </article>
    </div>
</main>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('registerForm');
        const submit = document.getElementById('registerSubmit');
        const message = document.getElementById('registerMessage');
        const password = document.getElementById('password');
        const passwordConfirm = document.getElementById('password_confirmation');
        const strength = document.getElementById('passwordStrength');

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

        function updateStrength(value) {
            let score = 0;
            const hasLength = value.length >= 12;
            const hasUppercase = /[A-Z]/.test(value);
            const hasLowercase = /[a-z]/.test(value);
            const hasDigit = /\d/.test(value);
            const hasSpecialChar = /[^A-Za-z0-9]/.test(value);

            if (hasLength) score += 1;
            if (hasUppercase && hasLowercase) score += 1;
            if (hasDigit) score += 1;
            if (hasSpecialChar) score += 1;

            if (!value) {
                strength.textContent = @json(__('ui.auth.password_strength').': '.__('ui.auth.strength_none'));
                strength.className = 'text-xs text-muted-foreground';
            } else if (score <= 1) {
                strength.textContent = @json(__('ui.auth.password_strength').': '.__('ui.auth.strength_weak'));
                strength.className = 'text-xs text-red-600';
            } else if (score <= 3) {
                strength.textContent = @json(__('ui.auth.password_strength').': '.__('ui.auth.strength_medium'));
                strength.className = 'text-xs text-amber-600';
            } else {
                strength.textContent = @json(__('ui.auth.password_strength').': '.__('ui.auth.strength_strong'));
                strength.className = 'text-xs text-green-600';
            }
        }

        password.addEventListener('input', function () {
            updateStrength(password.value);
            if (passwordConfirm.value && password.value !== passwordConfirm.value) {
                passwordConfirm.setCustomValidity(@json(__('ui.auth.password_mismatch')));
            } else {
                passwordConfirm.setCustomValidity('');
            }
        });

        passwordConfirm.addEventListener('input', function () {
            if (password.value !== passwordConfirm.value) {
                passwordConfirm.setCustomValidity(@json(__('ui.auth.password_mismatch')));
            } else {
                passwordConfirm.setCustomValidity('');
            }
        });

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            // Validate password match
            if (password.value !== passwordConfirm.value) {
                showMessage('error', @json(__('ui.auth.password_mismatch')));
                return;
            }

            submit.disabled = true;
            submit.classList.add('opacity-70', 'cursor-not-allowed');
            submit.textContent = @json(__('ui.auth.creating'));
            message.classList.add('hidden');

            const payload = {
                full_name: document.getElementById('full_name').value.trim(),
                email: document.getElementById('email').value.trim(),
                password: password.value,
                password_confirmation: passwordConfirm.value,
                locale: @json(app()->getLocale()),
            };

            try {
                const response = await fetch('/api/v1/register', {
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
                    let errorText = data?.message || @json(__('ui.auth.register_failed'));

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

                    // Track failed registration
                    if (window.eventTracker) {
                        window.eventTracker.trackFormSubmit({
                            form_type: 'registration',
                            locale: @json(app()->getLocale()),
                            success: false,
                            error_type: 'validation_error'
                        });
                    }
                    return;
                }

                const authToken = typeof data?.token === 'string' ? data.token.trim() : '';
                if (authToken === '') {
                    showMessage('error', @json(__('ui.auth.register_failed')));
                    return;
                }

                localStorage.setItem('auth_token', authToken);
                if (data?.user) {
                    localStorage.setItem('user', JSON.stringify(data.user));
                }

                // Track successful registration
                if (window.eventTracker) {
                    window.eventTracker.trackSignUp({
                        locale: @json(app()->getLocale()),
                        registration_method: 'email'
                    });
                }

                showMessage('success', @json(__('ui.auth.register_success')));
                setTimeout(() => {
                    window.location.href = @json(route('dashboard'));
                }, 700);
            } catch (_error) {
                showMessage('error', @json(__('ui.auth.connection_error')));

                // Track connection error
                if (window.eventTracker) {
                    window.eventTracker.trackFormSubmit({
                        form_type: 'registration',
                        locale: @json(app()->getLocale()),
                        success: false,
                        error_type: 'connection_error'
                    });
                }
            } finally {
                submit.disabled = false;
                submit.classList.remove('opacity-70', 'cursor-not-allowed');
                submit.textContent = @json(__('ui.auth.register_button'));
            }
        });
    });
</script>
@endpush

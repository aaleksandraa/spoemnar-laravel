@extends('layouts.app')

@section('title', __('ui.dashboard.title'))
@section('meta_description', __('ui.dashboard.meta_description'))

@php
    $currentLocale = app()->getLocale();
    $createMemorialUrl = route('memorial.create', ['locale' => $currentLocale]);
    $loginUrl = route('login', ['locale' => $currentLocale]);
    $homeUrl = route('home', ['locale' => $currentLocale]);
    $profileUrlTemplate = route('memorial.profile', ['locale' => $currentLocale, 'slug' => '__SLUG__']);
    $editUrlTemplate = route('memorial.edit', ['locale' => $currentLocale, 'slug' => '__SLUG__']);
@endphp

@section('content')
<main class="flex-1 bg-gradient-hero py-10 md:py-14">
    <div class="container mx-auto px-4 max-w-6xl space-y-8">
        <section class="rounded-2xl border border-border bg-card shadow-elegant p-6 md:p-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-serif font-bold text-primary">{{ __('ui.dashboard.title') }}</h1>
                    <p class="text-muted-foreground mt-2">{{ __('ui.dashboard.subtitle') }}</p>
                    <p id="dashboardUser" class="text-sm text-muted-foreground mt-3 hidden"></p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ $createMemorialUrl }}" class="inline-flex items-center justify-center px-5 h-11 rounded-lg bg-gradient-accent text-accent-foreground font-semibold hover:opacity-90 transition-opacity">
                        {{ __('ui.dashboard.create_memorial') }}
                    </a>
                    <button id="dashboardLogoutBtn" type="button" class="inline-flex items-center justify-center px-5 h-11 rounded-lg border border-border hover:bg-muted transition-colors">
                        {{ __('ui.dashboard.logout') }}
                    </button>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-border bg-card shadow-elegant overflow-hidden">
            <header class="px-6 py-4 border-b border-border flex items-center justify-between gap-4">
                <h2 class="text-xl font-serif font-semibold text-primary">{{ __('ui.dashboard.my_memorials') }}</h2>
                <span id="dashboardCount" class="text-sm text-muted-foreground">0</span>
            </header>
            <div class="p-6">
                <div id="dashboardAlert" class="hidden mb-6 rounded-lg border p-4 text-sm"></div>
                <div id="dashboardLoading" class="text-sm text-muted-foreground">{{ __('ui.dashboard.loading') }}</div>
                <div id="dashboardEmpty" class="hidden text-center py-10 text-muted-foreground">
                    <p>{{ __('ui.dashboard.no_memorials') }}</p>
                </div>
                <div id="dashboardGrid" class="hidden grid gap-5 md:grid-cols-2 lg:grid-cols-3"></div>
            </div>
        </section>
    </div>
</main>

<div id="qrModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 p-4">
    <div class="w-full max-w-md rounded-2xl border border-border bg-card shadow-elegant p-6">
        <div class="flex items-start justify-between gap-4">
            <h3 class="text-xl font-serif font-semibold text-primary">{{ __('ui.dashboard.qr_title') }}</h3>
            <button id="qrModalClose" type="button" class="text-muted-foreground hover:text-foreground">x</button>
        </div>
        <p class="text-sm text-muted-foreground mt-2">{{ __('ui.dashboard.qr_subtitle') }}</p>
        <div class="mt-5 rounded-xl border border-border bg-background p-4 flex items-center justify-center">
            <img id="qrModalImage" alt="QR Code" class="w-56 h-56 object-contain" />
        </div>
        <div class="mt-5 flex flex-wrap gap-3">
            <a id="qrDownloadLink" href="#" download class="inline-flex items-center justify-center px-4 h-10 rounded-lg bg-gradient-accent text-accent-foreground hover:opacity-90 transition-opacity">
                {{ __('ui.dashboard.download_qr') }}
            </a>
            <button id="qrCopyLinkBtn" type="button" class="inline-flex items-center justify-center px-4 h-10 rounded-lg border border-border hover:bg-muted transition-colors">
                {{ __('ui.dashboard.copy_link') }}
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const token = localStorage.getItem('auth_token') || '';
        const loginUrl = @json($loginUrl);
        const homeUrl = @json($homeUrl);
        const profileTemplate = @json($profileUrlTemplate);
        const editTemplate = @json($editUrlTemplate);
        const createUrl = @json($createMemorialUrl);

        const alertBox = document.getElementById('dashboardAlert');
        const loadingState = document.getElementById('dashboardLoading');
        const emptyState = document.getElementById('dashboardEmpty');
        const grid = document.getElementById('dashboardGrid');
        const countEl = document.getElementById('dashboardCount');
        const userEl = document.getElementById('dashboardUser');
        const logoutBtn = document.getElementById('dashboardLogoutBtn');

        const qrModal = document.getElementById('qrModal');
        const qrModalImage = document.getElementById('qrModalImage');
        const qrDownloadLink = document.getElementById('qrDownloadLink');
        const qrCopyLinkBtn = document.getElementById('qrCopyLinkBtn');
        const qrModalClose = document.getElementById('qrModalClose');

        let activeQrLink = '';

        function showAlert(type, text) {
            alertBox.classList.remove('hidden', 'border-red-200', 'bg-red-50', 'text-red-700', 'border-green-200', 'bg-green-50', 'text-green-700');
            if (type === 'error') {
                alertBox.classList.add('border-red-200', 'bg-red-50', 'text-red-700');
            } else {
                alertBox.classList.add('border-green-200', 'bg-green-50', 'text-green-700');
            }
            alertBox.textContent = text;
        }

        function hideAlert() {
            alertBox.classList.add('hidden');
        }

        function setLoading(isLoading) {
            loadingState.classList.toggle('hidden', !isLoading);
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function isAuthStatus(status) {
            return Number(status) === 401 || Number(status) === 403;
        }

        function isAuthError(error) {
            return isAuthStatus(error?.status);
        }

        function formatEuropeanDate(value) {
            if (typeof value !== 'string') {
                return '';
            }

            const normalized = value.trim();
            const match = normalized.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (!match) {
                return normalized;
            }

            return `${match[3]}.${match[2]}.${match[1]}.`;
        }

        async function apiRequest(url, options = {}) {
            const headers = Object.assign(
                { Accept: 'application/json' },
                options.headers || {}
            );

            if (!(options.body instanceof FormData)) {
                headers['Content-Type'] = headers['Content-Type'] || 'application/json';
            }

            if (token !== '') {
                headers.Authorization = `Bearer ${token}`;
            }

            let response;
            try {
                response = await fetch(url, Object.assign({}, options, { headers }));
            } catch (error) {
                const networkError = new Error('Network request failed.');
                networkError.code = 'NETWORK_ERROR';
                networkError.cause = error;
                throw networkError;
            }

            let payload = null;
            try {
                payload = await response.json();
            } catch (_error) {
                payload = null;
            }

            if (!response.ok) {
                const message = payload?.message || 'Request failed.';
                const requestError = new Error(message);
                requestError.status = response.status;
                requestError.payload = payload;
                throw requestError;
            }

            return payload;
        }

        function openQrModal(profileUrl) {
            activeQrLink = profileUrl;
            const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=512x512&data=${encodeURIComponent(profileUrl)}`;
            qrModalImage.src = qrUrl;
            qrDownloadLink.href = qrUrl;
            qrModal.classList.remove('hidden');
            qrModal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeQrModal() {
            qrModal.classList.add('hidden');
            qrModal.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }

        function renderMemorials(memorials) {
            grid.innerHTML = '';
            countEl.textContent = `${memorials.length}`;

            if (!Array.isArray(memorials) || memorials.length === 0) {
                emptyState.classList.remove('hidden');
                grid.classList.add('hidden');
                return;
            }

            emptyState.classList.add('hidden');
            grid.classList.remove('hidden');

            memorials.forEach((memorial) => {
                const profileUrl = profileTemplate.replace('__SLUG__', encodeURIComponent(memorial.slug));
                const editUrl = editTemplate.replace('__SLUG__', encodeURIComponent(memorial.slug));
                const birthDate = formatEuropeanDate(memorial.birthDate ?? '');
                const deathDate = formatEuropeanDate(memorial.deathDate ?? '');
                const lifespan = [birthDate, deathDate].filter((part) => part !== '').join(' - ');
                const profileImage = memorial.profileImageUrl
                    ? `<img src="${escapeHtml(memorial.profileImageUrl)}" alt="${escapeHtml(memorial.firstName)} ${escapeHtml(memorial.lastName)}" class="w-full h-full object-cover object-center" />`
                    : `<div class="w-full h-full flex items-center justify-center text-muted-foreground"><svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg></div>`;

                const card = document.createElement('article');
                card.className = 'rounded-xl border border-border bg-background shadow-sm overflow-hidden';
                card.innerHTML = `
                    <div class="aspect-square bg-muted overflow-hidden" style="aspect-ratio: 1 / 1;">${profileImage}</div>
                    <div class="p-4 space-y-3">
                        <div>
                            <h3 class="text-lg font-serif font-semibold text-primary">${escapeHtml(memorial.firstName)} ${escapeHtml(memorial.lastName)}</h3>
                            <p class="text-sm text-muted-foreground">${escapeHtml(lifespan)}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="${escapeHtml(profileUrl)}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center px-3 h-9 rounded-lg border border-border text-sm hover:bg-muted transition-colors">{{ __('ui.dashboard.view') }}</a>
                            <a href="${escapeHtml(editUrl)}" class="inline-flex items-center justify-center px-3 h-9 rounded-lg border border-border text-sm hover:bg-muted transition-colors">{{ __('ui.dashboard.edit') }}</a>
                            <button type="button" data-action="qr" class="inline-flex items-center justify-center px-3 h-9 rounded-lg border border-border text-sm hover:bg-muted transition-colors">{{ __('ui.dashboard.qr') }}</button>
                            <button type="button" data-action="delete" class="inline-flex items-center justify-center px-3 h-9 rounded-lg border border-red-200 text-red-700 text-sm hover:bg-red-50 transition-colors">{{ __('ui.dashboard.delete') }}</button>
                        </div>
                    </div>
                `;

                card.querySelector('[data-action="qr"]').addEventListener('click', function () {
                    openQrModal(profileUrl);
                });

                card.querySelector('[data-action="delete"]').addEventListener('click', async function () {
                    if (!confirm(@json(__('ui.dashboard.delete_confirm')))) {
                        return;
                    }

                    try {
                        await apiRequest(`/api/v1/memorials/${encodeURIComponent(memorial.id)}`, {
                            method: 'DELETE',
                        });
                        card.remove();
                        const remaining = grid.querySelectorAll('article').length;
                        countEl.textContent = `${remaining}`;
                        if (remaining === 0) {
                            emptyState.classList.remove('hidden');
                            grid.classList.add('hidden');
                        }
                        showAlert('success', @json(__('ui.dashboard.delete_success')));
                    } catch (error) {
                        showAlert('error', error.message || @json(__('ui.dashboard.action_failed')));
                    }
                });

                grid.appendChild(card);
            });
        }

        async function initializeDashboard() {
            if (!token) {
                window.location.href = loginUrl;
                return;
            }

            try {
                const meResponse = await apiRequest('/api/v1/me');
                const me = meResponse?.user ?? null;
                if (!me) {
                    throw new Error('Authentication failed.');
                }

                userEl.classList.remove('hidden');
                userEl.textContent = me.email || '';

                const memorialsResponse = await apiRequest('/api/v1/memorials?per_page=100&mine=1');
                const memorials = Array.isArray(memorialsResponse?.data) ? memorialsResponse.data : [];
                renderMemorials(memorials);
            } catch (error) {
                if (isAuthError(error)) {
                    localStorage.removeItem('auth_token');
                    localStorage.removeItem('user');
                    showAlert('error', error.message || @json(__('ui.dashboard.load_error')));
                    setTimeout(() => {
                        window.location.href = loginUrl;
                    }, 1200);
                    return;
                }

                showAlert('error', error.message || @json(__('ui.dashboard.load_error')));
            } finally {
                setLoading(false);
            }
        }

        logoutBtn.addEventListener('click', async function () {
            try {
                await apiRequest('/api/v1/logout', { method: 'POST' });
            } catch (_error) {
                // Always clear local session on client side.
            } finally {
                localStorage.removeItem('auth_token');
                localStorage.removeItem('user');
                window.location.href = homeUrl;
            }
        });

        qrModalClose.addEventListener('click', closeQrModal);
        qrModal.addEventListener('click', function (event) {
            if (event.target === qrModal) {
                closeQrModal();
            }
        });
        qrCopyLinkBtn.addEventListener('click', async function () {
            if (!activeQrLink) {
                return;
            }

            try {
                await navigator.clipboard.writeText(activeQrLink);
                showAlert('success', @json(__('ui.dashboard.copy_success')));
            } catch (_error) {
                showAlert('error', @json(__('ui.dashboard.copy_fail')));
            }
        });

        initializeDashboard();
    });
</script>
@endpush

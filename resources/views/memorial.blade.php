@extends('layouts.app')

@php
    $currentLocale = app()->getLocale();
    $memorialFullName = trim($memorial->first_name.' '.$memorial->last_name);
    $memorialBirthYear = \Carbon\Carbon::parse($memorial->birth_date)->format('Y');
    $memorialDeathYear = \Carbon\Carbon::parse($memorial->death_date)->format('Y');
    $profileImageUrl = \App\Support\MediaUrl::normalize($memorial->profile_image_url);
    $localizedBirthPlace = $memorial->localizedBirthPlace($currentLocale);
    $localizedDeathPlace = $memorial->localizedDeathPlace($currentLocale);
    $localizedBiography = $memorial->localizedBiography($currentLocale);
    $galleryImages = $memorial->images
        ->filter(static function ($image) use ($profileImageUrl) {
            $imageUrl = \App\Support\MediaUrl::normalize($image->image_url);
            return is_string($imageUrl) && $imageUrl !== '' && $imageUrl !== $profileImageUrl;
        })
        ->values();
    $galleryImageUrls = $galleryImages
        ->map(static fn ($image) => \App\Support\MediaUrl::normalize($image->image_url))
        ->values();
    $memorialSeoDescription = is_string($localizedBiography) && trim(strip_tags($localizedBiography)) !== ''
        ? \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags($localizedBiography)) ?? ''), 160)
        : __('ui.memorial.seo_profile_description', [
            'name' => $memorialFullName,
            'birth' => $memorialBirthYear,
            'death' => $memorialDeathYear,
        ]);
    $candleSummary = is_array($candleSummary ?? null) ? $candleSummary : ['enabled' => false];
    $candleSettings = is_array($candleSummary['settings'] ?? null) ? $candleSummary['settings'] : [];
    $activeCandle = is_array($candleSummary['activeCandle'] ?? null) ? $candleSummary['activeCandle'] : null;
    $familyCandle = is_array($candleSummary['familyCandle'] ?? null) ? $candleSummary['familyCandle'] : null;
    $primaryVisibleCandle = $activeCandle ?: $familyCandle;
    $recentCandleLighters = collect($candleSummary['recentLighters'] ?? []);
    $candleWallItems = collect($candleSummary['wallCandles'] ?? []);
    $anniversaryHighlight = is_array($candleSummary['anniversary'] ?? null) ? $candleSummary['anniversary'] : null;
    $isCandleFeatureEnabled = (bool) ($candleSummary['enabled'] ?? false);
@endphp

@section('title', __('ui.memorial.seo_profile_title', ['name' => $memorialFullName]))
@section('meta_description', $memorialSeoDescription)
@section('og_title', __('ui.memorial.seo_profile_title', ['name' => $memorialFullName]))
@section('og_description', $memorialSeoDescription)
@section('og_image', $profileImageUrl ?: config('seo.meta.default_og_image', '/spomenar-pozadina.jpg'))
@section('og_image_alt', $memorialFullName)

@section('head')
    {{-- Person Structured Data --}}
    <x-seo.structured-data type="person" :data="$memorial" />

    {{-- Breadcrumb Structured Data --}}
    <x-seo.structured-data
        type="breadcrumb"
        :breadcrumbs="[
            ['name' => __('ui.home.title'), 'url' => route('home', ['locale' => $currentLocale])],
            ['name' => __('ui.memorial.title'), 'url' => route('search.page', ['locale' => $currentLocale])],
            ['name' => $memorialFullName, 'url' => route('memorial.profile', ['locale' => $currentLocale, 'slug' => $memorial->slug])]
        ]"
    />

    @if($isCandleFeatureEnabled)
        <style>
            @keyframes memorial-candle-flicker {
                0%, 100% { transform: translate3d(0, 0, 0) scale(1) rotate(-1deg); opacity: 0.92; }
                20% { transform: translate3d(1px, -2px, 0) scale(1.07, 0.95) rotate(1deg); opacity: 1; }
                45% { transform: translate3d(-1px, -3px, 0) scale(0.96, 1.06) rotate(-2deg); opacity: 0.84; }
                70% { transform: translate3d(1px, -1px, 0) scale(1.04, 0.97) rotate(2deg); opacity: 0.98; }
            }

            @keyframes memorial-candle-glow {
                0%, 100% { opacity: 0.4; transform: scale(0.88); }
                50% { opacity: 0.92; transform: scale(1.18); }
            }

            @keyframes memorial-candle-smoke {
                0% { opacity: 0.18; transform: translate3d(0, 0, 0) scale(0.88); }
                45% { opacity: 0.48; transform: translate3d(-2px, -10px, 0) scale(1.04); }
                100% { opacity: 0; transform: translate3d(3px, -18px, 0) scale(1.22); }
            }

            .memorial-candle-flame,
            .memorial-candle-inner-flame,
            .memorial-candle-glow,
            .memorial-candle-halo,
            .memorial-candle-smoke,
            .memorial-candle-body {
                transition: opacity 700ms ease, transform 700ms ease, filter 700ms ease, box-shadow 700ms ease;
            }

            .memorial-candle-flame,
            .memorial-candle-inner-flame,
            .memorial-candle-glow,
            .memorial-candle-halo {
                opacity: 0;
                visibility: hidden;
            }

            .memorial-candle-section[data-state="active"] .memorial-candle-flame {
                display: block;
                animation: memorial-candle-flicker 2.6s ease-in-out infinite;
                opacity: 1;
                visibility: visible;
                filter: saturate(1.08);
            }

            .memorial-candle-section[data-state="active"] .memorial-candle-inner-flame {
                display: block;
                animation: memorial-candle-flicker 2.1s ease-in-out infinite reverse;
                opacity: 1;
                visibility: visible;
            }

            .memorial-candle-section[data-state="active"] .memorial-candle-glow,
            .memorial-candle-section[data-state="active"] .memorial-candle-halo {
                display: block;
                animation: memorial-candle-glow 3.3s ease-in-out infinite;
                opacity: 1;
                visibility: visible;
            }

            .memorial-candle-section[data-state="active"] .memorial-candle-smoke {
                opacity: 0;
                transform: translate3d(0, 6px, 0) scale(0.72);
            }

            .memorial-candle-section[data-state="active"] .memorial-candle-body {
                box-shadow: inset 0 1px 0 rgba(255,255,255,0.82), 0 12px 26px rgba(193,149,74,0.16);
                filter: saturate(1.02);
            }

            .memorial-candle-section[data-state="active"] .memorial-candle-stage {
                box-shadow: inset 0 1px 0 rgba(255,255,255,0.05), 0 18px 34px rgba(18,14,12,0.22);
            }

            .memorial-candle-section[data-state="inactive"] .memorial-candle-flame,
            .memorial-candle-section[data-state="disabled"] .memorial-candle-flame,
            .memorial-candle-section[data-state="inactive"] .memorial-candle-inner-flame,
            .memorial-candle-section[data-state="disabled"] .memorial-candle-inner-flame,
            .memorial-candle-section[data-state="inactive"] .memorial-candle-glow,
            .memorial-candle-section[data-state="disabled"] .memorial-candle-glow,
            .memorial-candle-section[data-state="inactive"] .memorial-candle-halo,
            .memorial-candle-section[data-state="disabled"] .memorial-candle-halo {
                display: none;
                opacity: 0;
                visibility: hidden;
                transform: translate3d(0, 8px, 0) scale(0.6);
                filter: saturate(0.3);
            }

            .memorial-candle-section[data-state="inactive"] .memorial-candle-flame,
            .memorial-candle-section[data-state="disabled"] .memorial-candle-flame,
            .memorial-candle-section[data-state="inactive"] .memorial-candle-inner-flame,
            .memorial-candle-section[data-state="disabled"] .memorial-candle-inner-flame {
                opacity: 0;
                transform: translate3d(0, 10px, 0) scale(0.52);
                filter: saturate(0.2) blur(1px);
            }

            .memorial-candle-section[data-state="inactive"][data-has-history="true"] .memorial-candle-smoke,
            .memorial-candle-section[data-state="disabled"][data-has-history="true"] .memorial-candle-smoke {
                animation: memorial-candle-smoke 3.8s ease-out infinite;
                opacity: 0.58;
            }

            .memorial-candle-section[data-has-history="false"] .memorial-candle-smoke {
                animation: none;
                opacity: 0;
            }

            .memorial-candle-section[data-state="inactive"] .memorial-candle-body,
            .memorial-candle-section[data-state="disabled"] .memorial-candle-body {
                filter: saturate(0.82) brightness(0.98);
                box-shadow: inset 0 1px 0 rgba(255,255,255,0.78), 0 8px 18px rgba(120,92,45,0.08);
            }

            .memorial-candle-section[data-state="inactive"] .memorial-candle-stage,
            .memorial-candle-section[data-state="disabled"] .memorial-candle-stage {
                box-shadow: inset 0 1px 0 rgba(255,255,255,0.04), 0 12px 28px rgba(18,14,12,0.18);
            }

            .memorial-candle-message-disclosure summary::-webkit-details-marker {
                display: none;
            }

            .memorial-candle-message-disclosure[open] summary svg {
                transform: rotate(180deg);
            }
        </style>
    @endif
@endsection

@push('scripts')
<script>
    const memorialImages = @json($galleryImageUrls);
    let currentImageIndex = 0;
    const memorialUrl = @json(route('memorial.profile', ['locale' => $currentLocale, 'slug' => $memorial->slug]));
    const loginUrl = @json(route('login', ['locale' => $currentLocale]));
    const memorialId = @json((string) $memorial->id);
    const memorialOwnerId = @json((string) $memorial->user_id);
    const memorialCandleInitialState = @json($candleSummary);
    let memorialCandleState = memorialCandleInitialState;
    let memorialPageViewer = null;
    const memorialCandleLabels = {
        eyebrowActive: @json(__('ui.memorial.candle.eyebrow_active')),
        eyebrowInactive: @json(__('ui.memorial.candle.eyebrow_inactive')),
        subtitleActive: @json(__('ui.memorial.candle.subtitle_active')),
        subtitleInactive: @json(__('ui.memorial.candle.subtitle_inactive')),
        countLabel: @json(__('ui.memorial.candle.count_label')),
        currentLighter: @json(__('ui.memorial.candle.current_lighter')),
        recentLighters: @json(__('ui.memorial.candle.recent_lighters')),
        remaining: @json(__('ui.memorial.candle.remaining')),
        lightButton: @json(__('ui.memorial.candle.light_button')),
        activeButton: @json(__('ui.memorial.candle.active_button')),
        loginButton: @json(__('ui.memorial.candle.login_button')),
        loading: @json(__('ui.memorial.candle.loading')),
        recentEmpty: @json(__('ui.memorial.candle.recent_empty')),
        messageLabel: @json(__('ui.memorial.candle.message_label')),
        messagePlaceholder: @json(__('ui.memorial.candle.message_placeholder')),
        messageHint: @json(__('ui.memorial.candle.message_hint')),
        messageToggle: @json(__('ui.memorial.candle.message_toggle')),
        visualStatusActive: @json(__('ui.memorial.candle.visual_status_active')),
        visualStatusInactive: @json(__('ui.memorial.candle.visual_status_inactive')),
        wallTitle: @json(__('ui.memorial.candle.wall_title')),
        wallEmpty: @json(__('ui.memorial.candle.wall_empty')),
        familyTitle: @json(__('ui.memorial.candle.family_title')),
        familySubtitle: @json(__('ui.memorial.candle.family_subtitle')),
        familyBadge: @json(__('ui.memorial.candle.family_badge')),
        premiumBadge: @json(__('ui.memorial.candle.premium_badge')),
        permanentLabel: @json(__('ui.memorial.candle.permanent_label')),
        anniversaryTitle: @json(__('ui.memorial.candle.anniversary_title')),
        familyManageTitle: @json(__('ui.memorial.candle.family_manage_title')),
        familyManageHint: @json(__('ui.memorial.candle.family_manage_hint')),
        familyManageSave: @json(__('ui.memorial.candle.family_manage_save')),
        familyManageDelete: @json(__('ui.memorial.candle.family_manage_delete')),
        familyManageSaving: @json(__('ui.memorial.candle.family_manage_saving')),
        familyManageDeleteConfirm: @json(__('ui.memorial.candle.family_manage_delete_confirm')),
        cancel: @json(__('ui.memorial_form.cancel')),
        units: {
            day: @json(__('ui.memorial.candle.countdown_units.day')),
            hour: @json(__('ui.memorial.candle.countdown_units.hour')),
            minute: @json(__('ui.memorial.candle.countdown_units.minute')),
        },
        messages: {
            ready: @json(__('ui.memorial.candle.messages.ready')),
            active: @json(__('ui.memorial.candle.messages.active')),
            loginRequired: @json(__('ui.memorial.candle.messages.login_required')),
            disabled: @json(__('ui.memorial.candle.messages.disabled')),
            familySaved: @json(__('ui.memorial.candle.messages.family_saved')),
            familyDeleted: @json(__('ui.memorial.candle.messages.family_deleted')),
            loadFailed: @json(__('ui.memorial.candle.messages.load_failed')),
            requestFailed: @json(__('ui.memorial.candle.messages.request_failed')),
        },
    };
    const tributeModerationLabels = {
        deleting: @json(__('ui.memorial.deleting_message')),
        confirm: @json(__('ui.memorial.delete_message_confirm')),
        success: @json(__('ui.memorial.delete_message_success')),
        failed: @json(__('ui.memorial.delete_message_failed')),
    };
    let memorialCandleCountdownTimer = null;
    let memorialCandleComposerOpen = false;

    // Track memorial profile view
    if (window.eventTracker) {
        window.eventTracker.trackMemorialView({
            memorial_id: @json($memorial->id),
            memorial_slug: @json($memorial->slug),
            locale: @json($currentLocale),
            is_public: @json($memorial->is_public ?? true)
        });
    }

    function copyMemorialLink() {
        navigator.clipboard.writeText(memorialUrl).then(() => {
            alert(@json(__('ui.memorial.copy_success')));
        }).catch(() => {
            alert(@json(__('ui.memorial.copy_fail')));
        });
    }

    function openLightbox(index) {
        if (!memorialImages.length) return;
        currentImageIndex = index;
        const lightbox = document.getElementById('lightbox');
        const image = document.getElementById('lightbox-image');
        const counter = document.getElementById('lightbox-counter');

        image.src = memorialImages[currentImageIndex];
        counter.textContent = `${currentImageIndex + 1} / ${memorialImages.length}`;
        lightbox.classList.remove('hidden');
        lightbox.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox(event) {
        if (event && event.target.id !== 'lightbox') return;
        const lightbox = document.getElementById('lightbox');
        lightbox.classList.add('hidden');
        lightbox.classList.remove('flex');
        document.body.style.overflow = 'auto';
    }

    function nextImage(event) {
        if (event) event.stopPropagation();
        if (!memorialImages.length) return;
        currentImageIndex = (currentImageIndex + 1) % memorialImages.length;
        document.getElementById('lightbox-image').src = memorialImages[currentImageIndex];
        document.getElementById('lightbox-counter').textContent = `${currentImageIndex + 1} / ${memorialImages.length}`;
    }

    function previousImage(event) {
        if (event) event.stopPropagation();
        if (!memorialImages.length) return;
        currentImageIndex = (currentImageIndex - 1 + memorialImages.length) % memorialImages.length;
        document.getElementById('lightbox-image').src = memorialImages[currentImageIndex];
        document.getElementById('lightbox-counter').textContent = `${currentImageIndex + 1} / ${memorialImages.length}`;
    }

    document.addEventListener('keydown', function (event) {
        const lightbox = document.getElementById('lightbox');
        if (!lightbox || lightbox.classList.contains('hidden')) return;

        if (event.key === 'Escape') closeLightbox();
        if (event.key === 'ArrowRight') nextImage(event);
        if (event.key === 'ArrowLeft') previousImage(event);
    });

    // Track tribute submission
    function handleTributeSubmit(event) {
        if (window.eventTracker) {
            window.eventTracker.trackTributeSubmit({
                memorial_id: @json($memorial->id),
                locale: @json($currentLocale),
                tribute_type: 'text'
            });
        }
    }

    function isAdminUser(user) {
        const relationAdmin = Array.isArray(user?.roles)
            ? user.roles.some((role) => (typeof role === 'string' ? role === 'admin' : role?.role === 'admin'))
            : false;

        return relationAdmin || user?.role === 'admin';
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function buildCandleMessageDisclosure(message, spacingClass = 'mt-2') {
        const safeMessage = String(message || '').trim();
        if (safeMessage === '') {
            return '';
        }

        return `
            <details class="memorial-candle-message-disclosure ${spacingClass} rounded-xl border border-border/70 bg-white/70 px-3 py-2">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-xs font-semibold uppercase tracking-[0.18em] text-amber-800/90">
                    <span>${escapeHtml(memorialCandleLabels.messageToggle)}</span>
                    <svg class="h-3.5 w-3.5 shrink-0 transition-transform duration-300" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </summary>
                <p class="mt-3 text-sm leading-relaxed text-muted-foreground">${escapeHtml(safeMessage)}</p>
            </details>
        `;
    }

    function formatCandleDate(value) {
        if (!value) {
            return '';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return '';
        }

        return date.toLocaleDateString(document.documentElement.lang || 'bs', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    }

    function formatCandleCountdown(totalSeconds) {
        const safeSeconds = Math.max(0, Number(totalSeconds) || 0);
        const days = Math.floor(safeSeconds / 86400);
        const hours = Math.floor((safeSeconds % 86400) / 3600);
        const minutes = Math.max(1, Math.floor((safeSeconds % 3600) / 60));

        if (days > 0) {
            return `${days}${memorialCandleLabels.units.day} ${hours}${memorialCandleLabels.units.hour}`;
        }

        if (hours > 0) {
            return `${hours}${memorialCandleLabels.units.hour} ${minutes}${memorialCandleLabels.units.minute}`;
        }

        return `${minutes}${memorialCandleLabels.units.minute}`;
    }

    function stopMemorialCandleCountdown() {
        if (memorialCandleCountdownTimer) {
            window.clearInterval(memorialCandleCountdownTimer);
            memorialCandleCountdownTimer = null;
        }
    }

    function startMemorialCandleCountdown(expiresAt) {
        const countdownValue = document.getElementById('memorialCandleCountdownValue');
        const countdownWrap = document.getElementById('memorialCandleCountdownWrap');
        if (!countdownValue || !countdownWrap || !expiresAt) {
            return;
        }

        const expiryTime = new Date(expiresAt).getTime();
        if (Number.isNaN(expiryTime)) {
            countdownWrap.hidden = true;
            return;
        }

        const tick = () => {
            const remainingSeconds = Math.floor((expiryTime - Date.now()) / 1000);
            if (remainingSeconds <= 0) {
                countdownValue.textContent = `0${memorialCandleLabels.units.minute}`;
                stopMemorialCandleCountdown();
                refreshMemorialCandleSummary().catch(() => {
                    return;
                });
                return;
            }

            countdownValue.textContent = formatCandleCountdown(remainingSeconds);
        };

        stopMemorialCandleCountdown();
        tick();
        memorialCandleCountdownTimer = window.setInterval(tick, 30000);
    }

    function showMemorialCandleFeedback(type, text) {
        const feedback = document.getElementById('memorialCandleFeedback');
        if (!feedback) {
            return;
        }

        feedback.classList.remove('text-muted-foreground', 'text-red-700', 'text-green-700');

        if (type === 'error') {
            feedback.classList.add('text-red-700');
        } else if (type === 'success') {
            feedback.classList.add('text-green-700');
        } else {
            feedback.classList.add('text-muted-foreground');
        }

        feedback.textContent = text;
    }

    function showMemorialFamilyManagerFeedback(type, text) {
        const feedback = document.getElementById('memorialFamilyManagerFeedback');
        if (!feedback) {
            return;
        }

        feedback.classList.remove('hidden', 'text-muted-foreground', 'text-red-700', 'text-green-700');

        if (type === 'error') {
            feedback.classList.add('text-red-700');
        } else if (type === 'success') {
            feedback.classList.add('text-green-700');
        } else {
            feedback.classList.add('text-muted-foreground');
        }

        feedback.textContent = text;
    }

    function openMemorialCandleComposer() {
        if (memorialCandleState?.reason === 'auth_required') {
            window.location.href = loginUrl;
            return;
        }

        if (!memorialCandleState?.canLight) {
            return;
        }

        memorialCandleComposerOpen = true;
        renderMemorialCandleSummary(memorialCandleState, {
            preserveComposer: true,
        });

        window.setTimeout(() => {
            const messageWrap = document.getElementById('memorialCandleMessageInputWrap');
            const messageInput = document.getElementById('memorialCandleMessageInput');
            const confirmButton = document.getElementById('memorialCandleConfirmButton');

            if (messageInput && messageWrap && !messageWrap.hidden) {
                messageInput.focus();
                return;
            }

            confirmButton?.focus();
        }, 0);
    }

    function closeMemorialCandleComposer() {
        if (!memorialCandleComposerOpen) {
            return;
        }

        memorialCandleComposerOpen = false;
        renderMemorialCandleSummary(memorialCandleState);
    }

    function renderMemorialCandleAnniversary(summary) {
        const wrap = document.getElementById('memorialCandleAnniversaryWrap');
        const headline = document.getElementById('memorialCandleAnniversaryHeadline');
        const description = document.getElementById('memorialCandleAnniversaryDescription');
        if (!wrap || !headline || !description) {
            return;
        }

        const anniversary = summary?.anniversary || null;
        wrap.hidden = !anniversary;

        if (!anniversary) {
            headline.textContent = '';
            description.textContent = '';
            return;
        }

        headline.textContent = String(anniversary.headline || '');
        description.textContent = String(anniversary.description || '');
    }

    function renderMemorialCandleFamily(summary) {
        const wrap = document.getElementById('memorialFamilyCandleWrap');
        const lighter = document.getElementById('memorialFamilyCandleLighter');
        const messageWrap = document.getElementById('memorialFamilyCandleMessageWrap');
        const message = document.getElementById('memorialFamilyCandleMessage');
        const meta = document.getElementById('memorialFamilyCandleMeta');
        const premiumBadge = document.getElementById('memorialFamilyCandlePremiumBadge');
        if (!wrap || !lighter || !messageWrap || !message || !meta || !premiumBadge) {
            return;
        }

        const familyCandle = summary?.familyCandle || null;
        const familyEnabled = Boolean(summary?.settings?.familyEnabled);
        wrap.hidden = !(familyEnabled && familyCandle);

        if (!(familyEnabled && familyCandle)) {
            lighter.textContent = '';
            messageWrap.hidden = true;
            message.textContent = '';
            meta.textContent = '';
            premiumBadge.hidden = true;
            return;
        }

        lighter.textContent = String(familyCandle.lighterName || '');
        messageWrap.hidden = !Boolean(familyCandle.message);
        message.textContent = String(familyCandle.message || '');
        premiumBadge.hidden = !familyCandle.isPremium;

        if (familyCandle.isPermanent) {
            meta.textContent = memorialCandleLabels.permanentLabel;
        } else if (familyCandle.expiresAt) {
            meta.textContent = `${memorialCandleLabels.remaining}: ${formatCandleCountdown(familyCandle.secondsRemaining || 0)}`;
        } else {
            meta.textContent = '';
        }
    }

    function renderMemorialCandleWall(summary) {
        const wrap = document.getElementById('memorialCandleWallWrap');
        const grid = document.getElementById('memorialCandleWallGrid');
        const empty = document.getElementById('memorialCandleWallEmpty');
        if (!wrap || !grid || !empty) {
            return;
        }

        const wallCandles = Array.isArray(summary?.wallCandles) ? summary.wallCandles : [];
        const showWall = Boolean(summary?.settings?.showWall);
        wrap.hidden = !showWall;
        grid.innerHTML = '';

        if (!showWall) {
            empty.hidden = true;
            return;
        }

        empty.hidden = wallCandles.length !== 0;

        wallCandles.forEach((candle) => {
            const badges = [];
            if (candle?.isFamily) {
                badges.push(`<span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-800">${escapeHtml(memorialCandleLabels.familyBadge)}</span>`);
            }
            if (candle?.isPremium) {
                badges.push(`<span class="inline-flex items-center rounded-full bg-stone-900 px-2.5 py-1 text-[11px] font-semibold text-white">${escapeHtml(memorialCandleLabels.premiumBadge)}</span>`);
            }

            const card = document.createElement('article');
            card.className = 'rounded-2xl border border-border/70 bg-white/85 px-4 py-4 shadow-sm';
            card.innerHTML = `
                <div class="flex items-start gap-3">
                    <div class="mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-lg">🕯</div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">${badges.join('')}</div>
                        <p class="mt-2 text-sm font-semibold text-foreground">${escapeHtml(candle?.lighterName || '')}</p>
                        <p class="mt-1 text-xs text-muted-foreground">${escapeHtml(formatCandleDate(candle?.litAt))}</p>
                        ${buildCandleMessageDisclosure(candle?.message)}
                    </div>
                </div>
            `;
            grid.appendChild(card);
        });
    }

    function renderMemorialCandleRecentLighters(summary) {
        const recentWrap = document.getElementById('memorialCandleRecentWrap');
        const recentList = document.getElementById('memorialCandleRecentList');
        if (!recentWrap || !recentList) {
            return;
        }

        const lighters = Array.isArray(summary?.recentLighters) ? summary.recentLighters : [];
        const shouldShow = Boolean(summary?.settings?.showRecentLighters) && lighters.length > 0;
        recentWrap.hidden = !shouldShow;
        recentList.innerHTML = '';

        if (!shouldShow) {
            return;
        }

        lighters.forEach((lighter) => {
            const item = document.createElement('li');
            item.className = 'rounded-xl border border-border/70 bg-white/80 px-4 py-3';
            const litAt = formatCandleDate(lighter?.litAt);
            item.innerHTML = `
                <p class="text-sm font-medium text-foreground">${escapeHtml(lighter?.lighterName || '')}</p>
                <p class="mt-1 text-xs text-muted-foreground">${escapeHtml(litAt)}</p>
                ${buildCandleMessageDisclosure(lighter?.message)}
            `;
            recentList.appendChild(item);
        });
    }

    function syncMemorialFamilyManager(user, summary = memorialCandleState) {
        const manager = document.getElementById('memorialFamilyManager');
        const premiumWrap = document.getElementById('memorialFamilyPremiumWrap');
        const premiumInput = document.getElementById('memorialFamilyPremiumInput');
        const messageInput = document.getElementById('memorialFamilyMessageInput');
        const deleteButton = document.getElementById('memorialFamilyDeleteButton');
        if (!manager || !premiumWrap || !premiumInput || !messageInput || !deleteButton) {
            return;
        }

        const canManage = Boolean(user) && (String(user?.id || '') === memorialOwnerId || isAdminUser(user));
        const familyEnabled = Boolean(summary?.settings?.familyEnabled);
        manager.hidden = !(canManage && familyEnabled);

        if (manager.hidden) {
            return;
        }

        premiumWrap.hidden = !Boolean(summary?.settings?.premiumEnabled);
        premiumInput.checked = Boolean(summary?.familyCandle?.isPremium);
        messageInput.value = String(summary?.familyCandle?.message || '');
        deleteButton.hidden = !Boolean(summary?.familyCandle);
    }

    function renderMemorialCandleSummary(summary, options = {}) {
        const section = document.getElementById('memorialCandleSection');
        if (!section || !summary) {
            return;
        }

        memorialCandleState = summary;
        section.hidden = !Boolean(summary.enabled);

        const state = String(summary.state || 'inactive');
        const totalCandles = Number(summary.totalCandles || 0);
        const activeCandle = summary.activeCandle || null;
        const familyCandle = summary.familyCandle || null;
        const primaryVisibleCandle = activeCandle || familyCandle || null;

        section.dataset.state = state;
        section.dataset.hasHistory = totalCandles > 0 ? 'true' : 'false';

        const eyebrow = document.getElementById('memorialCandleEyebrow');
        const subtitle = document.getElementById('memorialCandleSubtitle');
        const countBadge = document.getElementById('memorialCandleCountBadge');
        const countdownWrap = document.getElementById('memorialCandleCountdownWrap');
        const visualStatus = document.getElementById('memorialCandleVisualStatus');
        const currentWrap = document.getElementById('memorialCandleCurrentLighterWrap');
        const currentLighter = document.getElementById('memorialCandleCurrentLighter');
        const currentMessageWrap = document.getElementById('memorialCandleCurrentMessageWrap');
        const currentMessage = document.getElementById('memorialCandleCurrentMessage');
        const composer = document.getElementById('memorialCandleComposer');
        const messageWrap = document.getElementById('memorialCandleMessageInputWrap');
        const button = document.getElementById('memorialCandleButton');
        const confirmButton = document.getElementById('memorialCandleConfirmButton');
        const cancelButton = document.getElementById('memorialCandleCancelButton');

        const canCompose = Boolean(summary.canLight) && summary.reason !== 'auth_required' && state !== 'disabled';

        if (!options.preserveComposer && !canCompose) {
            memorialCandleComposerOpen = false;
        }

        const shouldShowComposer = canCompose && memorialCandleComposerOpen;

        if (eyebrow) {
            eyebrow.textContent = primaryVisibleCandle ? memorialCandleLabels.eyebrowActive : memorialCandleLabels.eyebrowInactive;
        }

        if (subtitle) {
            if (activeCandle) {
                subtitle.textContent = memorialCandleLabels.subtitleActive;
            } else if (familyCandle) {
                subtitle.textContent = memorialCandleLabels.familySubtitle;
            } else {
                subtitle.textContent = memorialCandleLabels.subtitleInactive;
            }
        }

        if (countBadge) {
            countBadge.textContent = `${totalCandles} ${memorialCandleLabels.countLabel}`;
        }

        if (visualStatus) {
            const isLit = Boolean(primaryVisibleCandle);
            visualStatus.textContent = isLit
                ? memorialCandleLabels.visualStatusActive
                : memorialCandleLabels.visualStatusInactive;
            visualStatus.classList.toggle('bg-amber-100', isLit);
            visualStatus.classList.toggle('text-amber-900', isLit);
            visualStatus.classList.toggle('border-amber-200', isLit);
            visualStatus.classList.toggle('bg-stone-200/80', !isLit);
            visualStatus.classList.toggle('text-stone-700', !isLit);
            visualStatus.classList.toggle('border-stone-300/80', !isLit);
        }

        if (currentWrap && currentLighter && currentMessageWrap && currentMessage) {
            const shouldShowCurrent = Boolean(primaryVisibleCandle?.lighterName);
            currentWrap.hidden = !shouldShowCurrent;
            currentLighter.textContent = shouldShowCurrent ? String(primaryVisibleCandle.lighterName || '') : '';
            currentMessageWrap.hidden = !Boolean(primaryVisibleCandle?.message);
            currentMessage.textContent = primaryVisibleCandle?.message ? String(primaryVisibleCandle.message) : '';
        }

        if (composer) {
            composer.hidden = !shouldShowComposer;
        }

        if (messageWrap) {
            messageWrap.hidden = !(shouldShowComposer && Boolean(summary?.settings?.messagesEnabled));
        }

        if (confirmButton) {
            confirmButton.hidden = !shouldShowComposer;
            confirmButton.disabled = false;
            confirmButton.textContent = memorialCandleLabels.lightButton;
        }

        if (cancelButton) {
            cancelButton.hidden = !shouldShowComposer;
            cancelButton.textContent = memorialCandleLabels.cancel;
        }

        renderMemorialCandleAnniversary(summary);
        renderMemorialCandleFamily(summary);
        renderMemorialCandleWall(summary);
        renderMemorialCandleRecentLighters(summary);
        syncMemorialFamilyManager(memorialPageViewer, summary);

        if (countdownWrap) {
            const shouldShowCountdown = Boolean(summary?.settings?.showCountdown) && primaryVisibleCandle?.expiresAt && !primaryVisibleCandle?.isPermanent;
            countdownWrap.hidden = !shouldShowCountdown;

            if (shouldShowCountdown) {
                startMemorialCandleCountdown(primaryVisibleCandle.expiresAt);
            } else {
                stopMemorialCandleCountdown();
            }
        }

        if (button) {
            button.hidden = shouldShowComposer;
            button.disabled = true;

            if (summary.reason === 'auth_required') {
                button.disabled = false;
                button.textContent = memorialCandleLabels.loginButton;
            } else if (summary.canLight) {
                button.disabled = false;
                button.textContent = memorialCandleLabels.lightButton;
            } else if (activeCandle) {
                button.textContent = memorialCandleLabels.activeButton;
            } else {
                button.textContent = memorialCandleLabels.lightButton;
            }
        }

        if (typeof options.errorMessage === 'string' && options.errorMessage !== '') {
            showMemorialCandleFeedback('error', options.errorMessage);
            return;
        }

        if (typeof options.successMessage === 'string' && options.successMessage !== '') {
            showMemorialCandleFeedback('success', options.successMessage);
            return;
        }

        if (summary.reason === 'auth_required') {
            showMemorialCandleFeedback('neutral', memorialCandleLabels.messages.loginRequired);
            return;
        }

        if (state === 'disabled') {
            showMemorialCandleFeedback('neutral', memorialCandleLabels.messages.disabled);
            return;
        }

        if (primaryVisibleCandle) {
            showMemorialCandleFeedback('neutral', memorialCandleLabels.messages.active);
            return;
        }

        showMemorialCandleFeedback('neutral', memorialCandleLabels.messages.ready);
    }

    async function refreshMemorialCandleSummary() {
        const section = document.getElementById('memorialCandleSection');
        if (!section) {
            return null;
        }

        const token = localStorage.getItem('auth_token') || '';
        const headers = {
            Accept: 'application/json',
        };

        if (token !== '') {
            headers.Authorization = `Bearer ${token}`;
        }

        const response = await fetch(`/api/v1/memorials/${memorialId}/candle?lang=${encodeURIComponent(@json($currentLocale))}`, {
            headers,
        });

        const payload = await response.json().catch(() => null);
        if (!response.ok) {
            throw new Error(payload?.message || memorialCandleLabels.messages.loadFailed);
        }

        renderMemorialCandleSummary(payload?.data || null);

        return payload?.data || null;
    }

    async function lightMemorialCandle() {
        const button = document.getElementById('memorialCandleConfirmButton');
        const messageInput = document.getElementById('memorialCandleMessageInput');
        if (!button) {
            return;
        }

        const token = localStorage.getItem('auth_token') || '';
        const originalLabel = button.textContent;
        button.disabled = true;
        button.textContent = memorialCandleLabels.loading;

        try {
            const headers = {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            };

            if (token !== '') {
                headers.Authorization = `Bearer ${token}`;
            }

            const response = await fetch(`/api/v1/memorials/${memorialId}/candle`, {
                method: 'POST',
                headers,
                body: JSON.stringify({
                    locale: @json($currentLocale),
                    message: messageInput ? messageInput.value : '',
                }),
            });

            const payload = await response.json().catch(() => null);

            if (!response.ok) {
                throw new Error(payload?.message || memorialCandleLabels.messages.requestFailed);
            }

            if (messageInput) {
                messageInput.value = '';
            }

            memorialCandleComposerOpen = false;
            renderMemorialCandleSummary(payload?.data || null, {
                successMessage: payload?.message || '',
            });
        } catch (error) {
            memorialCandleComposerOpen = true;
            button.disabled = false;
            button.textContent = originalLabel;
            renderMemorialCandleSummary(memorialCandleState, {
                errorMessage: error?.message || memorialCandleLabels.messages.requestFailed,
                preserveComposer: true,
            });
        }
    }

    async function saveMemorialFamilyCandle(event) {
        event.preventDefault();

        const form = document.getElementById('memorialFamilyForm');
        const button = document.getElementById('memorialFamilySaveButton');
        const messageInput = document.getElementById('memorialFamilyMessageInput');
        const premiumInput = document.getElementById('memorialFamilyPremiumInput');
        const token = localStorage.getItem('auth_token') || '';
        if (!form || !button || token === '') {
            return;
        }

        const originalLabel = button.textContent;
        button.disabled = true;
        button.textContent = memorialCandleLabels.familyManageSaving;

        try {
            const response = await fetch(`/api/v1/memorials/${memorialId}/candle/family`, {
                method: 'PUT',
                headers: {
                    Accept: 'application/json',
                    Authorization: `Bearer ${token}`,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    locale: @json($currentLocale),
                    message: messageInput ? messageInput.value : '',
                    is_premium: Boolean(premiumInput?.checked),
                }),
            });

            const payload = await response.json().catch(() => null);
            if (!response.ok) {
                throw new Error(payload?.message || memorialCandleLabels.messages.requestFailed);
            }

            renderMemorialCandleSummary(payload?.data || null, {
                successMessage: payload?.message || memorialCandleLabels.messages.familySaved,
            });
            showMemorialFamilyManagerFeedback('success', payload?.message || memorialCandleLabels.messages.familySaved);
        } catch (error) {
            showMemorialFamilyManagerFeedback('error', error?.message || memorialCandleLabels.messages.requestFailed);
        } finally {
            button.disabled = false;
            button.textContent = originalLabel;
        }
    }

    async function deleteMemorialFamilyCandle() {
        const button = document.getElementById('memorialFamilyDeleteButton');
        const token = localStorage.getItem('auth_token') || '';
        if (!button || token === '') {
            return;
        }

        if (!window.confirm(memorialCandleLabels.familyManageDeleteConfirm)) {
            return;
        }

        const originalLabel = button.textContent;
        button.disabled = true;
        button.textContent = memorialCandleLabels.familyManageSaving;

        try {
            const response = await fetch(`/api/v1/memorials/${memorialId}/candle/family`, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    Authorization: `Bearer ${token}`,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    locale: @json($currentLocale),
                }),
            });

            const payload = await response.json().catch(() => null);
            if (!response.ok) {
                throw new Error(payload?.message || memorialCandleLabels.messages.requestFailed);
            }

            renderMemorialCandleSummary(payload?.data || null, {
                successMessage: payload?.message || memorialCandleLabels.messages.familyDeleted,
            });
            showMemorialFamilyManagerFeedback('success', payload?.message || memorialCandleLabels.messages.familyDeleted);
        } catch (error) {
            showMemorialFamilyManagerFeedback('error', error?.message || memorialCandleLabels.messages.requestFailed);
        } finally {
            button.disabled = false;
            button.textContent = originalLabel;
        }
    }

    function initializeMemorialCandle() {
        const section = document.getElementById('memorialCandleSection');
        const button = document.getElementById('memorialCandleButton');
        const confirmButton = document.getElementById('memorialCandleConfirmButton');
        const cancelButton = document.getElementById('memorialCandleCancelButton');
        const familyForm = document.getElementById('memorialFamilyForm');
        const familyDeleteButton = document.getElementById('memorialFamilyDeleteButton');

        if (!section || !button) {
            return;
        }

        renderMemorialCandleSummary(memorialCandleInitialState);
        button.addEventListener('click', openMemorialCandleComposer);

        if (confirmButton) {
            confirmButton.addEventListener('click', lightMemorialCandle);
        }

        if (cancelButton) {
            cancelButton.addEventListener('click', closeMemorialCandleComposer);
        }

        if (familyForm) {
            familyForm.addEventListener('submit', saveMemorialFamilyCandle);
        }

        if (familyDeleteButton) {
            familyDeleteButton.addEventListener('click', deleteMemorialFamilyCandle);
        }

        refreshMemorialCandleSummary().catch(() => {
            return;
        });
    }

    function showTributeActionMessage(type, text) {
        const messageBox = document.getElementById('tributeActionMessage');
        if (!messageBox) {
            return;
        }

        messageBox.classList.remove(
            'hidden',
            'border-red-200',
            'bg-red-50',
            'text-red-700',
            'border-green-200',
            'bg-green-50',
            'text-green-800'
        );

        if (type === 'error') {
            messageBox.classList.add('border-red-200', 'bg-red-50', 'text-red-700');
        } else {
            messageBox.classList.add('border-green-200', 'bg-green-50', 'text-green-800');
        }

        messageBox.textContent = text;
    }

    function updateTributeEmptyState() {
        const tributeList = document.getElementById('tributeList');
        const emptyState = document.getElementById('tributeEmptyState');
        if (!tributeList || !emptyState) {
            return;
        }

        const remainingTributes = tributeList.querySelectorAll('[data-tribute-item]');
        emptyState.classList.toggle('hidden', remainingTributes.length !== 0);
    }

    async function deleteTribute(button) {
        const tributeId = String(button?.dataset?.tributeId || '').trim();
        if (!button || tributeId === '') {
            return;
        }

        if (!window.confirm(tributeModerationLabels.confirm)) {
            return;
        }

        const token = localStorage.getItem('auth_token') || '';
        if (token === '') {
            showTributeActionMessage('error', tributeModerationLabels.failed);
            return;
        }

        const originalLabel = button.textContent;
        button.disabled = true;
        button.textContent = tributeModerationLabels.deleting;
        button.classList.add('opacity-70', 'cursor-not-allowed');

        try {
            const response = await fetch(`/api/v1/tributes/${tributeId}`, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    Authorization: `Bearer ${token}`,
                },
            });

            if (!response.ok) {
                throw new Error(`Delete failed with status ${response.status}`);
            }

            const tributeCard = button.closest('[data-tribute-item]');
            if (tributeCard) {
                tributeCard.remove();
            }

            updateTributeEmptyState();
            showTributeActionMessage('success', tributeModerationLabels.success);
        } catch (_error) {
            button.disabled = false;
            button.textContent = originalLabel;
            button.classList.remove('opacity-70', 'cursor-not-allowed');
            showTributeActionMessage('error', tributeModerationLabels.failed);
        }
    }

    async function initializeMemorialOwnerTools() {
        const tributeDeleteButtons = Array.from(document.querySelectorAll('[data-tribute-delete]'));
        const token = localStorage.getItem('auth_token') || '';
        if (token === '') {
            return;
        }

        try {
            const response = await fetch('/api/v1/me', {
                headers: {
                    Accept: 'application/json',
                    Authorization: `Bearer ${token}`,
                },
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            const user = payload?.user || null;
            if (!user) {
                return;
            }

            memorialPageViewer = user;
            syncMemorialFamilyManager(user, memorialCandleState);

            const canModerateTributes = String(user.id || '') === memorialOwnerId || isAdminUser(user);
            if (!canModerateTributes) {
                return;
            }

            tributeDeleteButtons.forEach((button) => {
                button.hidden = false;
                button.addEventListener('click', () => deleteTribute(button));
            });
        } catch (_error) {
            return;
        }
    }

    // Set timestamp for anti-spam protection and initialize tribute moderation
    document.addEventListener('DOMContentLoaded', function() {
        const timestampField = document.getElementById('timestamp');
        if (timestampField) {
            timestampField.value = Math.floor(Date.now() / 1000); // Unix timestamp in seconds
        }

        initializeMemorialCandle();
        initializeMemorialOwnerTools();
    });
</script>
@endpush

@if(config('services.turnstile.site_key'))
    @push('scripts')
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endpush
@endif

@section('content')
<main class="flex-1 py-12 bg-gradient-hero">
    <div class="container mx-auto px-4 max-w-6xl space-y-8">
        <article class="mx-auto max-w-3xl shadow-elegant overflow-hidden border border-border rounded-xl bg-card">
            <div class="pt-8 pb-8 px-6 md:px-8">
                <div class="flex flex-col items-center text-center space-y-6">
                    <div class="relative">
                        @if($profileImageUrl)
                            <img
                                src="{{ $profileImageUrl }}"
                                alt="{{ $memorial->first_name }} {{ $memorial->last_name }}"
                                class="w-48 h-48 md:w-64 md:h-64 object-cover rounded-2xl shadow-elegant"
                            />
                        @else
                            <div class="w-48 h-48 md:w-64 md:h-64 rounded-2xl shadow-elegant bg-muted flex items-center justify-center">
                                <svg class="w-16 h-16 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                        @endif
                        <div class="absolute -bottom-3 left-1/2 -translate-x-1/2">
                            <div class="w-14 h-14 md:w-16 md:h-16 bg-background rounded-full flex items-center justify-center shadow-gold border-2 border-background">
                                <svg class="w-7 h-7 md:w-8 md:h-8" style="color: rgb(224, 186, 133);" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path
                                        d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5A5.5 5.5 0 017.5 3 5.98 5.98 0 0112 5.09 5.98 5.98 0 0116.5 3 5.5 5.5 0 0122 8.5c0 3.78-3.4 6.86-8.55 11.54z"
                                        stroke="currentColor"
                                        stroke-width="2.4"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h1 class="text-3xl md:text-5xl font-serif font-bold text-primary mb-2">
                            {{ $memorial->first_name }}
                        </h1>
                        <h2 class="text-3xl md:text-5xl font-serif font-bold text-primary">
                            {{ $memorial->last_name }}
                        </h2>
                    </div>

                    <div class="inline-flex items-center text-base md:text-lg px-6 py-2 rounded-full bg-secondary text-secondary-foreground">
                        <svg class="h-4 w-4 md:h-5 md:w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        {{ \Carbon\Carbon::parse($memorial->birth_date)->format('d.m.Y.') }}
                        -
                        {{ \Carbon\Carbon::parse($memorial->death_date)->format('d.m.Y.') }}
                    </div>

                    @if($isCandleFeatureEnabled)
                        <div class="w-full max-w-2xl pt-6 border-t border-border">
                            <section
                                id="memorialCandleSection"
                                data-state="{{ (string) ($candleSummary['state'] ?? 'inactive') }}"
                                data-has-history="{{ ((int) ($candleSummary['totalCandles'] ?? 0)) > 0 ? 'true' : 'false' }}"
                                class="memorial-candle-section overflow-hidden rounded-[1.75rem] border border-border/80 bg-transparent p-5 text-left shadow-none md:p-6"
                            >
                                <div class="space-y-5">
                                    <div
                                        id="memorialCandleAnniversaryWrap"
                                        class="rounded-2xl border border-amber-200/80 bg-white/85 px-5 py-4 shadow-sm"
                                        @if(!$anniversaryHighlight)
                                            hidden
                                        @endif
                                    >
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-amber-700/80">{{ __('ui.memorial.candle.anniversary_title') }}</p>
                                        <h4 id="memorialCandleAnniversaryHeadline" class="mt-2 text-lg font-serif font-semibold text-primary">
                                            {{ $anniversaryHighlight['headline'] ?? '' }}
                                        </h4>
                                        <p id="memorialCandleAnniversaryDescription" class="mt-2 text-sm leading-relaxed text-muted-foreground">
                                            {{ $anniversaryHighlight['description'] ?? '' }}
                                        </p>
                                    </div>

                                    <div
                                        id="memorialFamilyCandleWrap"
                                        class="rounded-2xl border border-amber-300/80 bg-[linear-gradient(135deg,rgba(255,250,240,0.95),rgba(255,244,220,0.92))] px-5 py-5 shadow-[0_10px_24px_rgba(191,144,66,0.14)]"
                                        @if(!$familyCandle)
                                            hidden
                                        @endif
                                    >
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-800">{{ __('ui.memorial.candle.family_badge') }}</span>
                                            <span
                                                id="memorialFamilyCandlePremiumBadge"
                                                class="inline-flex items-center rounded-full bg-stone-900 px-2.5 py-1 text-[11px] font-semibold text-white"
                                                @if(!($familyCandle['isPremium'] ?? false))
                                                    hidden
                                                @endif
                                            >
                                                {{ __('ui.memorial.candle.premium_badge') }}
                                            </span>
                                        </div>
                                        <h4 class="mt-3 text-xl font-serif font-semibold text-primary">{{ __('ui.memorial.candle.family_title') }}</h4>
                                        <p class="mt-1 text-sm text-muted-foreground">{{ __('ui.memorial.candle.family_subtitle') }}</p>
                                        <div class="mt-4 rounded-xl border border-border/60 bg-white/85 px-4 py-4">
                                            <p id="memorialFamilyCandleLighter" class="text-sm font-semibold text-foreground">{{ $familyCandle['lighterName'] ?? '' }}</p>
                                            <details
                                                id="memorialFamilyCandleMessageWrap"
                                                class="memorial-candle-message-disclosure mt-2 rounded-xl border border-border/70 bg-white/70 px-3 py-2"
                                                @if(!($familyCandle['message'] ?? null))
                                                    hidden
                                                @endif
                                            >
                                                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-xs font-semibold uppercase tracking-[0.18em] text-amber-800/90">
                                                    <span>{{ __('ui.memorial.candle.message_toggle') }}</span>
                                                    <svg class="h-3.5 w-3.5 shrink-0 transition-transform duration-300" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                                        <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                </summary>
                                                <p
                                                    id="memorialFamilyCandleMessage"
                                                    class="mt-3 text-sm leading-relaxed text-muted-foreground"
                                                >{{ $familyCandle['message'] ?? '' }}</p>
                                            </details>
                                            <p id="memorialFamilyCandleMeta" class="mt-3 text-xs font-medium uppercase tracking-[0.22em] text-amber-800/90">
                                                @if($familyCandle)
                                                    @if($familyCandle['isPermanent'] ?? false)
                                                        {{ __('ui.memorial.candle.permanent_label') }}
                                                    @elseif(isset($familyCandle['secondsRemaining']))
                                                        {{ __('ui.memorial.candle.remaining') }}: {{ max(1, (int) ceil(((int) $familyCandle['secondsRemaining']) / 60)) }}{{ __('ui.memorial.candle.countdown_units.minute') }}
                                                    @endif
                                                @endif
                                            </p>
                                        </div>
                                    </div>

                                    <div class="grid gap-6 lg:grid-cols-[170px_1fr] lg:items-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="memorial-candle-stage rounded-[1.75rem] bg-[radial-gradient(circle_at_center,rgba(31,27,24,0.98)_0%,rgba(11,10,9,0.98)_100%)] px-7 py-5">
                                                <div class="relative flex h-[8.5rem] w-[4.5rem] items-end justify-center">
                                                    <div class="memorial-candle-glow absolute bottom-[4.65rem] h-[3.15rem] w-[3.15rem] rounded-full bg-[radial-gradient(circle,rgba(255,190,80,0.22),transparent_70%)]"></div>
                                                    <div class="memorial-candle-halo absolute bottom-[4.45rem] h-[2.3rem] w-[2.3rem] rounded-full bg-amber-200/35 blur-md"></div>
                                                    <div class="memorial-candle-smoke absolute bottom-[4.85rem] h-8 w-4 rounded-full bg-stone-400/60 blur-[3px]"></div>
                                                    <div class="absolute bottom-[4.55rem] h-[0.65rem] w-[2px] rounded-full bg-stone-900"></div>
                                                    <div class="memorial-candle-flame absolute bottom-[4.82rem] h-[1.6rem] w-[0.78rem] rounded-[50%_50%_45%_45%] bg-[radial-gradient(circle_at_50%_30%,#fff7c2_0%,#ffd36a_40%,#ff8c2a_75%,transparent_100%)] blur-[0.4px]"></div>
                                                    <div class="memorial-candle-inner-flame absolute bottom-[5.05rem] h-[0.95rem] w-[0.38rem] rounded-[50%_50%_45%_45%] bg-gradient-to-t from-orange-300 via-yellow-100 to-white"></div>
                                                    <div class="memorial-candle-body relative h-[5.5rem] w-[1.15rem] overflow-hidden rounded-[0.5rem] bg-gradient-to-b from-[#f5f5f5] to-[#d9d9d9] shadow-[inset_0_-5px_10px_rgba(0,0,0,0.15)]">
                                                        <div class="absolute inset-x-0 top-0 h-4 rounded-t-[0.5rem] bg-white/55"></div>
                                                    </div>
                                                    <div class="absolute bottom-0 h-3 w-16 rounded-full bg-black/35 blur-md"></div>
                                                </div>
                                            </div>

                                            <p
                                                id="memorialCandleVisualStatus"
                                                class="inline-flex items-center rounded-full border px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] {{ $primaryVisibleCandle ? 'border-amber-200 bg-amber-100 text-amber-900' : 'border-stone-300/80 bg-stone-200/80 text-stone-700' }}"
                                            >
                                                {{ $primaryVisibleCandle ? __('ui.memorial.candle.visual_status_active') : __('ui.memorial.candle.visual_status_inactive') }}
                                            </p>

                                            <div class="text-center">
                                                <p id="memorialCandleEyebrow" class="text-[11px] font-semibold uppercase tracking-[0.28em] text-amber-700/80">
                                                    {{ $primaryVisibleCandle ? __('ui.memorial.candle.eyebrow_active') : __('ui.memorial.candle.eyebrow_inactive') }}
                                                </p>
                                                <p id="memorialCandleCountBadge" class="mt-2 text-sm text-muted-foreground">
                                                    {{ (int) ($candleSummary['totalCandles'] ?? 0) }} {{ __('ui.memorial.candle.count_label') }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="space-y-4">
                                            <div>
                                                <h3 class="text-2xl font-serif font-semibold text-primary">{{ __('ui.memorial.candle.title') }}</h3>
                                                <p id="memorialCandleSubtitle" class="mt-2 text-sm leading-relaxed text-muted-foreground md:text-base">
                                                    @if($activeCandle)
                                                        {{ __('ui.memorial.candle.subtitle_active') }}
                                                    @elseif($familyCandle)
                                                        {{ __('ui.memorial.candle.family_subtitle') }}
                                                    @else
                                                        {{ __('ui.memorial.candle.subtitle_inactive') }}
                                                    @endif
                                                </p>
                                            </div>

                                            <div class="flex flex-wrap gap-3 text-sm">
                                                <div
                                                    id="memorialCandleCountdownWrap"
                                                    class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-white/90 px-4 py-2 text-foreground"
                                                    @if(!(($primaryVisibleCandle['expiresAt'] ?? null) && !($primaryVisibleCandle['isPermanent'] ?? false) && ($candleSettings['showCountdown'] ?? false)))
                                                        hidden
                                                    @endif
                                                >
                                                    <span class="text-muted-foreground">{{ __('ui.memorial.candle.remaining') }}</span>
                                                    <span id="memorialCandleCountdownValue" class="font-semibold text-primary">
                                                        @if($primaryVisibleCandle && !($primaryVisibleCandle['isPermanent'] ?? false))
                                                            {{ max(1, (int) ceil(((int) ($primaryVisibleCandle['secondsRemaining'] ?? 0)) / 60)) }}{{ __('ui.memorial.candle.countdown_units.minute') }}
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>

                                            <div
                                                id="memorialCandleCurrentLighterWrap"
                                                class="rounded-xl border border-border/70 bg-white/80 px-4 py-3"
                                                @if(!$primaryVisibleCandle)
                                                    hidden
                                                @endif
                                            >
                                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-muted-foreground">{{ __('ui.memorial.candle.current_lighter') }}</p>
                                                <p id="memorialCandleCurrentLighter" class="mt-1 text-sm font-medium text-foreground">
                                                    {{ $primaryVisibleCandle['lighterName'] ?? '' }}
                                                </p>
                                                <details
                                                    id="memorialCandleCurrentMessageWrap"
                                                    class="memorial-candle-message-disclosure mt-2 rounded-xl border border-border/70 bg-white/70 px-3 py-2"
                                                    @if(!($primaryVisibleCandle['message'] ?? null))
                                                        hidden
                                                    @endif
                                                >
                                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-xs font-semibold uppercase tracking-[0.18em] text-amber-800/90">
                                                        <span>{{ __('ui.memorial.candle.message_toggle') }}</span>
                                                        <svg class="h-3.5 w-3.5 shrink-0 transition-transform duration-300" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                                            <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                                        </svg>
                                                    </summary>
                                                    <p id="memorialCandleCurrentMessage" class="mt-3 text-sm leading-relaxed text-muted-foreground">{{ $primaryVisibleCandle['message'] ?? '' }}</p>
                                                </details>
                                            </div>

                                            <div
                                                id="memorialCandleComposer"
                                                class="space-y-3 rounded-2xl border border-amber-200/80 bg-white/85 px-4 py-4 shadow-sm"
                                                hidden
                                            >
                                                <div
                                                    id="memorialCandleMessageInputWrap"
                                                    class="space-y-2 rounded-xl border border-border/70 bg-white/80 px-4 py-4"
                                                    @if(!($candleSettings['messagesEnabled'] ?? false))
                                                        hidden
                                                    @endif
                                                >
                                                    <label for="memorialCandleMessageInput" class="block text-sm font-medium text-foreground">{{ __('ui.memorial.candle.message_label') }}</label>
                                                    <textarea
                                                        id="memorialCandleMessageInput"
                                                        rows="3"
                                                        maxlength="280"
                                                        placeholder="{{ __('ui.memorial.candle.message_placeholder') }}"
                                                        class="w-full rounded-xl border border-border bg-background px-4 py-3 text-sm leading-relaxed text-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/20"
                                                    ></textarea>
                                                    <p class="text-xs text-muted-foreground">{{ __('ui.memorial.candle.message_hint') }}</p>
                                                </div>

                                                <div class="flex flex-wrap gap-3">
                                                    <button
                                                        id="memorialCandleConfirmButton"
                                                        type="button"
                                                        class="inline-flex h-11 items-center justify-center rounded-xl bg-gradient-to-r from-[#d6a257] via-[#efc06a] to-[#f6d7a2] px-5 text-sm font-semibold text-stone-900 shadow-[0_14px_28px_rgba(190,141,62,0.24)] transition-all hover:-translate-y-0.5 hover:shadow-[0_18px_30px_rgba(190,141,62,0.28)] disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:translate-y-0"
                                                        hidden
                                                    >
                                                        {{ __('ui.memorial.candle.light_button') }}
                                                    </button>
                                                    <button
                                                        id="memorialCandleCancelButton"
                                                        type="button"
                                                        class="inline-flex h-11 items-center justify-center rounded-xl border border-border px-5 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                                                        hidden
                                                    >
                                                        {{ __('ui.memorial_form.cancel') }}
                                                    </button>
                                                </div>
                                            </div>

                                            <div
                                                id="memorialCandleWallWrap"
                                                class="space-y-3"
                                                @if(!($candleSettings['showWall'] ?? false))
                                                    hidden
                                                @endif
                                            >
                                                <div class="flex items-center justify-between gap-3">
                                                    <h4 class="text-lg font-serif font-semibold text-primary">{{ __('ui.memorial.candle.wall_title') }}</h4>
                                                    <span class="text-xs uppercase tracking-[0.22em] text-muted-foreground">{{ $candleWallItems->count() }}</span>
                                                </div>
                                                <p
                                                    id="memorialCandleWallEmpty"
                                                    class="rounded-xl border border-dashed border-border/80 bg-white/70 px-4 py-4 text-sm text-muted-foreground"
                                                    @if($candleWallItems->isNotEmpty())
                                                        hidden
                                                    @endif
                                                >
                                                    {{ __('ui.memorial.candle.wall_empty') }}
                                                </p>
                                                <div id="memorialCandleWallGrid" class="grid gap-3 sm:grid-cols-2">
                                                    @foreach($candleWallItems as $wallCandle)
                                                        <article class="rounded-2xl border border-border/70 bg-white/85 px-4 py-4 shadow-sm">
                                                            <div class="flex items-start gap-3">
                                                                <div class="mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-lg">🕯</div>
                                                                <div class="min-w-0 flex-1">
                                                                    <div class="flex flex-wrap items-center gap-2">
                                                                        @if($wallCandle['isFamily'] ?? false)
                                                                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-800">{{ __('ui.memorial.candle.family_badge') }}</span>
                                                                        @endif
                                                                        @if($wallCandle['isPremium'] ?? false)
                                                                            <span class="inline-flex items-center rounded-full bg-stone-900 px-2.5 py-1 text-[11px] font-semibold text-white">{{ __('ui.memorial.candle.premium_badge') }}</span>
                                                                        @endif
                                                                    </div>
                                                                    <p class="mt-2 text-sm font-semibold text-foreground">{{ $wallCandle['lighterName'] ?? '' }}</p>
                                                                    <p class="mt-1 text-xs text-muted-foreground">
                                                                        @if(isset($wallCandle['litAt']))
                                                                            {{ \Carbon\Carbon::parse($wallCandle['litAt'])->translatedFormat('d. M Y.') }}
                                                                        @endif
                                                                    </p>
                                                                    @if($wallCandle['message'] ?? null)
                                                                        <details class="memorial-candle-message-disclosure mt-2 rounded-xl border border-border/70 bg-white/70 px-3 py-2">
                                                                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-xs font-semibold uppercase tracking-[0.18em] text-amber-800/90">
                                                                                <span>{{ __('ui.memorial.candle.message_toggle') }}</span>
                                                                                <svg class="h-3.5 w-3.5 shrink-0 transition-transform duration-300" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                                                                    <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                                                                </svg>
                                                                            </summary>
                                                                            <p class="mt-3 text-sm leading-relaxed text-muted-foreground">{{ $wallCandle['message'] ?? '' }}</p>
                                                                        </details>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </article>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <div
                                                id="memorialCandleRecentWrap"
                                                class="space-y-2"
                                                @if(!(($candleSettings['showRecentLighters'] ?? false) && $recentCandleLighters->isNotEmpty()))
                                                hidden
                                            @endif
                                        >
                                                <p class="text-sm font-medium text-primary">{{ __('ui.memorial.candle.recent_lighters') }}</p>
                                                <ul id="memorialCandleRecentList" class="grid gap-2 sm:grid-cols-2">
                                                    @foreach($recentCandleLighters as $lighter)
                                                        <li class="rounded-xl border border-border/70 bg-white/80 px-4 py-3">
                                                            <p class="text-sm font-medium text-foreground">{{ $lighter['lighterName'] ?? '' }}</p>
                                                            <p class="mt-1 text-xs text-muted-foreground">
                                                                @if(isset($lighter['litAt']))
                                                                    {{ \Carbon\Carbon::parse($lighter['litAt'])->translatedFormat('d. M Y.') }}
                                                                @endif
                                                            </p>
                                                            @if($lighter['message'] ?? null)
                                                                <details class="memorial-candle-message-disclosure mt-2 rounded-xl border border-border/70 bg-white/70 px-3 py-2">
                                                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-xs font-semibold uppercase tracking-[0.18em] text-amber-800/90">
                                                                        <span>{{ __('ui.memorial.candle.message_toggle') }}</span>
                                                                        <svg class="h-3.5 w-3.5 shrink-0 transition-transform duration-300" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                                                            <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                                                        </svg>
                                                                    </summary>
                                                                    <p class="mt-3 text-sm text-muted-foreground">{{ $lighter['message'] ?? '' }}</p>
                                                                </details>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>

                                            <div
                                                id="memorialFamilyManager"
                                                class="space-y-3 rounded-2xl border border-amber-200/80 bg-white/85 px-4 py-4 shadow-sm"
                                                hidden
                                            >
                                                <div>
                                                    <h4 class="text-lg font-serif font-semibold text-primary">{{ __('ui.memorial.candle.family_manage_title') }}</h4>
                                                    <p class="mt-1 text-sm text-muted-foreground">{{ __('ui.memorial.candle.family_manage_hint') }}</p>
                                                </div>

                                                <form id="memorialFamilyForm" class="space-y-3">
                                                    <div class="space-y-2">
                                                        <label for="memorialFamilyMessageInput" class="block text-sm font-medium text-foreground">{{ __('ui.memorial.candle.family_manage_message_label') }}</label>
                                                        <textarea
                                                            id="memorialFamilyMessageInput"
                                                            rows="3"
                                                            maxlength="320"
                                                            placeholder="{{ __('ui.memorial.candle.family_manage_message_placeholder') }}"
                                                            class="w-full rounded-xl border border-border bg-background px-4 py-3 text-sm leading-relaxed text-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/20"
                                                        ></textarea>
                                                    </div>

                                                    <label
                                                        id="memorialFamilyPremiumWrap"
                                                        class="flex items-center gap-3 rounded-xl border border-border/70 bg-background px-4 py-3 text-sm text-foreground"
                                                        hidden
                                                    >
                                                        <input id="memorialFamilyPremiumInput" type="checkbox" class="h-4 w-4 rounded border-border text-accent focus:ring-ring">
                                                        <span>{{ __('ui.memorial.candle.family_manage_premium_label') }}</span>
                                                    </label>

                                                    <div class="flex flex-wrap gap-3">
                                                        <button
                                                            id="memorialFamilySaveButton"
                                                            type="submit"
                                                            class="inline-flex h-10 items-center justify-center rounded-xl bg-stone-900 px-4 text-sm font-semibold text-white transition-opacity hover:opacity-90"
                                                        >
                                                            {{ __('ui.memorial.candle.family_manage_save') }}
                                                        </button>
                                                        <button
                                                            id="memorialFamilyDeleteButton"
                                                            type="button"
                                                            class="inline-flex h-10 items-center justify-center rounded-xl border border-border px-4 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                                                            hidden
                                                        >
                                                            {{ __('ui.memorial.candle.family_manage_delete') }}
                                                        </button>
                                                    </div>

                                                    <p id="memorialFamilyManagerFeedback" class="hidden text-sm"></p>
                                                </form>
                                            </div>

                                            <div class="space-y-3">
                                                <button
                                                    id="memorialCandleButton"
                                                    type="button"
                                                    class="inline-flex h-11 items-center justify-center rounded-xl bg-gradient-to-r from-[#d6a257] via-[#efc06a] to-[#f6d7a2] px-5 text-sm font-semibold text-stone-900 shadow-[0_14px_28px_rgba(190,141,62,0.24)] transition-all hover:-translate-y-0.5 hover:shadow-[0_18px_30px_rgba(190,141,62,0.28)] disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:translate-y-0"
                                                >
                                                    @if(($candleSummary['reason'] ?? '') === 'auth_required')
                                                        {{ __('ui.memorial.candle.login_button') }}
                                                    @elseif($candleSummary['canLight'] ?? false)
                                                        {{ __('ui.memorial.candle.light_button') }}
                                                    @elseif($activeCandle)
                                                        {{ __('ui.memorial.candle.active_button') }}
                                                    @else
                                                        {{ __('ui.memorial.candle.light_button') }}
                                                    @endif
                                                </button>
                                                <p id="memorialCandleFeedback" class="text-sm text-muted-foreground">
                                                    @if($primaryVisibleCandle)
                                                        {{ __('ui.memorial.candle.messages.active') }}
                                                    @elseif(($candleSummary['reason'] ?? '') === 'auth_required')
                                                        {{ __('ui.memorial.candle.messages.login_required') }}
                                                    @else
                                                        {{ __('ui.memorial.candle.messages.ready') }}
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    @endif

                    @if($localizedBirthPlace || $localizedDeathPlace)
                        <div class="w-full max-w-md pt-4 space-y-2 border-t border-border">
                            @if($localizedBirthPlace)
                                <div class="flex items-center justify-center gap-2 text-muted-foreground">
                                    <svg class="h-4 w-4 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0L6.343 16.657a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span class="text-sm md:text-base">{{ __('ui.memorial.born_in', ['place' => $localizedBirthPlace]) }}</span>
                                </div>
                            @endif
                            @if($localizedDeathPlace)
                                <div class="flex items-center justify-center gap-2 text-muted-foreground">
                                    <svg class="h-4 w-4 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0L6.343 16.657a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span class="text-sm md:text-base">{{ __('ui.memorial.died_in', ['place' => $localizedDeathPlace]) }}</span>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if($localizedBiography)
                        <div class="w-full max-w-2xl pt-6 border-t border-border text-left">
                            <h3 class="text-2xl font-serif font-semibold mb-4 text-primary text-center">{{ __('ui.memorial.biography') }}</h3>
                            <p class="text-foreground leading-relaxed whitespace-pre-wrap">{{ $localizedBiography }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </article>

        @if($galleryImages->count() > 0)
            <section class="shadow-elegant overflow-hidden border border-border rounded-xl bg-card">
                <header class="p-6 border-b border-border">
                    <h2 class="text-2xl font-serif font-semibold text-primary">{{ __('ui.memorial.gallery') }}</h2>
                </header>
                <div class="p-6">
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        @foreach($galleryImages as $index => $image)
                            @php
                                $imageUrl = \App\Support\MediaUrl::normalize($image->image_url);
                            @endphp
                            <button
                                type="button"
                                onclick="openLightbox({{ $index }})"
                                class="group relative aspect-[4/5] rounded-lg overflow-hidden text-left"
                            >
                                <img
                                    src="{{ $imageUrl }}"
                                    alt="{{ $image->caption ?: ($memorial->first_name . ' ' . $memorial->last_name) }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                />
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors"></div>
                            </button>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if($memorial->videos && $memorial->videos->count() > 0)
            <section class="shadow-elegant overflow-hidden border border-border rounded-xl bg-card">
                <header class="p-6 border-b border-border">
                    <h2 class="text-2xl font-serif font-semibold text-primary">{{ __('ui.memorial.video_gallery') }}</h2>
                </header>
                <div class="p-6">
                    <div class="grid gap-6 lg:grid-cols-2">
                        @foreach($memorial->videos as $video)
                            @php
                                $videoId = null;
                                $youtubeUrl = trim((string) $video->youtube_url);

                                if ($youtubeUrl !== '') {
                                    if (preg_match('/^[A-Za-z0-9_-]{11}$/', $youtubeUrl) === 1) {
                                        $videoId = $youtubeUrl;
                                    } else {
                                        $parts = parse_url($youtubeUrl);
                                        $host = strtolower((string) ($parts['host'] ?? ''));
                                        $path = trim((string) ($parts['path'] ?? ''), '/');
                                        $segments = $path === '' ? [] : explode('/', $path);

                                        parse_str((string) ($parts['query'] ?? ''), $query);
                                        $queryVideoId = (string) ($query['v'] ?? '');
                                        if ($queryVideoId !== '' && preg_match('/^[A-Za-z0-9_-]{11}$/', $queryVideoId) === 1) {
                                            $videoId = $queryVideoId;
                                        } elseif (str_contains($host, 'youtu.be') && isset($segments[0]) && preg_match('/^[A-Za-z0-9_-]{11}$/', $segments[0]) === 1) {
                                            $videoId = $segments[0];
                                        } elseif (isset($segments[0], $segments[1]) && in_array($segments[0], ['embed', 'shorts', 'live', 'v'], true) && preg_match('/^[A-Za-z0-9_-]{11}$/', $segments[1]) === 1) {
                                            $videoId = $segments[1];
                                        } elseif (isset($segments[0]) && preg_match('/^[A-Za-z0-9_-]{11}$/', $segments[0]) === 1) {
                                            $videoId = $segments[0];
                                        } elseif (preg_match('/(?:v=|\/)([A-Za-z0-9_-]{11})(?:[?&#\/]|$)/', $youtubeUrl, $matches) === 1) {
                                            $videoId = $matches[1];
                                        }
                                    }
                                }
                            @endphp
                            <article class="space-y-2">
                                <div class="w-full aspect-video rounded-lg overflow-hidden bg-muted">
                                    @if($videoId)
                                        <iframe
                                            class="w-full h-full"
                                            src="https://www.youtube.com/embed/{{ $videoId }}"
                                            title="{{ $video->title ?: __('ui.memorial.video_default_title') }}"
                                            frameborder="0"
                                            allowfullscreen
                                        ></iframe>
                                    @else
                                        <div class="w-full h-full flex items-center justify-center p-4 text-center text-muted-foreground">
                                            <a href="{{ $video->youtube_url }}" target="_blank" rel="noopener noreferrer" class="underline">{{ __('ui.memorial.open_video') }}</a>
                                        </div>
                                    @endif
                                </div>
                                @if($video->title)
                                    <h3 class="font-medium text-foreground">{{ $video->title }}</h3>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <section class="mx-auto max-w-3xl shadow-elegant overflow-hidden border border-border rounded-xl bg-card">
            <header class="p-6 border-b border-border">
                <h2 class="text-2xl font-serif font-semibold text-primary">{{ __('ui.memorial.share_title') }}</h2>
            </header>
            <div class="p-6">
                <p class="text-muted-foreground mb-4">{{ __('ui.memorial.share_desc') }}</p>
                <div class="flex flex-wrap gap-3">
                    <a
                        href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('memorial.profile', ['locale' => $currentLocale, 'slug' => $memorial->slug])) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center justify-center px-5 h-11 rounded-lg border border-border hover:border-accent hover:text-accent transition-colors"
                    >
                        {{ __('ui.buttons.facebook') }}
                    </a>
                    <a
                        href="https://wa.me/?text={{ urlencode(__('ui.memorial.share_whatsapp_text', ['name' => $memorialFullName, 'url' => route('memorial.profile', ['locale' => $currentLocale, 'slug' => $memorial->slug])])) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center justify-center px-5 h-11 rounded-lg border border-border hover:border-accent hover:text-accent transition-colors"
                    >
                        {{ __('ui.buttons.whatsapp') }}
                    </a>
                    <button
                        type="button"
                        onclick="copyMemorialLink()"
                        class="inline-flex items-center justify-center px-5 h-11 rounded-lg border border-border hover:border-accent hover:text-accent transition-colors"
                    >
                        {{ __('ui.buttons.copy_link') }}
                    </button>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-3xl shadow-elegant border border-border rounded-xl bg-card">
            <header class="p-6 border-b border-border">
                <h2 class="text-2xl font-serif font-semibold text-primary">{{ __('ui.memorial.tributes_title') }}</h2>
            </header>

            <div class="p-6">
                @if(session('success'))
                    <div class="mb-6 p-4 rounded-lg border border-green-200 bg-green-50 text-green-800">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-6 p-4 rounded-lg border border-red-200 bg-red-50 text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div id="tributeActionMessage" class="hidden mb-6 p-4 rounded-lg border text-sm"></div>

                @php
                    $formRenderedAt = now()->timestamp;
                    $formSignature = hash_hmac(
                        'sha256',
                        $formRenderedAt.'|'.$memorial->id.'|'.session()->getId(),
                        (string) config('app.key')
                    );
                    $mathChallenge = \App\Support\TributeMathChallenge::issue(
                        (string) $memorial->id,
                        (string) session()->getId(),
                        $formRenderedAt
                    );
                    $turnstileSiteKey = (string) config('services.turnstile.site_key');
                @endphp

                <form action="{{ route('tributes.store', ['memorial' => $memorial]) }}" method="POST" class="space-y-4 mb-8 p-4 bg-muted/30 rounded-lg" onsubmit="handleTributeSubmit(event)">
                    @csrf
                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label for="author_name" class="block text-sm font-medium text-foreground">{{ __('ui.memorial.your_name') }} *</label>
                            <input
                                id="author_name"
                                name="author_name"
                                type="text"
                                required
                                value="{{ old('author_name') }}"
                                class="w-full px-4 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-transparent bg-background"
                            />
                        </div>
                        <div class="space-y-2">
                            <label for="author_email" class="block text-sm font-medium text-foreground">{{ __('ui.memorial.your_email') }} *</label>
                            <input
                                id="author_email"
                                name="author_email"
                                type="email"
                                required
                                value="{{ old('author_email') }}"
                                class="w-full px-4 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-transparent bg-background"
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="message" class="block text-sm font-medium text-foreground">{{ __('ui.memorial.your_message') }} *</label>
                        <textarea
                            id="message"
                            name="message"
                            required
                            minlength="10"
                            rows="4"
                            placeholder="{{ __('ui.memorial.message_placeholder') }}"
                            class="w-full px-4 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-transparent bg-background"
                        >{{ old('message') }}</textarea>
                    </div>

                    <div class="space-y-2">
                        <label for="math_answer" class="block text-sm font-medium text-foreground">{{ __('ui.memorial.math_check') }} *</label>
                        <div class="flex flex-col gap-3 rounded-lg border border-border bg-background p-3 sm:flex-row sm:items-center">
                            <img
                                src="{{ $mathChallenge['image_data_uri'] }}"
                                alt="{{ __('ui.memorial.math_image_alt') }}"
                                class="h-[58px] w-[180px] rounded-md border border-border/60 bg-muted object-contain"
                                width="180"
                                height="58"
                            />
                            <div class="min-w-0 flex-1 space-y-2">
                                <p class="text-sm text-muted-foreground">{{ __('ui.memorial.math_check_desc') }}</p>
                                <input
                                    id="math_answer"
                                    name="math_answer"
                                    type="text"
                                    inputmode="numeric"
                                    required
                                    autocomplete="off"
                                    value="{{ old('math_answer') }}"
                                    placeholder="{{ __('ui.memorial.math_placeholder') }}"
                                    class="w-full max-w-xs px-4 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-transparent bg-background"
                                />
                            </div>
                        </div>
                    </div>

                    @if($turnstileSiteKey !== '')
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-foreground">{{ __('ui.memorial.security_check') }} *</label>
                            <div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}"></div>
                            <p class="text-xs text-muted-foreground">{{ __('ui.memorial.security_check_desc') }}</p>
                        </div>
                    @endif

                    <input
                        type="text"
                        name="honeypot"
                        value=""
                        tabindex="-1"
                        autocomplete="off"
                        style="display:none"
                    />
                    <input type="hidden" name="timestamp" id="timestamp" value="" />
                    <input type="hidden" name="form_rendered_at" value="{{ $formRenderedAt }}" />
                    <input type="hidden" name="form_signature" value="{{ $formSignature }}" />
                    <input type="hidden" name="math_challenge_payload" value="{{ $mathChallenge['payload'] }}" />

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center px-6 h-10 rounded-lg bg-gradient-accent text-accent-foreground hover:opacity-90 transition-opacity"
                    >
                        {{ __('ui.memorial.submit_message') }}
                    </button>
                </form>

                <div id="tributeList" class="space-y-4">
                    @foreach($memorial->tributes as $tribute)
                        <article class="border border-border rounded-lg p-4 hover:shadow-md transition-shadow" data-tribute-item>
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-primary">
                                        {{ $tribute->author_name ?? $tribute->name ?? __('ui.memorial.guest') }}
                                    </p>
                                    <p class="text-sm text-muted-foreground flex items-center gap-2">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        {{ $tribute->created_at ? $tribute->created_at->translatedFormat('j. F Y.') : '' }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    hidden
                                    data-tribute-delete
                                    data-tribute-id="{{ $tribute->id }}"
                                    class="inline-flex items-center justify-center px-3 h-9 rounded-lg border border-border text-sm font-medium hover:border-red-300 hover:bg-red-50 hover:text-red-700 transition-colors"
                                >
                                    {{ __('ui.memorial.delete_message') }}
                                </button>
                            </div>
                            <p class="mt-3 text-foreground leading-relaxed">{{ $tribute->message }}</p>
                        </article>
                    @endforeach
                    <article id="tributeEmptyState" class="border border-dashed border-border rounded-lg py-12 text-center {{ $memorial->tributes->isNotEmpty() ? 'hidden' : '' }}">
                        <p class="text-muted-foreground">{{ __('ui.memorial.no_tributes') }}</p>
                    </article>
                </div>
            </div>
        </section>

    </div>
</main>

<div id="lightbox" class="fixed inset-0 bg-black/90 z-50 hidden items-center justify-center p-4" onclick="closeLightbox(event)">
    <button type="button" onclick="closeLightbox()" class="absolute top-4 right-4 text-white hover:text-accent transition-colors">
        <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>

    <button type="button" onclick="previousImage(event)" class="absolute left-4 text-white hover:text-accent transition-colors">
        <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </button>

    <div class="max-w-6xl max-h-[90vh] flex items-center justify-center">
        <img id="lightbox-image" src="" alt="" class="max-w-full max-h-[90vh] object-contain rounded-lg shadow-2xl" onclick="event.stopPropagation()">
    </div>

    <button type="button" onclick="nextImage(event)" class="absolute right-4 text-white hover:text-accent transition-colors">
        <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </button>

    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 text-white text-sm bg-black/50 px-4 py-2 rounded-full">
        <span id="lightbox-counter"></span>
    </div>
</div>
@endsection

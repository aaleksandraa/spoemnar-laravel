@extends('layouts.app')

@php
    $currentLocale = app()->getLocale();
    $memorialFullName = trim($memorial->first_name.' '.$memorial->last_name);
    $memorialBirthYear = \Carbon\Carbon::parse($memorial->birth_date)->format('Y');
    $memorialDeathYear = \Carbon\Carbon::parse($memorial->death_date)->format('Y');
    $profileImageUrl = \App\Support\MediaUrl::normalize($memorial->profile_image_url);
    $galleryImages = $memorial->images
        ->filter(static function ($image) use ($profileImageUrl) {
            $imageUrl = \App\Support\MediaUrl::normalize($image->image_url);
            return is_string($imageUrl) && $imageUrl !== '' && $imageUrl !== $profileImageUrl;
        })
        ->values();
    $galleryImageUrls = $galleryImages
        ->map(static fn ($image) => \App\Support\MediaUrl::normalize($image->image_url))
        ->values();
    $memorialSeoDescription = __('ui.memorial.seo_profile_description', [
        'name' => $memorialFullName,
        'birth' => $memorialBirthYear,
        'death' => $memorialDeathYear,
    ]);
@endphp

@section('title', __('ui.memorial.seo_profile_title', ['name' => $memorialFullName]))
@section('meta_description', $memorialSeoDescription)
@section('og_title', __('ui.memorial.seo_profile_title', ['name' => $memorialFullName]))
@section('og_description', $memorialSeoDescription)

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
@endsection

@push('scripts')
<script>
    const memorialImages = @json($galleryImageUrls);
    let currentImageIndex = 0;
    const memorialUrl = @json(route('memorial.profile', ['locale' => $currentLocale, 'slug' => $memorial->slug]));

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

    // Set timestamp for anti-spam protection
    document.addEventListener('DOMContentLoaded', function() {
        const timestampField = document.getElementById('timestamp');
        if (timestampField) {
            timestampField.value = Math.floor(Date.now() / 1000); // Unix timestamp in seconds
        }
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

                    @if($memorial->birth_place || $memorial->death_place)
                        <div class="w-full max-w-md pt-4 space-y-2 border-t border-border">
                            @if($memorial->birth_place)
                                <div class="flex items-center justify-center gap-2 text-muted-foreground">
                                    <svg class="h-4 w-4 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0L6.343 16.657a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span class="text-sm md:text-base">{{ __('ui.memorial.born_in', ['place' => $memorial->birth_place]) }}</span>
                                </div>
                            @endif
                            @if($memorial->death_place)
                                <div class="flex items-center justify-center gap-2 text-muted-foreground">
                                    <svg class="h-4 w-4 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0L6.343 16.657a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span class="text-sm md:text-base">{{ __('ui.memorial.died_in', ['place' => $memorial->death_place]) }}</span>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if($memorial->biography)
                        <div class="w-full max-w-2xl pt-6 border-t border-border text-left">
                            <h3 class="text-2xl font-serif font-semibold mb-4 text-primary text-center">{{ __('ui.memorial.biography') }}</h3>
                            <p class="text-foreground leading-relaxed whitespace-pre-wrap">{{ $memorial->biography }}</p>
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

                @php
                    $formRenderedAt = now()->timestamp;
                    $formSignature = hash_hmac(
                        'sha256',
                        $formRenderedAt.'|'.$memorial->id.'|'.session()->getId(),
                        (string) config('app.key')
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

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center px-6 h-10 rounded-lg bg-gradient-accent text-accent-foreground hover:opacity-90 transition-opacity"
                    >
                        {{ __('ui.memorial.submit_message') }}
                    </button>
                </form>

                <div class="space-y-4">
                    @forelse($memorial->tributes as $tribute)
                        <article class="border border-border rounded-lg p-4 hover:shadow-md transition-shadow">
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
                            </div>
                            <p class="mt-3 text-foreground leading-relaxed">{{ $tribute->message }}</p>
                        </article>
                    @empty
                        <article class="border border-dashed border-border rounded-lg py-12 text-center">
                            <p class="text-muted-foreground">{{ __('ui.memorial.no_tributes') }}</p>
                        </article>
                    @endforelse
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

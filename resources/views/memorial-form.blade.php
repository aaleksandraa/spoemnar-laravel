@extends('layouts.app')

@php
    $currentLocale = app()->getLocale();
    $pageMode = ($mode ?? 'create') === 'edit' ? 'edit' : 'create';
    $initialSlug = $slug ?? null;
    $dashboardUrl = route('dashboard', ['locale' => $currentLocale]);
    $loginUrl = route('login', ['locale' => $currentLocale]);
@endphp

@section('title', $pageMode === 'edit' ? __('ui.memorial_form.edit_title') : __('ui.memorial_form.create_title'))
@section('meta_description', __('ui.memorial_form.meta_description'))

@push('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
@endpush

@section('content')
<main class="flex-1 bg-gradient-hero py-10 md:py-14">
    <div class="container mx-auto px-4 max-w-4xl">
        <article class="rounded-2xl border border-border bg-card shadow-elegant overflow-hidden">
            <header class="px-6 md:px-8 py-6 border-b border-border">
                <h1 class="text-3xl md:text-4xl font-serif font-bold text-primary">
                    {{ $pageMode === 'edit' ? __('ui.memorial_form.edit_title') : __('ui.memorial_form.create_title') }}
                </h1>
                <p class="text-muted-foreground mt-2">{{ __('ui.memorial_form.subtitle') }}</p>
            </header>

            <div class="p-6 md:p-8 space-y-6">
                <div id="memorialFormAlert" class="hidden rounded-lg border p-4 text-sm"></div>
                <div id="memorialFormLoading" class="text-sm text-muted-foreground">{{ __('ui.memorial_form.loading') }}</div>

                <form id="memorialForm" class="hidden space-y-6" novalidate>
                    @csrf

                    <section class="space-y-4">
                        <h2 class="text-xl font-serif font-semibold text-primary">{{ __('ui.memorial_form.basic_info') }}</h2>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label for="first_name" class="block text-sm font-medium text-foreground">{{ __('ui.memorial_form.first_name') }} *</label>
                                <input id="first_name" name="first_name" type="text" required class="w-full h-11 px-3 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring">
                            </div>
                            <div class="space-y-2">
                                <label for="last_name" class="block text-sm font-medium text-foreground">{{ __('ui.memorial_form.last_name') }} *</label>
                                <input id="last_name" name="last_name" type="text" required class="w-full h-11 px-3 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring">
                            </div>
                            <div class="space-y-2">
                                <label for="birth_date" class="block text-sm font-medium text-foreground">{{ __('ui.memorial_form.birth_date') }} *</label>
                                <div class="flex items-center gap-2">
                                    <input id="birth_date" name="birth_date" type="text" inputmode="numeric" required placeholder="31.12.2024." class="flex-1 h-11 px-3 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring">
                                    <button id="birthDatePickerBtn" type="button" class="inline-flex items-center justify-center px-3 h-11 rounded-lg border border-border hover:bg-muted transition-colors text-sm">
                                        {{ __('ui.memorial_form.pick_date') }}
                                    </button>
                                </div>
                                <input id="birth_date_picker" type="date" tabindex="-1" class="sr-only absolute opacity-0 pointer-events-none w-0 h-0">
                            </div>
                            <div class="space-y-2">
                                <label for="death_date" class="block text-sm font-medium text-foreground">{{ __('ui.memorial_form.death_date') }} *</label>
                                <div class="flex items-center gap-2">
                                    <input id="death_date" name="death_date" type="text" inputmode="numeric" required placeholder="31.12.2024." class="flex-1 h-11 px-3 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring">
                                    <button id="deathDatePickerBtn" type="button" class="inline-flex items-center justify-center px-3 h-11 rounded-lg border border-border hover:bg-muted transition-colors text-sm">
                                        {{ __('ui.memorial_form.pick_date') }}
                                    </button>
                                </div>
                                <input id="death_date_picker" type="date" tabindex="-1" class="sr-only absolute opacity-0 pointer-events-none w-0 h-0">
                            </div>
                            <div class="space-y-2">
                                <label for="birth_country_id" class="block text-sm font-medium text-foreground">{{ __('ui.memorial_form.birth_country') }}</label>
                                <select id="birth_country_id" name="birth_country_id" class="w-full h-11 px-3 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring">
                                    <option value="">{{ __('ui.memorial_form.select_country') }}</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label for="birth_place_id" class="block text-sm font-medium text-foreground">{{ __('ui.memorial_form.birth_place') }}</label>
                                <select id="birth_place_id" name="birth_place_id" disabled class="w-full h-11 px-3 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-60">
                                    <option value="">{{ __('ui.memorial_form.select_place') }}</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label for="death_country_id" class="block text-sm font-medium text-foreground">{{ __('ui.memorial_form.death_country') }}</label>
                                <select id="death_country_id" name="death_country_id" class="w-full h-11 px-3 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring">
                                    <option value="">{{ __('ui.memorial_form.select_country') }}</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label for="death_place_id" class="block text-sm font-medium text-foreground">{{ __('ui.memorial_form.death_place') }}</label>
                                <select id="death_place_id" name="death_place_id" disabled class="w-full h-11 px-3 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-60">
                                    <option value="">{{ __('ui.memorial_form.select_place') }}</option>
                                </select>
                            </div>
                        </div>
                        <p class="text-xs text-muted-foreground">{{ __('ui.memorial_form.date_format_hint') }}</p>
                    </section>

                    <section class="space-y-4">
                        <h2 class="text-xl font-serif font-semibold text-primary">{{ __('ui.memorial_form.biography') }}</h2>
                        <textarea id="biography" name="biography" rows="6" class="w-full px-3 py-2 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring" placeholder="{{ __('ui.memorial_form.biography_placeholder') }}"></textarea>
                    </section>

                    <section class="space-y-4">
                        <h2 class="text-xl font-serif font-semibold text-primary">{{ __('ui.memorial_form.media') }}</h2>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label for="profile_image" class="block text-sm font-medium text-foreground">{{ __('ui.memorial_form.profile_image') }}</label>
                                <input id="profile_image" name="profile_image" type="file" accept="image/*" class="w-full text-sm text-muted-foreground file:mr-3 file:px-3 file:h-10 file:rounded-lg file:border file:border-border file:bg-background file:text-foreground">
                                <div id="profileImagePreviewWrap" class="hidden pt-1">
                                    <div class="w-24 h-24 rounded-lg overflow-hidden border border-border bg-muted">
                                        <img id="profileImagePreview" src="" alt="Profile preview" class="w-full h-full object-cover">
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label for="gallery_images" class="block text-sm font-medium text-foreground">{{ __('ui.memorial_form.gallery_images') }}</label>
                                <input id="gallery_images" name="gallery_images" type="file" accept="image/*" multiple class="w-full text-sm text-muted-foreground file:mr-3 file:px-3 file:h-10 file:rounded-lg file:border file:border-border file:bg-background file:text-foreground">
                            </div>
                        </div>
                        <p class="text-xs text-muted-foreground">{{ __('ui.memorial_form.media_hint') }}</p>

                        <div id="pendingGalleryWrap" class="hidden space-y-3 pt-2">
                            <h3 class="text-sm font-semibold text-foreground">{{ __('ui.memorial_form.gallery_images') }}</h3>
                            <div id="pendingGalleryGrid" class="grid grid-cols-2 md:grid-cols-3 gap-3"></div>
                        </div>

                        <div id="existingImagesWrap" class="hidden space-y-3 pt-2">
                            <h3 class="text-sm font-semibold text-foreground">{{ __('ui.memorial_form.existing_gallery') }}</h3>
                            <div id="existingImagesGrid" class="grid grid-cols-2 md:grid-cols-3 gap-3"></div>
                            <p id="existingImagesEmpty" class="hidden text-xs text-muted-foreground">{{ __('ui.memorial_form.no_existing_images') }}</p>
                        </div>
                    </section>

                    <section class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <h2 class="text-xl font-serif font-semibold text-primary">{{ __('ui.memorial_form.videos') }}</h2>
                            <button id="addVideoFieldBtn" type="button" class="inline-flex items-center justify-center px-3 h-9 rounded-lg border border-border hover:bg-muted transition-colors text-sm">
                                {{ __('ui.memorial_form.add_video') }}
                            </button>
                        </div>
                        <div id="videoFields" class="space-y-3"></div>
                        <p class="text-xs text-muted-foreground">{{ __('ui.memorial_form.video_hint') }}</p>

                        <div id="existingVideosWrap" class="hidden space-y-3 pt-2">
                            <h3 class="text-sm font-semibold text-foreground">{{ __('ui.memorial_form.existing_videos') }}</h3>
                            <div id="existingVideosList" class="space-y-2"></div>
                            <p id="existingVideosEmpty" class="hidden text-xs text-muted-foreground">{{ __('ui.memorial_form.no_existing_videos') }}</p>
                        </div>
                    </section>

                    <section class="rounded-xl border border-border bg-background px-4 py-3">
                        <label class="inline-flex items-start gap-3">
                            <input id="is_public" name="is_public" type="checkbox" class="mt-1 rounded border-border">
                            <span>
                                <strong class="block text-sm text-foreground">{{ __('ui.memorial_form.public_label') }}</strong>
                                <span class="text-xs text-muted-foreground">{{ __('ui.memorial_form.public_hint') }}</span>
                            </span>
                        </label>
                    </section>

                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        <button id="memorialFormSubmit" type="submit" class="inline-flex items-center justify-center px-6 h-11 rounded-lg bg-gradient-accent text-accent-foreground font-semibold hover:opacity-90 transition-opacity">
                            {{ $pageMode === 'edit' ? __('ui.memorial_form.save_changes') : __('ui.memorial_form.create') }}
                        </button>
                        <a href="{{ $dashboardUrl }}" class="inline-flex items-center justify-center px-6 h-11 rounded-lg border border-border hover:bg-muted transition-colors">
                            {{ __('ui.memorial_form.cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </article>
    </div>
</main>

<div id="profileCropModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 p-4">
    <div class="w-full max-w-3xl rounded-2xl border border-border bg-card shadow-elegant p-5 md:p-6 space-y-4">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-lg md:text-xl font-serif font-semibold text-primary">{{ __('ui.memorial_form.profile_image') }} 1:1</h3>
            <button id="profileCropCloseTop" type="button" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-border hover:bg-muted transition-colors" aria-label="{{ __('ui.memorial_form.cancel') }}">
                x
            </button>
        </div>

        <div
            id="profileCropStage"
            class="relative mx-auto w-full max-w-md aspect-square rounded-xl overflow-hidden bg-muted"
        >
            <img id="profileCropImage" src="" alt="Profile crop" class="block w-full max-w-full select-none" draggable="false" />
        </div>

        <div class="space-y-2">
            <label for="profileCropZoom" class="block text-sm font-medium text-foreground">Zoom</label>
            <input id="profileCropZoom" type="range" min="100" max="800" step="1" value="100" class="w-full">
        </div>

        <div class="flex items-center justify-end gap-3 pt-1">
            <button id="profileCropCancelBtn" type="button" class="inline-flex items-center justify-center px-4 h-10 rounded-lg border border-border hover:bg-muted transition-colors">
                {{ __('ui.memorial_form.cancel') }}
            </button>
            <button id="profileCropApplyBtn" type="button" class="inline-flex items-center justify-center px-4 h-10 rounded-lg bg-gradient-accent text-accent-foreground font-semibold hover:opacity-90 transition-opacity">
                {{ __('ui.memorial_form.save_changes') }}
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const mode = @json($pageMode);
        const initialSlug = @json($initialSlug);
        const loginUrl = @json($loginUrl);
        const dashboardUrl = @json($dashboardUrl);
        const token = localStorage.getItem('auth_token') || '';

        const loadingEl = document.getElementById('memorialFormLoading');
        const formEl = document.getElementById('memorialForm');
        const alertEl = document.getElementById('memorialFormAlert');
        const submitBtn = document.getElementById('memorialFormSubmit');
        const videoFieldsEl = document.getElementById('videoFields');
        const addVideoFieldBtn = document.getElementById('addVideoFieldBtn');
        const profileImagePreviewWrap = document.getElementById('profileImagePreviewWrap');
        const profileImagePreview = document.getElementById('profileImagePreview');
        const pendingGalleryWrap = document.getElementById('pendingGalleryWrap');
        const pendingGalleryGrid = document.getElementById('pendingGalleryGrid');
        const existingImagesWrap = document.getElementById('existingImagesWrap');
        const existingImagesGrid = document.getElementById('existingImagesGrid');
        const existingImagesEmpty = document.getElementById('existingImagesEmpty');
        const existingVideosWrap = document.getElementById('existingVideosWrap');
        const existingVideosList = document.getElementById('existingVideosList');
        const existingVideosEmpty = document.getElementById('existingVideosEmpty');

        const firstNameInput = document.getElementById('first_name');
        const lastNameInput = document.getElementById('last_name');
        const birthDateInput = document.getElementById('birth_date');
        const birthDatePickerInput = document.getElementById('birth_date_picker');
        const birthDatePickerBtn = document.getElementById('birthDatePickerBtn');
        const deathDateInput = document.getElementById('death_date');
        const deathDatePickerInput = document.getElementById('death_date_picker');
        const deathDatePickerBtn = document.getElementById('deathDatePickerBtn');
        const birthCountryInput = document.getElementById('birth_country_id');
        const birthPlaceInput = document.getElementById('birth_place_id');
        const deathCountryInput = document.getElementById('death_country_id');
        const deathPlaceInput = document.getElementById('death_place_id');
        const biographyInput = document.getElementById('biography');
        const profileImageInput = document.getElementById('profile_image');
        const galleryImagesInput = document.getElementById('gallery_images');
        const isPublicInput = document.getElementById('is_public');
        const profileCropModal = document.getElementById('profileCropModal');
        const profileCropImage = document.getElementById('profileCropImage');
        const profileCropZoom = document.getElementById('profileCropZoom');
        const profileCropApplyBtn = document.getElementById('profileCropApplyBtn');
        const profileCropCancelBtn = document.getElementById('profileCropCancelBtn');
        const profileCropCloseTop = document.getElementById('profileCropCloseTop');

        let currentUser = null;
        let currentMemorial = null;
        let legacyBirthPlace = null;
        let legacyDeathPlace = null;
        let profileImagePreviewUrl = '';
        let croppedProfileFile = null;
        let profileCropper = null;
        let profileCropSourceUrl = '';
        let profileCropSourceFile = null;
        let profileCropBaseRatio = 1;
        const pendingGalleryFiles = [];
        const existingVideos = new Set();
        const locationsCache = {
            countries: [],
            placesByCountry: {},
        };
        const labels = {
            selectCountry: @json(__('ui.memorial_form.select_country')),
            selectPlace: @json(__('ui.memorial_form.select_place')),
            selectCountryFirst: @json(__('ui.memorial_form.select_country_first')),
            invalidDateFormat: @json(__('ui.memorial_form.invalid_date_format')),
            invalidDateOrder: @json(__('ui.memorial_form.invalid_date_order')),
            loadingLocations: @json(__('ui.memorial_form.loading_locations')),
            locationLoadError: @json(__('ui.memorial_form.location_load_error')),
        };

        function showAlert(type, text) {
            alertEl.classList.remove('hidden', 'border-red-200', 'bg-red-50', 'text-red-700', 'border-green-200', 'bg-green-50', 'text-green-700');
            if (type === 'error') {
                alertEl.classList.add('border-red-200', 'bg-red-50', 'text-red-700');
            } else {
                alertEl.classList.add('border-green-200', 'bg-green-50', 'text-green-700');
            }
            alertEl.textContent = text;
        }

        function hideAlert() {
            alertEl.classList.add('hidden');
        }

        function setLoading(isLoading) {
            loadingEl.classList.toggle('hidden', !isLoading);
            formEl.classList.toggle('hidden', isLoading);
        }

        function setSubmitting(isSubmitting) {
            submitBtn.disabled = isSubmitting;
            submitBtn.classList.toggle('opacity-70', isSubmitting);
            submitBtn.classList.toggle('cursor-not-allowed', isSubmitting);
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function normalizeMediaUrl(value) {
            const raw = String(value || '').trim();
            if (raw === '') {
                return '';
            }

            try {
                const parsed = new URL(raw, window.location.origin);
                return String(parsed.pathname || '').trim() || raw;
            } catch (_error) {
                return raw;
            }
        }

        function extractImageUrl(image) {
            return String(image?.imageUrl || image?.image_url || '').trim();
        }

        function setProfileImagePreview(url) {
            if (profileImagePreviewUrl.startsWith('blob:')) {
                URL.revokeObjectURL(profileImagePreviewUrl);
            }

            const normalizedUrl = String(url || '').trim();
            profileImagePreviewUrl = normalizedUrl;

            if (normalizedUrl === '') {
                profileImagePreviewWrap.classList.add('hidden');
                profileImagePreview.removeAttribute('src');
                return;
            }

            profileImagePreview.src = normalizedUrl;
            profileImagePreviewWrap.classList.remove('hidden');
        }

        function renderPendingGalleryFiles() {
            pendingGalleryGrid.innerHTML = '';
            pendingGalleryWrap.classList.toggle('hidden', pendingGalleryFiles.length === 0);

            pendingGalleryFiles.forEach((entry, index) => {
                const card = document.createElement('article');
                card.className = 'rounded-lg border border-border bg-background overflow-hidden';
                card.innerHTML = `
                    <img src="${escapeHtml(entry.previewUrl)}" alt="${escapeHtml(entry.file.name)}" class="w-full h-28 object-cover">
                    <div class="p-2 flex items-center justify-between gap-2">
                        <p class="text-xs text-muted-foreground truncate">${escapeHtml(entry.file.name)}</p>
                        <button type="button" data-remove-index="${index}" class="inline-flex items-center justify-center h-8 px-2 rounded-md border border-red-200 text-red-700 text-xs hover:bg-red-50 transition-colors">${escapeHtml(@json(__('ui.memorial_form.delete_image')))}</button>
                    </div>
                `;

                card.querySelector('[data-remove-index]').addEventListener('click', function () {
                    const removeIndex = Number.parseInt(this.getAttribute('data-remove-index') || '-1', 10);
                    if (Number.isNaN(removeIndex) || removeIndex < 0 || removeIndex >= pendingGalleryFiles.length) {
                        return;
                    }

                    const [removed] = pendingGalleryFiles.splice(removeIndex, 1);
                    if (removed?.previewUrl && removed.previewUrl.startsWith('blob:')) {
                        URL.revokeObjectURL(removed.previewUrl);
                    }
                    renderPendingGalleryFiles();
                });

                pendingGalleryGrid.appendChild(card);
            });
        }

        function appendPendingGalleryFiles(fileList) {
            const files = Array.from(fileList || []);
            files.forEach((file) => {
                if (!(file instanceof File) || !file.type.startsWith('image/')) {
                    return;
                }

                pendingGalleryFiles.push({
                    file,
                    previewUrl: URL.createObjectURL(file),
                });
            });

            renderPendingGalleryFiles();
        }

        function clearPendingGalleryFiles() {
            while (pendingGalleryFiles.length > 0) {
                const entry = pendingGalleryFiles.pop();
                if (entry?.previewUrl && entry.previewUrl.startsWith('blob:')) {
                    URL.revokeObjectURL(entry.previewUrl);
                }
            }

            galleryImagesInput.value = '';
            renderPendingGalleryFiles();
        }

        function destroyProfileCropper() {
            if (profileCropper) {
                profileCropper.destroy();
                profileCropper = null;
            }
        }

        function closeProfileCropModal() {
            profileCropModal.classList.add('hidden');
            profileCropModal.classList.remove('flex');
            document.body.style.overflow = '';
            destroyProfileCropper();

            if (profileCropSourceUrl.startsWith('blob:')) {
                URL.revokeObjectURL(profileCropSourceUrl);
            }
            profileCropSourceUrl = '';
            profileCropSourceFile = null;
            profileCropBaseRatio = 1;
            profileCropImage.removeAttribute('src');
        }

        function openProfileCropModal(file) {
            if (!(file instanceof File)) {
                return;
            }

            if (typeof Cropper === 'undefined') {
                showAlert('error', @json(__('ui.memorial_form.media_action_failed')));
                return;
            }

            destroyProfileCropper();
            if (profileCropSourceUrl.startsWith('blob:')) {
                URL.revokeObjectURL(profileCropSourceUrl);
            }

            profileCropSourceFile = file;
            profileCropSourceUrl = URL.createObjectURL(file);
            profileCropImage.onload = function () {
                profileCropper = new Cropper(profileCropImage, {
                    aspectRatio: 1,
                    viewMode: 1,
                    dragMode: 'move',
                    responsive: true,
                    modal: true,
                    guides: false,
                    center: true,
                    highlight: false,
                    background: false,
                    autoCropArea: 1,
                    movable: true,
                    zoomable: true,
                    zoomOnTouch: true,
                    zoomOnWheel: true,
                    wheelZoomRatio: 0.12,
                    cropBoxMovable: false,
                    cropBoxResizable: false,
                    toggleDragModeOnDblclick: false,
                    ready() {
                        const imageData = profileCropper?.getImageData();
                        const ratio = imageData?.naturalWidth ? (imageData.width / imageData.naturalWidth) : 1;
                        profileCropBaseRatio = ratio > 0 ? ratio : 1;
                        profileCropZoom.value = '100';
                    },
                    zoom(event) {
                        if (!profileCropBaseRatio) {
                            return;
                        }
                        const ratio = Number(event?.detail?.ratio || 0);
                        if (!ratio) {
                            return;
                        }
                        const percent = Math.round((ratio / profileCropBaseRatio) * 100);
                        const clampedPercent = Math.max(100, Math.min(800, percent));
                        if (profileCropZoom.value !== String(clampedPercent)) {
                            profileCropZoom.value = String(clampedPercent);
                        }
                    },
                });
            };
            profileCropImage.onerror = function () {
                closeProfileCropModal();
                showAlert('error', @json(__('ui.memorial_form.media_action_failed')));
            };
            profileCropImage.src = profileCropSourceUrl;
            profileCropZoom.value = '100';

            profileCropModal.classList.remove('hidden');
            profileCropModal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        async function buildCroppedProfileFile() {
            const sourceFile = profileCropSourceFile;
            if (!(sourceFile instanceof File) || !profileCropper) {
                throw new Error('Missing source image.');
            }

            const outputSize = 1024;
            const canvas = profileCropper.getCroppedCanvas({
                width: outputSize,
                height: outputSize,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });

            if (!canvas) {
                throw new Error('Crop rendering unavailable.');
            }

            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            const mimeType = allowedTypes.includes(sourceFile.type) ? sourceFile.type : 'image/jpeg';

            const croppedBlob = await new Promise((resolve, reject) => {
                canvas.toBlob(
                    (blob) => {
                        if (!blob) {
                            reject(new Error('Crop export failed.'));
                            return;
                        }
                        resolve(blob);
                    },
                    mimeType,
                    0.92
                );
            });

            const extension = mimeType === 'image/png' ? 'png' : (mimeType === 'image/webp' ? 'webp' : 'jpg');
            const safeBaseName = sourceFile.name.replace(/\.[^.]+$/, '') || 'profile-image';

            return new File([croppedBlob], `${safeBaseName}-profile.${extension}`, { type: mimeType });
        }

        function normalizeInteger(value) {
            if (value === null || value === undefined || value === '') {
                return null;
            }

            const parsed = Number.parseInt(String(value), 10);
            return Number.isNaN(parsed) ? null : parsed;
        }

        function pad2(value) {
            return String(value).padStart(2, '0');
        }

        function parseEuropeanDate(value) {
            const raw = String(value || '').trim();
            const match = raw.match(/^(\d{1,2})\.(\d{1,2})\.(\d{4})\.?$/);
            if (!match) {
                return null;
            }

            const day = Number.parseInt(match[1], 10);
            const month = Number.parseInt(match[2], 10);
            const year = Number.parseInt(match[3], 10);
            const parsed = new Date(year, month - 1, day);

            if (
                Number.isNaN(parsed.getTime()) ||
                parsed.getFullYear() !== year ||
                parsed.getMonth() !== month - 1 ||
                parsed.getDate() !== day
            ) {
                return null;
            }

            return {
                iso: `${year}-${pad2(month)}-${pad2(day)}`,
                display: `${pad2(day)}.${pad2(month)}.${year}.`,
                timestamp: parsed.getTime(),
            };
        }

        function formatIsoDateForInput(value) {
            const raw = String(value || '').trim();
            const match = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (!match) {
                return '';
            }

            return `${match[3]}.${match[2]}.${match[1]}.`;
        }

        function syncDatePickerFromDisplay(displayInput, pickerInput) {
            const parsed = parseEuropeanDate(displayInput.value);
            pickerInput.value = parsed ? parsed.iso : '';
        }

        function openNativeDatePicker(pickerInput) {
            if (!pickerInput) {
                return;
            }

            if (typeof pickerInput.showPicker === 'function') {
                pickerInput.showPicker();
                return;
            }

            pickerInput.click();
        }

        function normalizeDateField(input) {
            const parsed = parseEuropeanDate(input.value);
            if (parsed) {
                input.value = parsed.display;
            }
        }

        function setSelectOptions(selectEl, options, placeholder, selectedValue = null) {
            const normalizedSelected = selectedValue === null ? '' : String(selectedValue);
            selectEl.innerHTML = '';

            const placeholderOption = document.createElement('option');
            placeholderOption.value = '';
            placeholderOption.textContent = placeholder;
            selectEl.appendChild(placeholderOption);

            options.forEach((item) => {
                const option = document.createElement('option');
                option.value = String(item.value);
                option.textContent = item.label;
                selectEl.appendChild(option);
            });

            if (normalizedSelected !== '') {
                selectEl.value = normalizedSelected;
            }
        }

        function resetPlaceSelect(selectEl) {
            setSelectOptions(selectEl, [], labels.selectCountryFirst, null);
            selectEl.disabled = true;
        }

        function getSelectedPlaceLabel(selectEl) {
            if (!selectEl || !selectEl.value) {
                return null;
            }

            const option = selectEl.options[selectEl.selectedIndex];
            const text = option?.textContent ? option.textContent.trim() : '';
            return text !== '' ? text : null;
        }

        async function loadCountries() {
            if (locationsCache.countries.length > 0) {
                return locationsCache.countries;
            }

            const response = await apiRequest('/api/v1/locations/countries');
            const countries = Array.isArray(response?.data) ? response.data : [];
            locationsCache.countries = countries;
            return countries;
        }

        function populateCountrySelects(selectedBirthCountryId = null, selectedDeathCountryId = null) {
            const options = locationsCache.countries.map((country) => ({
                value: country.id,
                label: country.name,
            }));

            setSelectOptions(birthCountryInput, options, labels.selectCountry, selectedBirthCountryId);
            setSelectOptions(deathCountryInput, options, labels.selectCountry, selectedDeathCountryId);
        }

        async function loadPlacesByCountry(countryId) {
            const normalizedCountryId = normalizeInteger(countryId);
            if (!normalizedCountryId) {
                return [];
            }

            if (Array.isArray(locationsCache.placesByCountry[normalizedCountryId])) {
                return locationsCache.placesByCountry[normalizedCountryId];
            }

            const response = await apiRequest(`/api/v1/locations/countries/${encodeURIComponent(normalizedCountryId)}/places`);
            const places = Array.isArray(response?.data) ? response.data : [];
            locationsCache.placesByCountry[normalizedCountryId] = places;
            return places;
        }

        async function populatePlaceSelectForCountry(countryInput, placeInput, selectedPlaceId = null) {
            const countryId = normalizeInteger(countryInput.value);
            if (!countryId) {
                resetPlaceSelect(placeInput);
                return;
            }

            setSelectOptions(placeInput, [], labels.loadingLocations, null);
            placeInput.disabled = true;

            const places = await loadPlacesByCountry(countryId);
            const options = places.map((place) => ({
                value: place.id,
                label: place.name,
            }));

            setSelectOptions(placeInput, options, labels.selectPlace, selectedPlaceId);
            placeInput.disabled = false;
        }

        function extractYouTubeId(url) {
            const patterns = [
                /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)/,
                /youtube\.com\/shorts\/([^&\n?#]+)/,
            ];

            for (const pattern of patterns) {
                const match = String(url || '').match(pattern);
                if (match && match[1]) {
                    return match[1];
                }
            }

            return null;
        }

        function createVideoField(value = '') {
            const wrapper = document.createElement('div');
            wrapper.className = 'flex items-center gap-2';
            wrapper.innerHTML = `
                <input type="url" class="video-url-input w-full h-11 px-3 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring" placeholder="https://www.youtube.com/watch?v=..." value="${(value || '').replace(/"/g, '&quot;')}">
                <button type="button" class="remove-video-field inline-flex items-center justify-center w-10 h-10 rounded-lg border border-border hover:bg-muted transition-colors">x</button>
            `;

            wrapper.querySelector('.remove-video-field').addEventListener('click', function () {
                wrapper.remove();
                if (videoFieldsEl.children.length === 0) {
                    videoFieldsEl.appendChild(createVideoField(''));
                }
            });

            return wrapper;
        }

        async function apiRequest(url, options = {}) {
            const headers = Object.assign(
                { Accept: 'application/json' },
                options.headers || {}
            );

            if (!(options.body instanceof FormData)) {
                headers['Content-Type'] = headers['Content-Type'] || 'application/json';
            }

            if (token) {
                headers.Authorization = `Bearer ${token}`;
            }

            const response = await fetch(url, Object.assign({}, options, { headers }));
            let payload = null;
            try {
                payload = await response.json();
            } catch (_error) {
                payload = null;
            }

            if (!response.ok) {
                const validationMessage = payload?.errors ? Object.values(payload.errors).flat().join(' ') : '';
                const message = validationMessage || payload?.message || 'Request failed.';
                throw new Error(message);
            }

            return payload;
        }

        async function ensureAuthenticatedUser() {
            if (!token) {
                window.location.href = loginUrl;
                return null;
            }

            const meResponse = await apiRequest('/api/v1/me');
            if (!meResponse?.user) {
                throw new Error('Authentication failed.');
            }

            return meResponse.user;
        }

        async function uploadImage(memorialId, file, caption = '') {
            const formData = new FormData();
            formData.append('image', file);
            if (caption !== '') {
                formData.append('caption', caption);
            }

            const uploadResponse = await apiRequest(`/api/v1/memorials/${encodeURIComponent(memorialId)}/images`, {
                method: 'POST',
                body: formData,
            });

            // Track media upload
            if (window.eventTracker && uploadResponse?.image) {
                window.eventTracker.trackMediaUpload({
                    media_type: 'image',
                    memorial_id: String(memorialId),
                    file_size_kb: Math.round(file.size / 1024)
                });
            }

            return uploadResponse?.image || null;
        }

        async function uploadProfileImage(memorialId, file) {
            const formData = new FormData();
            formData.append('image', file);

            return apiRequest(`/api/v1/memorials/${encodeURIComponent(memorialId)}/profile-image`, {
                method: 'POST',
                body: formData,
            });
        }

        function syncExistingMedia(memorial) {
            if (mode !== 'edit') {
                return;
            }

            const profileImageUrl = String(memorial?.profileImageUrl || memorial?.profile_image_url || '').trim();
            const normalizedProfileImageUrl = normalizeMediaUrl(profileImageUrl);
            const galleryImages = Array.isArray(memorial?.images) ? memorial.images : [];
            const filteredImages = normalizedProfileImageUrl === ''
                ? galleryImages
                : galleryImages.filter((image) => normalizeMediaUrl(extractImageUrl(image)) !== normalizedProfileImageUrl);

            existingImagesWrap.classList.remove('hidden');
            existingVideosWrap.classList.remove('hidden');
            setProfileImagePreview(profileImageUrl);
            renderExistingImages(filteredImages);
            renderExistingVideos(Array.isArray(memorial?.videos) ? memorial.videos : []);
        }

        async function reloadMemorialMedia() {
            if (!currentMemorial?.slug) {
                return;
            }

            const response = await apiRequest(`/api/v1/memorials/${encodeURIComponent(currentMemorial.slug)}`);
            if (response?.data) {
                currentMemorial = Object.assign({}, currentMemorial, response.data);
                syncExistingMedia(currentMemorial);
            }
        }

        function renderExistingImages(images) {
            existingImagesGrid.innerHTML = '';
            existingImagesEmpty.classList.toggle('hidden', images.length > 0);

            images.forEach((image) => {
                const imageUrl = extractImageUrl(image);
                const wrapper = document.createElement('article');
                wrapper.className = 'rounded-lg border border-border bg-background overflow-hidden';
                wrapper.innerHTML = `
                    <img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(image.caption || '')}" class="w-full h-36 object-cover">
                    <div class="p-2 space-y-2">
                        <input type="text" class="image-caption-input w-full h-9 px-2 rounded-md border border-border bg-background text-sm focus:outline-none focus:ring-2 focus:ring-ring" value="${escapeHtml(image.caption || '')}" placeholder="${escapeHtml(@json(__('ui.memorial_form.caption_placeholder')))}">
                        <div class="flex gap-2">
                            <button type="button" data-action="save-caption" class="flex-1 inline-flex items-center justify-center h-8 rounded-md border border-border text-xs hover:bg-muted transition-colors">${escapeHtml(@json(__('ui.memorial_form.save_caption')))}</button>
                            <button type="button" data-action="delete-image" class="flex-1 inline-flex items-center justify-center h-8 rounded-md border border-red-200 text-red-700 text-xs hover:bg-red-50 transition-colors">${escapeHtml(@json(__('ui.memorial_form.delete_image')))}</button>
                        </div>
                    </div>
                `;

                wrapper.querySelector('[data-action="save-caption"]').addEventListener('click', async function () {
                    const captionValue = wrapper.querySelector('.image-caption-input').value.trim();
                    this.disabled = true;
                    try {
                        await apiRequest(`/api/v1/images/${encodeURIComponent(image.id)}`, {
                            method: 'PUT',
                            body: JSON.stringify({ caption: captionValue }),
                        });
                        showAlert('success', @json(__('ui.memorial_form.caption_saved')));
                    } catch (error) {
                        showAlert('error', error.message || @json(__('ui.memorial_form.media_action_failed')));
                    } finally {
                        this.disabled = false;
                    }
                });

                wrapper.querySelector('[data-action="delete-image"]').addEventListener('click', async function () {
                    if (!confirm(@json(__('ui.memorial_form.delete_image_confirm')))) {
                        return;
                    }

                    this.disabled = true;
                    try {
                        await apiRequest(`/api/v1/images/${encodeURIComponent(image.id)}`, { method: 'DELETE' });
                        showAlert('success', @json(__('ui.memorial_form.image_deleted')));
                        await reloadMemorialMedia();
                    } catch (error) {
                        showAlert('error', error.message || @json(__('ui.memorial_form.media_action_failed')));
                    } finally {
                        this.disabled = false;
                    }
                });

                existingImagesGrid.appendChild(wrapper);
            });
        }

        function renderExistingVideos(videos) {
            existingVideosList.innerHTML = '';
            existingVideos.clear();
            existingVideosEmpty.classList.toggle('hidden', videos.length > 0);

            videos.forEach((video) => {
                if (video?.youtubeUrl) {
                    existingVideos.add(video.youtubeUrl);
                }

                const videoId = extractYouTubeId(video.youtubeUrl || '');
                const thumbnailUrl = videoId ? `https://img.youtube.com/vi/${videoId}/mqdefault.jpg` : '';
                const wrapper = document.createElement('article');
                wrapper.className = 'rounded-lg border border-border bg-background p-3 flex flex-col md:flex-row gap-3';
                wrapper.innerHTML = `
                    <div class="w-full md:w-36 h-24 rounded-md overflow-hidden bg-muted flex items-center justify-center">
                        ${thumbnailUrl ? `<img src="${escapeHtml(thumbnailUrl)}" alt="${escapeHtml(video.title || '')}" class="w-full h-full object-cover">` : '<span class="text-xs text-muted-foreground">YouTube</span>'}
                    </div>
                    <div class="flex-1 space-y-2">
                        <input type="url" class="video-url-edit w-full h-9 px-2 rounded-md border border-border bg-background text-sm focus:outline-none focus:ring-2 focus:ring-ring" value="${escapeHtml(video.youtubeUrl || '')}" placeholder="https://www.youtube.com/watch?v=...">
                        <input type="text" class="video-title-edit w-full h-9 px-2 rounded-md border border-border bg-background text-sm focus:outline-none focus:ring-2 focus:ring-ring" value="${escapeHtml(video.title || '')}" placeholder="${escapeHtml(@json(__('ui.memorial_form.video_title_placeholder')))}">
                        <div class="flex gap-2">
                            <button type="button" data-action="save-video" class="flex-1 inline-flex items-center justify-center h-8 rounded-md border border-border text-xs hover:bg-muted transition-colors">${escapeHtml(@json(__('ui.memorial_form.save_video')))}</button>
                            <button type="button" data-action="delete-video" class="flex-1 inline-flex items-center justify-center h-8 rounded-md border border-red-200 text-red-700 text-xs hover:bg-red-50 transition-colors">${escapeHtml(@json(__('ui.memorial_form.delete_video')))}</button>
                        </div>
                    </div>
                `;

                wrapper.querySelector('[data-action="save-video"]').addEventListener('click', async function () {
                    const youtubeUrl = wrapper.querySelector('.video-url-edit').value.trim();
                    const titleValue = wrapper.querySelector('.video-title-edit').value.trim();
                    this.disabled = true;
                    try {
                        await apiRequest(`/api/v1/videos/${encodeURIComponent(video.id)}`, {
                            method: 'PUT',
                            body: JSON.stringify({
                                youtube_url: youtubeUrl,
                                title: titleValue || null,
                            }),
                        });
                        showAlert('success', @json(__('ui.memorial_form.video_saved')));
                        await reloadMemorialMedia();
                    } catch (error) {
                        showAlert('error', error.message || @json(__('ui.memorial_form.media_action_failed')));
                    } finally {
                        this.disabled = false;
                    }
                });

                wrapper.querySelector('[data-action="delete-video"]').addEventListener('click', async function () {
                    if (!confirm(@json(__('ui.memorial_form.delete_video_confirm')))) {
                        return;
                    }
                    this.disabled = true;
                    try {
                        await apiRequest(`/api/v1/videos/${encodeURIComponent(video.id)}`, { method: 'DELETE' });
                        showAlert('success', @json(__('ui.memorial_form.video_deleted')));
                        await reloadMemorialMedia();
                    } catch (error) {
                        showAlert('error', error.message || @json(__('ui.memorial_form.media_action_failed')));
                    } finally {
                        this.disabled = false;
                    }
                });

                existingVideosList.appendChild(wrapper);
            });
        }

        async function loadMemorialIntoForm(memorial) {
            firstNameInput.value = memorial.firstName || '';
            lastNameInput.value = memorial.lastName || '';
            birthDateInput.value = formatIsoDateForInput(memorial.birthDate || '');
            deathDateInput.value = formatIsoDateForInput(memorial.deathDate || '');
            birthDatePickerInput.value = memorial.birthDate || '';
            deathDatePickerInput.value = memorial.deathDate || '';
            biographyInput.value = memorial.biography || '';
            isPublicInput.checked = !!memorial.isPublic;
            legacyBirthPlace = memorial.birthPlace || null;
            legacyDeathPlace = memorial.deathPlace || null;

            populateCountrySelects(memorial.birthCountryId || null, memorial.deathCountryId || null);
            await Promise.all([
                populatePlaceSelectForCountry(birthCountryInput, birthPlaceInput, memorial.birthPlaceId || null),
                populatePlaceSelectForCountry(deathCountryInput, deathPlaceInput, memorial.deathPlaceId || null),
            ]);

            videoFieldsEl.innerHTML = '';
            videoFieldsEl.appendChild(createVideoField(''));
            croppedProfileFile = null;
            clearPendingGalleryFiles();
            syncExistingMedia(memorial);
        }

        addVideoFieldBtn.addEventListener('click', function () {
            videoFieldsEl.appendChild(createVideoField(''));
        });

        [birthDateInput, deathDateInput].forEach((input) => {
            input.addEventListener('blur', function () {
                normalizeDateField(input);
                if (input === birthDateInput) {
                    syncDatePickerFromDisplay(birthDateInput, birthDatePickerInput);
                } else if (input === deathDateInput) {
                    syncDatePickerFromDisplay(deathDateInput, deathDatePickerInput);
                }
            });
        });

        birthDatePickerBtn.addEventListener('click', function () {
            syncDatePickerFromDisplay(birthDateInput, birthDatePickerInput);
            openNativeDatePicker(birthDatePickerInput);
        });

        deathDatePickerBtn.addEventListener('click', function () {
            syncDatePickerFromDisplay(deathDateInput, deathDatePickerInput);
            openNativeDatePicker(deathDatePickerInput);
        });

        birthDatePickerInput.addEventListener('change', function () {
            birthDateInput.value = formatIsoDateForInput(birthDatePickerInput.value);
        });

        deathDatePickerInput.addEventListener('change', function () {
            deathDateInput.value = formatIsoDateForInput(deathDatePickerInput.value);
        });

        birthCountryInput.addEventListener('change', async function () {
            legacyBirthPlace = null;
            try {
                await populatePlaceSelectForCountry(birthCountryInput, birthPlaceInput, null);
            } catch (_error) {
                resetPlaceSelect(birthPlaceInput);
                showAlert('error', labels.locationLoadError);
            }
        });

        deathCountryInput.addEventListener('change', async function () {
            legacyDeathPlace = null;
            try {
                await populatePlaceSelectForCountry(deathCountryInput, deathPlaceInput, null);
            } catch (_error) {
                resetPlaceSelect(deathPlaceInput);
                showAlert('error', labels.locationLoadError);
            }
        });

        profileImageInput.addEventListener('change', function () {
            const profileFile = profileImageInput.files?.[0] || null;
            if (profileFile) {
                openProfileCropModal(profileFile);
                return;
            }

            if (!croppedProfileFile) {
                const existingProfileImageUrl = String(currentMemorial?.profileImageUrl || currentMemorial?.profile_image_url || '').trim();
                setProfileImagePreview(existingProfileImageUrl);
            }
        });

        galleryImagesInput.addEventListener('change', function () {
            appendPendingGalleryFiles(galleryImagesInput.files);
            galleryImagesInput.value = '';
        });

        profileCropZoom.addEventListener('input', function () {
            if (!profileCropper) {
                return;
            }

            const factor = Number.parseInt(profileCropZoom.value || '100', 10) / 100;
            const clampedFactor = Number.isNaN(factor) ? 1 : Math.max(1, Math.min(8, factor));
            profileCropper.zoomTo(profileCropBaseRatio * clampedFactor);
        });

        profileCropCloseTop.addEventListener('click', function () {
            profileImageInput.value = '';
            closeProfileCropModal();
        });

        profileCropCancelBtn.addEventListener('click', function () {
            profileImageInput.value = '';
            closeProfileCropModal();
        });

        profileCropApplyBtn.addEventListener('click', async function () {
            this.disabled = true;
            this.classList.add('opacity-70', 'cursor-not-allowed');

            try {
                croppedProfileFile = await buildCroppedProfileFile();
                profileImageInput.value = '';
                setProfileImagePreview(URL.createObjectURL(croppedProfileFile));
                closeProfileCropModal();
            } catch (error) {
                showAlert('error', error.message || @json(__('ui.memorial_form.media_action_failed')));
            } finally {
                this.disabled = false;
                this.classList.remove('opacity-70', 'cursor-not-allowed');
            }
        });

        profileCropModal.addEventListener('click', function (event) {
            if (event.target === profileCropModal) {
                profileImageInput.value = '';
                closeProfileCropModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !profileCropModal.classList.contains('hidden')) {
                profileImageInput.value = '';
                closeProfileCropModal();
            }
        });

        formEl.addEventListener('submit', async function (event) {
            event.preventDefault();
            hideAlert();
            setSubmitting(true);

            try {
                normalizeDateField(birthDateInput);
                normalizeDateField(deathDateInput);

                const birthDateParsed = parseEuropeanDate(birthDateInput.value);
                const deathDateParsed = parseEuropeanDate(deathDateInput.value);

                if (!birthDateParsed || !deathDateParsed) {
                    throw new Error(labels.invalidDateFormat);
                }

                if (deathDateParsed.timestamp <= birthDateParsed.timestamp) {
                    throw new Error(labels.invalidDateOrder);
                }

                const birthCountryId = normalizeInteger(birthCountryInput.value);
                const birthPlaceId = normalizeInteger(birthPlaceInput.value);
                const deathCountryId = normalizeInteger(deathCountryInput.value);
                const deathPlaceId = normalizeInteger(deathPlaceInput.value);

                const payload = {
                    first_name: firstNameInput.value.trim(),
                    last_name: lastNameInput.value.trim(),
                    birth_date: birthDateParsed.iso,
                    death_date: deathDateParsed.iso,
                    birth_country_id: birthCountryId,
                    birth_place_id: birthPlaceId,
                    birth_place: getSelectedPlaceLabel(birthPlaceInput) || legacyBirthPlace || null,
                    death_country_id: deathCountryId,
                    death_place_id: deathPlaceId,
                    death_place: getSelectedPlaceLabel(deathPlaceInput) || legacyDeathPlace || null,
                    biography: biographyInput.value.trim() || null,
                    is_public: isPublicInput.checked,
                };

                let responsePayload;
                if (mode === 'edit' && currentMemorial?.id) {
                    responsePayload = await apiRequest(`/api/v1/memorials/${encodeURIComponent(currentMemorial.id)}`, {
                        method: 'PUT',
                        body: JSON.stringify(payload),
                    });
                } else {
                    responsePayload = await apiRequest('/api/v1/memorials', {
                        method: 'POST',
                        body: JSON.stringify(payload),
                    });
                }

                const memorial = responsePayload?.data;
                if (!memorial?.id) {
                    throw new Error('Memorial data not returned.');
                }

                currentMemorial = memorial;

                const profileFile = croppedProfileFile || profileImageInput.files?.[0] || null;
                if (profileFile) {
                    const profileUploadResponse = await uploadProfileImage(memorial.id, profileFile);
                    const profileImageUrl = String(profileUploadResponse?.profileImageUrl || profileUploadResponse?.profile_image_url || '').trim();
                    if (profileImageUrl !== '') {
                        currentMemorial.profileImageUrl = profileImageUrl;
                        setProfileImagePreview(profileImageUrl);
                    }
                    croppedProfileFile = null;
                }

                const galleryFiles = pendingGalleryFiles.map((entry) => entry.file);
                for (const galleryFile of galleryFiles) {
                    await uploadImage(memorial.id, galleryFile);
                }
                clearPendingGalleryFiles();

                const submittedVideoUrls = Array.from(document.querySelectorAll('.video-url-input'))
                    .map((input) => input.value.trim())
                    .filter((value) => value !== '');

                for (const videoUrl of submittedVideoUrls) {
                    if (existingVideos.has(videoUrl)) {
                        continue;
                    }

                    await apiRequest(`/api/v1/memorials/${encodeURIComponent(memorial.id)}/videos`, {
                        method: 'POST',
                        body: JSON.stringify({ youtube_url: videoUrl }),
                    });
                }

                showAlert('success', @json(__('ui.memorial_form.save_success')));

                // Track memorial creation
                if (window.eventTracker && mode === 'create') {
                    window.eventTracker.trackMemorialCreation({
                        locale: @json(app()->getLocale()),
                        is_public: isPublicInput.checked
                    });
                }

                setTimeout(() => {
                    window.location.href = dashboardUrl;
                }, 700);
            } catch (error) {
                showAlert('error', error.message || @json(__('ui.memorial_form.save_error')));

                // Track form submission error
                if (window.eventTracker) {
                    window.eventTracker.trackFormSubmit({
                        form_type: 'memorial_create',
                        locale: @json(app()->getLocale()),
                        success: false,
                        error_type: 'submission_error'
                    });
                }
            } finally {
                setSubmitting(false);
            }
        });

        (async function initialize() {
            try {
                currentUser = await ensureAuthenticatedUser();
                resetPlaceSelect(birthPlaceInput);
                resetPlaceSelect(deathPlaceInput);

                try {
                    loadingEl.textContent = labels.loadingLocations;
                    await loadCountries();
                    populateCountrySelects();
                } catch (_error) {
                    throw new Error(labels.locationLoadError);
                }

                if (mode === 'edit') {
                    if (!initialSlug) {
                        throw new Error('Missing memorial slug.');
                    }

                    const memorialResponse = await apiRequest(`/api/v1/memorials/${encodeURIComponent(initialSlug)}`);
                    currentMemorial = memorialResponse?.data || null;
                    if (!currentMemorial) {
                        throw new Error(@json(__('ui.memorial_form.not_found')));
                    }

                    const isAdmin = Array.isArray(currentUser.roles)
                        ? currentUser.roles.some((role) => role.role === 'admin' || role === 'admin')
                        : currentUser.role === 'admin';
                    if (currentMemorial.userId !== currentUser.id && !isAdmin) {
                        throw new Error(@json(__('ui.memorial_form.unauthorized')));
                    }

                    await loadMemorialIntoForm(currentMemorial);
                } else {
                    videoFieldsEl.appendChild(createVideoField(''));
                    isPublicInput.checked = true;
                    croppedProfileFile = null;
                    setProfileImagePreview('');
                    clearPendingGalleryFiles();
                    existingImagesWrap.classList.add('hidden');
                    existingVideosWrap.classList.add('hidden');
                }

                setLoading(false);
            } catch (error) {
                const message = error.message || @json(__('ui.memorial_form.load_error'));
                const shouldClearSession = /Unauthenticated|Authentication failed|Unauthorized/i.test(message);
                if (shouldClearSession) {
                    localStorage.removeItem('auth_token');
                    localStorage.removeItem('user');
                }
                showAlert('error', message);
                setTimeout(() => {
                    window.location.href = shouldClearSession ? loginUrl : dashboardUrl;
                }, 1200);
            }
        })();
    });
</script>
@endpush

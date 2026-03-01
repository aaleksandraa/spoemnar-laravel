@extends('layouts.app')

@php
    $currentLocale = app()->getLocale();
    $homeUrl = route('home', ['locale' => $currentLocale]);
    $loginUrl = route('login', ['locale' => $currentLocale]);
    $dashboardUrl = route('dashboard', ['locale' => $currentLocale]);
    $profileUrlTemplate = route('memorial.profile', ['locale' => $currentLocale, 'slug' => '__SLUG__']);
    $editUrlTemplate = route('memorial.edit', ['locale' => $currentLocale, 'slug' => '__SLUG__']);
@endphp

@section('title', __('ui.admin.title'))
@section('meta_description', __('ui.admin.meta_description'))

@section('content')
<main class="flex-1 bg-gradient-hero py-10 md:py-14">
    <div class="container mx-auto px-4 max-w-7xl space-y-8">
        <section class="rounded-2xl border border-border bg-card shadow-elegant p-6 md:p-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-serif font-bold text-primary">{{ __('ui.admin.title') }}</h1>
                    <p class="text-muted-foreground mt-2">{{ __('ui.admin.subtitle') }}</p>
                    <p id="adminUser" class="text-sm text-muted-foreground mt-3 hidden"></p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ $dashboardUrl }}" class="inline-flex items-center justify-center px-5 h-11 rounded-lg border border-border hover:bg-muted transition-colors">
                        {{ __('ui.nav.dashboard') }}
                    </a>
                    <button id="adminLogoutBtn" type="button" class="inline-flex items-center justify-center px-5 h-11 rounded-lg bg-gradient-accent text-accent-foreground font-semibold hover:opacity-90 transition-opacity">
                        {{ __('ui.nav.logout') }}
                    </button>
                </div>
            </div>
        </section>

        <div id="adminAlert" class="hidden rounded-xl border p-4 text-sm"></div>

        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <article class="rounded-xl border border-border bg-card p-5 shadow-sm">
                <p class="text-xs text-muted-foreground uppercase tracking-wide">{{ __('ui.admin.total_users') }}</p>
                <p id="statUsers" class="text-3xl font-semibold text-primary mt-2">0</p>
            </article>
            <article class="rounded-xl border border-border bg-card p-5 shadow-sm">
                <p class="text-xs text-muted-foreground uppercase tracking-wide">{{ __('ui.admin.total_memorials') }}</p>
                <p id="statMemorials" class="text-3xl font-semibold text-primary mt-2">0</p>
            </article>
            <article class="rounded-xl border border-border bg-card p-5 shadow-sm">
                <p class="text-xs text-muted-foreground uppercase tracking-wide">{{ __('ui.admin.total_tributes') }}</p>
                <p id="statTributes" class="text-3xl font-semibold text-primary mt-2">0</p>
            </article>
            <article class="rounded-xl border border-border bg-card p-5 shadow-sm">
                <p class="text-xs text-muted-foreground uppercase tracking-wide">{{ __('ui.admin.public_memorials') }}</p>
                <p id="statPublicMemorials" class="text-3xl font-semibold text-primary mt-2">0</p>
            </article>
            <article class="rounded-xl border border-border bg-card p-5 shadow-sm">
                <p class="text-xs text-muted-foreground uppercase tracking-wide">{{ __('ui.admin.private_memorials') }}</p>
                <p id="statPrivateMemorials" class="text-3xl font-semibold text-primary mt-2">0</p>
            </article>
        </section>

        <section class="rounded-2xl border border-border bg-card shadow-elegant overflow-hidden">
            <header class="border-b border-border p-3 md:p-4">
                <nav class="grid grid-cols-2 md:grid-cols-6 gap-2" aria-label="{{ __('ui.admin.title') }}">
                    <button type="button" class="admin-tab-trigger h-10 rounded-lg px-3 text-sm border border-border bg-muted/60" data-tab-target="settings">{{ __('ui.admin.tabs.settings') }}</button>
                    <button type="button" class="admin-tab-trigger h-10 rounded-lg px-3 text-sm border border-border hover:bg-muted transition-colors" data-tab-target="users">{{ __('ui.admin.tabs.users') }}</button>
                    <button type="button" class="admin-tab-trigger h-10 rounded-lg px-3 text-sm border border-border hover:bg-muted transition-colors" data-tab-target="memorials">{{ __('ui.admin.tabs.memorials') }}</button>
                    <button type="button" class="admin-tab-trigger h-10 rounded-lg px-3 text-sm border border-border hover:bg-muted transition-colors" data-tab-target="hero">{{ __('ui.admin.tabs.hero') }}</button>
                    <button type="button" class="admin-tab-trigger h-10 rounded-lg px-3 text-sm border border-border hover:bg-muted transition-colors" data-tab-target="seo">{{ __('ui.admin.tabs.seo') }}</button>
                    <button type="button" class="admin-tab-trigger h-10 rounded-lg px-3 text-sm border border-border hover:bg-muted transition-colors" data-tab-target="locations">{{ __('ui.admin.tabs.locations') }}</button>
                </nav>
            </header>

            <div class="p-6 md:p-8">
                <section id="adminTab-settings" class="admin-tab-content space-y-4">
                    <div>
                        <h2 class="text-2xl font-serif font-semibold text-primary">{{ __('ui.admin.settings_title') }}</h2>
                        <p class="text-muted-foreground mt-1">{{ __('ui.admin.settings_description') }}</p>
                    </div>
                    <div id="adminSettingsLoading" class="text-sm text-muted-foreground">{{ __('ui.admin.loading') }}</div>
                    <div id="adminSettingsList" class="hidden space-y-3"></div>
                </section>

                <section id="adminTab-users" class="admin-tab-content hidden space-y-4">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div>
                            <h2 class="text-2xl font-serif font-semibold text-primary">{{ __('ui.admin.users_title') }}</h2>
                            <p class="text-muted-foreground mt-1">{{ __('ui.admin.users_description') }}</p>
                        </div>
                        <form id="adminUsersSearchForm" class="flex gap-2">
                            <input id="adminUsersSearch" type="search" class="w-64 h-10 px-3 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring" placeholder="{{ __('ui.admin.search_users') }}" />
                            <button type="submit" class="inline-flex items-center justify-center px-4 h-10 rounded-lg border border-border hover:bg-muted transition-colors">{{ __('ui.buttons.search') }}</button>
                        </form>
                    </div>
                    <div id="adminUsersLoading" class="text-sm text-muted-foreground">{{ __('ui.admin.loading') }}</div>
                    <div id="adminUsersList" class="hidden space-y-3"></div>
                    <div id="adminUsersEmpty" class="hidden text-sm text-muted-foreground">{{ __('ui.admin.no_users') }}</div>
                    <div id="adminUsersPagination" class="hidden items-center justify-between pt-2">
                        <button type="button" id="adminUsersPrev" class="inline-flex items-center justify-center px-3 h-9 rounded-lg border border-border hover:bg-muted transition-colors">{{ __('ui.admin.pagination.prev') }}</button>
                        <span id="adminUsersPageInfo" class="text-sm text-muted-foreground"></span>
                        <button type="button" id="adminUsersNext" class="inline-flex items-center justify-center px-3 h-9 rounded-lg border border-border hover:bg-muted transition-colors">{{ __('ui.admin.pagination.next') }}</button>
                    </div>
                </section>

                <section id="adminTab-memorials" class="admin-tab-content hidden space-y-4">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div>
                            <h2 class="text-2xl font-serif font-semibold text-primary">{{ __('ui.admin.memorials_title') }}</h2>
                            <p class="text-muted-foreground mt-1">{{ __('ui.admin.memorials_description') }}</p>
                        </div>
                        <form id="adminMemorialsSearchForm" class="flex gap-2">
                            <input id="adminMemorialsSearch" type="search" class="w-64 h-10 px-3 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring" placeholder="{{ __('ui.admin.search_memorials') }}" />
                            <button type="submit" class="inline-flex items-center justify-center px-4 h-10 rounded-lg border border-border hover:bg-muted transition-colors">{{ __('ui.buttons.search') }}</button>
                        </form>
                    </div>
                    <div id="adminMemorialsLoading" class="text-sm text-muted-foreground">{{ __('ui.admin.loading') }}</div>
                    <div id="adminMemorialsList" class="hidden grid gap-4 md:grid-cols-2"></div>
                    <div id="adminMemorialsEmpty" class="hidden text-sm text-muted-foreground">{{ __('ui.admin.no_memorials') }}</div>
                    <div id="adminMemorialsPagination" class="hidden items-center justify-between pt-2">
                        <button type="button" id="adminMemorialsPrev" class="inline-flex items-center justify-center px-3 h-9 rounded-lg border border-border hover:bg-muted transition-colors">{{ __('ui.admin.pagination.prev') }}</button>
                        <span id="adminMemorialsPageInfo" class="text-sm text-muted-foreground"></span>
                        <button type="button" id="adminMemorialsNext" class="inline-flex items-center justify-center px-3 h-9 rounded-lg border border-border hover:bg-muted transition-colors">{{ __('ui.admin.pagination.next') }}</button>
                    </div>
                </section>

                <section id="adminTab-hero" class="admin-tab-content hidden space-y-4">
                    <div>
                        <h2 class="text-2xl font-serif font-semibold text-primary">{{ __('ui.admin.hero_title') }}</h2>
                        <p class="text-muted-foreground mt-1">{{ __('ui.admin.hero_description') }}</p>
                    </div>
                    <div id="adminHeroLoading" class="text-sm text-muted-foreground">{{ __('ui.admin.loading') }}</div>
                    <form id="adminHeroForm" class="hidden space-y-4">
                        <div class="space-y-2">
                            <label for="hero_title" class="block text-sm font-medium text-foreground">{{ __('ui.admin.hero_fields.hero_title') }}</label>
                            <input id="hero_title" name="hero_title" type="text" required class="w-full h-11 px-3 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring">
                        </div>
                        <div class="space-y-2">
                            <label for="hero_subtitle" class="block text-sm font-medium text-foreground">{{ __('ui.admin.hero_fields.hero_subtitle') }}</label>
                            <input id="hero_subtitle" name="hero_subtitle" type="text" required class="w-full h-11 px-3 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring">
                        </div>
                        <div class="space-y-2">
                            <label for="hero_image_url" class="block text-sm font-medium text-foreground">{{ __('ui.admin.hero_fields.hero_image_url') }}</label>
                            <input id="hero_image_url" name="hero_image_url" type="text" class="w-full h-11 px-3 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring">
                        </div>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label for="cta_button_text" class="block text-sm font-medium text-foreground">{{ __('ui.admin.hero_fields.cta_button_text') }}</label>
                                <input id="cta_button_text" name="cta_button_text" type="text" required class="w-full h-11 px-3 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring">
                            </div>
                            <div class="space-y-2">
                                <label for="cta_button_link" class="block text-sm font-medium text-foreground">{{ __('ui.admin.hero_fields.cta_button_link') }}</label>
                                <input id="cta_button_link" name="cta_button_link" type="text" required class="w-full h-11 px-3 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring">
                            </div>
                        </div>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label for="secondary_button_text" class="block text-sm font-medium text-foreground">{{ __('ui.admin.hero_fields.secondary_button_text') }}</label>
                                <input id="secondary_button_text" name="secondary_button_text" type="text" required class="w-full h-11 px-3 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring">
                            </div>
                            <div class="space-y-2">
                                <label for="secondary_button_link" class="block text-sm font-medium text-foreground">{{ __('ui.admin.hero_fields.secondary_button_link') }}</label>
                                <input id="secondary_button_link" name="secondary_button_link" type="text" required class="w-full h-11 px-3 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-ring">
                            </div>
                        </div>
                        <button id="adminHeroSubmit" type="submit" class="inline-flex items-center justify-center px-6 h-11 rounded-lg bg-gradient-accent text-accent-foreground font-semibold hover:opacity-90 transition-opacity">
                            {{ __('ui.admin.hero_save') }}
                        </button>
                    </form>
                </section>

                <section id="adminTab-seo" class="admin-tab-content hidden space-y-4">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div>
                            <h2 class="text-2xl font-serif font-semibold text-primary">{{ __('ui.admin.seo_title') }}</h2>
                            <p class="text-muted-foreground mt-1">{{ __('ui.admin.seo_description') }}</p>
                        </div>
                        <button id="adminSeoRunBtn" type="button" class="inline-flex items-center justify-center px-5 h-10 rounded-lg border border-border hover:bg-muted transition-colors">
                            {{ __('ui.admin.seo_run_check') }}
                        </button>
                    </div>
                    <div id="adminSeoLoading" class="hidden text-sm text-muted-foreground">{{ __('ui.admin.loading') }}</div>
                    <div id="adminSeoSummary" class="hidden rounded-xl border border-border bg-background p-4"></div>
                    <div id="adminSeoLocales" class="hidden rounded-xl border border-border overflow-hidden"></div>
                </section>

                <section id="adminTab-locations" class="admin-tab-content hidden space-y-6">
                    <div>
                        <h2 class="text-2xl font-serif font-semibold text-primary">{{ __('ui.admin.locations_title') }}</h2>
                        <p class="text-muted-foreground mt-1">{{ __('ui.admin.locations_description') }}</p>
                    </div>

                    <article class="rounded-xl border border-border bg-background p-4 space-y-3">
                        <h3 class="text-lg font-semibold text-primary">{{ __('ui.admin.locations_add_country_title') }}</h3>
                        <form id="adminLocationCountryForm" class="grid gap-3 md:grid-cols-4">
                            <div class="space-y-1 md:col-span-1">
                                <label for="locationCountryCode" class="text-sm text-foreground">{{ __('ui.admin.locations_country_code') }}</label>
                                <input id="locationCountryCode" name="code" type="text" maxlength="2" class="w-full h-10 px-3 rounded-lg border border-border bg-card focus:outline-none focus:ring-2 focus:ring-ring" placeholder="BA">
                            </div>
                            <div class="space-y-1 md:col-span-2">
                                <label for="locationCountryName" class="text-sm text-foreground">{{ __('ui.admin.locations_country_name') }}</label>
                                <input id="locationCountryName" name="name" type="text" class="w-full h-10 px-3 rounded-lg border border-border bg-card focus:outline-none focus:ring-2 focus:ring-ring" placeholder="{{ __('ui.admin.locations_country_name_placeholder') }}">
                            </div>
                            <div class="flex items-end gap-2 md:col-span-1">
                                <button id="locationCountrySubmit" type="submit" class="inline-flex items-center justify-center px-4 h-10 rounded-lg bg-gradient-accent text-accent-foreground font-semibold hover:opacity-90 transition-opacity w-full">
                                    {{ __('ui.admin.locations_add_country') }}
                                </button>
                            </div>
                        </form>
                    </article>

                    <article class="rounded-xl border border-border bg-background p-4 space-y-3">
                        <h3 class="text-lg font-semibold text-primary">{{ __('ui.admin.locations_add_place_title') }}</h3>
                        <form id="adminLocationPlaceForm" class="grid gap-3 md:grid-cols-5">
                            <div class="space-y-1 md:col-span-2">
                                <label for="locationPlaceCountry" class="text-sm text-foreground">{{ __('ui.admin.locations_country') }}</label>
                                <select id="locationPlaceCountry" name="country_id" class="w-full h-10 px-3 rounded-lg border border-border bg-card focus:outline-none focus:ring-2 focus:ring-ring"></select>
                            </div>
                            <div class="space-y-1 md:col-span-2">
                                <label for="locationPlaceName" class="text-sm text-foreground">{{ __('ui.admin.locations_place_name') }}</label>
                                <input id="locationPlaceName" name="name" type="text" class="w-full h-10 px-3 rounded-lg border border-border bg-card focus:outline-none focus:ring-2 focus:ring-ring" placeholder="{{ __('ui.admin.locations_place_name_placeholder') }}">
                            </div>
                            <div class="space-y-1 md:col-span-1">
                                <label for="locationPlaceType" class="text-sm text-foreground">{{ __('ui.admin.locations_place_type') }}</label>
                                <select id="locationPlaceType" name="type" class="w-full h-10 px-3 rounded-lg border border-border bg-card focus:outline-none focus:ring-2 focus:ring-ring">
                                    <option value="city">city</option>
                                    <option value="town">town</option>
                                    <option value="village">village</option>
                                    <option value="settlement">settlement</option>
                                </select>
                            </div>
                            <div class="md:col-span-5 flex justify-end">
                                <button id="locationPlaceSubmit" type="submit" class="inline-flex items-center justify-center px-4 h-10 rounded-lg bg-gradient-accent text-accent-foreground font-semibold hover:opacity-90 transition-opacity">
                                    {{ __('ui.admin.locations_add_place') }}
                                </button>
                            </div>
                        </form>
                    </article>

                    <article class="rounded-xl border border-border bg-background p-4 space-y-3">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                            <h3 class="text-lg font-semibold text-primary">{{ __('ui.admin.locations_import_title') }}</h3>
                            <p class="text-xs text-muted-foreground">{{ __('ui.admin.locations_import_hint') }}</p>
                        </div>
                        <form id="adminLocationImportForm" class="grid gap-3 md:grid-cols-2">
                            <div class="space-y-1">
                                <label for="locationImportCountries" class="text-sm text-foreground">{{ __('ui.admin.locations_import_countries') }}</label>
                                <textarea id="locationImportCountries" name="country_lines" rows="7" class="w-full px-3 py-2 rounded-lg border border-border bg-card focus:outline-none focus:ring-2 focus:ring-ring" placeholder="BA|Bosna i Hercegovina&#10;RS|Srbija"></textarea>
                            </div>
                            <div class="space-y-1">
                                <label for="locationImportPlaces" class="text-sm text-foreground">{{ __('ui.admin.locations_import_places') }}</label>
                                <textarea id="locationImportPlaces" name="place_lines" rows="7" class="w-full px-3 py-2 rounded-lg border border-border bg-card focus:outline-none focus:ring-2 focus:ring-ring" placeholder="BA|Sarajevo|city&#10;RS|Beograd|city"></textarea>
                            </div>
                            <div class="md:col-span-2 flex justify-end">
                                <button id="locationImportSubmit" type="submit" class="inline-flex items-center justify-center px-4 h-10 rounded-lg border border-border hover:bg-muted transition-colors">
                                    {{ __('ui.admin.locations_run_import') }}
                                </button>
                            </div>
                        </form>
                    </article>

                    <article class="rounded-xl border border-border bg-background p-4 space-y-3">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                            <h3 class="text-lg font-semibold text-primary">{{ __('ui.admin.locations_list_title') }}</h3>
                            <form id="adminLocationSearchForm" class="flex gap-2">
                                <input id="adminLocationSearch" type="search" class="w-64 h-10 px-3 rounded-lg border border-border bg-card focus:outline-none focus:ring-2 focus:ring-ring" placeholder="{{ __('ui.admin.locations_search_placeholder') }}">
                                <select id="adminLocationCountryFilter" class="h-10 px-3 rounded-lg border border-border bg-card focus:outline-none focus:ring-2 focus:ring-ring"></select>
                                <button type="submit" class="inline-flex items-center justify-center px-4 h-10 rounded-lg border border-border hover:bg-muted transition-colors">{{ __('ui.buttons.search') }}</button>
                            </form>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-2">
                            <div>
                                <p class="text-sm font-medium text-foreground mb-2">{{ __('ui.admin.locations_countries_list') }}</p>
                                <div id="adminLocationsCountriesLoading" class="text-sm text-muted-foreground">{{ __('ui.admin.loading') }}</div>
                                <div id="adminLocationsCountriesList" class="hidden rounded-lg border border-border overflow-hidden"></div>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-foreground mb-2">{{ __('ui.admin.locations_places_list') }}</p>
                                <div id="adminLocationsPlacesLoading" class="text-sm text-muted-foreground">{{ __('ui.admin.loading') }}</div>
                                <div id="adminLocationsPlacesList" class="hidden rounded-lg border border-border overflow-hidden"></div>
                            </div>
                        </div>
                    </article>
                </section>
            </div>
        </section>
    </div>
</main>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const token = localStorage.getItem('auth_token') || '';
        const loginUrl = @json($loginUrl);
        const dashboardUrl = @json($dashboardUrl);
        const homeUrl = @json($homeUrl);
        const profileTemplate = @json($profileUrlTemplate);
        const editTemplate = @json($editUrlTemplate);

        const labels = {
            settings: {
                card_payment: @json(__('ui.admin.setting_labels.card_payment')),
                paypal_payment: @json(__('ui.admin.setting_labels.paypal_payment')),
                physical_qr_delivery: @json(__('ui.admin.setting_labels.physical_qr_delivery')),
                paid_memorials: @json(__('ui.admin.setting_labels.paid_memorials')),
            },
            users: {
                admin: @json(__('ui.admin.role_admin')),
                user: @json(__('ui.admin.role_user')),
                addAdmin: @json(__('ui.admin.add_admin')),
                removeAdmin: @json(__('ui.admin.remove_admin')),
                registered: @json(__('ui.admin.registered')),
                deleteUser: @json(__('ui.admin.delete_user')),
                deleteUserConfirm: @json(__('ui.admin.delete_user_confirm')),
                selfProtected: @json(__('ui.admin.delete_self_protected')),
            },
            memorials: {
                owner: @json(__('ui.admin.owner')),
                view: @json(__('ui.admin.view')),
                edit: @json(__('ui.admin.edit')),
                delete: @json(__('ui.admin.delete')),
                deleteConfirm: @json(__('ui.admin.delete_memorial_confirm')),
            },
            seo: {
                ok: @json(__('ui.admin.seo_ok')),
                fail: @json(__('ui.admin.seo_fail')),
                checkedAt: @json(__('ui.admin.seo_checked_at')),
                locales: @json(__('ui.admin.seo_locales_checked')),
                passed: @json(__('ui.admin.seo_passed')),
                failed: @json(__('ui.admin.seo_failed')),
                issues: @json(__('ui.admin.seo_issues')),
                sitemap: @json(__('ui.admin.seo_sitemap_status')),
            },
            locations: {
                allCountries: @json(__('ui.admin.locations_all_countries')),
                noCountries: @json(__('ui.admin.locations_no_countries')),
                noPlaces: @json(__('ui.admin.locations_no_places')),
                importDone: @json(__('ui.admin.locations_import_done')),
                selectCountry: @json(__('ui.admin.locations_select_country')),
                countryColumn: @json(__('ui.admin.locations_column_country')),
                codeColumn: @json(__('ui.admin.locations_column_code')),
                placesColumn: @json(__('ui.admin.locations_column_places')),
                placeColumn: @json(__('ui.admin.locations_column_place')),
                typeColumn: @json(__('ui.admin.locations_column_type')),
            },
        };

        const alertBox = document.getElementById('adminAlert');
        const userEl = document.getElementById('adminUser');
        const logoutBtn = document.getElementById('adminLogoutBtn');

        const statUsersEl = document.getElementById('statUsers');
        const statMemorialsEl = document.getElementById('statMemorials');
        const statTributesEl = document.getElementById('statTributes');
        const statPublicMemorialsEl = document.getElementById('statPublicMemorials');
        const statPrivateMemorialsEl = document.getElementById('statPrivateMemorials');

        const tabs = Array.from(document.querySelectorAll('.admin-tab-trigger'));
        const tabContents = Array.from(document.querySelectorAll('.admin-tab-content'));

        const settingsLoading = document.getElementById('adminSettingsLoading');
        const settingsList = document.getElementById('adminSettingsList');

        const usersSearchForm = document.getElementById('adminUsersSearchForm');
        const usersSearchInput = document.getElementById('adminUsersSearch');
        const usersLoading = document.getElementById('adminUsersLoading');
        const usersList = document.getElementById('adminUsersList');
        const usersEmpty = document.getElementById('adminUsersEmpty');
        const usersPagination = document.getElementById('adminUsersPagination');
        const usersPrevBtn = document.getElementById('adminUsersPrev');
        const usersNextBtn = document.getElementById('adminUsersNext');
        const usersPageInfo = document.getElementById('adminUsersPageInfo');

        const memorialsSearchForm = document.getElementById('adminMemorialsSearchForm');
        const memorialsSearchInput = document.getElementById('adminMemorialsSearch');
        const memorialsLoading = document.getElementById('adminMemorialsLoading');
        const memorialsList = document.getElementById('adminMemorialsList');
        const memorialsEmpty = document.getElementById('adminMemorialsEmpty');
        const memorialsPagination = document.getElementById('adminMemorialsPagination');
        const memorialsPrevBtn = document.getElementById('adminMemorialsPrev');
        const memorialsNextBtn = document.getElementById('adminMemorialsNext');
        const memorialsPageInfo = document.getElementById('adminMemorialsPageInfo');

        const heroLoading = document.getElementById('adminHeroLoading');
        const heroForm = document.getElementById('adminHeroForm');
        const heroSubmitBtn = document.getElementById('adminHeroSubmit');

        const seoRunBtn = document.getElementById('adminSeoRunBtn');
        const seoLoading = document.getElementById('adminSeoLoading');
        const seoSummary = document.getElementById('adminSeoSummary');
        const seoLocales = document.getElementById('adminSeoLocales');

        const locationCountryForm = document.getElementById('adminLocationCountryForm');
        const locationCountryCodeInput = document.getElementById('locationCountryCode');
        const locationCountryNameInput = document.getElementById('locationCountryName');
        const locationCountrySubmitBtn = document.getElementById('locationCountrySubmit');
        const locationPlaceForm = document.getElementById('adminLocationPlaceForm');
        const locationPlaceCountryInput = document.getElementById('locationPlaceCountry');
        const locationPlaceNameInput = document.getElementById('locationPlaceName');
        const locationPlaceTypeInput = document.getElementById('locationPlaceType');
        const locationPlaceSubmitBtn = document.getElementById('locationPlaceSubmit');
        const locationImportForm = document.getElementById('adminLocationImportForm');
        const locationImportCountriesInput = document.getElementById('locationImportCountries');
        const locationImportPlacesInput = document.getElementById('locationImportPlaces');
        const locationImportSubmitBtn = document.getElementById('locationImportSubmit');
        const locationSearchForm = document.getElementById('adminLocationSearchForm');
        const locationSearchInput = document.getElementById('adminLocationSearch');
        const locationCountryFilterInput = document.getElementById('adminLocationCountryFilter');
        const locationsCountriesLoading = document.getElementById('adminLocationsCountriesLoading');
        const locationsCountriesList = document.getElementById('adminLocationsCountriesList');
        const locationsPlacesLoading = document.getElementById('adminLocationsPlacesLoading');
        const locationsPlacesList = document.getElementById('adminLocationsPlacesList');

        let currentUser = null;
        let usersState = { page: 1, lastPage: 1 };
        let memorialsState = { page: 1, lastPage: 1 };
        let locationCountriesState = [];

        function showAlert(type, text) {
            alertBox.classList.remove('hidden', 'border-red-200', 'bg-red-50', 'text-red-700', 'border-green-200', 'bg-green-50', 'text-green-700');
            if (type === 'error') {
                alertBox.classList.add('border-red-200', 'bg-red-50', 'text-red-700');
            } else {
                alertBox.classList.add('border-green-200', 'bg-green-50', 'text-green-700');
            }
            alertBox.textContent = text;
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

        function formatDate(value) {
            if (!value) {
                return '-';
            }
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                return '-';
            }
            return date.toLocaleDateString();
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

        function setButtonLoading(button, isLoading) {
            if (!button) {
                return;
            }
            button.disabled = isLoading;
            button.classList.toggle('opacity-70', isLoading);
            button.classList.toggle('cursor-not-allowed', isLoading);
        }

        function isAdminUser(user) {
            const relationAdmin = Array.isArray(user?.roles)
                ? user.roles.some((role) => {
                    if (typeof role === 'string') {
                        return role === 'admin';
                    }
                    return role?.role === 'admin';
                })
                : false;

            return relationAdmin || user?.role === 'admin';
        }

        async function apiRequest(url, options = {}) {
            const headers = Object.assign({ Accept: 'application/json' }, options.headers || {});
            if (!(options.body instanceof FormData)) {
                headers['Content-Type'] = headers['Content-Type'] || 'application/json';
            }

            if (token) {
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
                const validationMessage = payload?.errors ? Object.values(payload.errors).flat().join(' ') : '';
                const message = validationMessage || payload?.message || 'Request failed.';
                const requestError = new Error(message);
                requestError.status = response.status;
                requestError.payload = payload;
                throw requestError;
            }

            return payload;
        }

        function activateTab(target) {
            tabs.forEach((tab) => {
                const active = tab.dataset.tabTarget === target;
                tab.classList.toggle('bg-muted/60', active);
                tab.classList.toggle('hover:bg-muted', !active);
            });

            tabContents.forEach((content) => {
                content.classList.toggle('hidden', content.id !== `adminTab-${target}`);
            });
        }

        tabs.forEach((tab) => {
            tab.addEventListener('click', function () {
                activateTab(tab.dataset.tabTarget);
            });
        });

        async function loadDashboardStats() {
            const stats = await apiRequest('/api/v1/admin/dashboard');
            statUsersEl.textContent = `${stats.totalUsers ?? stats.total_users ?? 0}`;
            statMemorialsEl.textContent = `${stats.totalMemorials ?? stats.total_memorials ?? 0}`;
            statTributesEl.textContent = `${stats.totalTributes ?? stats.total_tributes ?? 0}`;
            statPublicMemorialsEl.textContent = `${stats.publicMemorials ?? stats.public_memorials ?? 0}`;
            statPrivateMemorialsEl.textContent = `${stats.privateMemorials ?? stats.private_memorials ?? 0}`;
        }

        async function loadSettings() {
            settingsLoading.classList.remove('hidden');
            settingsList.classList.add('hidden');

            const settingsResponse = await apiRequest('/api/v1/admin/settings');
            const settings = Array.isArray(settingsResponse?.data) ? settingsResponse.data : [];
            const keyOrder = ['card_payment', 'paypal_payment', 'physical_qr_delivery', 'paid_memorials'];
            settings.sort((a, b) => keyOrder.indexOf(a.key) - keyOrder.indexOf(b.key));

            settingsList.innerHTML = '';
            settings.forEach((setting) => {
                const row = document.createElement('label');
                row.className = 'flex items-center justify-between gap-4 rounded-lg border border-border bg-background px-4 py-3';
                row.innerHTML = `
                    <span class="text-sm font-medium text-foreground">${escapeHtml(labels.settings[setting.key] || setting.key)}</span>
                    <input type="checkbox" data-setting-key="${escapeHtml(setting.key)}" class="h-5 w-5 rounded border-border" ${setting.isEnabled ? 'checked' : ''}>
                `;
                settingsList.appendChild(row);
            });

            settingsList.querySelectorAll('input[data-setting-key]').forEach((checkbox) => {
                checkbox.addEventListener('change', async function () {
                    const settingKey = this.dataset.settingKey;
                    const enabled = this.checked;
                    this.disabled = true;
                    try {
                        await apiRequest(`/api/v1/admin/settings/${encodeURIComponent(settingKey)}`, {
                            method: 'PUT',
                            body: JSON.stringify({ is_enabled: enabled }),
                        });
                        showAlert('success', @json(__('ui.admin.settings_saved')));
                    } catch (error) {
                        this.checked = !enabled;
                        showAlert('error', error.message || @json(__('ui.admin.action_failed')));
                    } finally {
                        this.disabled = false;
                    }
                });
            });

            settingsLoading.classList.add('hidden');
            settingsList.classList.remove('hidden');
        }

        function renderUsers(users) {
            usersList.innerHTML = '';
            users.forEach((user) => {
                const isAdmin = user.role === 'admin' || (Array.isArray(user.roles) && user.roles.includes('admin'));
                const displayName = user?.profile?.fullName || user.email;
                const card = document.createElement('article');
                card.className = 'rounded-xl border border-border bg-background p-4';
                card.innerHTML = `
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div>
                            <h3 class="text-base font-semibold text-primary">${escapeHtml(displayName || '-')}</h3>
                            <p class="text-sm text-muted-foreground">${escapeHtml(user.email || '-')}</p>
                            <p class="text-xs text-muted-foreground mt-1">${escapeHtml(labels.users.registered)}: ${escapeHtml(formatDate(user.createdAt))}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center justify-center px-2.5 h-8 rounded-full text-xs ${isAdmin ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'}">${isAdmin ? escapeHtml(labels.users.admin) : escapeHtml(labels.users.user)}</span>
                            <button type="button" data-action="toggle-admin" class="inline-flex items-center justify-center px-3 h-9 rounded-lg border border-border text-sm hover:bg-muted transition-colors">${isAdmin ? escapeHtml(labels.users.removeAdmin) : escapeHtml(labels.users.addAdmin)}</button>
                            <button type="button" data-action="delete-user" class="inline-flex items-center justify-center px-3 h-9 rounded-lg border border-red-200 text-red-700 text-sm hover:bg-red-50 transition-colors">${escapeHtml(labels.users.deleteUser)}</button>
                        </div>
                    </div>
                `;

                card.querySelector('[data-action="toggle-admin"]').addEventListener('click', async () => {
                    try {
                        await apiRequest(`/api/v1/admin/users/${encodeURIComponent(user.id)}/role`, {
                            method: 'PUT',
                            body: JSON.stringify({
                                role: 'admin',
                                action: isAdmin ? 'remove' : 'add',
                            }),
                        });
                        showAlert('success', @json(__('ui.admin.user_updated')));
                        await loadUsers(usersState.page);
                    } catch (error) {
                        showAlert('error', error.message || @json(__('ui.admin.action_failed')));
                    }
                });

                card.querySelector('[data-action="delete-user"]').addEventListener('click', async () => {
                    if ((currentUser?.id || '') === user.id) {
                        showAlert('error', labels.users.selfProtected);
                        return;
                    }
                    if (!confirm(labels.users.deleteUserConfirm)) {
                        return;
                    }
                    try {
                        await apiRequest(`/api/v1/admin/users/${encodeURIComponent(user.id)}`, { method: 'DELETE' });
                        showAlert('success', @json(__('ui.admin.user_deleted')));
                        await Promise.all([loadUsers(usersState.page), loadDashboardStats()]);
                    } catch (error) {
                        showAlert('error', error.message || @json(__('ui.admin.action_failed')));
                    }
                });

                usersList.appendChild(card);
            });
        }

        async function loadUsers(page = 1) {
            usersLoading.classList.remove('hidden');
            usersList.classList.add('hidden');
            usersEmpty.classList.add('hidden');
            usersPagination.classList.add('hidden');
            usersPagination.classList.remove('flex');

            const search = usersSearchInput.value.trim();
            const query = new URLSearchParams({
                per_page: '10',
                page: String(page),
            });
            if (search) {
                query.set('search', search);
            }

            const usersResponse = await apiRequest(`/api/v1/admin/users?${query.toString()}`);
            const users = Array.isArray(usersResponse?.data) ? usersResponse.data : [];
            const meta = usersResponse?.meta || {};
            usersState = {
                page: Number(meta.currentPage || 1),
                lastPage: Number(meta.lastPage || 1),
            };

            if (users.length === 0) {
                usersEmpty.classList.remove('hidden');
            } else {
                renderUsers(users);
                usersList.classList.remove('hidden');
            }

            usersPageInfo.textContent = `${usersState.page} / ${usersState.lastPage}`;
            usersPrevBtn.disabled = usersState.page <= 1;
            usersNextBtn.disabled = usersState.page >= usersState.lastPage;
            usersPagination.classList.remove('hidden');
            usersPagination.classList.add('flex');
            usersLoading.classList.add('hidden');
        }

        function renderMemorials(memorials) {
            memorialsList.innerHTML = '';
            memorials.forEach((memorial) => {
                const profileUrl = profileTemplate.replace('__SLUG__', encodeURIComponent(memorial.slug));
                const editUrl = editTemplate.replace('__SLUG__', encodeURIComponent(memorial.slug));
                const birthDate = formatEuropeanDate(memorial.birthDate || '');
                const deathDate = formatEuropeanDate(memorial.deathDate || '');
                const lifespan = [birthDate, deathDate].filter((part) => part !== '').join(' - ');
                const owner = memorial?.owner?.fullName || memorial?.owner?.email || '-';
                const imageMarkup = memorial.profileImageUrl
                    ? `<img src="${escapeHtml(memorial.profileImageUrl)}" alt="${escapeHtml(memorial.firstName || '')}" class="w-20 h-20 rounded-lg object-cover">`
                    : `<div class="w-20 h-20 rounded-lg bg-muted flex items-center justify-center text-muted-foreground">IMG</div>`;

                const card = document.createElement('article');
                card.className = 'rounded-xl border border-border bg-background p-4';
                card.innerHTML = `
                    <div class="flex items-start gap-4">
                        ${imageMarkup}
                        <div class="flex-1 space-y-2">
                            <h3 class="text-lg font-serif font-semibold text-primary">${escapeHtml(memorial.firstName || '')} ${escapeHtml(memorial.lastName || '')}</h3>
                            <p class="text-sm text-muted-foreground">${escapeHtml(lifespan)}</p>
                            <p class="text-xs text-muted-foreground">${escapeHtml(labels.memorials.owner)}: ${escapeHtml(owner)}</p>
                            <div class="flex flex-wrap gap-2 pt-1">
                                <a href="${escapeHtml(profileUrl)}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center px-3 h-9 rounded-lg border border-border text-sm hover:bg-muted transition-colors">${escapeHtml(labels.memorials.view)}</a>
                                <a href="${escapeHtml(editUrl)}" class="inline-flex items-center justify-center px-3 h-9 rounded-lg border border-border text-sm hover:bg-muted transition-colors">${escapeHtml(labels.memorials.edit)}</a>
                                <button type="button" data-action="delete-memorial" class="inline-flex items-center justify-center px-3 h-9 rounded-lg border border-red-200 text-red-700 text-sm hover:bg-red-50 transition-colors">${escapeHtml(labels.memorials.delete)}</button>
                            </div>
                        </div>
                    </div>
                `;

                card.querySelector('[data-action="delete-memorial"]').addEventListener('click', async () => {
                    if (!confirm(labels.memorials.deleteConfirm)) {
                        return;
                    }
                    try {
                        await apiRequest(`/api/v1/admin/memorials/${encodeURIComponent(memorial.id)}`, { method: 'DELETE' });
                        showAlert('success', @json(__('ui.admin.memorial_deleted')));
                        await Promise.all([loadMemorials(memorialsState.page), loadDashboardStats()]);
                    } catch (error) {
                        showAlert('error', error.message || @json(__('ui.admin.action_failed')));
                    }
                });

                memorialsList.appendChild(card);
            });
        }

        async function loadMemorials(page = 1) {
            memorialsLoading.classList.remove('hidden');
            memorialsList.classList.add('hidden');
            memorialsEmpty.classList.add('hidden');
            memorialsPagination.classList.add('hidden');
            memorialsPagination.classList.remove('flex');

            const search = memorialsSearchInput.value.trim();
            const query = new URLSearchParams({
                per_page: '8',
                page: String(page),
            });
            if (search) {
                query.set('search', search);
            }

            const memorialsResponse = await apiRequest(`/api/v1/admin/memorials?${query.toString()}`);
            const memorials = Array.isArray(memorialsResponse?.data) ? memorialsResponse.data : [];
            const meta = memorialsResponse?.meta || {};
            memorialsState = {
                page: Number(meta.currentPage || 1),
                lastPage: Number(meta.lastPage || 1),
            };

            if (memorials.length === 0) {
                memorialsEmpty.classList.remove('hidden');
            } else {
                renderMemorials(memorials);
                memorialsList.classList.remove('hidden');
            }

            memorialsPageInfo.textContent = `${memorialsState.page} / ${memorialsState.lastPage}`;
            memorialsPrevBtn.disabled = memorialsState.page <= 1;
            memorialsNextBtn.disabled = memorialsState.page >= memorialsState.lastPage;
            memorialsPagination.classList.remove('hidden');
            memorialsPagination.classList.add('flex');
            memorialsLoading.classList.add('hidden');
        }

        async function loadHeroSettings() {
            heroLoading.classList.remove('hidden');
            heroForm.classList.add('hidden');
            const heroSettings = await apiRequest('/api/v1/hero-settings');

            ['hero_title', 'hero_subtitle', 'hero_image_url', 'cta_button_text', 'cta_button_link', 'secondary_button_text', 'secondary_button_link']
                .forEach((field) => {
                    const input = heroForm.querySelector(`#${field}`);
                    input.value = heroSettings?.[field] || '';
                });

            heroLoading.classList.add('hidden');
            heroForm.classList.remove('hidden');
        }

        async function runSeoHealthCheck() {
            seoLoading.classList.remove('hidden');
            seoSummary.classList.add('hidden');
            seoLocales.classList.add('hidden');

            try {
                const response = await apiRequest('/api/v1/admin/seo/locale-health');
                const summary = response?.summary || {};
                const sitemapOk = response?.checks?.sitemap?.ok ? labels.seo.ok : labels.seo.fail;
                seoSummary.innerHTML = `
                    <div class="grid gap-3 md:grid-cols-3">
                        <div><p class="text-xs text-muted-foreground uppercase">${escapeHtml(labels.seo.checkedAt)}</p><p class="font-medium">${escapeHtml(formatDate(response.checked_at))}</p></div>
                        <div><p class="text-xs text-muted-foreground uppercase">${escapeHtml(labels.seo.locales)}</p><p class="font-medium">${escapeHtml(String(summary.locales_checked ?? 0))}</p></div>
                        <div><p class="text-xs text-muted-foreground uppercase">${escapeHtml(labels.seo.sitemap)}</p><p class="font-medium">${escapeHtml(sitemapOk)}</p></div>
                        <div><p class="text-xs text-muted-foreground uppercase">${escapeHtml(labels.seo.passed)}</p><p class="font-medium">${escapeHtml(String(summary.locale_pages_passed ?? 0))}</p></div>
                        <div><p class="text-xs text-muted-foreground uppercase">${escapeHtml(labels.seo.failed)}</p><p class="font-medium">${escapeHtml(String(summary.locale_pages_failed ?? 0))}</p></div>
                        <div><p class="text-xs text-muted-foreground uppercase">${escapeHtml(labels.seo.issues)}</p><p class="font-medium">${escapeHtml(String(summary.issues_count ?? 0))}</p></div>
                    </div>
                `;

                const localePages = response?.checks?.locale_pages || {};
                const rows = Object.keys(localePages).map((localeKey) => {
                    const item = localePages[localeKey];
                    const ok = item?.ok ? labels.seo.ok : labels.seo.fail;
                    return `
                        <tr class="border-b border-border">
                            <td class="px-4 py-3 text-sm font-medium uppercase">${escapeHtml(localeKey)}</td>
                            <td class="px-4 py-3 text-sm">${escapeHtml(ok)}</td>
                            <td class="px-4 py-3 text-sm">${escapeHtml(String(item?.http_status ?? '-'))}</td>
                            <td class="px-4 py-3 text-sm">${escapeHtml(item?.canonical?.ok ? labels.seo.ok : labels.seo.fail)}</td>
                            <td class="px-4 py-3 text-sm">${escapeHtml(item?.hreflang?.ok ? labels.seo.ok : labels.seo.fail)}</td>
                        </tr>
                    `;
                }).join('');

                seoLocales.innerHTML = `
                    <table class="w-full text-left">
                        <thead class="bg-muted/40">
                            <tr>
                                <th class="px-4 py-3 text-xs uppercase text-muted-foreground">Locale</th>
                                <th class="px-4 py-3 text-xs uppercase text-muted-foreground">Status</th>
                                <th class="px-4 py-3 text-xs uppercase text-muted-foreground">HTTP</th>
                                <th class="px-4 py-3 text-xs uppercase text-muted-foreground">Canonical</th>
                                <th class="px-4 py-3 text-xs uppercase text-muted-foreground">Hreflang</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                `;

                seoSummary.classList.remove('hidden');
                seoLocales.classList.remove('hidden');
                showAlert('success', @json(__('ui.admin.seo_check_completed')));
            } catch (error) {
                showAlert('error', error.message || @json(__('ui.admin.action_failed')));
            } finally {
                seoLoading.classList.add('hidden');
            }
        }

        function renderLocationCountryOptions(selectEl, includeAllOption = false, selectedValue = null) {
            if (!selectEl) {
                return;
            }

            const normalizedSelected = selectedValue === null ? '' : String(selectedValue);
            selectEl.innerHTML = '';

            if (includeAllOption) {
                const allOption = document.createElement('option');
                allOption.value = '';
                allOption.textContent = labels.locations.allCountries;
                selectEl.appendChild(allOption);
            } else {
                const selectOption = document.createElement('option');
                selectOption.value = '';
                selectOption.textContent = labels.locations.selectCountry;
                selectEl.appendChild(selectOption);
            }

            locationCountriesState.forEach((country) => {
                const option = document.createElement('option');
                option.value = String(country.id);
                option.textContent = `${country.name} (${country.code})`;
                selectEl.appendChild(option);
            });

            if (normalizedSelected !== '') {
                selectEl.value = normalizedSelected;
            }
        }

        function renderLocationCountriesList(countries) {
            if (!Array.isArray(countries) || countries.length === 0) {
                locationsCountriesList.innerHTML = `<div class="p-3 text-sm text-muted-foreground">${escapeHtml(labels.locations.noCountries)}</div>`;
                return;
            }

            const rows = countries.map((country) => `
                <tr class="border-b border-border">
                    <td class="px-3 py-2 text-sm font-medium">${escapeHtml(country.name || '-')}</td>
                    <td class="px-3 py-2 text-xs uppercase text-muted-foreground">${escapeHtml(country.code || '-')}</td>
                    <td class="px-3 py-2 text-sm">${escapeHtml(String(country.placesCount ?? 0))}</td>
                </tr>
            `).join('');

            locationsCountriesList.innerHTML = `
                <table class="w-full text-left">
                    <thead class="bg-muted/40">
                        <tr>
                            <th class="px-3 py-2 text-xs uppercase text-muted-foreground">${escapeHtml(labels.locations.countryColumn)}</th>
                            <th class="px-3 py-2 text-xs uppercase text-muted-foreground">${escapeHtml(labels.locations.codeColumn)}</th>
                            <th class="px-3 py-2 text-xs uppercase text-muted-foreground">${escapeHtml(labels.locations.placesColumn)}</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            `;
        }

        function renderLocationPlacesList(places) {
            if (!Array.isArray(places) || places.length === 0) {
                locationsPlacesList.innerHTML = `<div class="p-3 text-sm text-muted-foreground">${escapeHtml(labels.locations.noPlaces)}</div>`;
                return;
            }

            const rows = places.map((place) => `
                <tr class="border-b border-border">
                    <td class="px-3 py-2 text-sm font-medium">${escapeHtml(place.name || '-')}</td>
                    <td class="px-3 py-2 text-xs uppercase text-muted-foreground">${escapeHtml(place.type || '-')}</td>
                    <td class="px-3 py-2 text-sm">${escapeHtml(place.countryName || place.countryCode || '-')}</td>
                </tr>
            `).join('');

            locationsPlacesList.innerHTML = `
                <table class="w-full text-left">
                    <thead class="bg-muted/40">
                        <tr>
                            <th class="px-3 py-2 text-xs uppercase text-muted-foreground">${escapeHtml(labels.locations.placeColumn)}</th>
                            <th class="px-3 py-2 text-xs uppercase text-muted-foreground">${escapeHtml(labels.locations.typeColumn)}</th>
                            <th class="px-3 py-2 text-xs uppercase text-muted-foreground">${escapeHtml(labels.locations.countryColumn)}</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            `;
        }

        async function loadLocationCountries() {
            locationsCountriesLoading.classList.remove('hidden');
            locationsCountriesList.classList.add('hidden');

            const query = new URLSearchParams();
            const searchTerm = locationSearchInput.value.trim();
            if (searchTerm) {
                query.set('q', searchTerm);
            }

            const queryString = query.toString();
            const response = await apiRequest(`/api/v1/admin/locations/countries${queryString ? `?${queryString}` : ''}`);
            const countries = Array.isArray(response?.data) ? response.data : [];
            locationCountriesState = countries;

            renderLocationCountryOptions(locationPlaceCountryInput, false, locationPlaceCountryInput.value || null);
            renderLocationCountryOptions(locationCountryFilterInput, true, locationCountryFilterInput.value || null);
            renderLocationCountriesList(countries);

            locationsCountriesLoading.classList.add('hidden');
            locationsCountriesList.classList.remove('hidden');
        }

        async function loadLocationPlaces() {
            locationsPlacesLoading.classList.remove('hidden');
            locationsPlacesList.classList.add('hidden');

            const query = new URLSearchParams();
            const searchTerm = locationSearchInput.value.trim();
            const countryFilter = locationCountryFilterInput.value;
            if (searchTerm) {
                query.set('q', searchTerm);
            }
            if (countryFilter) {
                query.set('country_id', countryFilter);
            }

            const response = await apiRequest(`/api/v1/admin/locations/places?${query.toString()}`);
            const places = Array.isArray(response?.data) ? response.data : [];
            renderLocationPlacesList(places);

            locationsPlacesLoading.classList.add('hidden');
            locationsPlacesList.classList.remove('hidden');
        }

        locationCountryForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            const code = locationCountryCodeInput.value.trim().toUpperCase();
            const name = locationCountryNameInput.value.trim();

            if (!code || !name) {
                showAlert('error', @json(__('ui.admin.action_failed')));
                return;
            }

            setButtonLoading(locationCountrySubmitBtn, true);
            try {
                await apiRequest('/api/v1/admin/locations/countries', {
                    method: 'POST',
                    body: JSON.stringify({
                        code,
                        name,
                        is_active: true,
                    }),
                });
                locationCountryForm.reset();
                await Promise.all([loadLocationCountries(), loadLocationPlaces()]);
                showAlert('success', @json(__('ui.admin.settings_saved')));
            } catch (error) {
                showAlert('error', error.message || @json(__('ui.admin.action_failed')));
            } finally {
                setButtonLoading(locationCountrySubmitBtn, false);
            }
        });

        locationPlaceForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            const countryId = locationPlaceCountryInput.value;
            const name = locationPlaceNameInput.value.trim();
            const type = locationPlaceTypeInput.value;

            if (!countryId || !name) {
                showAlert('error', @json(__('ui.admin.action_failed')));
                return;
            }

            setButtonLoading(locationPlaceSubmitBtn, true);
            try {
                await apiRequest('/api/v1/admin/locations/places', {
                    method: 'POST',
                    body: JSON.stringify({
                        country_id: Number(countryId),
                        name,
                        type,
                        is_active: true,
                    }),
                });
                locationPlaceNameInput.value = '';
                await loadLocationPlaces();
                await loadLocationCountries();
                showAlert('success', @json(__('ui.admin.settings_saved')));
            } catch (error) {
                showAlert('error', error.message || @json(__('ui.admin.action_failed')));
            } finally {
                setButtonLoading(locationPlaceSubmitBtn, false);
            }
        });

        locationImportForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            const countryLines = locationImportCountriesInput.value;
            const placeLines = locationImportPlacesInput.value;

            if (!countryLines.trim() && !placeLines.trim()) {
                showAlert('error', @json(__('ui.admin.action_failed')));
                return;
            }

            setButtonLoading(locationImportSubmitBtn, true);
            try {
                const response = await apiRequest('/api/v1/admin/locations/import', {
                    method: 'POST',
                    body: JSON.stringify({
                        country_lines: countryLines,
                        place_lines: placeLines,
                    }),
                });

                const summary = response?.summary || {};
                const successMessage = `${labels.locations.importDone}: ${summary.countries_imported ?? 0} / ${summary.places_imported ?? 0}`;
                showAlert('success', successMessage);

                await Promise.all([loadLocationCountries(), loadLocationPlaces()]);
            } catch (error) {
                showAlert('error', error.message || @json(__('ui.admin.action_failed')));
            } finally {
                setButtonLoading(locationImportSubmitBtn, false);
            }
        });

        locationSearchForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            try {
                await Promise.all([loadLocationCountries(), loadLocationPlaces()]);
            } catch (error) {
                showAlert('error', error.message || @json(__('ui.admin.action_failed')));
            }
        });

        locationCountryFilterInput.addEventListener('change', async function () {
            try {
                await loadLocationPlaces();
            } catch (error) {
                showAlert('error', error.message || @json(__('ui.admin.action_failed')));
            }
        });

        usersSearchForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            try {
                await loadUsers(1);
            } catch (error) {
                showAlert('error', error.message || @json(__('ui.admin.action_failed')));
            }
        });

        usersPrevBtn.addEventListener('click', async function () {
            if (usersState.page <= 1) return;
            try {
                await loadUsers(usersState.page - 1);
            } catch (error) {
                showAlert('error', error.message || @json(__('ui.admin.action_failed')));
            }
        });

        usersNextBtn.addEventListener('click', async function () {
            if (usersState.page >= usersState.lastPage) return;
            try {
                await loadUsers(usersState.page + 1);
            } catch (error) {
                showAlert('error', error.message || @json(__('ui.admin.action_failed')));
            }
        });

        memorialsSearchForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            try {
                await loadMemorials(1);
            } catch (error) {
                showAlert('error', error.message || @json(__('ui.admin.action_failed')));
            }
        });

        memorialsPrevBtn.addEventListener('click', async function () {
            if (memorialsState.page <= 1) return;
            try {
                await loadMemorials(memorialsState.page - 1);
            } catch (error) {
                showAlert('error', error.message || @json(__('ui.admin.action_failed')));
            }
        });

        memorialsNextBtn.addEventListener('click', async function () {
            if (memorialsState.page >= memorialsState.lastPage) return;
            try {
                await loadMemorials(memorialsState.page + 1);
            } catch (error) {
                showAlert('error', error.message || @json(__('ui.admin.action_failed')));
            }
        });

        heroForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            heroSubmitBtn.disabled = true;
            heroSubmitBtn.classList.add('opacity-70', 'cursor-not-allowed');

            const payload = {
                hero_title: heroForm.querySelector('#hero_title').value.trim(),
                hero_subtitle: heroForm.querySelector('#hero_subtitle').value.trim(),
                hero_image_url: heroForm.querySelector('#hero_image_url').value.trim() || null,
                cta_button_text: heroForm.querySelector('#cta_button_text').value.trim(),
                cta_button_link: heroForm.querySelector('#cta_button_link').value.trim(),
                secondary_button_text: heroForm.querySelector('#secondary_button_text').value.trim(),
                secondary_button_link: heroForm.querySelector('#secondary_button_link').value.trim(),
            };

            try {
                await apiRequest('/api/v1/admin/hero-settings', {
                    method: 'PUT',
                    body: JSON.stringify(payload),
                });
                showAlert('success', @json(__('ui.admin.hero_saved')));
            } catch (error) {
                showAlert('error', error.message || @json(__('ui.admin.hero_save_error')));
            } finally {
                heroSubmitBtn.disabled = false;
                heroSubmitBtn.classList.remove('opacity-70', 'cursor-not-allowed');
            }
        });

        seoRunBtn.addEventListener('click', runSeoHealthCheck);

        logoutBtn.addEventListener('click', async function () {
            try {
                await apiRequest('/api/v1/logout', { method: 'POST' });
            } catch (_error) {
                // Ignore and clear client session regardless.
            } finally {
                localStorage.removeItem('auth_token');
                localStorage.removeItem('user');
                window.location.href = homeUrl;
            }
        });

        async function initialize() {
            activateTab('settings');

            if (!token) {
                window.location.href = loginUrl;
                return;
            }

            try {
                const meResponse = await apiRequest('/api/v1/me');
                currentUser = meResponse?.user || null;
                if (!currentUser) {
                    throw new Error('Authentication failed.');
                }

                if (!isAdminUser(currentUser)) {
                    window.location.href = dashboardUrl;
                    return;
                }

                userEl.classList.remove('hidden');
                userEl.textContent = currentUser.email || '';

                await Promise.all([
                    loadDashboardStats(),
                    loadSettings(),
                    loadUsers(1),
                    loadMemorials(1),
                    loadHeroSettings(),
                    loadLocationCountries(),
                    loadLocationPlaces(),
                ]);
            } catch (error) {
                if (isAuthError(error)) {
                    localStorage.removeItem('auth_token');
                    localStorage.removeItem('user');
                    showAlert('error', error.message || @json(__('ui.admin.load_error')));
                    setTimeout(() => {
                        window.location.href = loginUrl;
                    }, 1000);
                    return;
                }

                showAlert('error', error.message || @json(__('ui.admin.load_error')));
            }
        }

        initialize();
    });
</script>
@endpush

@extends('layouts.app')

@section('title', 'Design System Test')

@section('content')
<main class="flex-1 bg-gradient-hero py-14 md:py-20">
    <div class="container mx-auto px-4 space-y-12">
        <header class="mx-auto max-w-3xl text-center">
            <span class="inline-flex items-center rounded-full border border-accent/30 bg-accent/10 px-4 py-2 text-sm font-medium text-accent">
                Spomenar UI
            </span>
            <h1 class="mt-5 text-4xl font-serif font-bold text-primary md:text-5xl">
                Design System Test
            </h1>
            <p class="mt-4 text-lg text-muted-foreground">
                Pregled boja, tipografije, komponenti i interakcija na desktop i mobilnim ekranima.
            </p>
        </header>

        <section class="rounded-2xl border border-border bg-card p-6 shadow-elegant md:p-8">
            <h2 class="mb-6 text-2xl font-serif font-semibold text-primary">Paleta boja</h2>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                <div class="space-y-2">
                    <div class="h-16 rounded-lg bg-primary"></div>
                    <p class="text-sm font-medium">Primary</p>
                </div>
                <div class="space-y-2">
                    <div class="h-16 rounded-lg bg-secondary"></div>
                    <p class="text-sm font-medium">Secondary</p>
                </div>
                <div class="space-y-2">
                    <div class="h-16 rounded-lg bg-accent"></div>
                    <p class="text-sm font-medium">Accent</p>
                </div>
                <div class="space-y-2">
                    <div class="h-16 rounded-lg bg-muted"></div>
                    <p class="text-sm font-medium">Muted</p>
                </div>
                <div class="space-y-2">
                    <div class="h-16 rounded-lg bg-success"></div>
                    <p class="text-sm font-medium">Success</p>
                </div>
                <div class="space-y-2">
                    <div class="h-16 rounded-lg bg-destructive"></div>
                    <p class="text-sm font-medium">Destructive</p>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-border bg-card p-6 shadow-elegant md:p-8">
            <h2 class="mb-6 text-2xl font-serif font-semibold text-primary">Tipografija</h2>
            <div class="space-y-3">
                <h1 class="text-4xl font-serif font-bold">Naslov H1</h1>
                <h2 class="text-3xl font-serif font-semibold">Naslov H2</h2>
                <h3 class="text-2xl font-serif font-medium">Naslov H3</h3>
                <p class="text-base text-foreground">Osnovni tekst za sadrzaj stranica i forme.</p>
                <p class="text-sm text-muted-foreground">Pomocni tekst i sekundarne informacije.</p>
            </div>
        </section>

        <section class="rounded-2xl border border-border bg-card p-6 shadow-elegant md:p-8">
            <h2 class="mb-6 text-2xl font-serif font-semibold text-primary">Dugmad i kartice</h2>
            <div class="mb-8 flex flex-wrap gap-3">
                <button type="button" class="inline-flex h-11 items-center justify-center rounded-lg bg-gradient-accent px-5 font-medium text-accent-foreground shadow-gold">
                    Primary
                </button>
                <button type="button" class="inline-flex h-11 items-center justify-center rounded-lg border border-border bg-card px-5 font-medium hover:bg-muted transition-colors">
                    Outline
                </button>
                <button type="button" class="inline-flex h-11 items-center justify-center rounded-lg bg-primary px-5 font-medium text-primary-foreground">
                    Dark
                </button>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <article class="rounded-xl border border-border p-5 transition-all duration-300 hover:-translate-y-1 hover:shadow-elegant">
                    <h3 class="font-serif text-xl font-semibold">Card One</h3>
                    <p class="mt-2 text-muted-foreground">Hover efekat i standardni spacing.</p>
                </article>
                <article class="rounded-xl border border-border p-5 transition-all duration-300 hover:-translate-y-1 hover:shadow-elegant">
                    <h3 class="font-serif text-xl font-semibold">Card Two</h3>
                    <p class="mt-2 text-muted-foreground">Isti vizual radi konzistentnosti interfejsa.</p>
                </article>
                <article class="rounded-xl border border-border p-5 transition-all duration-300 hover:-translate-y-1 hover:shadow-elegant">
                    <h3 class="font-serif text-xl font-semibold">Card Three</h3>
                    <p class="mt-2 text-muted-foreground">Responzivno ponasanje na svim breakpointima.</p>
                </article>
            </div>
        </section>

        <section class="rounded-2xl border border-border bg-card p-6 shadow-elegant md:p-8">
            <h2 class="mb-6 text-2xl font-serif font-semibold text-primary">Forma</h2>
            <form class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <label for="test-name" class="block text-sm font-medium">Ime</label>
                    <input id="test-name" type="text" class="h-11 w-full rounded-lg border border-border bg-background px-4 focus:outline-none focus:ring-2 focus:ring-ring" placeholder="Unesite ime">
                </div>
                <div class="space-y-2">
                    <label for="test-email" class="block text-sm font-medium">Email</label>
                    <input id="test-email" type="email" class="h-11 w-full rounded-lg border border-border bg-background px-4 focus:outline-none focus:ring-2 focus:ring-ring" placeholder="ime@primer.com">
                </div>
                <div class="space-y-2 md:col-span-2">
                    <label for="test-message" class="block text-sm font-medium">Poruka</label>
                    <textarea id="test-message" rows="4" class="w-full rounded-lg border border-border bg-background px-4 py-3 focus:outline-none focus:ring-2 focus:ring-ring" placeholder="Test poruka"></textarea>
                </div>
                <div class="md:col-span-2">
                    <button type="button" class="inline-flex h-11 items-center justify-center rounded-lg bg-gradient-accent px-6 font-semibold text-accent-foreground">
                        Potvrdi izgled
                    </button>
                </div>
            </form>
        </section>
    </div>
</main>
@endsection

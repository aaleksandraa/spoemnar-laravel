@extends('layouts.app')

@section('title', trans('legal.privacy.title'))
@section('meta_description', trans('legal.privacy.meta_description'))

@section('content')
<main class="flex-1 bg-gradient-hero py-10 md:py-14">
    <div class="container mx-auto px-4 max-w-4xl space-y-6">
        <section class="rounded-2xl border border-border bg-card shadow-elegant p-6 md:p-8">
            <h1 class="text-3xl md:text-4xl font-serif font-bold text-primary">{{ trans('legal.privacy.title') }}</h1>
            <p class="text-muted-foreground mt-3">{{ trans('legal.privacy.intro') }}</p>
            <p class="text-xs text-muted-foreground mt-4">
                {{ trans('legal.common.last_updated', ['date' => trans('legal.privacy.updated_date')]) }}
            </p>
        </section>

        @php
            $privacySections = trans('legal.privacy.sections');
        @endphp

        @if(is_array($privacySections))
            @foreach($privacySections as $section)
                <article class="rounded-2xl border border-border bg-card shadow-sm p-6 md:p-8">
                    <h2 class="text-2xl font-serif font-semibold text-primary">
                        {{ $section['title'] ?? '' }}
                    </h2>
                    @if(!empty($section['body']))
                        <p class="text-foreground/90 mt-3 leading-relaxed">{{ $section['body'] }}</p>
                    @endif
                    @if(isset($section['items']) && is_array($section['items']) && count($section['items']) > 0)
                        <ul class="mt-4 space-y-2 list-disc pl-5 text-foreground/90">
                            @foreach($section['items'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    @endif
                </article>
            @endforeach
        @endif

        <section class="rounded-2xl border border-border bg-card shadow-sm p-6 md:p-8">
            <p class="text-sm md:text-base text-muted-foreground leading-relaxed">
                {{ trans('legal.privacy.contact_note') }}
            </p>
        </section>
    </div>
</main>
@endsection

@extends('layouts.app')

@section('title', __('ui.footer.privacy'))

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">{{ __('ui.footer.privacy') }}</h1>

    <div class="prose dark:prose-invert max-w-none">
        <p>Privacy Policy content goes here.</p>
    </div>
</div>
@endsection

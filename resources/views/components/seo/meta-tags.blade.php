{{-- Meta Description --}}
<meta name="description" content="{{ $description }}">

{{-- Canonical URL --}}
<link rel="canonical" href="{{ $canonicalUrl }}">

{{-- Open Graph Tags --}}
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:image" content="{{ $image }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:type" content="website">

{{-- Twitter Card Tags --}}
@foreach($twitterTags as $name => $content)
<meta name="{{ $name }}" content="{{ $content }}">
@endforeach

{{-- What link-preview bots (Facebook, WhatsApp, X, Telegram…) read for a shared ad. People never see it: they're redirected. --}}
@php
    $siteName    = gs('site_name');
    $description = \Illuminate\Support\Str::limit(trim(strip_tags($ad->caption ?? '')) ?: 'Shop quality food products from ' . $siteName . '.', 200);
    $image       = $ad->previewImageUrl();
    $imageSize   = $ad->previewImageSize();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $ad->title }} | {{ $siteName }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $shareUrl }}">

    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $ad->title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $shareUrl }}">
    <meta property="og:image" content="{{ $image }}">
    <meta property="og:image:secure_url" content="{{ $image }}">
    <meta property="og:image:alt" content="{{ $ad->title }}">
    @if ($imageSize)
        <meta property="og:image:type" content="{{ $imageSize['mime'] }}">
        <meta property="og:image:width" content="{{ $imageSize['width'] }}">
        <meta property="og:image:height" content="{{ $imageSize['height'] }}">
    @endif
    @if ($ad->isVideo())
        <meta property="og:type" content="video.other">
        <meta property="og:video" content="{{ $ad->mediaUrl() }}">
        <meta property="og:video:secure_url" content="{{ $ad->mediaUrl() }}">
        <meta property="og:video:type" content="video/{{ pathinfo($ad->media, PATHINFO_EXTENSION) === 'mov' ? 'quicktime' : pathinfo($ad->media, PATHINFO_EXTENSION) }}">
    @else
        <meta property="og:type" content="website">
    @endif

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $ad->title }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $image }}">
</head>
<body>
    <h1>{{ $ad->title }}</h1>
    <p>{{ $description }}</p>
    <p><img src="{{ $image }}" alt="{{ $ad->title }}" width="600"></p>
    <p><a href="{{ $ad->destinationUrl() }}">Visit the {{ $siteName }} shop</a></p>
</body>
</html>

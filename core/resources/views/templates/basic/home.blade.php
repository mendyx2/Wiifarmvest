@extends($activeTemplate . 'layouts.frontend')
@php
    $bannerSectionContent = getContent('banner.content', true);
@endphp
@section('content')
    @include($activeTemplate . 'sections.banner')
    
@endsection

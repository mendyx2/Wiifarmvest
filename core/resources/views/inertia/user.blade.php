{{--
    Root view for React (Inertia) pages in the user area.

    It extends the existing Blade dashboard layout, so the sidebar, header,
    notifications and styles stay exactly as they are. Only the page body
    (the @inertia div) is rendered by React.
--}}
@extends($activeTemplate . 'layouts.master')

@push('style')
    @viteReactRefresh
    @vite('resources/js/app.tsx')
@endpush

@section('content')
    @inertia
@endsection

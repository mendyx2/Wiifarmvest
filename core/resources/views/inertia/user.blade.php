{{--
    Root view for React (Inertia) pages in the user area: a bare HTML page.

    The sidebar, top bar and footer are React now (resources/js/Layouts/DashboardLayout.tsx),
    so this file only loads the shared stylesheets and the React app. It
    replaces layouts.master + partials.dashboard*, which the remaining Blade
    pages (payment gateways) still use. See notes Step 78.

    Left out on purpose, compared with layouts/app.blade.php: select2, owl,
    magnific-popup, isotope and main.js (no React page uses them), and the
    jQuery block that patched form labels and table cells once on page load
    (it ran before React had drawn anything, so it never touched React pages).
--}}
<!doctype html>
<html lang="{{ config('app.locale') }}" itemscope itemtype="http://schema.org/WebPage">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title inertia>{{ gs()->siteName(__($pageTitle ?? '')) }}</title>
    @include('partials.seo')
    <meta name="theme-color" content="#135D26">
    <link href="{{ asset('assets/global/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/global/css/all.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/global/css/line-awesome.min.css') }}" rel="stylesheet">
    <link href="{{ asset($activeTemplateTrue . 'css/main.css') }}" rel="stylesheet">
    <link href="{{ asset($activeTemplateTrue . 'css/custom.css') }}" rel="stylesheet">
    {{-- Fonts for the login / verification pages. Plain <link>s, not CSS @import: a blocked
         @import makes the browser fail the whole stylesheet, and the page with it. --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Courier+Prime&display=swap" rel="stylesheet">
    @viteReactRefresh
    @vite(['resources/css/dashboard.css', 'resources/js/app.tsx'])
    <link href="{{ asset($activeTemplateTrue . 'css/color.php') }}?color={{ gs('base_color') }}&secondColor={{ gs('secondary_color') }}" rel="stylesheet">
</head>
<body>
    @php echo loadExtension('google-analytics') @endphp

    @inertia

    {{-- The live-chat extension the old login layout (layouts/app1) loaded. --}}
    @if (request()->routeIs('user.login'))
        @php echo loadExtension('tawk-chat') @endphp
    @endif

    {{-- window.notify(), and the toasts flashed before this full page load --}}
    @include('partials.notify')

    @if (gs('pn'))
        <script src="{{ asset('assets/global/js/jquery-3.7.1.min.js') }}"></script>
        @include('partials.push_script')
    @endif
</body>
</html>

{{--
    Root view for React (Inertia) pages in the admin panel. The sidebar and
    top bar are React (resources/js/Layouts/AdminLayout.tsx), fed by the
    shared "adminLayout" prop (App\Support\AdminLayout).

    The same stylesheets as admin/layouts/master.blade.php. Left out:
    jQuery, Bootstrap's JS, bootstrap-toggle, select2, nicEdit, cuModal and
    admin/js/app.js; React pages do those jobs themselves. See notes Step 93.
--}}
<!doctype html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title inertia>{{ gs()->siteName($pageTitle ?? '') }}</title>
    <link rel="shortcut icon" type="image/png" href="{{ siteFavicon() }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/global/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/global/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/global/css/line-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/app.css') }}">
    @viteReactRefresh
    @vite(['resources/js/app.tsx'])
</head>
<body>
    @inertia

    {{-- window.notify(), and the toasts flashed before this full page load --}}
    @include('partials.notify')
</body>
</html>

<?php

namespace App\Http\Middleware;

use App\Support\DashboardLayout;
use App\Support\PublicLayout;
use App\Support\Vite;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The Blade view that wraps every Inertia page on its first (full) load:
     * a bare HTML page with the stylesheets. The sidebar and header are React
     * (Layouts/DashboardLayout.tsx), fed by the shared "layout" prop below.
     *
     * @var string
     */
    protected $rootView = 'inertia.user';

    /** Pages drawn by Layouts/AuthLayout.tsx instead of the dashboard. */
    private const GUEST_PAGES = ['user.login', 'user.register', 'user.password.*', 'user.authorization'];

    /**
     * Asset version: when the build changes, Inertia forces a full reload so
     * users never run stale JavaScript.
     */
    public function version(Request $request): ?string
    {
        $manifest = Vite::buildPath('manifest.json');

        return file_exists($manifest) ? hash_file('xxh128', $manifest) : null;
    }

    /**
     * Props available to every React page via usePage().props.
     * Everything here is embedded in the HTML, so only expose what the
     * browser genuinely needs.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id'       => $user->id,
                    'username' => $user->username,
                    'fullname' => $user->fullname,
                ] : null,
            ],
            'siteName'     => gs('site_name'),
            'csrfToken'    => csrf_token(),
            'locale'       => app()->getLocale(),
            'translations' => fn () => (object) $this->translations(), // object, so an empty file is {} not []
            'notify'       => fn () => $request->session()->get('notify', []),
            // Sidebar, top bar and footer. A closure, so partial reloads
            // (router.reload({ only: [...] })) skip its queries.
            // Not on the login / password / verification pages: they have their own
            // layout, and an unverified member has no dashboard yet.
            'layout'       => fn () => $request->routeIs([...self::GUEST_PAGES, ...PublicLayout::ROUTES]) ? null : DashboardLayout::for($request),
            // Shop header and footer, for the public pages (Layouts/PublicLayout.tsx).
            'publicLayout' => fn () => $request->routeIs(PublicLayout::ROUTES) ? PublicLayout::for($request) : null,
        ]);
    }

    /**
     * The same JSON file Laravel's __() / @lang() read, so the admin
     * Language manager translates Blade and React pages in one place.
     *
     * @return array<string, string>
     */
    protected function translations(): array
    {
        $file = lang_path(app()->getLocale() . '.json');

        if (!file_exists($file)) {
            return [];
        }

        return json_decode(file_get_contents($file), true) ?: [];
    }
}

<?php

namespace App\Support;

use App\Models\Category;
use App\Services\ShopCartService;
use Illuminate\Http\Request;

/**
 * The header and footer around the public pages (the shop and the terms
 * page), drawn by resources/js/Layouts/PublicLayout.tsx. It replaces the PHP
 * that sat at the top of partials/shop_header.blade.php and
 * partials/shop_footer.blade.php.
 */
class PublicLayout
{
    /** Routes drawn inside this layout (HandleInertiaRequests shares the data only there). */
    public const ROUTES = ['shop.*', 'agreement'];

    public static function for(Request $request): array
    {
        $user       = $request->user();
        $categories = Category::active()->hasActiveProduct()->get(['id', 'name']);

        return [
            'logo'     => siteLogo('dark'),
            'user'     => $user ? ['firstname' => (string) $user->firstname, 'fullname' => (string) $user->fullname] : null,
            'cartCount' => app(ShopCartService::class)->count(),
            'categories' => $categories->map(fn (Category $c) => [
                'id'   => $c->id,
                'name' => $c->name,
                'url'  => route('shop.index', ['category' => $c->id]),
            ])->values(),
            // Only the shop list highlights a category and keeps the search text.
            'onList'   => $request->routeIs('shop.index'),
            'category' => $request->routeIs('shop.index') && $request->filled('category') ? $request->integer('category') : null,
            'search'   => $request->routeIs('shop.index') ? (string) $request->query('q', '') : '',
            'socials'  => getContent('social_icon.element')->map(fn ($s) => [
                'url'   => (string) @$s->data_values->url,
                'title' => (string) @$s->data_values->title,
                'icon'  => (string) @$s->data_values->social_icon,
            ])->values(),
            'email' => (string) gs('email_from'),
            'year'  => (int) now()->year,
            'links' => [
                'shop'      => route('shop.index'),
                'cart'      => route('shop.cart'),
                'cartAdd'   => route('shop.cart.add'),
                'terms'     => route('agreement'),
                'login'     => route('user.login'),
                'register'  => route('user.register'),
                'dashboard' => route('user.home'),
                'affiliate' => route('user.affiliate.dashboard'),
                'logout'    => route('user.logout'),
            ],
        ];
    }
}

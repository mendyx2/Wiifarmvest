<?php

namespace App\Support;

use App\Constants\Status;
use App\Http\Middleware\AdminAccessMiddleware;
use App\Models\Admin;
use App\Models\AdminNotification;
use App\Models\Deposit;
use App\Models\StockTransfer;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Http\Request;

/**
 * The admin panel's sidebar and top bar, drawn by
 * resources/js/Layouts/AdminLayout.tsx. Built from the same
 * views/admin/partials/sidenav.json as the Blade sidebar
 * (partials/sidenav.blade.php), with the same rules:
 *
 *  - a link is shown only when AdminAccessMiddleware would let this admin
 *    open it, and an empty dropdown is dropped;
 *  - a section header moves to the next visible entry when its own entry
 *    is hidden;
 *  - "roles" on a submenu entry limits it to those roles;
 *  - counters are the same queries the Blade view composer runs.
 *
 * Most admin pages are still Blade. Links to them do a full page load;
 * only the routes in REACT_ROUTES are opened as Inertia visits.
 */
class AdminLayout
{
    /** Admin pages that are React (Pages/Admin/*.tsx). Grows as pages move. */
    public const REACT_ROUTES = [
        'admin.profile',
        'admin.password',
        'admin.notifications',
        // 7b: reports and logs
        'admin.report.transaction',
        'admin.report.referral.commission',
        'admin.report.stageOut.commission',
        'admin.report.binary.commission',
        'admin.report.login.history',
        'admin.report.login.ipHistory',
        'admin.report.notification.history',
        'admin.report.invest',
        'admin.report.bvLog',
        'admin.users.notification.log',
        'admin.report.financial',
        'admin.pv-logs.index',
        'admin.system.action.log',
        // 7c: users
        'admin.users.all',
        'admin.users.active',
        'admin.users.banned',
        'admin.users.email.verified',
        'admin.users.email.unverified',
        'admin.users.mobile.verified',
        'admin.users.mobile.unverified',
        'admin.users.kyc.unverified',
        'admin.users.kyc.pending',
        'admin.users.with.balance',
        'admin.users.paid',
        'admin.users.free',
        'admin.users.stages',
        'admin.users.kyc.details',
        'admin.users.sponsor.history',
        'admin.users.referral',
        'admin.users.detail',
        'admin.users.notification.single',
        'admin.users.notification.all',
        'admin.users.other.tree',
        'admin.users.single.tree',
        // 7d-1: shop
        'admin.product.index',
        'admin.product.create',
        'admin.product.edit',
        'admin.category.index',
        'admin.state.index',
        'admin.order.index',
        'admin.order.show',
        'admin.sales.index',
        'admin.sales.show',
        'admin.repurchase-award.index',
        'admin.repurchase-award.qualified',
        // 7d-2: stockists
        'admin.stockist.dashboard',
        'admin.stockist.index',
        'admin.stockist.activeStockist',
        'admin.stockist.inactiveStockist',
        'admin.stockist.detail',
        'admin.stockist.details',
        'admin.stockist.wallet-page',
        'admin.stockist.activation-page',
        'admin.stockist.report',
        'admin.stockist.report.show',
        'admin.stockist.transfers.index',
        'admin.stockist.transfers.show',
        'admin.stockist-orders.index',
        'admin.stockist-orders.show',
        'admin.sorder.index',
        'admin.supply.index',
        // 7d-3: affiliate, ads, welcome pack
        'admin.affiliate.dashboard',
        'admin.affiliate.orders',
        'admin.affiliate.order.show',
        'admin.affiliate.affiliates',
        'admin.affiliate.funnel',
        'admin.affiliate.audit',
        'admin.affiliate.settings',
        'admin.ads.index',
        'admin.ads.create',
        'admin.ads.edit',
        'admin.welcome-pack.index',
        // 7e-1: deposits and withdrawals
        'admin.deposit.list',
        'admin.deposit.pending',
        'admin.deposit.rejected',
        'admin.deposit.approved',
        'admin.deposit.successful',
        'admin.deposit.initiated',
        'admin.deposit.details',
        'admin.withdraw.data.pending',
        'admin.withdraw.data.approved',
        'admin.withdraw.data.rejected',
        'admin.withdraw.data.all',
        'admin.withdraw.data.details',
        // 7e-2: pins and the Stage Twice report
        'admin.pin.index',
        'admin.pin.used',
        'admin.pin.unused',
        'admin.pin.generate',
        'admin.pin.user',
        'admin.users.generate.pin',
        'admin.users.used.pin',
        'admin.report.stage.twice',
        // 7e-3: savings and loans
        'admin.savings.settings',
        'admin.savings.index',
        'admin.savings.show',
        'admin.savings.cycles',
        'admin.loans.index',
        'admin.loans.pending',
        'admin.loans.show',
        'admin.loans.products',
        // 7f-1: settings
        'admin.setting.system',
        'admin.setting.general',
        'admin.setting.system.configuration',
        'admin.setting.logo.icon',
        'admin.setting.custom.css',
        'admin.setting.sitemap',
        'admin.setting.robot',
        'admin.setting.cookie',
        'admin.maintenance.mode',
        'admin.setting.notice',
    ];

    public static function for(Request $request): ?array
    {
        /** @var Admin|null $admin */
        $admin = auth('admin')->user();
        if (!$admin) {
            return null;
        }

        $unread = AdminNotification::where('is_read', Status::NO);

        return [
            'logo'  => siteLogo('dark'),
            'admin' => [
                'username' => (string) $admin->username,
                'image'    => getImage(getFilePath('adminProfile') . '/' . $admin->image, getFileSize('adminProfile')),
            ],
            'nav'           => self::nav($admin, $request),
            'notifications' => [
                'count' => (clone $unread)->count(),
                'items' => (clone $unread)->orderByDesc('id')->take(10)->get()->map(fn (AdminNotification $n) => [
                    'id'    => $n->id,
                    'title' => __($n->title),
                    'time'  => diffForHumans($n->created_at),
                    'url'   => route('admin.notification.read', $n->id),
                ])->values(),
            ],
            'version' => ['name' => __(systemDetails()['name']), 'version' => systemDetails()['version']],
            'links'   => [
                'dashboard'     => route('admin.dashboard'),
                'website'       => route('home'),
                'notifications' => route('admin.notifications'),
                'settings'      => route('admin.setting.system'),
                'profile'       => route('admin.profile'),
                'password'      => route('admin.password'),
                'logout'        => route('admin.logout'),
                'search'        => route('admin.search.index'),
            ],
            // Whether these top-bar links are React pages (Inertia visit) or Blade (full load).
            'react' => [
                'notifications' => true,
                'profile'       => true,
                'settings'      => self::isReact('admin.setting.system'),
                'dashboard'     => self::isReact('admin.dashboard'),
            ],
        ];
    }

    public static function isReact(string $route): bool
    {
        return in_array($route, self::REACT_ROUTES, true);
    }

    /**
     * Search box entries: every sidebar link and every System Setting card
     * this admin may open. The Blade search listed them all, including pages
     * that then answered 403.
     *
     * @return list<array{title: string, parent: string, url: string, keywords: list<string>, react: bool}>
     */
    public static function searchIndex(Admin $admin): array
    {
        $items = [];
        $add = function (object $entry, string $parent) use (&$items, $admin) {
            $route = (string) @$entry->route_name;
            if (!$route || !AdminAccessMiddleware::allows($admin, $route) || !self::rolesAllow($admin, $entry)) {
                return;
            }
            $items[] = [
                'title'    => __((string) $entry->title),
                'parent'   => $parent,
                'url'      => self::url($entry),
                'keywords' => array_values(array_map('strval', (array) (@$entry->keyword ?? []))),
                'react'    => self::isReact($route),
            ];
        };

        foreach (self::json('views/admin/partials/sidenav.json') as $entry) {
            if (@$entry->submenu) {
                foreach ($entry->submenu as $sub) {
                    $add($sub, __((string) $entry->title));
                }
            } else {
                $add($entry, __('Main Menu'));
            }
        }
        foreach (self::json('views/admin/setting/settings.json') as $entry) {
            $add($entry, __('System Setting'));
        }

        return $items;
    }

    /** The sidebar, already filtered for this admin. */
    private static function nav(Admin $admin, Request $request): array
    {
        $counts  = self::counters();
        $nav     = [];
        $pending = null;

        foreach (self::json('views/admin/partials/sidenav.json') as $entry) {
            if (@$entry->header) {
                $pending = __((string) $entry->header);
            }

            if (@$entry->submenu) {
                $items = [];
                foreach ($entry->submenu as $sub) {
                    if (!AdminAccessMiddleware::allows($admin, (string) @$sub->route_name) || !self::rolesAllow($admin, $sub)) {
                        continue;
                    }
                    $items[] = self::link($sub, $request, $counts, 'las la-dot-circle');
                }
                if (!$items) {
                    continue;
                }
                $item = [
                    'type'   => 'group',
                    'id'     => (string) (@$entry->menu_active ? json_encode($entry->menu_active) : $entry->title),
                    'title'  => __((string) $entry->title),
                    'icon'   => (string) @$entry->icon,
                    'active' => self::active(@$entry->menu_active, $request),
                    // The Blade sidebar showed a "!" when any of these counters was above 0.
                    'alert'  => collect((array) (@$entry->counters ?? []))->contains(fn ($c) => ($counts[$c] ?? 0) > 0),
                    'items'  => $items,
                ];
            } else {
                if (!AdminAccessMiddleware::allows($admin, (string) @$entry->route_name)) {
                    continue;
                }
                $item = ['type' => 'link'] + self::link($entry, $request, $counts, (string) @$entry->icon);
            }

            if ($pending) {
                $nav[]   = ['type' => 'header', 'title' => $pending];
                $pending = null;
            }
            $nav[] = $item;
        }

        return $nav;
    }

    private static function link(object $entry, Request $request, array $counts, string $icon): array
    {
        $counter = @$entry->counter ? (int) ($counts[$entry->counter] ?? 0) : 0;

        return [
            'title'   => __((string) $entry->title),
            'icon'    => $icon,
            'url'     => self::url($entry),
            'active'  => self::active(@$entry->menu_active, $request),
            'counter' => $counter ?: null,
            'reload'  => !self::isReact((string) $entry->route_name),
        ];
    }

    /** route() with the entry's params, the way the Blade sidebar passed them. */
    private static function url(object $entry): string
    {
        $params = @$entry->params ? array_values((array) $entry->params) : [];

        return route((string) $entry->route_name, $params);
    }

    private static function active($patterns, Request $request): bool
    {
        return $patterns ? $request->routeIs(...(array) $patterns) : false;
    }

    private static function rolesAllow(Admin $admin, object $entry): bool
    {
        if (empty($entry->roles)) {
            return true;
        }
        foreach ($entry->roles as $role) {
            if ($admin->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /** Menu counters; the Blade sidebar's view composer (AppServiceProvider) uses them too. */
    public static function counters(): array
    {
        return [
            'bannedUsersCount'           => User::banned()->count(),
            'emailUnverifiedUsersCount'  => User::emailUnverified()->count(),
            'mobileUnverifiedUsersCount' => User::mobileUnverified()->count(),
            'kycUnverifiedUsersCount'    => User::kycUnverified()->count(),
            'kycPendingUsersCount'       => User::kycPending()->count(),
            'pendingTicketCount'         => SupportTicket::whereIn('status', [Status::TICKET_OPEN, Status::TICKET_REPLY])->count(),
            'pendingDepositsCount'       => Deposit::pending()->count(),
            'pendingWithdrawCount'       => Withdrawal::pending()->count(),
            'pendingStockTransfersCount' => StockTransfer::where('status', StockTransfer::STATUS_PENDING)->count(),
        ];
    }

    /** @return iterable<object> */
    private static function json(string $file): iterable
    {
        return (array) json_decode(file_get_contents(resource_path($file)));
    }
}

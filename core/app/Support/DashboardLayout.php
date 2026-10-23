<?php

namespace App\Support;

use App\Constants\Status;
use App\Models\Cart;
use App\Models\GuarantorRequest;
use App\Models\NotificationLog;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\UserAward;
use App\Models\Useridcard;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Everything the React dashboard layout (resources/js/Layouts/DashboardLayout.tsx)
 * shows around a page: the sidebar menu, the top bar and the footer.
 *
 * It replaces the PHP that sat at the top of partials/dashboard.blade.php,
 * dashboard_stockist_main.blade.php and dashboard_affiliate.blade.php. The
 * menu is built here, with route() URLs and request()->routeIs() for the
 * "active" highlight, exactly like the Blade menuActive() helper did. It is
 * sent again with every Inertia visit, so the highlight and the badges are
 * always current.
 *
 * The Blade partials still exist for the pages that are not React yet
 * (payment gateway pages). Keep the two menus in step until those go.
 */
class DashboardLayout
{
    /**
     * Menu links whose page is still Blade. Inertia cannot show a Blade page
     * inside the React layout, so the browser loads these with a full page
     * load. Remove a name from this list when its page moves to React.
     */
    private const BLADE_ROUTES = [
        'user.home',
        'user.affiliate.dashboard',
        'user.affiliate.orders',
        'user.affiliate.ads',
        'user.distributor.index',
        'user.bonus.transfer.index',
        'user.logout',
    ];

    public function __construct(private Request $request, private User $user)
    {
    }

    public static function for(Request $request): ?array
    {
        $user = $request->user();

        return $user instanceof User ? (new self($request, $user))->toArray() : null;
    }

    public function toArray(): array
    {
        $user = $this->user;
        $idCard = Useridcard::where('user_id', $user->id)->first();
        $unread = NotificationLog::where('user_id', $user->id)->where('user_read', false)->count();
        $cart = Cart::where('user_id', $user->id)->first();
        $hour = (int) now()->format('H');

        return [
            'variant' => $this->variant(),
            'logo'    => siteLogo('dark'),
            'homeUrl'    => route('user.home'),
            'homeReload' => in_array('user.home', self::BLADE_ROUTES, true),
            'user'    => [
                'fullname'  => $user->fullname,
                'firstname' => $user->firstname,
                'email'     => $user->email,
                'initials'  => strtoupper(substr((string) $user->firstname, 0, 1) . substr((string) $user->lastname, 0, 1)),
                'idLabel'   => $idCard ? 'WCS' . $idCard->id : '@ ' . $user->username,
            ],
            'greeting' => __($hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening')),
            'today'    => now()->format('l, F j, Y'),
            'counts'   => [
                'cart'          => $cart ? (int) $cart->items()->sum('quantity') : 0,
                'notifications' => $unread,
            ],
            'links' => [
                'deposit'       => route('user.deposit.index'),
                'cart'          => route('user.cart.index'),
                'notifications' => route('user.notifications'),
                'profile'       => route('user.profile.setting'),
                'password'      => route('user.change.password'),
                'twofactor'     => route('user.twofactor'),
                'logout'        => route('user.logout'),
            ],
            'nav'     => $this->nav($unread),
            'socials' => getContent('social_icon.element')->map(fn ($social) => [
                'url'   => (string) @$social->data_values->url,
                'title' => (string) @$social->data_values->title,
                // Icon markup (<i class="...">) entered by the admin in Frontend Manager.
                'icon'  => (string) @$social->data_values->social_icon,
            ])->values(),
        ];
    }

    /** Which sidebar to show. The same rules as layouts/master.blade.php. */
    private function variant(): string
    {
        if ($this->request->routeIs('user.affiliate.*')) {
            return 'affiliate';
        }

        // Find Stockist is a member Shop page that happens to share the prefix.
        if ($this->request->routeIs('user.stockist.*') && !$this->request->routeIs('user.stockist.find')) {
            return 'stockist';
        }

        return 'member';
    }

    private function nav(int $unread): array
    {
        return match ($this->variant()) {
            'stockist'  => $this->stockistNav($unread),
            'affiliate' => $this->affiliateNav($unread),
            default     => $this->memberNav($unread),
        };
    }

    private function memberNav(int $unread): array
    {
        $user = $this->user;
        $isNetwork = (int) $user->section === 1;
        $project = $user->project;

        $main = [
            $this->link('Dashboard', 'la-home', 'user.home'),
            $this->link('Investments', 'la-chart-line', 'user.investment.portfolio'),
            $this->link('My Referrals', 'la-users', 'user.my.ref'),
            $this->link('Affiliate Program', 'la-link', 'user.affiliate.dashboard'),
            $this->link('My Project', 'la-project-diagram', 'user.project.index', 'user.project.*',
                $project ? ['text' => Str::limit($project->title, 8), 'tone' => 'project', 'color' => $project->color, 'title' => $project->title] : null),
        ];

        $nav = [$this->label('Main'), $this->links($main)];

        if ($isNetwork) {
            $nav[] = $this->group('tree', 'User Tree', 'la-sitemap', [
                $this->link('Add Distributor', 'la-user-plus', 'user.distributor.index', 'user.distributor*'),
                $this->link('Genealogy', 'la-sitemap', 'user.my.tree'),
                $this->link('Binary List', 'la-list-ul', 'user.binary.list'),
                $this->link('Binary Summary', 'la-balance-scale', 'user.binary.summery'),
                $this->link('PV Log', 'la-list-alt', 'user.pv.log'),
            ]);
        }

        if ($user->stockist()->exists()) {
            $nav[] = $this->links([$this->link('Stockist Dashboard', 'la-store-alt', 'user.stockist.dashboard')]);
        }

        $nav[] = $this->group('shop', 'Shop', 'la-shopping-bag', [
            $this->link('Browse Shop', 'la-store', 'user.products', ['user.products', 'user.product.*']),
            $this->link('Orders', 'la-box', 'user.orders.index', 'user.orders*'),
            $this->link('Find Stockist', 'la-map-marker', 'user.stockist.find'),
        ]);

        $nav[] = $this->group('finance', 'Finance', 'la-wallet', [
            $this->link('Deposit', 'la-arrow-circle-down', 'user.deposit.index'),
            $this->link('Withdraw', 'la-arrow-circle-up', 'user.withdraw.history', 'user.withdraw*'),
            $this->link('Transactions', 'la-exchange-alt', 'user.transactions'),
            $this->link('Deposit History', 'la-history', 'user.deposit.history', ['user.deposit*', '!user.deposit.index']),
            $this->link('Bonus Transfer', 'la-random', 'user.bonus.transfer.index', 'user.bonus.transfer*'),
        ]);

        if ($isNetwork) {
            $pendingAwards = UserAward::where('user_id', $user->id)->where('status', 0)->count();
            $awards = [
                $this->link('My Awards', 'la-trophy', 'user.awards', null, $pendingAwards ? ['text' => (string) $pendingAwards, 'tone' => 'award'] : null),
                $this->link('Repurchase Award', 'la-medal', 'user.repurchase.award'),
            ];

            if ($user->isAcb()) {
                $awards[] = $this->link('ACB Bonus', 'la-star', 'user.acb', null,
                    (float) $user->acb > 0 ? ['text' => showAmount($user->acb), 'tone' => 'acb'] : null);
            }

            $nav[] = $this->group('awards', 'Achievements', 'la-trophy', $awards,
                $pendingAwards ? ['text' => (string) $pendingAwards, 'tone' => 'award'] : null);
        }

        $nav[] = $this->accountGroup($unread, full: true);

        return $nav;
    }

    private function stockistNav(int $unread): array
    {
        $stockist = $this->user->stockist;
        $isMega = (bool) $stockist?->isMega();

        $links = [
            $this->link('Back to Main Menu', 'la-home', 'user.home', '!'),
            $this->link('Dashboard', 'la-store-alt', 'user.stockist.dashboard'),
            $this->link('Point of Sale', 'la-cash-register', 'user.stockist.pos.index', 'user.stockist.pos.*', ['text' => 'POS', 'tone' => 'pos']),
            $this->link('Profile', 'la-user-circle', 'user.stockist.profile'),
            $this->link('Redemption History', 'la-history', 'user.stockist.history'),
            $this->link('Redeem Affiliate Products', 'la-link', 'user.stockist.affiliate.redeem', 'user.stockist.affiliate.*'),
            $this->link('Redemption Report', 'la-clipboard-list', 'user.stockist.redemptions'),
            $this->link('Inventory', 'la-boxes', 'user.stockist.inventory.dashboard'),
        ];

        // A Mega orders stock from the company and sells to the Minis in its
        // state; a Mini restocks only by buying from a Mega.
        if ($isMega) {
            $pending = StockTransfer::where('seller_stockist_id', $stockist->id)->where('status', StockTransfer::STATUS_PENDING)->count();
            $links[] = $this->link('My Orders', 'la-shopping-basket', 'user.stockist.inventory.orders', [
                'user.stockist.inventory.orders', 'user.stockist.inventory.order.details',
                'user.stockist.inventory.catalog', 'user.stockist.inventory.checkout',
            ]);
            $links[] = $this->link('Stock Requests', 'la-exchange-alt', 'user.stockist.transfers.index', 'user.stockist.transfers.*',
                $pending ? ['text' => $pending > 9 ? '9+' : (string) $pending, 'tone' => 'danger'] : null);
        } elseif ($stockist) {
            $links[] = $this->link('Buy from Mega', 'la-truck-loading', 'user.stockist.transfers.create');
            $links[] = $this->link('My Orders', 'la-shopping-basket', 'user.stockist.transfers.index', [
                'user.stockist.transfers.index', 'user.stockist.transfers.show',
                'user.stockist.inventory.orders', 'user.stockist.inventory.order.details',
            ]);
        }

        return [
            $this->label('Main'),
            $this->links($links),
            $this->label('Account'),
            $this->links([$this->logoutLink()]),
            $this->accountGroup($unread, full: false),
        ];
    }

    private function affiliateNav(int $unread): array
    {
        return [
            $this->links([$this->link('Back to Main Menu', 'la-home', 'user.home', '!')]),
            $this->label('Affiliate'),
            $this->links([
                $this->link('Dashboard', 'la-tachometer-alt', 'user.affiliate.dashboard'),
                $this->link('My Orders', 'la-box', 'user.affiliate.orders'),
                $this->link('Ad Center', 'la-bullhorn', 'user.affiliate.ads', 'user.affiliate.ads*'),
                $this->link('Bonus Transfer', 'la-exchange-alt', 'user.bonus.transfer.index', 'user.bonus.transfer.*'),
            ]),
            $this->label('Account'),
            $this->links([$this->logoutLink()]),
            $this->accountGroup($unread, full: true),
        ];
    }

    /** The "Account" group. The stockist sidebar only ever had two of its links. */
    private function accountGroup(int $unread, bool $full): array
    {
        $user = $this->user;
        $unreadBadge = $unread ? ['text' => $unread > 9 ? '9+' : (string) $unread, 'tone' => 'danger'] : null;
        $guarantors = GuarantorRequest::where('guarantor_id', $user->id)->where('status', 'pending')->count();
        $guarantor = $this->link('Guarantor Requests', 'la-handshake', 'user.guarantor.requests', null,
            $guarantors ? ['text' => (string) $guarantors, 'tone' => 'danger'] : null);
        $twofactor = $this->link('2FA Security', 'la-shield-alt', 'user.twofactor');

        if (!$full) {
            return $this->group('account', 'Account', 'la-user-circle', [$guarantor, $twofactor], $unreadBadge);
        }

        $kv = (int) $user->kv;
        $kycRoute = $kv === Status::KYC_UNVERIFIED && !$user->kyc_data && !$user->nin ? 'user.kyc.form' : 'user.kyc.data';
        $kycBadge = match ($kv) {
            Status::KYC_VERIFIED => ['text' => __('Verified'), 'tone' => 'verified'],
            Status::KYC_PENDING  => ['text' => __('Pending'), 'tone' => 'pending'],
            default              => ['text' => '!', 'tone' => 'danger'],
        };

        return $this->group('account', 'Account', 'la-user-circle', [
            $this->link('Notifications', 'la-bell', 'user.notifications', null, $unreadBadge),
            $this->link('Profile', 'la-user-cog', 'user.profile.setting'),
            $this->link('Change password', 'la-user-cog', 'user.change.password'),
            $this->link('KYC Verification', 'la-id-card', $kycRoute, ['user.kyc.form', 'user.kyc.data'], $kycBadge),
            $guarantor,
            $twofactor,
            $this->logoutLink(),
        ], $unreadBadge);
    }

    private function logoutLink(): array
    {
        return ['logout' => true] + $this->link('Sign Out', 'la-sign-out-alt', 'user.logout', '!');
    }

    private function label(string $text): array
    {
        return ['type' => 'label', 'text' => __($text)];
    }

    private function links(array $items): array
    {
        return ['type' => 'links', 'items' => $items];
    }

    /** A collapsible group. It starts open when one of its links is the current page. */
    private function group(string $id, string $label, string $icon, array $items, ?array $badge = null): array
    {
        return [
            'type'   => 'group',
            'id'     => $id,
            'label'  => __($label),
            'icon'   => $icon,
            'badge'  => $badge,
            'active' => collect($items)->contains('active', true),
            'items'  => $items,
        ];
    }

    /**
     * One menu link.
     *
     * @param string|array|null $activeOn Route patterns that highlight this link
     *        (default: the link's own route). '!' = never highlighted; a
     *        pattern starting with '!' excludes that route.
     */
    private function link(string $label, string $icon, string $route, string|array|null $activeOn = null, ?array $badge = null): array
    {
        return [
            'label'  => __($label),
            'icon'   => $icon,
            'url'    => route($route),
            'active' => $this->isActive($activeOn ?? $route),
            'badge'  => $badge,
            'reload' => in_array($route, self::BLADE_ROUTES, true),
        ];
    }

    private function isActive(string|array $patterns): bool
    {
        $patterns = (array) $patterns;
        $include = array_values(array_filter($patterns, fn ($p) => !str_starts_with($p, '!')));
        $exclude = array_map(fn ($p) => substr($p, 1), array_filter($patterns, fn ($p) => str_starts_with($p, '!') && $p !== '!'));

        return $include
            && $this->request->routeIs(...$include)
            && !($exclude && $this->request->routeIs(...$exclude));
    }
}

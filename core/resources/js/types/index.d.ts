/** A legacy flash message: ['success' | 'error' | 'warning' | 'info', message] */
export type NotifyMessage = [string, string];

export interface AuthUser {
    id: number;
    username: string;
    fullname: string;
}

/** A small pill next to a menu link (App\Support\DashboardLayout). */
export interface NavBadge {
    text: string;
    tone: 'danger' | 'project' | 'award' | 'acb' | 'verified' | 'pending' | 'pos';
    /** Project colour, for tone "project" */
    color?: string;
    title?: string;
}

export interface NavLink {
    label: string;
    icon: string;
    url: string;
    active: boolean;
    badge: NavBadge | null;
    /** The page is still Blade: open it with a full page load, not an Inertia visit. */
    reload: boolean;
    logout?: boolean;
}

export type NavEntry =
    | { type: 'label'; text: string }
    | { type: 'links'; items: NavLink[] }
    | { type: 'group'; id: string; label: string; icon: string; badge: NavBadge | null; active: boolean; items: NavLink[] };

/** Everything the dashboard layout shows around a page. */
export interface DashboardChrome {
    variant: 'member' | 'stockist' | 'affiliate';
    logo: string;
    homeUrl: string;
    /** The dashboard page is still Blade */
    homeReload: boolean;
    user: { fullname: string; firstname: string; email: string; initials: string; idLabel: string };
    greeting: string;
    today: string;
    counts: { cart: number; notifications: number };
    links: Record<'deposit' | 'cart' | 'notifications' | 'profile' | 'password' | 'twofactor' | 'logout', string>;
    nav: NavEntry[];
    socials: { url: string; title: string; icon: string }[];
}

/** Header and footer of the public pages (App\Support\PublicLayout). */
export interface PublicChrome {
    logo: string;
    user: { firstname: string; fullname: string } | null;
    cartCount: number;
    categories: { id: number; name: string; url: string }[];
    /** The page is the shop list */
    onList: boolean;
    /** Category the shop list is filtered by, for the highlight */
    category: number | null;
    /** Search text on the shop list */
    search: string;
    socials: { url: string; title: string; icon: string }[];
    email: string;
    year: number;
    links: Record<'shop' | 'cart' | 'cartAdd' | 'terms' | 'login' | 'register' | 'dashboard' | 'affiliate' | 'logout', string>;
}

/** Props shared with every page by App\Http\Middleware\HandleInertiaRequests. */
export interface SharedProps {
    auth: { user: AuthUser | null };
    siteName: string;
    csrfToken: string;
    locale: string;
    translations: Record<string, string>;
    /** Messages flashed with ->withNotify() on the previous request. */
    notify: NotifyMessage[];
    /** Sidebar, top bar and footer data (App\Support\DashboardLayout); null for guests. */
    layout: DashboardChrome | null;
    /** Shop header and footer (App\Support\PublicLayout); null outside the public pages. */
    publicLayout: PublicChrome | null;
    [key: string]: unknown;
}

/** One link in Laravel's paginator output (`$paginator->toArray()['links']`). */
export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

/** A Laravel LengthAwarePaginator as Inertia sends it to the browser. */
export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
}

/** One field a user filled in for a manual deposit / withdrawal (App\Support\PaymentPresenter::details). */
export interface PaymentDetail {
    name: string;
    /** Text value, or a download URL when isFile is true */
    value: string | null;
    isFile: boolean;
}

/** Page props = the page's own props + the shared props. */
export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & SharedProps;

// Tell Inertia's own types (usePage, router events) about the shared props.
declare module '@inertiajs/core' {
    interface InertiaConfig {
        sharedPageProps: SharedProps;
    }
}

declare global {
    interface Window {
        /** Toast helper defined by resources/views/partials/notify.blade.php. */
        notify?: (status: string, message: string | string[]) => void;
    }
}

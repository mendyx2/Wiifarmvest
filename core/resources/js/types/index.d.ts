/** A legacy flash message: ['success' | 'error' | 'warning' | 'info', message] */
export type NotifyMessage = [string, string];

export interface AuthUser {
    id: number;
    username: string;
    fullname: string;
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
    [key: string]: unknown;
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

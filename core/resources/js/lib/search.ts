import { router } from '@inertiajs/react';

/**
 * Reload the current page with new query values, dropping empty ones and
 * the page number (so a new search starts on page 1). No full page reload.
 */
export function visitWithQuery(currentUrl: string, query: Record<string, string>) {
    const params = Object.fromEntries(Object.entries(query).filter(([, value]) => value !== ''));
    router.get(currentUrl.split('?')[0], params, { preserveState: true, preserveScroll: true });
}

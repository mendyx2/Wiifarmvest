import { createInertiaApp, router } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import type { ComponentType } from 'react';
import { showNotifications } from '@/lib/notify';

let csrfToken = '';
let siteName = '';

// Laravel checks a CSRF token on every POST. This app does not encrypt
// cookies, so the XSRF-TOKEN cookie route axios would normally use is not
// available; send the token from the shared props as a header instead.
router.on('before', (event) => {
    event.detail.visit.headers['X-CSRF-TOKEN'] = csrfToken;
});

router.on('navigate', (event) => {
    csrfToken = event.detail.page.props.csrfToken;
    siteName = event.detail.page.props.siteName;
});

// Flash messages (->withNotify()) that come back after a form submit or link
// visit. The first, full page load is already handled by the Blade notify partial.
router.on('success', (event) => {
    showNotifications(event.detail.page.props.notify);
});

createInertiaApp({
    // Same format as the Blade pages: "Site Name - Page Title".
    title: (title) => (title ? `${siteName} - ${title}` : siteName),
    // Each page is its own small JS file, downloaded only when it is visited.
    resolve: (name) => {
        const pages = import.meta.glob<{ default: ComponentType }>('./Pages/**/*.tsx');
        const page = pages[`./Pages/${name}.tsx`];

        if (!page) {
            throw new Error(`Inertia page not found: ./Pages/${name}.tsx`);
        }

        return page();
    },
    setup({ el, App, props }) {
        csrfToken = props.initialPage.props.csrfToken;
        siteName = props.initialPage.props.siteName;
        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: '#16A34A',
    },
});

import { createInertiaApp, router } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import type { ComponentType, ReactNode } from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { showNotifications } from '@/lib/notify';

type PageModule = { default: ComponentType & { layout?: (page: ReactNode) => ReactNode } };

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

// A link that lands on a page which is still Blade gets an HTML response
// instead of an Inertia one. Inertia would show that HTML in a pop-up; load
// the page normally instead. (Menu links to Blade pages already do a full
// load; this catches any other link.) Not for form posts: there the pop-up
// is the only place a server error would show.
router.on('invalid', (event) => {
    const { response } = event.detail;
    const url = response.request?.responseURL;

    if (response.config?.method?.toLowerCase() === 'get' && url && response.status < 400) {
        event.preventDefault();
        window.location.href = url;
    }
});

createInertiaApp({
    // Same format as the Blade pages: "Site Name - Page Title".
    title: (title) => (title ? `${siteName} - ${title}` : siteName),
    // Each page is its own small JS file, downloaded only when it is visited.
    resolve: async (name) => {
        const pages = import.meta.glob<PageModule>('./Pages/**/*.tsx');
        const page = pages[`./Pages/${name}.tsx`];

        if (!page) {
            throw new Error(`Inertia page not found: ./Pages/${name}.tsx`);
        }

        const module = await page();

        // Every page sits inside the dashboard layout unless it sets its own
        // (e.g. the login pages). Because it is the same layout component on
        // every page, React keeps it mounted between visits: the sidebar
        // keeps its state and only the page body is replaced.
        module.default.layout ??= (content: ReactNode) => <DashboardLayout>{content}</DashboardLayout>;

        return module;
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

import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useTrans } from '@/lib/i18n';
import './AdminAuthLayout.css';

/**
 * The admin sign-in and password-reset pages (Pages/Admin/Auth/*): no
 * sidebar, one card on a dark page. Pages opt in with
 *   Page.layout = withAdminAuthLayout
 * which replaces the admin layout app.tsx gives other Admin/ pages.
 */
export default function AdminAuthLayout({ children }: { children: ReactNode }) {
    return (
        <main className="al-page">
            <div className="al-grid" aria-hidden />
            <div className="al-blob al-blob-1" aria-hidden />
            <div className="al-blob al-blob-2" aria-hidden />
            {children}
        </main>
    );
}

export const withAdminAuthLayout = (page: ReactNode) => <AdminAuthLayout>{page}</AdminAuthLayout>;

/** The card: the site's logo (or a leaf) and name, then a small heading and the page's form. */
export function AdminAuthCard({ tag, title, text, logo, children }: { tag: string; title: string; text?: ReactNode; logo?: string | null; children: ReactNode }) {
    const t = useTrans();
    const { siteName } = usePage<{ siteName: string }>().props;

    return (
        <div className="al-card">
            <div className="al-brand">
                {logo ? (
                    <img src={logo} alt="" className="al-site-logo" />
                ) : (
                    <div className="al-logo-ring" aria-hidden>
                        <i className="las la-leaf" />
                    </div>
                )}
                <div className="al-site-name">{siteName}</div>
                <div className="al-site-sub">{t('Admin Control Centre')}</div>
            </div>
            <div className="al-divider" />
            <div className="al-head">
                <div className="al-head-tag">{tag}</div>
                <h1>{title}</h1>
                {text && <p>{text}</p>}
            </div>
            {children}
        </div>
    );
}

import type { ReactNode } from 'react';
import './AuthLayout.css';

/**
 * Guest pages (login, password reset, verification): the green gradient
 * page with one centred card, the design of the old login page
 * (layouts/app1.blade.php). Pages opt in with
 *   Page.layout = (page) => <AuthLayout>{page}</AuthLayout>
 * which replaces the default dashboard layout set in app.tsx.
 */
export default function AuthLayout({ children }: { children: ReactNode }) {
    return <div className="auth-shell">{children}</div>;
}

export const withAuthLayout = (page: ReactNode) => <AuthLayout>{page}</AuthLayout>;

/** The login card: gradient header (logo or icon, title, subtitle) and a white body. */
export function AuthCard({ title, subtitle, logo, icon, children, wide = false }: { title: string; subtitle?: string; logo?: string; icon?: string; children: ReactNode; wide?: boolean }) {
    return (
        <div className="login-container" style={wide ? { maxWidth: 480 } : undefined}>
            <div className="login-card">
                <div className="card-header">
                    <div className="brand-logo">{logo ? <img src={logo} alt="logo" /> : <i className={icon ?? 'fas fa-lock'} />}</div>
                    <h1>{title}</h1>
                    {subtitle && <p>{subtitle}</p>}
                </div>
                <div className="card-body">{children}</div>
            </div>
        </div>
    );
}

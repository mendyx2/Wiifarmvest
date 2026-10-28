import { Link } from '@inertiajs/react';
import { useTrans } from '@/lib/i18n';

/** Automatic / Manual tabs on the payment gateway pages (gateways/top_bar.blade.php). */
export default function GatewayTabs({ tabs, active }: { tabs: { automatic: string; manual: string }; active: 'automatic' | 'manual' }) {
    const t = useTrans();
    return (
        <ul className="nav nav-tabs mb-4">
            <li className="nav-item">
                <Link href={tabs.automatic} className={`nav-link text-dark ${active === 'automatic' ? 'active' : ''}`}>
                    <i className="las la-credit-card" /> {t('Automatic Gateway')}
                </Link>
            </li>
            <li className="nav-item">
                <Link href={tabs.manual} className={`nav-link text-dark ${active === 'manual' ? 'active' : ''}`}>
                    <i className="las la-wallet" /> {t('Manual Gateway')}
                </Link>
            </li>
        </ul>
    );
}

/** Enable / Disable buttons with the state the admin saw (a repeat says "already"). */
export function StatusBadge({ active }: { active: boolean }) {
    const t = useTrans();
    return <span className={`badge ${active ? 'badge--success' : 'badge--warning'}`}>{active ? t('Enabled') : t('Disabled')}</span>;
}

import { Head, Link } from '@inertiajs/react';
import { useCopy } from '@/lib/clipboard';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';
import type { AffiliateOrderRow } from './types';

type Props = PageProps<{
    shopLink: string;
    stats: { clicks: string; orders: string; fulfilled: string; bonus: string };
    recentOrders: AffiliateOrderRow[];
    adsCount: number;
    urls: { ads: string; orders: string; bonusTransfer: string };
}>;

export default function AffiliateDashboard({ shopLink, stats, recentOrders, adsCount, urls }: Props) {
    const t = useTrans();
    const { copied, copy } = useCopy(2000);

    return (
        <div className="nc-wrap">
            <Head title={t('Affiliate Dashboard')} />
            <br />
            <div className="sl-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div>
                    <h4 className="sl-page-title mb-1">{t('Affiliate Dashboard')}</h4>
                    <p className="sl-page-subtitle mb-0">{t('Share your link, earn a bonus on every order.')}</p>
                </div>
                <div className="d-flex flex-wrap gap-2">
                    <Link href={urls.ads} className="sl-btn sl-btn-primary">
                        <i className="las la-bullhorn me-1" /> {t('Ad Center')}
                        {adsCount > 0 && <span className="adc-pill">{adsCount}</span>}
                    </Link>
                    <Link href={urls.orders} className="sl-btn sl-btn-outline">
                        <i className="las la-box me-1" /> {t('My Sales')}
                    </Link>
                </div>
            </div>

            <div className="aff-link-box mb-4">
                <div>
                    <div className="fw-bold mb-1">{t('Your Affiliate Link')}</div>
                    <code>{shopLink}</code>
                </div>
                <button
                    type="button"
                    className="aff-copy-btn"
                    onClick={async () => {
                        if (!(await copy(shopLink))) window.notify?.('error', t('Failed to copy link.'));
                    }}
                >
                    <i className={`las ${copied ? 'la-check' : 'la-copy'}`} /> {copied ? t('Copied!') : t('Copy Link')}
                </button>
            </div>

            <div className="row g-3 mb-4">
                {[
                    [stats.clicks, t('Total Clicks')],
                    [stats.orders, t('Orders Placed')],
                    [stats.fulfilled, t('Fulfilled')],
                    [stats.bonus, t('Bonus Balance')],
                ].map(([value, label]) => (
                    <div className="col-6 col-lg-3" key={label}>
                        <div className="aff-stat-card">
                            <div className="aff-stat-val">{value}</div>
                            <div className="aff-stat-lbl">{label}</div>
                        </div>
                    </div>
                ))}
            </div>

            <div className="aff-panel">
                <div className="aff-panel-header">{t('Recent Orders')}</div>
                <div className="table-responsive">
                    <table className="table mb-0">
                        <thead>
                            <tr>
                                <th>{t('Code')}</th>
                                <th>{t('Buyer')}</th>
                                <th>{t('Total')}</th>
                                <th>{t('Payment')}</th>
                                <th>{t('Status')}</th>
                                <th>{t('Date')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {recentOrders.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="text-center text-muted py-4">
                                        {t('No orders yet. Share your link to get started.')}
                                    </td>
                                </tr>
                            ) : (
                                recentOrders.map((order) => (
                                    <tr key={order.id}>
                                        <td data-label={t('Code')}>
                                            <code>{order.code}</code>
                                        </td>
                                        <td data-label={t('Buyer')}>{order.buyer}</td>
                                        <td data-label={t('Total')}>{order.total}</td>
                                        <td data-label={t('Payment')}>{t(order.payment)}</td>
                                        <td data-label={t('Status')}>
                                            <span className={`badge ${order.status.class}`}>{t(order.status.label)}</span>
                                        </td>
                                        <td data-label={t('Date')}>{order.date}</td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            <p className="text-muted small mt-3">
                {t('Your affiliate bonus is credited to')} <strong>{stats.bonus}</strong> {t('and can be moved to your Money Box from')}{' '}
                <Link href={urls.bonusTransfer}>{t('Bonus Transfer')}</Link>.
            </p>
        </div>
    );
}

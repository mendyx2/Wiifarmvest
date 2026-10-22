import { Head, Link } from '@inertiajs/react';
import Pagination from '@/Components/Pagination';
import { useTrans } from '@/lib/i18n';
import type { PageProps, Paginated } from '@/types';

type Row = { id: number; code: string; date: string; other: string; otherName: string; products: number; units: number; amount: string; pending: boolean; statusLabel: string; statusClass: string; url: string };

type Props = PageProps<{
    isMega: boolean;
    stats: { pending: { count: number; total: string }; completed: { count: number; total: string }; closed: number; wallet: string; storeType: string };
    tabs: { key: string; label: string; count: number; url: string }[];
    status: string | null;
    transfers: Paginated<Row>;
    companyOrders: number;
    urls: { index: string; create: string; companyOrders: string };
}>;

export default function TransfersIndex({ isMega, stats, tabs, status, transfers, companyOrders, urls }: Props) {
    const t = useTrans();
    const title = isMega ? t('Stock Requests') : t('My Orders');

    return (
        <div className="nc-wrap stx" id="ncWrap">
            <Head title={title} />
            <br />
            <div className="sl-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div>
                    <h4 className="sl-page-title mb-1">{title}</h4>
                    <p className="sl-page-subtitle mb-0">
                        {isMega
                            ? t('Mini stockists in your state buying from your stock. Their payment is already held: confirm to hand the stock over.')
                            : t('Your stock comes from a Mega stockist in your state. You pay when you order; the money is held until they confirm.')}
                    </p>
                </div>
                {!isMega && <Link href={urls.create} className="sl-btn sl-btn-primary"><i className="las la-truck-loading me-1" /> {t('Buy from Mega')}</Link>}
            </div>

            <div className="row g-3 mb-4">
                <div className="col-6 col-xl-3">
                    <div className="sl-stat-card sl-stat-gold">
                        <div className="sl-stat-icon"><i className="las la-hourglass-half" /></div>
                        <div>
                            <p className="sl-stat-label">{isMega ? t('Waiting for you') : t('Waiting for Mega')}</p>
                            <h3 className="sl-stat-value">{stats.pending.count}</h3>
                            <p className="sl-stat-note">{t(':amount held', { amount: stats.pending.total })}</p>
                        </div>
                    </div>
                </div>
                <div className="col-6 col-xl-3">
                    <div className="sl-stat-card sl-stat-primary">
                        <div className="sl-stat-icon"><i className="las la-check-double" /></div>
                        <div>
                            <p className="sl-stat-label">{t('Completed')}</p>
                            <h3 className="sl-stat-value">{stats.completed.count}</h3>
                            <p className="sl-stat-note">{isMega ? t(':amount earned', { amount: stats.completed.total }) : t(':amount bought', { amount: stats.completed.total })}</p>
                        </div>
                    </div>
                </div>
                <div className="col-6 col-xl-3">
                    <div className="sl-stat-card sl-stat-info">
                        <div className="sl-stat-icon"><i className="las la-undo-alt" /></div>
                        <div>
                            <p className="sl-stat-label">{t('Declined / cancelled')}</p>
                            <h3 className="sl-stat-value">{stats.closed}</h3>
                            <p className="sl-stat-note">{t('Payments refunded')}</p>
                        </div>
                    </div>
                </div>
                <div className="col-6 col-xl-3">
                    <div className="sl-stat-card sl-stat-success">
                        <div className="sl-stat-icon"><i className="las la-wallet" /></div>
                        <div>
                            <p className="sl-stat-label">{t('Stockist wallet')}</p>
                            <h3 className="sl-stat-value">{stats.wallet}</h3>
                            <p className="sl-stat-note">{t(stats.storeType)}</p>
                        </div>
                    </div>
                </div>
            </div>

            <nav className="stx-tabs mb-3" aria-label={t('Filter by status')}>
                <Link href={urls.index} className={`stx-tab ${!status ? 'is-active' : ''}`} aria-current={!status ? 'page' : undefined} preserveScroll>{t('All')}</Link>
                {tabs.map((tab) => (
                    <Link key={tab.key} href={tab.url} className={`stx-tab ${status === tab.key ? 'is-active' : ''}`} aria-current={status === tab.key ? 'page' : undefined} preserveScroll>
                        {t(tab.label)}
                        {tab.count > 0 && <span>{tab.count}</span>}
                    </Link>
                ))}
            </nav>

            <div className="sl-card">
                {transfers.data.length === 0 ? (
                    <div className="sl-empty-state">
                        <div className="sl-empty-icon"><i className="las la-exchange-alt" /></div>
                        <p className="sl-empty-title">{status ? t('Nothing here') : isMega ? t('No stock requests yet') : t('No orders yet')}</p>
                        <p className="sl-empty-sub">
                            {isMega
                                ? t('When a Mini stockist in your state asks to buy from your stock, it shows up here.')
                                : t("Order stock from a Mega stockist in your state. You pay from your stockist wallet; it's held until they confirm.")}
                        </p>
                        {!isMega && <Link href={urls.create} className="sl-btn sl-btn-primary mt-3"><i className="las la-truck-loading me-1" /> {t('Buy from Mega')}</Link>}
                    </div>
                ) : (
                    <div className="table-responsive">
                        <table className="sl-table" aria-label={t('Stock requests')}>
                            <thead>
                                <tr>
                                    <th>{isMega ? t('Request') : t('Order')}</th>
                                    <th>{isMega ? t('Mini stockist') : t('Mega stockist')}</th>
                                    <th>{t('Items')}</th>
                                    <th>{t('Amount')}</th>
                                    <th>{t('Status')}</th>
                                    <th><span className="visually-hidden">{t('Open')}</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                {transfers.data.map((row) => {
                                    const review = isMega && row.pending;
                                    return (
                                        <tr key={row.id} className={review ? 'stx-row-pending' : undefined}>
                                            <td data-label={isMega ? t('Request') : t('Order')}>
                                                <strong className="stx-code">{row.code}</strong>
                                                <small className="d-block text-muted">{row.date}</small>
                                            </td>
                                            <td data-label={isMega ? t('Mini stockist') : t('Mega stockist')}>
                                                {row.other}
                                                <small className="d-block text-muted">{row.otherName}</small>
                                            </td>
                                            <td data-label={t('Items')}>
                                                {row.products === 1 ? t('1 product') : t(':count products', { count: row.products })}
                                                <small className="d-block text-muted">{row.units === 1 ? t('1 unit') : t(':count units', { count: row.units.toLocaleString() })}</small>
                                            </td>
                                            <td data-label={t('Amount')}><strong>{row.amount}</strong></td>
                                            <td data-label={t('Status')}><span className={`sl-status ${row.statusClass}`}>{t(row.statusLabel)}</span></td>
                                            <td data-label="">
                                                <Link href={row.url} className={`sl-btn ${review ? 'sl-btn-primary' : 'sl-btn-outline'} stx-btn-sm`}>{review ? t('Review') : t('View')}</Link>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
            <Pagination links={transfers.links} className="mt-3" />

            {!isMega && companyOrders > 0 && (
                <p className="stx-earlier">
                    <i className="las la-history" />{' '}
                    {companyOrders === 1 ? t('You also have 1 earlier order from the company.') : t('You also have :count earlier orders from the company.', { count: companyOrders })}{' '}
                    <Link href={urls.companyOrders}>{companyOrders === 1 ? t('View it') : t('View them')}</Link>
                </p>
            )}
        </div>
    );
}

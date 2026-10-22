import { Head, Link } from '@inertiajs/react';
import Pagination from '@/Components/Pagination';
import OrderStatus from '@/Components/Stockist/OrderStatus';
import { useTrans } from '@/lib/i18n';
import type { PageProps, Paginated } from '@/types';

type Order = { id: number; number: string; date: string; time: string; items: number; units: number; total: string; status: string; url: string };

type Props = PageProps<{
    canOrderFromCompany: boolean;
    stats: { total: number; pending: number; approved: number; delivered: number };
    orders: Paginated<Order>;
    urls: { catalog: string; transfers: string; buy: string };
}>;

export default function StockistOrders({ canOrderFromCompany, stats, orders, urls }: Props) {
    const t = useTrans();
    const title = canOrderFromCompany ? t('Order History') : t('Earlier Company Orders');

    return (
        <div className="nc-wrap" id="ncWrap">
            <Head title={title} />
            <br />
            <div className="sl-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div>
                    <h4 className="sl-page-title mb-1">{title}</h4>
                    <p className="sl-page-subtitle mb-0">
                        {canOrderFromCompany
                            ? t('Track and manage your product orders.')
                            : t('Orders you placed with the company before. Mini stockists now buy their stock from a Mega stockist in their state.')}
                    </p>
                </div>
                {canOrderFromCompany ? (
                    <Link href={urls.catalog} className="sl-btn sl-btn-primary"><i className="las la-plus me-1" /> {t('New Order')}</Link>
                ) : (
                    <div className="d-flex gap-2 flex-wrap">
                        <Link href={urls.transfers} className="sl-btn sl-btn-outline"><i className="las la-shopping-basket me-1" /> {t('My Orders')}</Link>
                        <Link href={urls.buy} className="sl-btn sl-btn-primary"><i className="las la-truck-loading me-1" /> {t('Buy from Mega')}</Link>
                    </div>
                )}
            </div>

            <div className="row g-3 mb-4">
                {[
                    { tone: 'primary', icon: 'la-shopping-bag', label: t('Total Orders'), value: stats.total },
                    { tone: 'gold', icon: 'la-clock', label: t('Pending'), value: stats.pending },
                    { tone: 'info', icon: 'la-check-circle', label: t('Approved'), value: stats.approved },
                    { tone: 'success', icon: 'la-truck', label: t('Delivered'), value: stats.delivered },
                ].map((card) => (
                    <div className="col-6 col-sm-6 col-xl-3" key={card.label}>
                        <div className={`sl-stat-card sl-stat-${card.tone}`}>
                            <div className="sl-stat-icon"><i className={`las ${card.icon}`} /></div>
                            <div className="sl-stat-body">
                                <p className="sl-stat-label">{card.label}</p>
                                <h3 className="sl-stat-value">{card.value}</h3>
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            <div className="sl-card">
                <div className="sl-card-header"><i className="las la-history me-1" /> {t('Recent Orders')}</div>
                <div className="sl-card-body p-0">
                    {orders.data.length === 0 ? (
                        <div className="sl-empty-state">
                            <div className="sl-empty-icon"><i className="las la-shopping-bag" /></div>
                            <p className="sl-empty-title">{t('No Orders Yet')}</p>
                            {canOrderFromCompany ? (
                                <>
                                    <p className="sl-empty-sub">{t("You haven't placed any orders yet.")}</p>
                                    <Link href={urls.catalog} className="sl-btn sl-btn-primary mt-3"><i className="las la-shopping-cart me-1" /> {t('Place Your First Order')}</Link>
                                </>
                            ) : (
                                <>
                                    <p className="sl-empty-sub">{t('No company orders. Your stock comes from a Mega stockist in your state.')}</p>
                                    <Link href={urls.buy} className="sl-btn sl-btn-primary mt-3"><i className="las la-truck-loading me-1" /> {t('Buy from Mega')}</Link>
                                </>
                            )}
                        </div>
                    ) : (
                        <>
                            <div className="table-responsive">
                                <table className="sl-table" aria-label={t('Order history')}>
                                    <thead>
                                        <tr>
                                            <th>{t('Order #')}</th>
                                            <th>{t('Date')}</th>
                                            <th>{t('Items')}</th>
                                            <th>{t('Total Amount')}</th>
                                            <th>{t('Status')}</th>
                                            <th>{t('Actions')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {orders.data.map((order) => (
                                            <tr key={order.id}>
                                                <td data-label={t('Order #')}><span className="fw-700">{order.number}</span></td>
                                                <td data-label={t('Date')}><small className="text-muted">{order.date}<br />{order.time}</small></td>
                                                <td data-label={t('Items')}><small>{t(':count item(s)', { count: order.items })}<br /><span className="text-muted">{t(':count units', { count: order.units })}</span></small></td>
                                                <td data-label={t('Total Amount')} className="fw-700" style={{ color: 'var(--sl-green)' }}>{order.total}</td>
                                                <td data-label={t('Status')}><OrderStatus status={order.status} /></td>
                                                <td data-label={t('Actions')}><Link href={order.url} className="sl-action-btn">{t('View')} <i className="las la-arrow-right ms-1" /></Link></td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                            {orders.last_page > 1 && (
                                <div className="d-flex flex-wrap justify-content-between align-items-center gap-2 p-3" style={{ borderTop: '1px solid var(--sl-border)' }}>
                                    <small className="text-muted">{t('Showing :from to :to of :total orders', { from: orders.from ?? 0, to: orders.to ?? 0, total: orders.total })}</small>
                                    <Pagination links={orders.links} />
                                </div>
                            )}
                        </>
                    )}
                </div>
            </div>
        </div>
    );
}

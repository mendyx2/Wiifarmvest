import { Head, Link } from '@inertiajs/react';
import Pagination from '@/Components/Pagination';
import { useTrans } from '@/lib/i18n';
import type { PageProps, Paginated } from '@/types';
import type { AffiliateOrderRow } from './types';

type Row = AffiliateOrderRow & { buyerEmail: string | null; items: number };

type Props = PageProps<{ orders: Paginated<Row>; urls: { dashboard: string } }>;

export default function AffiliateOrders({ orders, urls }: Props) {
    const t = useTrans();

    return (
        <div className="nc-wrap">
            <Head title={t('My Affiliate Orders')} />
            <br />
            <div className="sl-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div>
                    <h4 className="sl-page-title mb-1">{t('My Affiliate Sales')}</h4>
                    <p className="sl-page-subtitle mb-0">{t('Every order placed through your affiliate link.')}</p>
                </div>
                <Link href={urls.dashboard} className="sl-btn sl-btn-outline">
                    <i className="las la-arrow-left me-1" /> {t('Back to Dashboard')}
                </Link>
            </div>

            <div className="aff-panel">
                <div className="table-responsive">
                    <table className="table mb-0">
                        <thead>
                            <tr>
                                <th>{t('Code')}</th>
                                <th>{t('Buyer')}</th>
                                <th>{t('Items')}</th>
                                <th>{t('Total')}</th>
                                <th>{t('Payment')}</th>
                                <th>{t('Status')}</th>
                                <th>{t('Date')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {orders.data.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="text-center text-muted py-4">
                                        {t('No orders yet.')}
                                    </td>
                                </tr>
                            ) : (
                                orders.data.map((order) => (
                                    <tr key={order.id}>
                                        <td data-label={t('Code')}>
                                            <code>{order.code}</code>
                                        </td>
                                        <td data-label={t('Buyer')}>
                                            {order.buyer}
                                            {order.buyerEmail && (
                                                <>
                                                    <br />
                                                    <span className="text-muted small">{order.buyerEmail}</span>
                                                </>
                                            )}
                                        </td>
                                        <td data-label={t('Items')}>{order.items}</td>
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
            <div className="mt-3">
                <Pagination links={orders.links} />
            </div>
        </div>
    );
}

import { Head, Link } from '@inertiajs/react';
import Sku from '@/Components/Shop/Sku';
import { ORDER_STATUS_ICON } from '@/Components/Stockist/OrderStatus';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type Props = PageProps<{
    order: {
        number: string;
        status: string;
        placed: string;
        approved: string | null;
        shipped: string | null;
        delivered: string | null;
        subtotal: string;
        shipping: string;
        total: string;
        units: number;
        notes: string | null;
        items: { id: number; name: string; sku: string | null; image: string | null; price: string; quantity: number; total: string }[];
    };
    ordersUrl: string;
}>;

export default function StockistOrderDetails({ order, ordersUrl }: Props) {
    const t = useTrans();
    const timeline = [
        { when: order.placed, label: 'Order Placed', icon: 'la-shopping-cart', color: 'var(--sl-green)' },
        { when: order.approved, label: 'Order Approved', icon: 'la-check', color: 'var(--sl-blue)' },
        { when: order.shipped, label: 'Order Shipped', icon: 'la-shipping-fast', color: '#6366F1' },
        { when: order.delivered, label: 'Order Delivered', icon: 'la-check-circle', color: 'var(--sl-green)' },
    ].filter((step) => step.when);
    const status = order.status.charAt(0).toUpperCase() + order.status.slice(1);

    return (
        <div className="nc-wrap" id="ncWrap">
            <Head title={t('Order Details')} />
            <br />
            <div className="sl-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div>
                    <h4 className="sl-page-title mb-1">{t('Order Details')}</h4>
                    <p className="sl-page-subtitle mb-0">{t('Order #:number', { number: order.number })}</p>
                </div>
                <Link href={ordersUrl} className="sl-btn sl-btn-outline"><i className="las la-arrow-left me-1" /> {t('Back to Orders')}</Link>
            </div>

            <div className="row g-4">
                <div className="col-12 col-lg-8">
                    <div className="sl-card mb-4">
                        <div className="sl-card-body">
                            <div className="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                <div className="d-flex align-items-center gap-3">
                                    <div className={`sl-status-icon sl-status-icon-${order.status}`}>
                                        {ORDER_STATUS_ICON[order.status] && <i className={`las ${ORDER_STATUS_ICON[order.status]}`} />}
                                    </div>
                                    <div>
                                        <h5 className="fw-700 mb-1">{t(status)}</h5>
                                        <p className="text-muted mb-0" style={{ fontSize: '.82rem' }}>{t('Order placed on :date', { date: order.placed })}</p>
                                        {order.approved && <p className="mb-0" style={{ fontSize: '.78rem', color: 'var(--sl-green)' }}>{t('Approved on :date', { date: order.approved })}</p>}
                                        {order.delivered && <p className="mb-0" style={{ fontSize: '.78rem', color: 'var(--sl-green)' }}>{t('Delivered on :date', { date: order.delivered })}</p>}
                                    </div>
                                </div>
                                <div className="text-end">
                                    <h3 className="fw-800 mb-0" style={{ color: 'var(--sl-green)' }}>{order.total}</h3>
                                    <small className="text-muted">{t('Total Amount')}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="sl-card mb-4">
                        <div className="sl-card-header"><i className="las la-boxes me-1" /> {t('Order Items')}</div>
                        <div className="sl-card-body p-0">
                            <div className="table-responsive">
                                <table className="sl-table" aria-label={t('Order items')}>
                                    <thead>
                                        <tr>
                                            <th>{t('Product')}</th>
                                            <th>{t('Price')}</th>
                                            <th>{t('Quantity')}</th>
                                            <th>{t('Total')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {order.items.map((item) => (
                                            <tr key={item.id}>
                                                <td data-label={t('Product')}>
                                                    <div className="sl-table-name">
                                                        {item.image ? <img src={item.image} alt="" className="sl-table-avatar" style={{ objectFit: 'cover' }} /> : <div className="sl-table-avatar" style={{ background: '#F3F4F6', color: '#9CA3AF' }}><i className="las la-box" /></div>}
                                                        <div>
                                                            <p className="fw-600 mb-0">{item.name}</p>
                                                            <Sku value={item.sku} />
                                                        </div>
                                                    </div>
                                                </td>
                                                <td data-label={t('Price')}>{item.price}</td>
                                                <td data-label={t('Quantity')}>{item.quantity}</td>
                                                <td data-label={t('Total')} className="fw-600" style={{ color: 'var(--sl-green)' }}>{item.total}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot>
                                        <tr><td colSpan={3} className="text-end fw-700">{t('Subtotal:')}</td><td className="fw-700">{order.subtotal}</td></tr>
                                        <tr><td colSpan={3} className="text-end fw-700">{t('Shipping:')}</td><td className="fw-700" style={{ color: 'var(--sl-green)' }}>{order.shipping}</td></tr>
                                        <tr><td colSpan={3} className="text-end fw-800">{t('Grand Total:')}</td><td className="fw-800" style={{ color: 'var(--sl-green)' }}>{order.total}</td></tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    {order.notes && (
                        <div className="sl-card">
                            <div className="sl-card-header"><i className="las la-sticky-note me-1" /> {t('Order Notes')}</div>
                            <div className="sl-card-body"><p className="text-muted mb-0" style={{ whiteSpace: 'pre-line' }}>{order.notes}</p></div>
                        </div>
                    )}
                </div>

                <div className="col-12 col-lg-4">
                    <div className="sl-card mb-4">
                        <div className="sl-card-header"><i className="las la-file-invoice me-1" /> {t('Order Summary')}</div>
                        <div className="sl-card-body">
                            <div className="sl-info-row"><span className="fw-600">{t('Order Number')}</span><code>{order.number}</code></div>
                            <div className="sl-info-row"><span className="fw-600">{t('Items')}</span><span>{order.items.length}</span></div>
                            <div className="sl-info-row"><span className="fw-600">{t('Total Units')}</span><span>{order.units}</span></div>
                            <div className="sl-info-row"><span className="fw-600">{t('Payment Method')}</span><span style={{ color: 'var(--sl-green)' }}>{t('Wallet')}</span></div>
                        </div>
                    </div>
                    <div className="sl-card">
                        <div className="sl-card-header"><i className="las la-history me-1" /> {t('Order Timeline')}</div>
                        <div className="sl-card-body">
                            {timeline.map((step) => (
                                <div className="sl-timeline-item" key={step.label}>
                                    <div className="sl-timeline-icon" style={{ background: step.color }}><i className={`las ${step.icon}`} /></div>
                                    <div>
                                        <p className="fw-600 mb-0">{t(step.label)}</p>
                                        <small className="text-muted">{step.when}</small>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

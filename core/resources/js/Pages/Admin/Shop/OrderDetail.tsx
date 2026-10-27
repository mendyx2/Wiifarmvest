import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { BadgeCell, type AdminUser, type Badge, type When } from '@/Components/Admin/Cells';
import { ConfirmButton } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';
import './OrderDetail.css';

type Item = { id: number; name: string; sku: string | null; image: string | null; quantity: number; collected: number; price: string; subtotal: string };
type Redemption = { id: number; stockist: string; username: string | null; amount: string; date: When; notes: string | null; items: { name: string; quantity: number }[] };

type Props = {
    title: string;
    order: { id: number; invoice: string; trx: string; user: AdminUser; state: string | null; total: string; date: When; status: Badge; redeemed: Badge };
    items: Item[];
    redemptions: Redemption[];
    actions: { ship: boolean; cancel: boolean; url: string };
    urls: { index: string };
};

/** By the collection badge's class (its label is translated). */
const HERO: Record<string, { icon: string; label: string; cls: string }> = {
    'badge--success': { icon: 'las la-check-double', label: 'Fully Collected', cls: 'badge--success' },
    'badge--warning': { icon: 'las la-adjust', label: 'Partly Collected', cls: 'badge--warning' },
    'badge--dark': { icon: 'las la-times-circle', label: 'Not Collected', cls: 'badge--danger' },
};

/** One member order: products, collection at stockists, and Ship / Cancel while it is pending. */
export default function OrderDetail({ title, order, items, redemptions, actions, urls }: Props) {
    const t = useTrans();
    const hero = HERO[order.redeemed.class] ?? HERO['badge--dark'];

    return (
        <div className="page-order-detail">
            <PageHeader title={title}>
                {actions.ship && (
                    <ConfirmButton url={actions.url} data={{ status: 1 }} className="btn btn-outline--success" question={t('Mark order :invoice as shipped? The member is notified.', { invoice: order.invoice })}>
                        <i className="las la-truck" /> {t('Mark Shipped')}
                    </ConfirmButton>
                )}
                {actions.cancel && (
                    <ConfirmButton url={actions.url} data={{ status: 2 }} className="btn btn-outline--danger" question={t('Cancel order :invoice? :total goes back to the member\'s product wallet.', { invoice: order.invoice, total: order.total })}>
                        <i className="las la-ban" /> {t('Cancel Order')}
                    </ConfirmButton>
                )}
                <Link href={urls.index} className="btn btn-outline--primary">
                    <i className="las la-arrow-left" /> {t('Back to Orders')}
                </Link>
            </PageHeader>

            <div className="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <p className="text-muted mb-0 small">
                    {t('Placed on :date', { date: order.date?.date ?? '—' })} · TRX <code>{order.trx || '—'}</code> · {t('Status')}: <BadgeCell badge={order.status} />
                </p>
                <span className={`status-hero-badge ${hero.cls}`}>
                    <i className={`${hero.icon} me-1`} />
                    {t(hero.label)}
                </span>
            </div>
            {!actions.cancel && actions.ship && (
                <div className="alert alert-info small" role="note">
                    {t('Part of this order was already collected at a stockist, so it can no longer be cancelled.')}
                </div>
            )}

            <div className="row g-3 mb-4">
                <Meta icon="las la-user" bg="primary" label={t('Customer')} value={order.user ? <Link href={order.user.url}>{order.user.username}</Link> : '—'} sub={order.user?.fullname} />
                <Meta icon="las la-map-marker-alt" bg="info" label={t('State')} value={order.state ?? '—'} />
                <Meta icon="las la-file-invoice" bg="warning" label={t('Invoice Code')} value={<span className="inv-code-sm">{order.invoice}</span>} />
                <Meta icon="las la-money-bill-wave" bg="success" label={t('Total Amount')} value={<span className="amount-hero">{order.total}</span>} />
            </div>

            <div className="card b-radius--10 mb-4">
                <div className="card-header">
                    <h5 className="card-title mb-0">
                        <i className="las la-box-open text--primary me-2" />
                        {t('Products in This Order')}
                        <span className="badge badge--primary ms-2">{items.length}</span>
                    </h5>
                </div>
                <div className="card-body p-0">
                    <div className="table-responsive--md table-responsive">
                        <table className="table table--light style--two mb-0">
                            <thead>
                                <tr>
                                    <th>{t('Product')}</th>
                                    <th className="text-center">{t('Qty Ordered')}</th>
                                    <th className="text-center">{t('Qty Collected')}</th>
                                    <th className="text-end">{t('Unit Price')}</th>
                                    <th className="text-end">{t('Subtotal')}</th>
                                    <th className="text-center">{t('Status')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.length ? (
                                    items.map((item) => {
                                        const full = item.collected >= item.quantity;
                                        return (
                                            <tr key={item.id}>
                                                <td data-label={t('Product')}>
                                                    <div className="d-flex align-items-center gap-2">
                                                        {item.image ? (
                                                            <img src={item.image} alt="" className="product-thumb" />
                                                        ) : (
                                                            <div className="product-thumb-placeholder">
                                                                <i className="las la-image" />
                                                            </div>
                                                        )}
                                                        <span>
                                                            <span className="fw-semibold">{item.name}</span>
                                                            {item.sku && (
                                                                <>
                                                                    <br />
                                                                    <small className="text-muted">SKU: {item.sku}</small>
                                                                </>
                                                            )}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="text-center" data-label={t('Qty Ordered')}>
                                                    <span className="qty-badge">{item.quantity}</span>
                                                </td>
                                                <td className="text-center" data-label={t('Qty Collected')}>
                                                    <span className={`qty-badge ${item.collected > 0 ? 'redeemed' : 'none'}`}>{item.collected}</span>
                                                </td>
                                                <td className="text-end" data-label={t('Unit Price')}>
                                                    {item.price}
                                                </td>
                                                <td className="text-end fw-bold" data-label={t('Subtotal')}>
                                                    {item.subtotal}
                                                </td>
                                                <td className="text-center" data-label={t('Status')}>
                                                    {full ? (
                                                        <span className="badge badge--success item-status-badge">{t('Collected')}</span>
                                                    ) : item.collected > 0 ? (
                                                        <span className="badge badge--warning item-status-badge">{t('Partial')}</span>
                                                    ) : (
                                                        <span className="badge badge--danger item-status-badge">{t('Waiting')}</span>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })
                                ) : (
                                    <tr>
                                        <td colSpan={6} className="text-center py-4 text-muted">
                                            {t('No products found for this order.')}
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                            {items.length > 0 && (
                                <tfoot>
                                    <tr className="order-total-row">
                                        <td colSpan={4} className="text-end fw-bold py-3">
                                            {t('Order Total')}
                                        </td>
                                        <td className="text-end fw-bold py-3 total-amount-cell">{order.total}</td>
                                        <td />
                                    </tr>
                                </tfoot>
                            )}
                        </table>
                    </div>
                </div>
            </div>

            <div className="card b-radius--10 mb-4">
                <div className="card-header">
                    <h5 className="card-title mb-0">
                        <i className="las la-store text--success me-2" />
                        {t('Collection History')}
                        <span className="badge badge--success ms-2">{redemptions.length}</span>
                    </h5>
                </div>
                <div className="card-body p-0">
                    {redemptions.length ? (
                        redemptions.map((r, i) => (
                            <div className={`redemption-block ${i < redemptions.length - 1 ? 'border-bottom' : ''}`} key={r.id}>
                                <div className="redemption-header">
                                    <div className="d-flex flex-wrap align-items-center gap-3">
                                        <div className="stockist-info">
                                            <div className="stockist-avatar">
                                                <i className="las la-store-alt" />
                                            </div>
                                            <div>
                                                <p className="stockist-name mb-0">{r.stockist}</p>
                                                {r.username && <p className="stockist-sub mb-0">@{r.username}</p>}
                                            </div>
                                        </div>
                                        <div className="ms-auto text-end">
                                            <p className="mb-0 fw-bold text--success">{r.amount}</p>
                                            <p className="text-muted small mb-0">{r.date?.date ?? '—'}</p>
                                        </div>
                                    </div>
                                    {r.notes && <div className="redemption-notes mt-2">{r.notes}</div>}
                                </div>
                                {r.items.length > 0 && (
                                    <div className="redemption-items">
                                        <div className="row g-2">
                                            {r.items.map((item, j) => (
                                                <div className="col-12 col-sm-6 col-md-4" key={j}>
                                                    <div className="ritem-card">
                                                        <div className="ritem-details">
                                                            <p className="ritem-name mb-0">{item.name}</p>
                                                            <p className="ritem-qty mb-0">
                                                                {t('Qty')}: <strong>{item.quantity}</strong>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>
                        ))
                    ) : (
                        <div className="text-center py-5 empty-state">
                            <i className="las la-store" />
                            <p className="mt-2 text-muted">{t('Nothing collected at a stockist yet.')}</p>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

function Meta({ icon, bg, label, value, sub }: { icon: string; bg: string; label: string; value: ReactNode; sub?: string }) {
    return (
        <div className="col-md-3 col-sm-6">
            <div className="meta-card">
                <div className={`meta-icon bg--${bg}`}>
                    <i className={icon} />
                </div>
                <div className="meta-body">
                    <p className="meta-label">{label}</p>
                    <p className="meta-value">{value}</p>
                    {sub && <p className="meta-sub">{sub}</p>}
                </div>
            </div>
        </div>
    );
}

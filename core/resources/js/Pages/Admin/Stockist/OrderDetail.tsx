import { Link } from '@inertiajs/react';
import { BadgeCell, type AdminUser, type Badge } from '@/Components/Admin/Cells';
import { ConfirmButton } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';
import './OrderDetail.css';

type Step = { key: string; label: string; at: string | null };

type Props = {
    title: string;
    order: { id: number; number: string; status: string; badge: Badge; notes: string | null; subtotal: string; shipping: string; tax: string; total: string; units: number; steps: Step[] };
    stockist: { name: string; owner: AdminUser; type: string; wallet: string; url: string } | null;
    items: { id: number; name: string; sku: string | null; image: string | null; quantity: number; unit: string; total: string }[];
    actions: { approve: string | null; process: string | null; ship: string | null; deliver: string | null; cancel: string | null };
    urls: { index: string };
};

const ORDER = ['pending', 'approved', 'processing', 'shipped', 'delivered'];

/** One stock order from a stockist: its items, where it is, and the next step. */
export default function StockistOrderDetail({ title, order, stockist, items, actions, urls }: Props) {
    const t = useTrans();
    const reached = ORDER.indexOf(order.status);
    const cancelled = order.status === 'cancelled';
    const any = Object.values(actions).some(Boolean);

    return (
        <div className="page-stockist-order">
            <PageHeader title={title}>
                <Link href={urls.index} className="btn btn-outline--primary">
                    <i className="las la-arrow-left" /> {t('Back to Orders')}
                </Link>
            </PageHeader>
            <div className="row gy-4">
                <div className="col-lg-8">
                    <div className="card mb-4">
                        <div className="card-header d-flex justify-content-between align-items-center">
                            <h5 className="card-title mb-0">
                                <i className="las la-box" /> {t('Order Items')}
                            </h5>
                            <BadgeCell badge={order.badge} />
                        </div>
                        <div className="card-body p-0">
                            <div className="table-responsive--md table-responsive">
                                <table className="table table--light style--two mb-0">
                                    <thead>
                                        <tr>
                                            <th>{t('Product')}</th>
                                            <th>{t('Quantity')}</th>
                                            <th className="text-end">{t('Unit Price')}</th>
                                            <th className="text-end">{t('Total')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {items.map((item) => (
                                            <tr key={item.id}>
                                                <td data-label={t('Product')}>
                                                    <div className="d-flex align-items-center gap-2">
                                                        {item.image ? <img src={item.image} alt="" className="sko-thumb" /> : <span className="sko-thumb sko-thumb--empty" aria-hidden="true" />}
                                                        <span>
                                                            <strong>{item.name}</strong>
                                                            {item.sku && <small className="d-block text-muted">SKU: {item.sku}</small>}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td data-label={t('Quantity')}>
                                                    <span className="badge badge--primary">{item.quantity}</span>
                                                </td>
                                                <td className="text-end" data-label={t('Unit Price')}>
                                                    {item.unit}
                                                </td>
                                                <td className="text-end fw-bold" data-label={t('Total')}>
                                                    {item.total}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot>
                                        {(
                                            [
                                                [t('Subtotal'), order.subtotal],
                                                [t('Shipping'), order.shipping],
                                                [t('Tax'), order.tax],
                                            ] as const
                                        ).map(([label, value]) => (
                                            <tr key={label}>
                                                <td colSpan={3} className="text-end">
                                                    {label}
                                                </td>
                                                <td className="text-end">{value}</td>
                                            </tr>
                                        ))}
                                        <tr>
                                            <th colSpan={3} className="text-end">
                                                {t('Grand Total')}
                                            </th>
                                            <th className="text-end">{order.total}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div className="card">
                        <div className="card-header">
                            <h5 className="card-title mb-0">
                                <i className="las la-stream" /> {t('Progress')}
                            </h5>
                        </div>
                        <div className="card-body">
                            {cancelled && (
                                <div className="alert alert-danger" role="status">
                                    {t('This order was cancelled; the amount was refunded and the stock returned.')}
                                </div>
                            )}
                            <ol className="sko-steps">
                                {order.steps.map((step, i) => {
                                    const done = !cancelled && i <= reached;
                                    return (
                                        <li key={step.key} className={`${done ? 'is-done' : ''} ${!cancelled && i === reached ? 'is-current' : ''}`} aria-current={!cancelled && i === reached ? 'step' : undefined}>
                                            <span className="sko-steps__dot" aria-hidden="true">
                                                <i className={done ? 'las la-check' : 'las la-circle'} />
                                            </span>
                                            <div>
                                                <strong>{t(step.label)}</strong>
                                                <small>{step.at ?? (done ? '—' : t('Waiting'))}</small>
                                            </div>
                                        </li>
                                    );
                                })}
                            </ol>
                        </div>
                    </div>
                </div>

                <div className="col-lg-4">
                    <div className="card mb-4">
                        <div className="card-header">
                            <h5 className="card-title mb-0">
                                <i className="las la-cogs" /> {t('Actions')}
                            </h5>
                        </div>
                        <div className="card-body d-grid gap-2">
                            {actions.approve && <Step url={actions.approve} label={t('Approve Order')} icon="las la-check" cls="btn--success" question={t('Approve order :n?', { n: order.number })} />}
                            {actions.process && <Step url={actions.process} label={t('Mark as Processing')} icon="las la-cog" cls="btn--info" question={t('Mark order :n as processing?', { n: order.number })} />}
                            {actions.ship && <Step url={actions.ship} label={t('Mark as Shipped')} icon="las la-shipping-fast" cls="btn--primary" question={t('Mark order :n as shipped?', { n: order.number })} />}
                            {actions.deliver && (
                                <Step url={actions.deliver} label={t('Mark as Delivered')} icon="las la-check-circle" cls="btn--success" question={t("Mark order :n as delivered? :units units go into the stockist's store.", { n: order.number, units: order.units })} />
                            )}
                            {actions.cancel && <Step url={actions.cancel} label={t('Cancel Order')} icon="las la-times" cls="btn--danger" question={t('Cancel order :n? :total goes back to the stockist wallet and the stock returns.', { n: order.number, total: order.total })} />}
                            {!any && <p className="text-muted mb-0">{t('Nothing left to do for this order.')}</p>}
                        </div>
                    </div>
                    {stockist && (
                        <div className="card">
                            <div className="card-header">
                                <h5 className="card-title mb-0">
                                    <i className="las la-store" /> {t('Stockist')}
                                </h5>
                            </div>
                            <ul className="list-group list-group-flush">
                                <li className="list-group-item d-flex justify-content-between gap-2">
                                    <span>{t('Business')}</span>
                                    <Link href={stockist.url}>{stockist.name}</Link>
                                </li>
                                <li className="list-group-item d-flex justify-content-between gap-2">
                                    <span>{t('Owner')}</span>
                                    {stockist.owner ? <Link href={stockist.owner.url}>{stockist.owner.fullname}</Link> : '—'}
                                </li>
                                <li className="list-group-item d-flex justify-content-between gap-2">
                                    <span>{t('Store type')}</span>
                                    <span>{stockist.type}</span>
                                </li>
                                <li className="list-group-item d-flex justify-content-between gap-2">
                                    <span>{t('Wallet now')}</span>
                                    <strong>{stockist.wallet}</strong>
                                </li>
                            </ul>
                            {order.notes && (
                                <div className="card-body border-top">
                                    <strong className="d-block mb-1">{t('Notes')}</strong>
                                    <p className="mb-0">{order.notes}</p>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

function Step({ url, label, icon, cls, question }: { url: string; label: string; icon: string; cls: string; question: string }) {
    return (
        <ConfirmButton url={url} className={`btn ${cls} w-100`} question={question}>
            <i className={icon} /> {label}
        </ConfirmButton>
    );
}

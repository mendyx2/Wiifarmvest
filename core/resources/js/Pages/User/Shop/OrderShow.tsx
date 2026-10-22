import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import CopyNotice from '@/Components/Shop/CopyNotice';
import Sku from '@/Components/Shop/Sku';
import type { OrderStatus } from '@/Components/Shop/types';
import { useCopy } from '@/lib/clipboard';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type Props = PageProps<{
    order: {
        code: string;
        state: string;
        status: OrderStatus;
        date: string;
        redeemedAt: string | null;
        total: string;
        items: { id: number; name: string; sku: string | null; description: string; image: string | null; quantity: number; price: string; lineTotal: string }[];
        invoiceCode: string | null;
    };
    timeline: { icon: string; text: string; date: string }[];
    ordersUrl: string;
    productsUrl: string;
}>;

const ICONS: Record<OrderStatus['key'], [string, string]> = {
    pending: ['pend', 'la-clock'],
    redeemed: ['redeemed', 'la-box-open'],
    cancelled: ['cancel', 'la-times-circle'],
};

export default function OrderShow({ order, timeline, ordersUrl, productsUrl }: Props) {
    const t = useTrans();
    const { copy } = useCopy();
    const [copied, setCopied] = useState<string | null>(null);
    const [tone, icon] = ICONS[order.status.key];

    const copyCode = async () => {
        if (!order.invoiceCode) return;
        if (await copy(order.invoiceCode)) setCopied(order.invoiceCode);
        else window.notify?.('error', t('Failed to copy invoice code.'));
    };

    return (
        <div className="osd-page">
            <Head title={t('Order Details')} />

            <div className="osd-header-card">
                <div className="osd-header-left">
                    <div className="osd-header-icon"><i className="las la-receipt" /></div>
                    <div>
                        <h6 className="osd-header-title">{t('Order Details')}</h6>
                        <p className="osd-header-sub">
                            {t('Invoice:')} <strong>{order.code}</strong> &bull; <i className="las la-map-marker-alt" /> {order.state}
                        </p>
                    </div>
                </div>
                <Link href={ordersUrl} className="osd-back-btn">
                    <i className="las la-arrow-left" /> {t('Back to Orders')}
                </Link>
            </div>

            <div className="osd-layout">
                <div className="osd-left">
                    <div className="osd-status-card">
                        <div className="osd-status-icon-wrap">
                            <div className={`osd-status-icon ${tone}`}><i className={`las ${icon}`} /></div>
                        </div>
                        <div className="osd-status-info">
                            <h6 className="osd-status-label">{t(order.status.label)}</h6>
                            <p className="osd-status-date">{t('Order placed on :date', { date: order.date })}</p>
                            {order.redeemedAt && (
                                <p className="osd-status-redeemed"><i className="las la-check-circle" /> {t('Redeemed on :date', { date: order.redeemedAt })}</p>
                            )}
                        </div>
                        <div className="osd-status-amount">
                            <span className="osd-amount">{order.total}</span>
                            <span className="osd-amount-label">{t('Total Amount')}</span>
                        </div>
                    </div>

                    <div className="osd-card">
                        <div className="osd-card-head"><i className="las la-boxes" /> {t('Order Items')}</div>
                        {order.items.map((item) => (
                            <div className="osd-item" key={item.id}>
                                <div className="osd-item-img">
                                    {item.image ? (
                                        <img src={item.image} alt={item.name} />
                                    ) : (
                                        <div className="osd-item-placeholder"><i className="las la-image" /></div>
                                    )}
                                </div>
                                <div className="osd-item-info">
                                    <p className="osd-item-name">{item.name} <Sku value={item.sku} className="ms-1" /></p>
                                    {item.description && <p className="osd-item-desc">{item.description}</p>}
                                    <p className="osd-item-price">{item.price}</p>
                                </div>
                                <div className="osd-item-right">
                                    <span className="osd-item-qty">×{item.quantity}</span>
                                    <span className="osd-item-total">{item.lineTotal}</span>
                                </div>
                            </div>
                        ))}
                    </div>

                    {order.invoiceCode && (
                        <div className="osd-card">
                            <div className="osd-card-head"><i className="las la-receipt" /> {t('Invoice Information')}</div>
                            <div className="osd-invoice-body">
                                <div className="osd-invoice-row">
                                    <label className="osd-invoice-label" htmlFor="invoiceCode">{t('Invoice Code')}</label>
                                    <div className="osd-code-wrap">
                                        <input type="text" className="osd-code-input" value={order.invoiceCode} readOnly id="invoiceCode" />
                                        <button className="osd-copy-btn" type="button" onClick={copyCode}>
                                            <i className="las la-copy" /> {t('Copy')}
                                        </button>
                                    </div>
                                    <small className="osd-code-hint">{t('Present this code to redeem your products')}</small>
                                </div>
                                <div className="osd-invoice-row">
                                    <span className="osd-invoice-label">{t('Status')}</span>
                                    {order.redeemedAt ? (
                                        <span className="osd-inv-badge ok"><i className="las la-check-circle" /> {t('Redeemed on :date', { date: order.redeemedAt })}</span>
                                    ) : (
                                        <span className="osd-inv-badge pend"><i className="las la-clock" /> {t('Awaiting Redemption')}</span>
                                    )}
                                </div>

                                <div className="osd-redeem-steps">
                                    <p className="osd-redeem-title"><i className="las la-info-circle" /> {t('How to Redeem Your Products')}</p>
                                    <ol className="osd-redeem-list">
                                        <li>{t('Visit any authorised stockist location')}</li>
                                        <li>{t('Present your invoice code:')} <code>{order.invoiceCode}</code></li>
                                        <li>{t('Show your ID for verification')}</li>
                                        <li>{t('Collect your products')}</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                <div className="osd-right">
                    <div className="osd-card">
                        <div className="osd-card-head osd-card-head--green"><i className="las la-file-invoice" /> {t('Order Summary')}</div>
                        <div className="osd-summary-body">
                            <div className="osd-sum-row">
                                <span>{t('Subtotal')}</span>
                                <span>{order.total}</span>
                            </div>
                            <div className="osd-sum-row">
                                <span>{t('Shipping')}</span>
                                <span className="osd-free">{t('FREE')}</span>
                            </div>
                            <div className="osd-sum-div" />
                            <div className="osd-sum-row osd-sum-total">
                                <span>{t('Total')}</span>
                                <span>{order.total}</span>
                            </div>
                            <div className="osd-payment-method">
                                <div className="osd-pm-icon"><i className="las la-wallet" /></div>
                                <div>
                                    <p className="osd-pm-name">{t('Wallet Payment')}</p>
                                    <p className="osd-pm-sub">{t('Paid from your wallet balance')}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="osd-card">
                        <div className="osd-card-head"><i className="las la-history" /> {t('Order Timeline')}</div>
                        <div className="osd-timeline">
                            {timeline.map((step, i) => (
                                <div className={`osd-tl-item ${i < timeline.length - 1 ? 'has-line' : ''}`} key={step.text}>
                                    <div className="osd-tl-dot"><i className={`las ${step.icon}`} /></div>
                                    <div className="osd-tl-info">
                                        <p className="osd-tl-label">{step.text}</p>
                                        <p className="osd-tl-date">{step.date}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="osd-card">
                        <div className="osd-card-head"><i className="las la-bolt" /> {t('Quick Actions')}</div>
                        <div className="osd-actions">
                            {order.invoiceCode && !order.redeemedAt && (
                                <button className="osd-action-btn outline" type="button" onClick={copyCode}>
                                    <i className="las la-copy" /> {t('Copy Invoice Code')}
                                </button>
                            )}
                            <Link href={ordersUrl} className="osd-action-btn outline">
                                <i className="las la-list" /> {t('View All Orders')}
                            </Link>
                            <Link href={productsUrl} className="osd-action-btn green">
                                <i className="las la-store" /> {t('Continue Shopping')}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <CopyNotice copied={copied ? { code: copied, kind: 'invoice' } : null} onClose={() => setCopied(null)} />
        </div>
    );
}

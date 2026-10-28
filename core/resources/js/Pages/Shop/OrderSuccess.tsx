import { Head, Link } from '@inertiajs/react';
import { withPublicLayout } from '@/Layouts/PublicLayout';
import { useCopy } from '@/lib/clipboard';
import { useTrans } from '@/lib/i18n';

type Props = {
    order: {
        code: string;
        total: string;
        payment: string;
        cash: boolean;
        state: string;
        email: string;
        status: { label: string; class: string };
        awaitingPayment: boolean;
    };
    urls: { invoice: string; pay: string | null; shop: string };
};

/** After checkout (/shop/order/{code}): the redemption code to show at pickup, and the invoice. */
export default function ShopOrderSuccess({ order, urls }: Props) {
    const t = useTrans();
    const { copied, copy } = useCopy(2000);

    return (
        <div className="shop-wrap">
            <Head title={order.awaitingPayment ? t('Complete Payment') : t('Order Confirmed')} />
            <div className="shop-container shop-container--narrow">
                <div className="shop-success-card">
                    {order.awaitingPayment ? (
                        // The old page said "Your payment was received" here too.
                        <>
                            <div className="shop-success-icon shop-success-icon--pending">
                                <i className="las la-hourglass-half" />
                            </div>
                            <h4 className="fw-bold mb-1">{t('Payment Not Completed')}</h4>
                            <p className="text-muted mb-0">{t('Your order is saved, but we have not received the payment yet.')}</p>
                            {urls.pay && (
                                <Link href={urls.pay} className="shop-buy-btn mt-3">
                                    <i className="las la-credit-card" /> {t('Pay Now')}
                                </Link>
                            )}
                        </>
                    ) : (
                        <>
                            <div className="shop-success-icon">
                                <i className="las la-check" />
                            </div>
                            <h4 className="fw-bold mb-1">{t('Order Confirmed')}</h4>
                            <p className="text-muted mb-0">
                                {order.cash
                                    ? t('Pay when you pick up your order at any partner location.')
                                    : t('Your payment was received. Present your code to collect your order.')}
                            </p>
                        </>
                    )}

                    <div className="shop-code-box">
                        <div className="text-muted small text-uppercase fw-bold mb-1">{t('Redemption Code')}</div>
                        <div className="shop-code-val" id="redemptionCode">{order.code}</div>
                        <button type="button" className={`shop-copy-btn ${copied ? 'copied' : ''}`} id="copyCodeBtn" onClick={() => copy(order.code)}>
                            <i className={copied ? 'las la-check' : 'las la-copy'} /> {copied ? t('Copied!') : t('Copy Code')}
                        </button>
                    </div>

                    <table className="shop-detail-table">
                        <tbody>
                            <tr>
                                <td>{t('Order Total')}</td>
                                <td>{order.total}</td>
                            </tr>
                            <tr>
                                <td>{t('Payment Method')}</td>
                                <td>{order.awaitingPayment ? t('Pay Online') : t(order.payment)}</td>
                            </tr>
                            <tr>
                                <td>{t('Pickup State')}</td>
                                <td>{order.state}</td>
                            </tr>
                            <tr>
                                <td>{t('Status')}</td>
                                <td>
                                    <span className={`badge ${order.status.class}`}>{t(order.status.label)}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    {/* A PDF download: a plain link, not an Inertia visit */}
                    <a href={urls.invoice} className="shop-buy-btn mt-3">
                        <i className="las la-file-pdf" /> {t('Download Invoice')}
                    </a>
                    {/* The invoice email goes out once the order is paid or set for cash on pickup. */}
                    {!order.awaitingPayment && (
                        <p className="text-muted small mt-3 mb-0">{t('A copy of your invoice and this code has also been emailed to :email.', { email: order.email })}</p>
                    )}
                    <p className="small mt-2 mb-0">
                        <Link href={urls.shop}>{t('Continue Shopping')}</Link>
                    </p>
                </div>
            </div>
        </div>
    );
}

ShopOrderSuccess.layout = withPublicLayout;

import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import Modal from '@/Components/Modal';
import Sku from '@/Components/Shop/Sku';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type Line = {
    id: number;
    name: string;
    sku: string | null;
    image: string | null;
    state: string;
    quantity: number;
    price: string;
    lineTotal: string;
};

type Props = PageProps<{
    items: Line[];
    total: string;
    wallet: string;
    canPay: boolean;
    shortfall: string | null;
    payUrl: string;
    cartUrl: string;
}>;

export default function Checkout({ items, total, wallet, canPay, shortfall, payUrl, cartUrl }: Props) {
    const t = useTrans();
    const [paying, setPaying] = useState(false);

    // On success the server redirects to the new order; on failure it comes
    // back here with an error toast. The button stays disabled while the
    // request runs, and the server also locks the cart, so one click = one order.
    const pay = () => {
        if (paying) return;
        router.post(payUrl, {}, {
            onStart: () => setPaying(true),
            onFinish: () => setPaying(false),
        });
    };

    return (
        <div className="chk-page">
            <Head title={t('Checkout')} />

            <div className="chk-layout">
                <div className="chk-items-card">
                    <div className="chk-card-head">
                        <i className="las la-shopping-cart" /> {t('Order Summary')}
                    </div>
                    <div className="chk-items-body">
                        {items.map((item) => (
                            <div className="chk-item" key={item.id}>
                                <div className="chk-item-img">
                                    {item.image ? (
                                        <img src={item.image} alt={item.name} />
                                    ) : (
                                        <div className="chk-item-placeholder"><i className="las la-image" /></div>
                                    )}
                                </div>
                                <div className="chk-item-info">
                                    <p className="chk-item-name">{item.name}</p>
                                    <p className="chk-item-state">
                                        <Sku value={item.sku} /> <i className="las la-map-marker-alt" /> {item.state}
                                    </p>
                                </div>
                                <div className="chk-item-right">
                                    <span className="chk-item-qty">×{item.quantity}</span>
                                    <span className="chk-item-total">{item.lineTotal}</span>
                                    <small className="chk-item-each">{t(':price each', { price: item.price })}</small>
                                </div>
                            </div>
                        ))}
                    </div>

                    <div className="chk-info-box">
                        <div className="chk-info-title"><i className="las la-info-circle" /> {t('Important Information')}</div>
                        <ul className="chk-info-list">
                            <li><i className="las la-receipt" /> {t('You will receive an invoice code after payment')}</li>
                            <li><i className="las la-store" /> {t('Present the code at any authorised stockist to redeem')}</li>
                            <li><i className="las la-clock" /> {t('Please do not share code with members')}</li>
                        </ul>
                    </div>
                </div>

                <div className="chk-pay-card">
                    <div className="chk-card-head chk-card-head--pay">
                        <i className="las la-credit-card" /> {t('Payment Summary')}
                    </div>
                    <div className="chk-pay-body">
                        <div className="chk-pay-row">
                            <span>{t('Subtotal')}</span>
                            <span>{total}</span>
                        </div>
                        <div className="chk-pay-row">
                            <span>{t('Shipping')}</span>
                            <span className="chk-free">{t('FREE')}</span>
                        </div>
                        <div className="chk-pay-divider" />
                        <div className="chk-pay-row chk-pay-total">
                            <span>{t('Total')}</span>
                            <span>{total}</span>
                        </div>

                        <div className="chk-wallet-box">
                            <div className="chk-wallet-row">
                                <span><i className="las la-shopping-bag" /> {t('Re-purchase wallet')}</span>
                                <span className="chk-wallet-amt">{wallet}</span>
                            </div>
                            {shortfall ? (
                                <div className="chk-wallet-alert warn">
                                    <i className="las la-exclamation-triangle" />{' '}
                                    {t('You need :amount more in your Re-purchase wallet to complete this purchase.', { amount: shortfall })}
                                </div>
                            ) : (
                                <div className="chk-wallet-alert ok">
                                    <i className="las la-check-circle" /> {t('Your Re-purchase wallet has sufficient funds.')}
                                </div>
                            )}
                        </div>

                        {canPay ? (
                            <button type="button" className="chk-pay-btn" onClick={pay} disabled={paying}>
                                {paying ? <><i className="las la-spinner la-spin" /> {t('Processing...')}</> : <><i className="las la-lock" /> {t('Pay Now')}</>}
                            </button>
                        ) : (
                            <>
                                <button type="button" className="chk-pay-btn disabled" disabled>
                                    <i className="las la-lock" /> {t('Insufficient Re-purchase wallet')}
                                </button>
                                <p className="chk-secure mt-2" style={{ color: '#DC2626' }}>
                                    <i className="las la-info-circle" /> {t('Products can only be purchased using your Re-purchase wallet balance.')}
                                </p>
                            </>
                        )}

                        <Link href={cartUrl} className="chk-back-link">
                            <i className="las la-arrow-left" /> {t('Back to Cart')}
                        </Link>

                        <p className="chk-secure">
                            <i className="las la-shield-alt" /> {t('Your payment is secure and encrypted')}
                        </p>
                    </div>
                </div>
            </div>

            <Modal open={paying} onClose={() => undefined} staticBackdrop labelledBy="paying-title" contentClassName="pd-modal-content">
                <div className="pd-modal-header">
                    <div className="pd-modal-icon"><i className="las la-spinner" /></div>
                    <h6 className="pd-modal-title" id="paying-title">{t('Processing Payment')}</h6>
                </div>
                <div className="pd-modal-body" style={{ textAlign: 'center' }}>
                    <div className="chk-spinner" />
                    <p>{t('Please wait while we process your payment...')}</p>
                    <p style={{ fontSize: '.78rem', color: '#9CA3AF' }}>{t('Do not refresh or close this page.')}</p>
                </div>
            </Modal>
        </div>
    );
}

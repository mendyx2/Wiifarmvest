import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import ConfirmDialog from '@/Components/ConfirmDialog';
import Sku from '@/Components/Shop/Sku';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type CartLine = {
    id: number;
    name: string;
    sku: string | null;
    image: string | null;
    state: string;
    quantity: number;
    price: string;
    lineTotal: string;
    updateUrl: string;
    removeUrl: string;
};

type Props = PageProps<{
    items: CartLine[];
    total: string;
    wallet: string;
    canCheckout: boolean;
    shortfall: string | null;
    productsUrl: string;
    checkoutUrl: string;
}>;

function CartItem({ item }: { item: CartLine }) {
    const t = useTrans();
    const [busy, setBusy] = useState(false);
    const [draft, setDraft] = useState<string | null>(null);
    const [confirming, setConfirming] = useState(false);

    const save = (quantity: number) => {
        const qty = Math.min(99, Math.max(1, Math.floor(quantity) || 1));
        setDraft(null);
        if (qty === item.quantity) return;
        router.put(item.updateUrl, { quantity: qty }, {
            preserveScroll: true,
            onStart: () => setBusy(true),
            onFinish: () => setBusy(false),
            onError: () => window.notify?.('error', t('Error updating cart. Please try again.')),
        });
    };

    const remove = () => {
        router.delete(item.removeUrl, {
            preserveScroll: true,
            onStart: () => setBusy(true),
            onFinish: () => {
                setBusy(false);
                setConfirming(false);
            },
        });
    };

    return (
        <div className="cart-item" style={busy ? { opacity: 0.6 } : undefined}>
            <div className="cart-item-img">
                {item.image ? (
                    <img src={item.image} alt={item.name} />
                ) : (
                    <div className="cart-item-img-placeholder"><i className="las la-image" /></div>
                )}
            </div>
            <div className="cart-item-info">
                <p className="cart-item-name">{item.name}</p>
                <p className="cart-item-state">
                    <Sku value={item.sku} /> <i className="las la-map-marker-alt" /> {item.state}
                </p>
                <p className="cart-item-price">{item.price} <span>{t('each')}</span></p>
            </div>
            <div className="cart-item-qty">
                <button className="cart-qty-btn" type="button" disabled={busy || item.quantity <= 1} onClick={() => save(item.quantity - 1)} aria-label={t('Less')}>
                    <i className="las la-minus" />
                </button>
                <input
                    className="cart-qty-input"
                    type="number"
                    min={1}
                    max={99}
                    value={draft ?? item.quantity}
                    disabled={busy}
                    aria-label={t('Quantity')}
                    onChange={(e) => setDraft(e.target.value)}
                    onBlur={() => draft !== null && save(Number(draft))}
                    onKeyDown={(e) => e.key === 'Enter' && e.currentTarget.blur()}
                />
                <button className="cart-qty-btn" type="button" disabled={busy || item.quantity >= 99} onClick={() => save(item.quantity + 1)} aria-label={t('More')}>
                    <i className="las la-plus" />
                </button>
            </div>
            <div className="cart-item-total">{item.lineTotal}</div>
            <button className="cart-item-remove" title={t('Remove item')} aria-label={t('Remove item')} onClick={() => setConfirming(true)} disabled={busy}>
                <i className="las la-trash-alt" />
            </button>
            <ConfirmDialog
                open={confirming}
                question={t('Remove this item from your cart?')}
                onConfirm={remove}
                onCancel={() => setConfirming(false)}
                busy={busy}
            />
        </div>
    );
}

export default function Cart({ items, total, wallet, canCheckout, shortfall, productsUrl, checkoutUrl }: Props) {
    const t = useTrans();

    return (
        <div className="cart-page">
            <Head title={t('Shopping Cart')} />

            <div className="cart-header-card">
                <div className="cart-header-left">
                    <div className="cart-header-icon"><i className="las la-shopping-cart" /></div>
                    <div>
                        <h6 className="cart-header-title">{t('Shopping Cart')}</h6>
                        <p className="cart-header-sub">{t('Check the state selected before proceeding to checkout')}</p>
                    </div>
                </div>
                <Link href={productsUrl} className="cart-continue-btn">
                    <i className="las la-arrow-left" /> {t('Continue Shopping')}
                </Link>
            </div>

            {items.length === 0 ? (
                <div className="cart-empty">
                    <div className="cart-empty-icon"><i className="las la-shopping-cart" /></div>
                    <h6>{t('Your cart is empty')}</h6>
                    <p>{t("Looks like you haven't added any items to your cart yet.")}</p>
                    <Link href={productsUrl} className="cart-empty-btn">
                        <i className="las la-store" /> {t('Start Shopping')}
                    </Link>
                </div>
            ) : (
                <div className="cart-layout">
                    <div className="cart-items-card">
                        <div className="cart-items-head">
                            <i className="las la-box" /> {t('Cart Items (:count)', { count: items.length })}
                        </div>
                        {items.map((item) => (
                            <CartItem item={item} key={item.id} />
                        ))}
                    </div>

                    <div className="cart-summary-card">
                        <div className="cart-summary-head">
                            <i className="las la-receipt" /> {t('Order Summary')}
                        </div>
                        <div className="cart-summary-body">
                            <div className="cart-summary-row">
                                <span>{t('Subtotal')}</span>
                                <span>{total}</span>
                            </div>
                            <div className="cart-summary-row">
                                <span>{t('Shipping')}</span>
                                <span className="cart-free">{t('FREE')}</span>
                            </div>
                            <div className="cart-summary-divider" />
                            <div className="cart-summary-row cart-summary-total">
                                <span>{t('Total')}</span>
                                <span>{total}</span>
                            </div>

                            <div className="cart-wallet-box">
                                <div className="cart-wallet-row">
                                    <span><i className="las la-shopping-bag" /> {t('Re-purchase wallet')}</span>
                                    <span className="cart-wallet-amt">{wallet}</span>
                                </div>
                                {shortfall ? (
                                    <div className="cart-wallet-alert warn">
                                        <i className="las la-exclamation-triangle" /> {t('You need :amount more in your Re-purchase wallet', { amount: shortfall })}
                                    </div>
                                ) : (
                                    <div className="cart-wallet-alert ok">
                                        <i className="las la-check-circle" /> {t('Sufficient Re-purchase wallet balance')}
                                    </div>
                                )}
                            </div>

                            {canCheckout ? (
                                <Link href={checkoutUrl} className="cart-checkout-btn">
                                    <i className="las la-lock" /> {t('Proceed to Checkout')}
                                </Link>
                            ) : (
                                <>
                                    <button className="cart-checkout-btn disabled" disabled>
                                        <i className="las la-lock" /> {t('Insufficient Re-purchase wallet')}
                                    </button>
                                    <p className="cart-secure-note" style={{ color: '#DC2626', marginTop: '.5rem' }}>
                                        <i className="las la-info-circle" /> {t('Products are purchased using your Re-purchase wallet only.')}
                                    </p>
                                </>
                            )}

                            <p className="cart-secure-note">
                                <i className="las la-shield-alt" /> {t('Secure checkout with SSL encryption')}
                            </p>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

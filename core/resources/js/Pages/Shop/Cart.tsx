import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import ProductPrice from '@/Components/Shop/ProductPrice';
import Sku from '@/Components/Shop/Sku';
import type { Price } from '@/Components/Shop/types';
import { withPublicLayout } from '@/Layouts/PublicLayout';
import { useTrans } from '@/lib/i18n';

type Line = {
    id: number;
    name: string;
    sku: string | null;
    image: string;
    url: string;
    quantity: number;
    /** Most this line can hold (stock, up to the cart limit) */
    max: number;
    unitPrice: Price;
    lineTotal: string;
};

type Props = {
    lines: Line[];
    subtotal: string;
    currency: string;
    urls: { shop: string; checkout: string; update: string; remove: string };
};

/**
 * The public shop's cart (/shop/cart). Every change is saved at once and the
 * page's props come back with the new totals (the old page reloaded itself).
 */
export default function ShopCart({ lines, subtotal, currency, urls }: Props) {
    const t = useTrans();
    const [busy, setBusy] = useState(false);

    const send = (url: string, data: Record<string, number>) =>
        router.post(url, data, {
            preserveScroll: true,
            preserveState: true,
            onStart: () => setBusy(true),
            onFinish: () => setBusy(false),
        });

    const setQuantity = (line: Line, quantity: number) => {
        const next = Math.min(line.max, Math.max(1, Math.trunc(quantity) || 1));
        if (next !== line.quantity) send(urls.update, { product_id: line.id, quantity: next });
    };

    return (
        <div className="shop-wrap">
            <Head title={t('Your Cart')} />
            <div className="shop-container">
                <Link href={urls.shop} className="shop-back-link">
                    <i className="las la-arrow-left" /> {t('Continue Shopping')}
                </Link>
                <h2 className="shop-related-heading mt-0">{t('Your Cart')}</h2>

                {lines.length === 0 ? (
                    <div className="shop-empty">
                        <i className="las la-shopping-cart" />
                        <p>{t('Your cart is empty.')}</p>
                        <Link href={urls.shop} className="shop-buy-btn">
                            <i className="las la-store" /> {t('Browse Products')}
                        </Link>
                    </div>
                ) : (
                    <div className="shop-cart-grid">
                        <div id="shopCartItems" aria-busy={busy}>
                            {lines.map((line) => (
                                <CartRow
                                    key={line.id}
                                    line={line}
                                    disabled={busy}
                                    onQuantity={(quantity) => setQuantity(line, quantity)}
                                    onRemove={() => send(urls.remove, { product_id: line.id })}
                                />
                            ))}
                        </div>

                        <div className="shop-summary">
                            <p className="shop-summary-heading">{t('Order Summary')}</p>
                            <div className="shop-summary-row">
                                <span>{t('Subtotal')}</span>
                                <span id="shopCartSubtotal">{subtotal}</span>
                            </div>
                            <div className="shop-summary-row">
                                <span>{t('Delivery / Pickup')}</span>
                                <span>{t('At checkout')}</span>
                            </div>
                            <div className="shop-summary-total">
                                <span>{t('Total')}</span>
                                <span id="shopCartTotal">
                                    {subtotal} <small className="shop-cur">{currency}</small>
                                </span>
                            </div>
                            <Link href={urls.checkout} className="shop-checkout-btn">
                                {t('Proceed to Checkout')} <i className="las la-arrow-right" />
                            </Link>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}

ShopCart.layout = withPublicLayout;

type RowProps = {
    line: Line;
    disabled: boolean;
    onQuantity: (quantity: number) => void;
    onRemove: () => void;
};

function CartRow({ line, disabled, onQuantity, onRemove }: RowProps) {
    const t = useTrans();
    // What is typed in the box; saved on Enter or when the box loses focus.
    const [draft, setDraft] = useState(String(line.quantity));
    useEffect(() => setDraft(String(line.quantity)), [line.quantity]);

    const commit = () => {
        const value = parseInt(draft, 10);
        if (Number.isFinite(value) && value > 0) onQuantity(value);
        else setDraft(String(line.quantity));
    };

    return (
        <div className="shop-cart-item" data-cart-row data-product-id={line.id}>
            <img src={line.image} alt={line.name} />
            <div className="flex-grow-1">
                <p className="shop-cart-item-name">
                    <Link href={line.url}>{line.name}</Link>
                </p>
                <div className="pp-meta my-1">
                    <Sku value={line.sku} />
                    <span className="shop-cart-item-price">
                        <ProductPrice price={line.unitPrice} showCode={false} /> {t('each')}
                    </span>
                </div>
                <div className="shop-qty mt-2">
                    <button type="button" aria-label={t('Fewer')} disabled={disabled || line.quantity <= 1} onClick={() => onQuantity(line.quantity - 1)}>
                        &minus;
                    </button>
                    <input
                        type="number"
                        value={draft}
                        min={1}
                        max={line.max}
                        aria-label={t('Quantity of :name', { name: line.name })}
                        disabled={disabled}
                        onChange={(e) => setDraft(e.target.value)}
                        onBlur={commit}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                commit();
                            }
                        }}
                    />
                    <button type="button" aria-label={t('More')} disabled={disabled || line.quantity >= line.max} onClick={() => onQuantity(line.quantity + 1)}>
                        +
                    </button>
                </div>
            </div>
            <div className="shop-cart-item-total" data-line-total>
                {line.lineTotal}
            </div>
            <button type="button" className="shop-cart-remove" title={t('Remove')} aria-label={t('Remove :name', { name: line.name })} disabled={disabled} onClick={onRemove}>
                <i className="las la-trash" />
            </button>
        </div>
    );
}

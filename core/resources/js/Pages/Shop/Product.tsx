import { Head, Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import ProductPrice from '@/Components/Shop/ProductPrice';
import ShopCard from '@/Components/Shop/ShopCard';
import Sku from '@/Components/Shop/Sku';
import type { PublicProduct } from '@/Components/Shop/types';
import { useAddToCart } from '@/Components/Shop/useAddToCart';
import { withPublicLayout } from '@/Layouts/PublicLayout';
import { useTrans } from '@/lib/i18n';

type Props = {
    product: PublicProduct & { description: string; stock: number; maxQuantity: number };
    related: PublicProduct[];
    urls: { shop: string };
};

/** One product on the public shop (/shop/product/{id}). */
export default function ShopProduct({ product, related, urls }: Props) {
    const t = useTrans();
    const { add, busy } = useAddToCart();
    const [quantity, setQuantity] = useState(1);

    // A related product opens in this same component: start again at 1.
    useEffect(() => setQuantity(1), [product.id]);

    const clamp = (value: number) => Math.min(product.maxQuantity, Math.max(1, Number.isFinite(value) ? Math.trunc(value) : 1));

    return (
        <div className="shop-wrap">
            <Head title={product.name} />
            <div className="shop-container">
                <Link href={urls.shop} className="shop-back-link">
                    <i className="las la-arrow-left" /> {t('Back to Shop')}
                </Link>

                <div className="shop-detail">
                    <div className="shop-detail-img">
                        <img src={product.image} alt={product.name} />
                    </div>
                    <div className="shop-detail-body">
                        <div className="pp-meta">
                            <span className="shop-detail-cat">{product.category}</span>
                            <Sku value={product.sku} />
                        </div>
                        <h1 className="shop-detail-title">{product.name}</h1>
                        <div className="shop-detail-price">
                            <ProductPrice price={product.price} />
                        </div>
                        {product.description && <p className="shop-detail-desc">{product.description}</p>}

                        {product.inStock ? (
                            <>
                                <div className="shop-qty-row">
                                    <div className="shop-qty">
                                        <button type="button" aria-label={t('Fewer')} onClick={() => setQuantity((q) => clamp(q - 1))}>
                                            &minus;
                                        </button>
                                        <input
                                            type="number"
                                            id="detailQty"
                                            aria-label={t('Quantity')}
                                            value={quantity}
                                            min={1}
                                            max={product.maxQuantity}
                                            onChange={(e) => setQuantity(clamp(e.target.valueAsNumber))}
                                        />
                                        <button type="button" aria-label={t('More')} onClick={() => setQuantity((q) => clamp(q + 1))}>
                                            +
                                        </button>
                                    </div>
                                    <span className="text-muted small">{t(':count in stock', { count: product.stock })}</span>
                                </div>
                                <button type="button" className="shop-buy-btn" disabled={busy !== null} onClick={() => add(product.id, quantity)}>
                                    <i className={busy === product.id ? 'las la-spinner la-spin' : 'las la-shopping-cart'} /> {t('Add to Cart')}
                                </button>
                            </>
                        ) : (
                            <div className="text-danger fw-bold">{t('Out of stock')}</div>
                        )}
                    </div>
                </div>

                {related.length > 0 && (
                    <>
                        <h2 className="shop-related-heading">{t('You may also like')}</h2>
                        <div className="shop-grid">
                            {related.map((item) => (
                                <ShopCard key={item.id} product={item} onAdd={add} adding={busy === item.id} />
                            ))}
                        </div>
                    </>
                )}
            </div>
        </div>
    );
}

ShopProduct.layout = withPublicLayout;

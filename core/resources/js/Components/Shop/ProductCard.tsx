import { Link } from '@inertiajs/react';
import { useTrans } from '@/lib/i18n';
import ProductPrice from './ProductPrice';
import Sku from './Sku';
import type { ProductCardData } from './types';

type Props = {
    product: ProductCardData;
    /** Label of the button at the bottom ("Buy Now" on the list, "Details" for related products) */
    action: string;
    /** Opens the photo full screen (the old page used a jQuery pop-up) */
    onZoom: (src: string) => void;
};

/** One product tile, same markup as the Blade product grid. */
export default function ProductCard({ product, action, onZoom }: Props) {
    const t = useTrans();
    return (
        <div className="product-item h-100">
            <div className="product-thumb">
                <img src={product.image} alt={product.name} loading="lazy" />
                <ul className="product-options">
                    <li>
                        <a
                            className="image"
                            href={product.image}
                            aria-label={t('Enlarge photo')}
                            onClick={(e) => {
                                e.preventDefault();
                                onZoom(product.image);
                            }}
                        >
                            <i className="las la-expand" />
                        </a>
                    </li>
                </ul>
            </div>
            <div className="product-content">
                <h6 className="product-title">
                    <Link href={product.url}>{product.name}</Link>
                </h6>

                {product.inStock ? (
                    <span className="product-availablity text--success">{t('in stock')}</span>
                ) : (
                    <span className="product-availablity text--danger">{t('out stock')}</span>
                )}

                <div className="pp-meta">
                    <Sku value={product.sku} />
                </div>
                <div className="product-price">
                    <ProductPrice price={product.price} className="current-price" />
                    {product.variesByState && <span className="pp-hint">{t('depends on your state')}</span>}
                </div>

                <Link className="add-to-cart cmn--btn-2" href={product.url}>
                    {action}
                </Link>
            </div>
        </div>
    );
}

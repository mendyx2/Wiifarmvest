import { Link } from '@inertiajs/react';
import { useTrans } from '@/lib/i18n';
import ProductPrice from './ProductPrice';
import Sku from './Sku';
import type { PublicProduct } from './types';

type Props = {
    product: PublicProduct;
    onAdd: (productId: number) => void;
    adding: boolean;
};

/** One product tile on the public shop, same markup as shop/index.blade.php. */
export default function ShopCard({ product, onAdd, adding }: Props) {
    const t = useTrans();
    return (
        <div className="shop-card">
            <Link href={product.url} className="shop-card-img">
                <img src={product.image} alt={product.name} loading="lazy" />
                {product.featured && <span className="shop-card-badge">{t('Featured')}</span>}
            </Link>
            <div className="shop-card-body">
                <div className="shop-card-meta">
                    <span className="shop-card-cat">{product.category}</span>
                    <Sku value={product.sku} />
                </div>
                <h3 className="shop-card-title">
                    <Link href={product.url}>{product.name}</Link>
                </h3>
                <div className="shop-card-foot">
                    <ProductPrice price={product.price} className="shop-card-price" />
                    <button
                        type="button"
                        className="shop-add-btn"
                        disabled={!product.inStock || adding}
                        onClick={() => onAdd(product.id)}
                        aria-label={product.inStock ? t('Add :name to cart', { name: product.name }) : t('Out of stock')}
                        title={product.inStock ? t('Add to Cart') : t('Out of stock')}
                    >
                        <i className={adding ? 'las la-spinner la-spin' : 'las la-plus'} />
                    </button>
                </div>
            </div>
        </div>
    );
}

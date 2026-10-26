import { Head, Link } from '@inertiajs/react';
import Pagination from '@/Components/Pagination';
import ShopCard from '@/Components/Shop/ShopCard';
import type { PublicProduct } from '@/Components/Shop/types';
import { useAddToCart } from '@/Components/Shop/useAddToCart';
import { withPublicLayout } from '@/Layouts/PublicLayout';
import { useTrans } from '@/lib/i18n';
import type { Paginated } from '@/types';

type Props = {
    products: Paginated<PublicProduct>;
    search: string;
    urls: { shop: string };
};

/** The public shop's product list (/shop), filtered by the header's category links and search box. */
export default function ShopIndex({ products, search, urls }: Props) {
    const t = useTrans();
    const { add, busy } = useAddToCart();

    return (
        <div className="shop-wrap">
            <Head title={t('Shop')} />
            <div className="shop-container">
                {search && (
                    <p className="shop-search-note">
                        {t('Results for “:q”', { q: search })} · <Link href={urls.shop}>{t('Clear search')}</Link>
                    </p>
                )}

                {products.data.length > 0 ? (
                    <>
                        <div className="shop-grid">
                            {products.data.map((product) => (
                                <ShopCard key={product.id} product={product} onAdd={add} adding={busy === product.id} />
                            ))}
                        </div>
                        <Pagination links={products.links} className="mt-4" />
                    </>
                ) : (
                    <div className="shop-empty">
                        <i className="las la-box-open" />
                        <p>{t('No products found.')}</p>
                    </div>
                )}
            </div>
        </div>
    );
}

ShopIndex.layout = withPublicLayout;

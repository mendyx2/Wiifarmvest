import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import Lightbox from '@/Components/Lightbox';
import Pagination from '@/Components/Pagination';
import ProductCard from '@/Components/Shop/ProductCard';
import type { ProductCardData } from '@/Components/Shop/types';
import { useTrans } from '@/lib/i18n';
import type { PageProps, Paginated } from '@/types';

type Category = { id: number; name: string; url: string };

type Props = PageProps<{
    products: Paginated<ProductCardData>;
    categories: Category[];
    categoryId: number | null;
    allUrl: string;
}>;

export default function Products({ products, categories, categoryId, allUrl }: Props) {
    const t = useTrans();
    const [zoom, setZoom] = useState<string | null>(null);

    return (
        <div className="nc-wrap" id="ncWrap">
            <Head title={t('Products')} />
            <br />
            <section className="product-section padding-top padding-bottom mb-5">
                <div className="container">
                    <ul className="mr-list justify-content-center">
                        <li className="mr-list__item">
                            <Link className={`mr-list__btn ${!categoryId ? 'active' : ''}`} href={allUrl}>
                                {t('All Products')}
                            </Link>
                        </li>
                        {categories.map((category) => (
                            <li className="mr-list__item" key={category.id}>
                                <Link className={`mr-list__btn ${categoryId === category.id ? 'active' : ''}`} href={category.url}>
                                    {category.name}
                                </Link>
                            </li>
                        ))}
                    </ul>

                    <div className="row g-3 justify-content-center">
                        {products.data.map((product) => (
                            <div className="col-6 col-md-4 col-xl-3" key={product.id}>
                                <ProductCard product={product} action={t('Buy Now')} onZoom={setZoom} />
                            </div>
                        ))}
                        {products.data.length === 0 && <p className="text-center text-muted">{t('No products found')}</p>}
                    </div>

                    <Pagination links={products.links} className="pagination-wrapper" />
                </div>
            </section>
            <Lightbox src={zoom} onClose={() => setZoom(null)} />
        </div>
    );
}

import { Link, usePage } from '@inertiajs/react';
import { ConfirmButton, withId } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import SearchForm from '@/Components/Admin/SearchForm';
import Pagination from '@/Components/Pagination';
import { useTrans } from '@/lib/i18n';
import type { Paginated } from '@/types';

type Product = {
    id: number;
    name: string;
    sku: string;
    category: string | null;
    price: string;
    pv: string;
    stock: number;
    featured: boolean;
    enabled: boolean;
    image: string;
    editUrl: string;
};

type Props = {
    products: Paginated<Product>;
    search: string;
    urls: { create: string; status: string; feature: string };
};

/** Shop → Manage Product. */
export default function Products({ products, search, urls }: Props) {
    const t = useTrans();
    const url = usePage().url;

    return (
        <div className="prod-page">
            <PageHeader title={t('Products')}>
                <SearchForm url={url} value={search} placeholder="Product or category" />
                <Link className="btn btn-outline--primary h-45" href={urls.create}>
                    <i className="las la-plus" /> {t('Add Product')}
                </Link>
            </PageHeader>
            <div className="prod-table-card">
                <div className="prod-table-head">
                    <div className="prod-table-head-left">
                        <span className="prod-count-badge">{t(':n products', { n: products.total })}</span>
                    </div>
                </div>
                <div className="table-responsive">
                    <table className="table prod-table">
                        <thead>
                            <tr>
                                <th style={{ width: 56 }} aria-label={t('Image')} />
                                <th>{t('Product')}</th>
                                <th>{t('Category')}</th>
                                <th>{t('Price')}</th>
                                <th className="text-center">PV</th>
                                <th className="text-center">{t('Stock')}</th>
                                <th className="text-center">{t('Featured')}</th>
                                <th className="text-center">{t('Status')}</th>
                                <th className="text-right">{t('Actions')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {products.data.length ? (
                                products.data.map((p) => (
                                    <tr key={p.id}>
                                        <td>
                                            <img className="prod-thumb" src={p.image} alt="" loading="lazy" />
                                        </td>
                                        <td>
                                            <div className="prod-name" title={p.name}>
                                                {p.name.length > 32 ? `${p.name.slice(0, 32)}…` : p.name}
                                            </div>
                                            <div className="prod-sku-tag">SKU {p.sku}kg</div>
                                        </td>
                                        <td>
                                            <span className="prod-cat-chip">{p.category ?? '—'}</span>
                                        </td>
                                        <td>
                                            <span className="prod-price">{p.price}</span>
                                        </td>
                                        <td className="text-center">
                                            <span className="prod-metric-chip prod-metric-pv">{p.pv}</span>
                                        </td>
                                        <td className="text-center">
                                            <span className={`prod-stock-chip ${p.stock <= 0 ? 'prod-stock-out' : p.stock <= 10 ? 'prod-stock-low' : 'prod-stock-ok'}`}>{p.stock <= 0 ? t('Out') : p.stock}</span>
                                        </td>
                                        <td className="text-center">
                                            <span className={`prod-badge ${p.featured ? 'prod-badge-featured' : 'prod-badge-unfeatured'}`}>{p.featured ? t('Featured') : t('Standard')}</span>
                                        </td>
                                        <td className="text-center">
                                            <span className={`prod-badge ${p.enabled ? 'prod-badge-active' : 'prod-badge-inactive'}`}>{p.enabled ? t('Active') : t('Off')}</span>
                                        </td>
                                        <td className="text-right">
                                            <div className="prod-actions">
                                                <Link href={p.editUrl} className="prod-action-btn prod-action-edit" title={t('Edit')} aria-label={t('Edit :name', { name: p.name })}>
                                                    <i className="las la-pen" />
                                                </Link>
                                                {p.featured ? (
                                                    <ConfirmButton url={withId(urls.feature, p.id)} data={{ state: 'disable' }} question={t('Remove featured status from this product?')} className="prod-action-btn prod-action-unfeature" title={t('Unfeature')}>
                                                        <i className="las la-star" />
                                                    </ConfirmButton>
                                                ) : (
                                                    <ConfirmButton url={withId(urls.feature, p.id)} data={{ state: 'enable' }} question={t('Mark this product as featured?')} className="prod-action-btn prod-action-feature" title={t('Feature')}>
                                                        <i className="lar la-star" />
                                                    </ConfirmButton>
                                                )}
                                                {p.enabled ? (
                                                    <ConfirmButton url={withId(urls.status, p.id)} data={{ state: 'disable' }} question={t('Disable this product?')} className="prod-action-btn prod-action-disable" title={t('Disable')}>
                                                        <i className="las la-toggle-on" />
                                                    </ConfirmButton>
                                                ) : (
                                                    <ConfirmButton url={withId(urls.status, p.id)} data={{ state: 'enable' }} question={t('Enable this product?')} className="prod-action-btn prod-action-enable" title={t('Enable')}>
                                                        <i className="las la-toggle-off" />
                                                    </ConfirmButton>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={9}>
                                        <div className="prod-empty">
                                            <i className="las la-box-open" />
                                            <p>{t('No products found')}</p>
                                        </div>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                {products.last_page > 1 && (
                    <div className="prod-pagination">
                        <Pagination links={products.links} />
                    </div>
                )}
            </div>
        </div>
    );
}

import { Head, Link, router } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import Modal from '@/Components/Modal';
import Pagination from '@/Components/Pagination';
import ProductPrice from '@/Components/Shop/ProductPrice';
import Sku from '@/Components/Shop/Sku';
import type { Price } from '@/Components/Shop/types';
import { useTrans } from '@/lib/i18n';
import type { PageProps, Paginated } from '@/types';

type Product = { id: number; name: string; sku: string | null; image: string | null; description: string; price: Price; stock: number; min: number; max: number; inCart: number };

type Props = PageProps<{
    products: Paginated<Product>;
    wallet: string;
    cartCount: number;
    urls: { add: string; checkout: string; dashboard: string };
}>;

function ProductCard({ product, url, onAdded, onError }: { product: Product; url: string; onAdded: (name: string) => void; onError: (message: string) => void }) {
    const t = useTrans();
    // How many more fit: the max minus what is already in the cart.
    const room = Math.max(0, product.max - product.inCart);
    const [qty, setQty] = useState(Math.min(product.min, room) || product.min);
    const [busy, setBusy] = useState(false);
    const clamp = (n: number) => Math.min(Math.max(n || product.min, product.min), Math.max(room, product.min));

    const add = (event: FormEvent) => {
        event.preventDefault();
        router.post(url, { product_id: product.id, quantity: clamp(qty) }, {
            preserveScroll: true,
            onStart: () => setBusy(true),
            onFinish: () => setBusy(false),
            onSuccess: () => {
                setQty(product.min);
                onAdded(product.name);
            },
            onError: (errors) => onError(Object.values(errors)[0] ?? t('Add to cart failed. Please try again.')),
        });
    };

    return (
        <div className="sl-catalog-card">
            <div className="sl-catalog-img-wrap">
                {product.image ? <img src={product.image} alt={product.name} className="sl-catalog-img" /> : <div className="sl-catalog-img-placeholder"><i className="las la-box" /></div>}
            </div>
            <div className="sl-catalog-body">
                <h6 className="sl-catalog-name" title={product.name}>{product.name}</h6>
                <div className="pp-meta"><Sku value={product.sku} /></div>
                <p className="sl-catalog-desc">{product.description}</p>
                <div className="sl-catalog-divider" />
                <div className="sl-catalog-price-row">
                    <ProductPrice price={product.price} className="sl-catalog-price" />
                    <span className="sl-catalog-stock-note"><i className="las la-check-circle me-1" style={{ color: 'var(--sl-green)' }} />{t(':count in stock', { count: product.stock })}</span>
                </div>
                <div className="sl-catalog-actions">
                    {room >= product.min ? (
                        <form onSubmit={add}>
                            <div className="sl-qty-stepper mb-2">
                                <button type="button" className="sl-qty-btn" aria-label={t('Less')} onClick={() => setQty(clamp(qty - 1))}>&minus;</button>
                                <input
                                    type="number"
                                    className="sl-qty-input"
                                    aria-label={t('Quantity of :name', { name: product.name })}
                                    value={qty}
                                    min={product.min}
                                    max={room}
                                    onChange={(e) => setQty(parseInt(e.target.value, 10) || 0)}
                                    onBlur={() => setQty(clamp(qty))}
                                    required
                                />
                                <button type="button" className="sl-qty-btn" aria-label={t('More')} onClick={() => setQty(clamp(qty + 1))}>&#43;</button>
                            </div>
                            <p className="sl-field-hint text-center mb-2">
                                {t('Min :min · Max :max', { min: product.min, max: product.max })}
                                {product.inCart > 0 && <> · {t(':count in cart', { count: product.inCart })}</>}
                            </p>
                            <button type="submit" className="sl-btn sl-btn-primary w-100" disabled={busy}>
                                <i className={`las ${busy ? 'la-spinner la-spin' : 'la-cart-plus'} me-1`} /> {t('Add to Cart')}
                            </button>
                        </form>
                    ) : (
                        <button className="sl-btn sl-btn-outline w-100" disabled>
                            <i className="las la-check me-1" /> {t('Maximum already in cart')}
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
}

export default function Catalog({ products, wallet, cartCount, urls }: Props) {
    const t = useTrans();
    const [added, setAdded] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);

    return (
        <div className="nc-wrap" id="ncWrap">
            <Head title={t('Product Catalog')} />
            <br />
            <div className="sl-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div>
                    <h4 className="sl-page-title mb-1">{t('Product Catalog')}</h4>
                    <p className="sl-page-subtitle mb-0">{t('Browse and order products for your inventory.')}</p>
                </div>
                <div className="d-flex align-items-center gap-2 flex-wrap">
                    <Link href={urls.checkout} className="sl-btn sl-btn-outline position-relative">
                        <i className="las la-shopping-cart me-1" /> {t('Cart')}
                        <span className="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cartCount">{cartCount}</span>
                    </Link>
                    <span className="sl-badge-count" style={{ fontSize: '.82rem', padding: '.5rem .9rem' }}>{t('Wallet: :amount', { amount: wallet })}</span>
                    <Link href={urls.dashboard} className="sl-btn sl-btn-outline"><i className="las la-arrow-left me-1" /> {t('Back to Dashboard')}</Link>
                </div>
            </div>

            <div className="row g-3">
                {products.data.length === 0 ? (
                    <div className="col-12">
                        <div className="sl-card">
                            <div className="sl-empty-state">
                                <div className="sl-empty-icon"><i className="las la-box-open" /></div>
                                <p className="sl-empty-title">{t('No Products Available')}</p>
                                <p className="sl-empty-sub">{t('There are currently no products available for ordering.')}</p>
                            </div>
                        </div>
                    </div>
                ) : (
                    products.data.map((product) => (
                        <div className="col-6 col-md-6 col-xl-4" key={product.id}>
                            <ProductCard product={product} url={urls.add} onAdded={setAdded} onError={setError} />
                        </div>
                    ))
                )}
            </div>

            {products.last_page > 1 && (
                <div className="sl-card mt-4">
                    <div className="sl-card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <small className="text-muted">{t('Showing :from to :to of :total products', { from: products.from ?? 0, to: products.to ?? 0, total: products.total })}</small>
                        <Pagination links={products.links} />
                    </div>
                </div>
            )}

            <Modal open={added !== null} onClose={() => setAdded(null)} labelledBy="added-title">
                <div className="modal-body text-center p-4">
                    <div className="mb-3" style={{ color: 'var(--sl-green)' }}><i className="las la-check-circle" style={{ fontSize: '3.5rem' }} /></div>
                    <h4 className="mb-3" id="added-title">{t('Added to Cart!')}</h4>
                    <p className="text-muted mb-4">{t(':name has been added to your cart.', { name: added ?? '' })}</p>
                    <div className="d-grid gap-2">
                        <button type="button" className="sl-btn sl-btn-primary" onClick={() => setAdded(null)}>{t('Continue Shopping')}</button>
                        <Link href={urls.checkout} className="sl-btn sl-btn-outline">{t('Proceed to Checkout')}</Link>
                    </div>
                </div>
            </Modal>

            <Modal open={error !== null} onClose={() => setError(null)} labelledBy="add-error-title">
                <div className="modal-body text-center p-4">
                    <div className="mb-3" style={{ color: '#D97706' }}><i className="las la-exclamation-circle" style={{ fontSize: '3.5rem' }} /></div>
                    <h4 className="mb-3" id="add-error-title">{t('Not added')}</h4>
                    <p className="text-muted mb-4">{error}</p>
                    <button type="button" className="sl-btn sl-btn-primary" onClick={() => setError(null)}>{t('Continue')}</button>
                </div>
            </Modal>
        </div>
    );
}

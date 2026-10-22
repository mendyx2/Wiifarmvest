import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import Lightbox from '@/Components/Lightbox';
import Modal from '@/Components/Modal';
import ProductCard from '@/Components/Shop/ProductCard';
import ProductPrice from '@/Components/Shop/ProductPrice';
import Sku from '@/Components/Shop/Sku';
import type { Price, ProductCardData } from '@/Components/Shop/types';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type StatePrice = { id: number; name: string; price: Price };

type Props = PageProps<{
    product: {
        id: number;
        name: string;
        sku: string | null;
        category: string | null;
        categoryUrl: string;
        inStock: boolean;
        images: string[];
        keywords: string[];
        specifications: { name: string; value: string }[];
        /** Already cleaned by HTMLPurifier on the server */
        description: string;
        priceRange: Price;
    };
    states: StatePrice[];
    relates: ProductCardData[];
    share: { facebook: string; twitter: string; linkedin: string };
    cartCount: number;
    addToCartUrl: string;
    cartUrl: string;
    shapeImage: string;
}>;

/** The cart badge sits in the Blade header, outside React: update it by hand. */
function setCartBadge(count: number) {
    const badge = document.getElementById('cart-count');
    if (badge) badge.textContent = String(count);
}

export default function ProductDetail({ product, states, relates, share, addToCartUrl, cartUrl, shapeImage }: Props) {
    const t = useTrans();
    const [image, setImage] = useState(0);
    const [zoom, setZoom] = useState<string | null>(null);
    const [stateId, setStateId] = useState('');
    const [quantity, setQuantity] = useState(1);
    const [busy, setBusy] = useState(false);
    // 'added' → success dialog; a string → the reason it was not added
    const [result, setResult] = useState<'added' | string | null>(null);

    const chosen = states.find((s) => String(s.id) === stateId);
    // Until a state is chosen: the range across states; then that state's price.
    const price = chosen ? chosen.price : product.priceRange;

    const addToCart = () => {
        if (!stateId) {
            setResult(t('Please select your state first.'));
            return;
        }
        router.post(
            addToCartUrl,
            { productId: product.id, quantity, product_state_id: stateId },
            {
                preserveScroll: true,
                preserveState: true,
                onStart: () => setBusy(true),
                onFinish: () => setBusy(false),
                onSuccess: (page) => {
                    setCartBadge(page.props.cartCount as number);
                    setResult('added');
                },
                onError: (errors) => setResult(Object.values(errors)[0] ?? t('Add to cart failed. Please try again.')),
            },
        );
    };

    return (
        <div className="nc-wrap" id="ncWrap">
            <Head title={product.name} />

            <section className="product-details padding-top padding-bottom pos-rel">
                <div className="container">
                    <div className="row gy-4 gy-sm-5">
                        <div className="col-lg-5">
                            <div className="product-thumb-wrapper">
                                <div className="thumbs">
                                    <img
                                        className="zoom_img w-100"
                                        src={product.images[image]}
                                        alt={product.name}
                                        style={{ cursor: 'zoom-in' }}
                                        onClick={() => setZoom(product.images[image])}
                                    />
                                </div>
                                {product.images.length > 1 && (
                                    <div className="d-flex gap-2 mt-2 flex-wrap">
                                        {product.images.map((src, i) => (
                                            <button
                                                type="button"
                                                key={src + i}
                                                className="p-0 border-0 bg-transparent"
                                                onClick={() => setImage(i)}
                                                aria-label={t('Show photo :n', { n: i + 1 })}
                                                aria-pressed={i === image}
                                                style={{ width: 72, opacity: i === image ? 1 : 0.55 }}
                                            >
                                                <img src={src} alt="" className="w-100 rounded" />
                                            </button>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
                        <div className="col-lg-7">
                            <div className="product-info-wrapper">
                                <h3 className="title">{product.name}</h3>
                                <div className="pp-meta">
                                    <Sku value={product.sku} />
                                    {product.category && <span className="pp-cat">{product.category}</span>}
                                </div>
                                <small className="text-muted">
                                    <i className="fas fa-map-marker-alt" /> {t('Select state for specific pricing')}
                                </small>

                                <select
                                    name="product_state_id"
                                    className="form-select state-select"
                                    value={stateId}
                                    onChange={(e) => setStateId(e.target.value)}
                                    aria-label={t('Your state')}
                                >
                                    <option value="">📍 {t('Select Your State')}</option>
                                    {states.map((s) => (
                                        <option value={s.id} key={s.id}>{s.name}</option>
                                    ))}
                                </select>

                                <div className="product-price">
                                    <span className="current-price" id="statePrice">
                                        <ProductPrice price={price} />
                                    </span>
                                </div>

                                {product.inStock ? (
                                    <span className="custom--badge bg--success mt-3">{t('In Stock')}</span>
                                ) : (
                                    <span className="custom--badge bg--danger mt-3">{t('Out of Stock')}</span>
                                )}

                                <div className="add-cart-wrapper">
                                    <div className="cart-plus-minus">
                                        <button
                                            type="button"
                                            className="cart-decrease qtybutton dec border-0"
                                            onClick={() => setQuantity((q) => Math.max(1, q - 1))}
                                            aria-label={t('Less')}
                                        >
                                            <i className="las la-minus" />
                                        </button>
                                        <input className="cart-count" type="text" value={quantity} readOnly aria-label={t('Quantity')} />
                                        <button
                                            type="button"
                                            className="cart-increase qtybutton inc active border-0"
                                            onClick={() => setQuantity((q) => Math.min(99, q + 1))}
                                            aria-label={t('More')}
                                        >
                                            <i className="las la-plus" />
                                        </button>
                                    </div>
                                    <button type="button" className="cart--btn cmn--btn-2 bg--primary add-to-cart" onClick={addToCart} disabled={busy}>
                                        {busy ? <i className="las la-spinner la-spin" /> : t('Add to Cart')}
                                    </button>
                                </div>

                                <ul className="product-meta">
                                    <li className="meta-item">
                                        <h6 className="title">{t('Category')} :</h6>
                                        <Link href={product.categoryUrl}>{product.category}</Link>
                                    </li>
                                    {product.keywords.length > 0 && (
                                        <li className="meta-item">
                                            <h6 className="title">{t('Tags')} :</h6>
                                            <div>
                                                {product.keywords.map((keyword) => (
                                                    <span className="me-2" key={keyword}>{keyword}</span>
                                                ))}
                                            </div>
                                        </li>
                                    )}
                                </ul>

                                {product.specifications.length > 0 && (
                                    <div className="specifications mt-3">
                                        <h5 className="title">{t('Product Full Specifications')}</h5>
                                        <table className="specification-table">
                                            <tbody>
                                                {product.specifications.map((spec, i) => (
                                                    <tr key={i}>
                                                        <th>{spec.name}</th>
                                                        <td>{spec.value}</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
                <div className="shape shape1"><img src={shapeImage} alt="" /></div>
            </section>

            <div className="product-details section-bg pb-80">
                <div className="container">
                    <div className="product-details-wrapper">
                        <div className="description">
                            <h3 className="mb-3">{t('Description')}</h3>
                            <div dangerouslySetInnerHTML={{ __html: product.description }} />
                        </div>

                        <div className="mt-5">
                            <div className="blog-details__share d-flex align-items-center flex-wrap">
                                <ul className="social-list">
                                    <li className="social-list__item"><b>{t('Share Now :')}</b></li>
                                    <li className="social-list__item">
                                        <a className="social-list__link flex-center facebook" href={share.facebook} target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                                            <i className="fab fa-facebook-f" />
                                        </a>
                                    </li>
                                    <li className="social-list__item">
                                        <a className="social-list__link flex-center twitter" href={share.twitter} target="_blank" rel="noopener noreferrer" aria-label="X">
                                            <i className="fab fa-twitter" />
                                        </a>
                                    </li>
                                    <li className="social-list__item">
                                        <a className="social-list__link flex-center linkedin" href={share.linkedin} target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">
                                            <i className="fab fa-linkedin-in" />
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <section className="product-section padding-top padding-bottom">
                <div className="container">
                    <div className="row justify-content-center">
                        <div className="col-lg-8 col-md-10">
                            <div className="section-header text-center">
                                <h2 className="title">{t('Related Products')}</h2>
                            </div>
                        </div>
                    </div>
                    {relates.length > 0 ? (
                        <div className="row g-3 justify-content-center">
                            {relates.map((related) => (
                                <div className="col-6 col-md-4 col-xl-3" key={related.id}>
                                    <ProductCard product={related} action={t('Details')} onZoom={setZoom} />
                                </div>
                            ))}
                        </div>
                    ) : (
                        <span className="d-block w-100 text-center">{t('Related product not found')}</span>
                    )}
                </div>
            </section>

            <Lightbox src={zoom} onClose={() => setZoom(null)} />

            <Modal open={result !== null} onClose={() => setResult(null)} labelledBy="cart-result-title" contentClassName="rounded-4 shadow">
                <div className="modal-body p-5">
                    {result === 'added' ? (
                        <>
                            <h3 className="fw-bold mb-0" id="cart-result-title">{t('Confirm')}</h3>
                            <h4 className="my-0">{t('Product added Successfully')}</h4>
                        </>
                    ) : (
                        <>
                            <h3 className="fw-bold mb-0" id="cart-result-title">{t('Warning !!!')}</h3>
                            <h4 className="my-0 mt-2">{result}</h4>
                        </>
                    )}
                    <div className="mt-4 d-flex gap-2">
                        <button className="btn btn--info btn--sm" type="button" onClick={() => setResult(null)}>{t('Close')}</button>
                        <Link href={cartUrl} className="btn btn--sm btn-success">{t('View cart')}</Link>
                    </div>
                </div>
            </Modal>
        </div>
    );
}

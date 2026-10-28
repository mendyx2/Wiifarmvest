import { Head, Link, useForm } from '@inertiajs/react';
import Sku from '@/Components/Shop/Sku';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type Seller = { id: number; name: string; contact: string; phone: string | null; products: number; url: string };
type Line = { productId: number; name: string; sku: string | null; image: string; available: number; price: number; priceText: string };

type Props = PageProps<{
    state: string;
    wallet: number;
    walletText: string;
    sellers: Seller[];
    seller: { id: number; name: string } | null;
    catalogue: Line[];
    urls: { index: string; store: string };
}>;

const naira = (n: number) => '₦' + n.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export default function TransferCreate({ state, wallet, walletText, sellers, seller, catalogue, urls }: Props) {
    const t = useTrans();
    const form = useForm<{ seller_id: number | null; quantities: Record<number, number>; note: string }>({
        seller_id: seller?.id ?? null,
        quantities: {},
        note: '',
    });

    const qty = (id: number) => form.data.quantities[id] ?? 0;
    const setQty = (line: Line, value: number) =>
        form.setData('quantities', { ...form.data.quantities, [line.productId]: Math.min(Math.max(Math.floor(value) || 0, 0), line.available) });

    // Display only: the server prices the request again from the state price.
    const picked = catalogue.filter((l) => qty(l.productId) > 0);
    const units = picked.reduce((s, l) => s + qty(l.productId), 0);
    const total = picked.reduce((s, l) => s + qty(l.productId) * l.price, 0);
    const short = total > wallet + 0.004;

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        if (!units || short || form.processing) return;
        form.post(urls.store, { preserveScroll: true });
    };

    return (
        <div className="nc-wrap stx" id="ncWrap">
            <Head title={t('Buy Stock from a Mega Stockist')} />
            <br />
            <div className="sl-page-header d-flex flex-wrap align-items-center gap-3 mb-4">
                <Link href={urls.index} className="sl-back-btn" aria-label={t('Back to stock requests')}><i className="las la-arrow-left" /></Link>
                <div>
                    <h4 className="sl-page-title mb-1">{t('Buy stock from a Mega stockist')}</h4>
                    <p className="sl-page-subtitle mb-0">{t('Pay from your stockist wallet. The money is held until the Mega stockist confirms; if they decline, it comes straight back.')}</p>
                </div>
            </div>

            {sellers.length === 0 ? (
                <div className="sl-card">
                    <div className="sl-empty-state">
                        <div className="sl-empty-icon"><i className="las la-store-alt" /></div>
                        <p className="sl-empty-title">{t('No Mega stockist in your state yet')}</p>
                        <p className="sl-empty-sub">{t('Mini stockists restock only through a Mega stockist in their state. Ask admin to activate a Mega stockist in :state; you can order as soon as there is one.', { state })}</p>
                    </div>
                </div>
            ) : (
                <>
                    <p className="stx-step"><span>1</span> {t('Choose a Mega stockist in :state', { state })}</p>
                    <div className="stx-sellers mb-4">
                        {sellers.map((option) => (
                            <Link key={option.id} href={option.url} preserveScroll className={`stx-seller ${seller?.id === option.id ? 'is-selected' : ''}`} aria-current={seller?.id === option.id ? 'true' : undefined}>
                                <span className="stx-seller__icon"><i className="las la-warehouse" /></span>
                                <span className="stx-seller__body">
                                    <strong>{option.name}</strong>
                                    <small>{option.contact}{option.phone && <> &middot; {option.phone}</>}</small>
                                    <em>{option.products === 1 ? t('1 product in stock') : t(':count products in stock', { count: option.products })}</em>
                                </span>
                                <i className="las la-check-circle stx-seller__tick" />
                            </Link>
                        ))}
                    </div>

                    {seller && (
                        <>
                            <p className="stx-step"><span>2</span> {t('Choose quantities from :name', { name: seller.name })}</p>
                            {catalogue.length === 0 ? (
                                <div className="sl-card">
                                    <div className="sl-empty-state">
                                        <div className="sl-empty-icon"><i className="las la-box-open" /></div>
                                        <p className="sl-empty-title">{t('Nothing to buy right now')}</p>
                                        <p className="sl-empty-sub">{t(':name has no stock with a price for your state. Try another Mega stockist or check back later.', { name: seller.name })}</p>
                                    </div>
                                </div>
                            ) : (
                                <form className="stx-buy" onSubmit={submit}>
                                    <div className="sl-card stx-buy__list">
                                        <div className="table-responsive">
                                            <table className="sl-table" aria-label={t('Products')}>
                                                <thead>
                                                    <tr>
                                                        <th>{t('Product')}</th>
                                                        <th>{t('In stock')}</th>
                                                        <th>{t('Price')}</th>
                                                        <th>{t('Quantity')}</th>
                                                        <th className="text-end">{t('Subtotal')}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {catalogue.map((line) => {
                                                        const q = qty(line.productId);
                                                        return (
                                                            <tr className={`stx-line ${q > 0 ? 'is-picked' : ''}`} key={line.productId}>
                                                                <td data-label={t('Product')}>
                                                                    <div className="sl-table-name">
                                                                        <img className="stx-thumb" alt="" loading="lazy" src={line.image} />
                                                                        <span>{line.name}<br /><Sku value={line.sku} /></span>
                                                                    </div>
                                                                </td>
                                                                <td data-label={t('In stock')}><span className="sl-status sl-status-in-stock">{line.available.toLocaleString()}</span></td>
                                                                <td data-label={t('Price')}>{line.priceText}</td>
                                                                <td data-label={t('Quantity')}>
                                                                    <div className="sl-qty-stepper stx-qty">
                                                                        <button type="button" className="sl-qty-btn" aria-label={t('Less')} onClick={() => setQty(line, q - 1)}>&minus;</button>
                                                                        <input type="number" className="sl-qty-input" min={0} max={line.available} inputMode="numeric" aria-label={t('Quantity of :name', { name: line.name })} value={q} onChange={(e) => setQty(line, Number(e.target.value))} />
                                                                        <button type="button" className="sl-qty-btn" aria-label={t('More')} onClick={() => setQty(line, q + 1)}>+</button>
                                                                    </div>
                                                                </td>
                                                                <td data-label={t('Subtotal')} className="text-end"><strong className="stx-subtotal">{naira(q * line.price)}</strong></td>
                                                            </tr>
                                                        );
                                                    })}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <aside className="sl-card stx-summary">
                                        <div className="sl-card-header"><i className="las la-receipt" /> {t('Summary')}</div>
                                        <div className="sl-card-body">
                                            <dl className="stx-totals">
                                                <div><dt>{t('Products')}</dt><dd>{picked.length}</dd></div>
                                                <div><dt>{t('Units')}</dt><dd>{units.toLocaleString()}</dd></div>
                                                <div className="stx-totals__grand"><dt>{t('Total')}</dt><dd>{naira(total)}</dd></div>
                                                <div><dt>{t('Stockist wallet')}</dt><dd>{walletText}</dd></div>
                                                <div><dt>{t('After this request')}</dt><dd className={short ? 'is-negative' : undefined}>{naira(wallet - total)}</dd></div>
                                            </dl>
                                            {short && (
                                                <p className="sl-alert-warning stx-short"><i className="las la-exclamation-triangle" /> {t("Your wallet doesn't cover this. Lower the quantities or ask admin to top up your stockist wallet.")}</p>
                                            )}
                                            <label className="sl-label" htmlFor="stxNote">{t('Note to :name', { name: seller.name })} <small className="text-muted">{t('optional')}</small></label>
                                            <textarea className="sl-input stx-note" id="stxNote" rows={2} maxLength={500} placeholder={t("e.g. I'll pick up on Friday morning")} value={form.data.note} onChange={(e) => form.setData('note', e.target.value)} />
                                            <button type="submit" className="sl-btn sl-btn-primary w-100 justify-content-center mt-3" disabled={!units || short || form.processing}>
                                                <i className={`las ${form.processing ? 'la-spinner la-spin' : 'la-paper-plane'}`} /> {t('Pay & send request')}
                                            </button>
                                            <p className="stx-fineprint">{t('Prices are the :state stockist prices. You can cancel any time before the Mega stockist confirms.', { state })}</p>
                                        </div>
                                    </aside>
                                </form>
                            )}
                        </>
                    )}
                </>
            )}
        </div>
    );
}

import { Head, router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import Sku from '@/Components/Shop/Sku';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type Item = { productId: number; name: string; sku: string | null; image: string | null; stock: number; price: number | null; priceText: string | null };
type Welcome = { code: string; amount: number; amountText: string; username: string; fullname: string };
type Receipt = { trx: string; items: number; total: string; payment: string; rebate: string | null };

type Props = PageProps<{
    businessName: string;
    wallet: string;
    inventory: Item[];
    isMega: boolean;
    welcome: Welcome | null;
    welcomeError: string | null;
    receipt: Receipt | null;
    urls: { pos: string; checkout: string; buy: string };
}>;

const naira = (n: number) => '₦' + n.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export default function Pos({ businessName, wallet, inventory, isMega, welcome, welcomeError, receipt, urls }: Props) {
    const t = useTrans();
    // product id → quantity; the order lives in the browser until checkout.
    const [cart, setCart] = useState<Record<number, number>>({});
    const [tab, setTab] = useState<'cash' | 'welcome_pack'>(welcome ? 'welcome_pack' : 'cash');
    const [search, setSearch] = useState('');
    const [code, setCode] = useState(welcome?.code ?? '');
    const [busy, setBusy] = useState(false);
    const [showReceipt, setShowReceipt] = useState(Boolean(receipt));

    useEffect(() => setShowReceipt(Boolean(receipt)), [receipt]);
    useEffect(() => {
        if (welcomeError) window.notify?.('error', welcomeError);
    }, [welcomeError]);

    const byId = useMemo(() => Object.fromEntries(inventory.map((i) => [i.productId, i])), [inventory]);
    const lines = Object.entries(cart)
        .map(([id, qty]) => ({ item: byId[Number(id)], qty }))
        .filter((l) => l.item && l.qty > 0);
    const totalQty = lines.reduce((s, l) => s + l.qty, 0);
    const total = lines.reduce((s, l) => s + (l.item.price ?? 0) * l.qty, 0);
    const shown = inventory.filter((i) => i.name.toLowerCase().includes(search.trim().toLowerCase()));

    const setQty = (item: Item, qty: number) => setCart((c) => {
        const next = { ...c };
        const clamped = Math.min(Math.max(qty, 0), item.stock);
        if (clamped === 0) delete next[item.productId];
        else next[item.productId] = clamped;
        return next;
    });

    const verify = () => {
        const clean = code.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
        if (clean.length !== 10) {
            window.notify?.('error', t('Enter the 10-character welcome code.'));
            return;
        }
        router.get(urls.pos, { code: clean }, { preserveState: true, preserveScroll: true, only: ['welcome', 'welcomeError'], onStart: () => setBusy(true), onFinish: () => setBusy(false) });
    };
    const clearCode = () => {
        setCode('');
        router.get(urls.pos, {}, { preserveState: true, preserveScroll: true, only: ['welcome', 'welcomeError'] });
    };

    // The welcome package must cover the order exactly (the server checks this too).
    const welcomeMatches = welcome !== null && Math.abs(welcome.amount - total) < 0.005;

    const checkout = (method: 'cash' | 'welcome_pack') => {
        if (!lines.length || busy) return;
        router.post(urls.checkout, {
            payment_method: method,
            welcome_code: method === 'welcome_pack' ? welcome?.code : undefined,
            items: lines.map((l) => ({ product_id: l.item.productId, quantity: l.qty })),
        }, {
            preserveScroll: true,
            onStart: () => setBusy(true),
            onFinish: () => setBusy(false),
            onSuccess: () => {
                setCart({});
                setCode('');
                setTab('cash');
            },
            onError: (errors) => window.notify?.('error', Object.values(errors)[0] ?? t('An error occurred. Please try again.')),
        });
    };

    return (
        <div className="pos-wrap" id="posWrap">
            <Head title={t('Point of Sale')} />
            <div className="pos-left">
                <div className="pos-toolbar">
                    <div className="pos-toolbar-brand">
                        <div className="pos-toolbar-icon"><i className="las la-cash-register" /></div>
                        <div>
                            <p className="pos-toolbar-title">{t('Point of Sale')}</p>
                            <p className="pos-toolbar-sub">{businessName}</p>
                        </div>
                    </div>
                    <div className="pos-search-wrap">
                        <i className="las la-search pos-search-icon" />
                        <input type="search" className="pos-search-input" placeholder={t('Search products…')} aria-label={t('Search products')} value={search} onChange={(e) => setSearch(e.target.value)} />
                    </div>
                    <div className="pos-toolbar-meta">
                        <span className="pos-meta-pill"><i className="las la-boxes" /> {t(':count items', { count: shown.length })}</span>
                        <span className="pos-meta-pill green"><i className="las la-wallet" /> {wallet}</span>
                    </div>
                </div>

                <div className="pos-grid-wrap">
                    {inventory.length === 0 ? (
                        <div className="pos-empty-state">
                            <div className="pos-empty-icon"><i className="las la-box-open" /></div>
                            <p className="pos-empty-title">{t('No inventory available')}</p>
                            {isMega ? (
                                <p className="pos-empty-sub">{t('Your stock is empty. Order products from the company to begin selling.')}</p>
                            ) : (
                                <p className="pos-empty-sub">{t('Your stock is empty.')} <a href={urls.buy}>{t('Buy from a Mega stockist')}</a> {t('to begin selling.')}</p>
                            )}
                        </div>
                    ) : (
                        shown.map((item) => {
                            const inCart = cart[item.productId] ?? 0;
                            const left = item.stock - inCart;
                            return (
                                <div className={`pos-card ${left <= 0 ? 'pos-card--out' : ''}`} key={item.productId}>
                                    <div className={`pos-card-stock ${left <= 5 ? 'pos-card-stock--low' : ''}`}>{t(':count left', { count: left })}</div>
                                    <div className="pos-card-img-wrap">
                                        {item.image ? <img src={item.image} alt="" className="pos-card-img" /> : <div className="pos-card-img-placeholder"><i className="las la-box-open" /></div>}
                                    </div>
                                    <div className="pos-card-body">
                                        <p className="pos-card-name">{item.name}</p>
                                        <div className="pp-meta my-1"><Sku value={item.sku} /></div>
                                        <p className="pos-card-price">{item.priceText ?? <span className="text-danger" style={{ fontSize: '.75rem' }}>{t('No price for your state')}</span>}</p>
                                    </div>
                                    <button
                                        type="button"
                                        className="pos-card-add"
                                        aria-label={t('Add :name', { name: item.name })}
                                        disabled={left <= 0 || item.price === null}
                                        onClick={() => setQty(item, inCart + 1)}
                                    >
                                        <i className="las la-plus" />
                                    </button>
                                </div>
                            );
                        })
                    )}
                </div>
            </div>

            <div className="pos-right">
                <div className="pos-cart-header">
                    <div className="pos-cart-header-left">
                        <span className="pos-cart-header-icon"><i className="las la-shopping-basket" /></span>
                        <span className="pos-cart-header-label">{t('Current Order')}</span>
                    </div>
                    <button type="button" className="pos-cart-clear-btn" title={t('Clear order')} aria-label={t('Clear order')} onClick={() => setCart({})} disabled={!lines.length}>
                        <i className="las la-trash-alt" />
                    </button>
                </div>

                <div className="pos-cart-list" aria-live="polite">
                    {lines.map(({ item, qty }) => (
                        <div className="pos-cart-item" key={item.productId}>
                            <div className="pos-ci-info">
                                <p className="pos-ci-name">{item.name}</p>
                                <p className="pos-ci-price"><Sku value={item.sku} /> {t(':price each', { price: item.priceText ?? '' })}</p>
                            </div>
                            <div className="pos-ci-controls">
                                <button type="button" className="pos-ci-btn" aria-label={t('Less :name', { name: item.name })} onClick={() => setQty(item, qty - 1)}><i className="las la-minus" /></button>
                                <span className="pos-ci-qty">{qty}</span>
                                <button type="button" className="pos-ci-btn" aria-label={t('More :name', { name: item.name })} disabled={qty >= item.stock} onClick={() => setQty(item, qty + 1)}><i className="las la-plus" /></button>
                            </div>
                            <div className="pos-ci-right">
                                <p className="pos-ci-subtotal">{naira((item.price ?? 0) * qty)}</p>
                                <button type="button" className="pos-ci-remove" aria-label={t('Remove :name', { name: item.name })} onClick={() => setQty(item, 0)}><i className="las la-times" /></button>
                            </div>
                        </div>
                    ))}
                    {lines.length === 0 && (
                        <div className="pos-cart-empty">
                            <div className="pos-cart-empty-icon"><i className="las la-shopping-basket" /></div>
                            <p>{t('Add products from the left panel to begin an order')}</p>
                        </div>
                    )}
                </div>

                <div className="pos-cart-footer">
                    <div className="pos-totals">
                        <div className="pos-totals-row"><span>{t('Items')}</span><span>{totalQty}</span></div>
                        <div className="pos-totals-row pos-totals-row--total"><span>{t('Total')}</span><span>{naira(total)}</span></div>
                    </div>

                    <div className="pos-pay-tabs" role="tablist" aria-label={t('Payment method')}>
                        <button type="button" role="tab" aria-selected={tab === 'cash'} className={`pos-pay-tab ${tab === 'cash' ? 'active' : ''}`} onClick={() => setTab('cash')}>
                            <i className="las la-money-bill-wave" /> {t('Cash')}
                        </button>
                        <button type="button" role="tab" aria-selected={tab === 'welcome_pack'} className={`pos-pay-tab ${tab === 'welcome_pack' ? 'active' : ''}`} onClick={() => setTab('welcome_pack')}>
                            <i className="las la-gift" /> {t('Welcome Code')}
                        </button>
                    </div>

                    {tab === 'cash' ? (
                        <div className="pos-pay-panel" role="tabpanel">
                            <button type="button" className="pos-checkout-btn pos-checkout-btn--cash" disabled={!lines.length || busy} onClick={() => checkout('cash')}>
                                <i className={`las ${busy ? 'la-spinner la-spin' : 'la-check-circle'}`} />
                                <span>{busy ? t('Processing…') : lines.length ? t('Process Cash Sale — :amount', { amount: naira(total) }) : t('Select products to checkout')}</span>
                            </button>
                            <p className="pos-pay-note"><i className="las la-info-circle" /> {t('Collect cash from customer and press to confirm')}</p>
                        </div>
                    ) : (
                        <div className="pos-pay-panel" role="tabpanel">
                            {!welcome ? (
                                <div className="pos-code-entry">
                                    <div className="pos-code-field-wrap">
                                        <input
                                            type="text"
                                            className="pos-code-input"
                                            placeholder={t('Enter 10-char code')}
                                            aria-label={t('Welcome package code')}
                                            autoComplete="off"
                                            spellCheck={false}
                                            value={code}
                                            onChange={(e) => setCode(e.target.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 10))}
                                            onKeyDown={(e) => e.key === 'Enter' && verify()}
                                        />
                                        <button type="button" className="pos-code-verify-btn" aria-label={t('Verify code')} onClick={verify} disabled={busy}>
                                            <i className={`las ${busy ? 'la-spinner la-spin' : 'la-search-plus'}`} />
                                        </button>
                                    </div>
                                    <p className="pos-pay-note"><i className="las la-info-circle" /> {t('Ask customer for their welcome package code')}</p>
                                </div>
                            ) : (
                                <div className="pos-code-verified">
                                    <div className="pos-verified-badge">
                                        <i className="las la-check-circle" />
                                        <div>
                                            <p className="pos-verified-name">{welcome.fullname || '—'} (@{welcome.username})</p>
                                            <p className="pos-verified-meta">{t('Package value: :amount', { amount: welcome.amountText })}</p>
                                        </div>
                                        <button type="button" className="pos-verified-clear" title={t('Use different code')} aria-label={t('Use different code')} onClick={clearCode}><i className="las la-times" /></button>
                                    </div>
                                    {lines.length > 0 && !welcomeMatches && (
                                        <p className="pos-pay-note" style={{ color: '#D97706' }}>
                                            <i className="las la-exclamation-triangle" />{' '}
                                            {total < welcome.amount
                                                ? t('Add :amount more to match the package value.', { amount: naira(welcome.amount - total) })
                                                : t('Remove :amount to match the package value.', { amount: naira(total - welcome.amount) })}
                                        </p>
                                    )}
                                    <button type="button" className="pos-checkout-btn pos-checkout-btn--code" disabled={!welcomeMatches || busy} onClick={() => checkout('welcome_pack')}>
                                        <i className={`las ${busy ? 'la-spinner la-spin' : 'la-gift'}`} />
                                        <span>{busy ? t('Processing…') : welcomeMatches ? t('Charge Package — :amount', { amount: naira(total) }) : t('Confirm & Charge Package')}</span>
                                    </button>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>

            {showReceipt && receipt && (
                <div className="pos-receipt-overlay" role="dialog" aria-modal="true" aria-labelledby="receipt-title">
                    <div className="pos-receipt-card">
                        <div className="pos-receipt-icon-wrap">
                            <div className="pos-receipt-icon"><i className="las la-check-double" /></div>
                            <div className="pos-receipt-rings"><div className="pos-receipt-ring pos-receipt-ring-1" /><div className="pos-receipt-ring pos-receipt-ring-2" /></div>
                        </div>
                        <h4 className="pos-receipt-title" id="receipt-title">{t('Sale Complete!')}</h4>
                        <p className="pos-receipt-trx">TRX: {receipt.trx}</p>
                        <div className="pos-receipt-detail-grid">
                            <div className="pos-rd-row"><span>{t('Items Sold')}</span><strong>{receipt.items}</strong></div>
                            <div className="pos-rd-row"><span>{t('Sale Total')}</span><strong>{receipt.total}</strong></div>
                            <div className="pos-rd-row"><span>{t('Payment')}</span><strong>{t(receipt.payment)}</strong></div>
                            {receipt.rebate && <div className="pos-receipt-credit"><i className="las la-wallet" /> {t(':amount credited to stockist rebate', { amount: receipt.rebate })}</div>}
                        </div>
                        <button type="button" className="pos-receipt-close-btn" onClick={() => setShowReceipt(false)} autoFocus>
                            <i className="las la-plus-circle" /> {t('New Order')}
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}

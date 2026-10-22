import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import ConfirmDialog from '@/Components/ConfirmDialog';
import Modal from '@/Components/Modal';
import Sku from '@/Components/Shop/Sku';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type Line = { productId: number; name: string; sku: string | null; image: string | null; price: string; quantity: number; total: string; min: number; max: number; problem: string | null };

type Props = PageProps<{
    lines: Line[];
    total: string;
    wallet: string;
    shortfall: string | null;
    canPlace: boolean;
    urls: { update: string; place: string; catalog: string };
}>;

function CartRow({ line, url }: { line: Line; url: string }) {
    const t = useTrans();
    const [busy, setBusy] = useState(false);
    const [draft, setDraft] = useState<string | null>(null);
    const [confirm, setConfirm] = useState(false);

    const send = (quantity: number) => {
        router.post(url, { product_id: line.productId, quantity }, {
            preserveScroll: true,
            onStart: () => setBusy(true),
            onFinish: () => {
                setBusy(false);
                setConfirm(false);
            },
            onError: (errors) => window.notify?.('error', Object.values(errors)[0] ?? t('Error updating quantity. Please try again.')),
        });
    };
    const save = (value: number) => {
        setDraft(null);
        const qty = Math.min(Math.max(value || line.min, line.min), Math.max(line.max, line.min));
        if (qty !== line.quantity) send(qty);
    };

    return (
        <tr style={busy ? { opacity: 0.6 } : undefined}>
            <td data-label={t('Product')}>
                <div className="sl-table-name">
                    {line.image ? <img src={line.image} alt="" className="sl-table-avatar" style={{ objectFit: 'cover' }} /> : <div className="sl-table-avatar" style={{ background: '#F3F4F6', color: '#9CA3AF' }}><i className="las la-box" /></div>}
                    <div>
                        <p className="fw-600 mb-0">{line.name}</p>
                        <Sku value={line.sku} />
                        {line.problem && <small className="d-block" style={{ color: '#DC2626' }}><i className="las la-exclamation-triangle me-1" />{line.problem}</small>}
                    </div>
                </div>
            </td>
            <td data-label={t('Price')}>{line.price}</td>
            <td data-label={t('Quantity')}>
                <div className="sl-qty-stepper" style={{ maxWidth: 120 }}>
                    <button type="button" className="sl-qty-btn" aria-label={t('Less')} disabled={busy || line.quantity <= line.min} onClick={() => save(line.quantity - 1)}>&minus;</button>
                    <input
                        type="number"
                        className="sl-qty-input"
                        aria-label={t('Quantity of :name', { name: line.name })}
                        min={line.min}
                        max={line.max}
                        disabled={busy}
                        value={draft ?? line.quantity}
                        onChange={(e) => setDraft(e.target.value)}
                        onBlur={() => draft !== null && save(parseInt(draft, 10))}
                        onKeyDown={(e) => e.key === 'Enter' && e.currentTarget.blur()}
                    />
                    <button type="button" className="sl-qty-btn" aria-label={t('More')} disabled={busy || line.quantity >= line.max} onClick={() => save(line.quantity + 1)}>&#43;</button>
                </div>
            </td>
            <td data-label={t('Total')} className="fw-700" style={{ color: 'var(--sl-green)' }}>{line.total}</td>
            <td data-label="">
                <button type="button" className="sl-btn sl-btn-outline" style={{ color: '#DC2626', borderColor: '#FECACA' }} title={t('Remove')} aria-label={t('Remove :name', { name: line.name })} onClick={() => setConfirm(true)} disabled={busy}>
                    <i className="las la-trash" />
                </button>
                <ConfirmDialog open={confirm} question={t('Are you sure you want to remove this item from your cart?')} onConfirm={() => send(0)} onCancel={() => setConfirm(false)} busy={busy} />
            </td>
        </tr>
    );
}

export default function StockistCheckout({ lines, total, wallet, shortfall, canPlace, urls }: Props) {
    const t = useTrans();
    const form = useForm({ notes: '' });

    const place = () => {
        if (form.processing) return;
        form.post(urls.place, { preserveScroll: true });
    };

    return (
        <div className="nc-wrap" id="ncWrap">
            <Head title={t('Checkout')} />
            <br />
            <div className="sl-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div>
                    <h4 className="sl-page-title mb-1">{t('Checkout')}</h4>
                    <p className="sl-page-subtitle mb-0">{t('Review your order and complete purchase.')}</p>
                </div>
                <Link href={urls.catalog} className="sl-btn sl-btn-outline"><i className="las la-arrow-left me-1" /> {t('Back to Catalog')}</Link>
            </div>

            {lines.length === 0 ? (
                <div className="sl-card">
                    <div className="sl-empty-state">
                        <div className="sl-empty-icon"><i className="las la-shopping-cart" /></div>
                        <p className="sl-empty-title">{t('Your Cart is Empty')}</p>
                        <p className="sl-empty-sub">{t('Add some products to your cart before checking out.')}</p>
                        <Link href={urls.catalog} className="sl-btn sl-btn-primary mt-3"><i className="las la-shopping-cart me-1" /> {t('Browse Products')}</Link>
                    </div>
                </div>
            ) : (
                <div className="row g-4">
                    <div className="col-12 col-lg-8">
                        <div className="sl-card mb-4">
                            <div className="sl-card-header"><i className="las la-shopping-bag me-1" /> {t('Order Summary')}</div>
                            <div className="sl-card-body p-0">
                                <div className="table-responsive">
                                    <table className="sl-table" aria-label={t('Cart items')}>
                                        <thead>
                                            <tr>
                                                <th>{t('Product')}</th>
                                                <th>{t('Price')}</th>
                                                <th>{t('Quantity')}</th>
                                                <th>{t('Total')}</th>
                                                <th><span className="visually-hidden">{t('Remove')}</span></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {lines.map((line) => <CartRow line={line} url={urls.update} key={line.productId} />)}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div className="sl-card">
                            <div className="sl-card-header"><label htmlFor="orderNotes" className="mb-0"><i className="las la-sticky-note me-1" /> {t('Order Notes (optional)')}</label></div>
                            <div className="sl-card-body">
                                <textarea className="sl-input" id="orderNotes" rows={3} maxLength={1000} value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)} placeholder={t('Add any special instructions or notes for your order…')} />
                            </div>
                        </div>
                    </div>

                    <div className="col-12 col-lg-4">
                        <div className="sl-preview-card sticky-lg-top" style={{ top: 100 }}>
                            <div className="sl-preview-header"><i className="las la-receipt me-2" />{t('Order Total')}</div>
                            <div className="sl-preview-body">
                                <div className="sl-preview-row"><span>{t('Subtotal')}</span><strong>{total}</strong></div>
                                <div className="sl-preview-row"><span>{t('Shipping')}</span><strong style={{ color: 'var(--sl-green)' }}>{t('FREE')}</strong></div>
                                <div className="sl-preview-divider" />
                                <div className="sl-preview-row sl-preview-total"><span>{t('Total')}</span><strong>{total}</strong></div>
                                <div className="d-flex justify-content-between align-items-center mt-4 mb-2">
                                    <span className="fw-600" style={{ fontSize: '.85rem' }}><i className="las la-wallet me-1" style={{ color: 'var(--sl-green)' }} />{t('Wallet Balance')}</span>
                                    <strong style={{ color: 'var(--sl-green)' }}>{wallet}</strong>
                                </div>
                                {shortfall ? (
                                    <div className="alert sl-alert-warning" style={{ fontSize: '.8rem' }}>
                                        <i className="las la-exclamation-triangle me-1" />{t('Insufficient balance. You need :amount more.', { amount: shortfall })}
                                        <br />
                                        {t('Your stockist wallet is credited when you redeem customer invoices.')}
                                    </div>
                                ) : (
                                    <div className="alert sl-alert-success" style={{ fontSize: '.8rem' }}><i className="las la-check-circle me-1" />{t('Sufficient balance available')}</div>
                                )}
                                <div className="d-grid mt-3">
                                    <button type="button" className="sl-btn sl-btn-primary" style={{ padding: '.75rem 1rem', fontSize: '.9rem' }} onClick={place} disabled={!canPlace || form.processing}>
                                        <i className={`las ${form.processing ? 'la-spinner la-spin' : canPlace ? 'la-check-circle' : 'la-lock'} me-1`} />
                                        {shortfall ? t('Insufficient Funds') : t('Place Order')}
                                    </button>
                                </div>
                                <p className="text-center text-muted mt-3 mb-0" style={{ fontSize: '.72rem' }}><i className="las la-shield-alt me-1" />{t('Secure payment · Funds deducted from wallet')}</p>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            <Modal open={form.processing} onClose={() => undefined} staticBackdrop labelledBy="placing-title">
                <div className="modal-body text-center p-4">
                    <div className="spinner-border mb-3" style={{ width: '3rem', height: '3rem', color: 'var(--sl-green)' }} role="status" />
                    <h5 id="placing-title">{t('Processing Order…')}</h5>
                    <p className="text-muted mb-0">{t('Please wait while we process your order')}</p>
                </div>
            </Modal>
        </div>
    );
}

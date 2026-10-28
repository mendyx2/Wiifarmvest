import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState, type FormEvent } from 'react';
import Modal from '@/Components/Modal';
import Sku from '@/Components/Shop/Sku';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type InvoiceItem = { productId: number; name: string; sku: string | null; ordered: number; redeemed: number; remaining: number };

type Props = PageProps<{
    fullname: string;
    stats: { rebate: string; wallet: string; today: number; total: string };
    recent: { id: number; invoice: string; customer: string; amount: string; date: string }[];
    /** The code in ?invoice=, if one was looked up */
    code: string;
    invoice: { code: string; customer: string; items: InvoiceItem[] } | null;
    invoiceError: string | null;
    dashboardUrl: string;
    processUrl: string;
    historyUrl: string;
}>;

export default function StockistDashboard({ fullname, stats, recent, code, invoice, invoiceError, dashboardUrl, processUrl, historyUrl }: Props) {
    const t = useTrans();
    const [query, setQuery] = useState(code);
    const [qty, setQty] = useState<Record<number, string>>({});
    const [notes, setNotes] = useState('');
    const [busy, setBusy] = useState(false);
    const [errorOpen, setErrorOpen] = useState(Boolean(invoiceError));
    const input = useRef<HTMLInputElement>(null);

    // Each time an invoice (re)loads, start the redeem quantities at "everything remaining".
    useEffect(() => {
        setQty(Object.fromEntries((invoice?.items ?? []).map((i) => [i.productId, String(i.remaining)])));
        setNotes('');
    }, [invoice]);
    useEffect(() => setErrorOpen(Boolean(invoiceError)), [invoiceError]);

    const verify = (event: FormEvent) => {
        event.preventDefault();
        if (!query.trim()) return;
        router.get(dashboardUrl, { invoice: query.trim() }, { preserveState: true, preserveScroll: true, onStart: () => setBusy(true), onFinish: () => setBusy(false) });
    };

    const clear = () => {
        setQuery('');
        router.get(dashboardUrl, {}, { preserveState: true, preserveScroll: true });
        input.current?.focus();
    };

    const process = (event: FormEvent) => {
        event.preventDefault();
        if (!invoice) return;
        const items = Object.fromEntries(invoice.items.map((i) => [i.productId, Math.min(i.remaining, Math.max(0, parseInt(qty[i.productId] ?? '0', 10) || 0))]));
        router.post(processUrl, { invoice_code: invoice.code, redeemed_items: items, notes }, {
            preserveScroll: true,
            onStart: () => setBusy(true),
            onFinish: () => setBusy(false),
        });
    };

    const anyToRedeem = invoice?.items.some((i) => (parseInt(qty[i.productId] ?? '0', 10) || 0) > 0) ?? false;
    const statCards = [
        { tone: 'primary', icon: 'la-file-invoice', label: t('Stockist Rebate'), value: stats.rebate },
        { tone: 'info', icon: 'la-receipt', label: t('Stockist wallet'), value: stats.wallet },
        { tone: 'gold', icon: 'la-calendar-day', label: t("Today's Redemptions"), value: String(stats.today) },
        { tone: 'success', icon: 'la-coins', label: t('Total Processed'), value: stats.total },
    ];

    return (
        <div className="nc-wrap" id="ncWrap">
            <Head title={t('Stockist Dashboard')} />
            <br />
            <div className="sl-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div>
                    <h4 className="sl-page-title mb-1">{t('Stockist Dashboard')}</h4>
                    <p className="sl-page-subtitle mb-0">{t('Welcome back, :name.', { name: fullname })}</p>
                </div>
                <div className="d-flex align-items-center gap-2">
                    <span className="sl-status sl-status-active">{t('Active Stockist')}</span>
                    <Link href={historyUrl} className="sl-btn sl-btn-outline"><i className="las la-history me-1" /> {t('Redemption History')}</Link>
                </div>
            </div>

            <div className="row g-3 mb-4">
                {statCards.map((card) => (
                    <div className="col-6 col-sm-6 col-xl-3" key={card.label}>
                        <div className={`sl-stat-card sl-stat-${card.tone}`}>
                            <div className="sl-stat-icon"><i className={`las ${card.icon}`} /></div>
                            <div className="sl-stat-body">
                                <p className="sl-stat-label">{card.label}</p>
                                <h3 className="sl-stat-value">{card.value}</h3>
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            <div className="row g-4">
                <div className="col-12 col-lg-8">
                    <div className="sl-card mb-4">
                        <div className="sl-card-header"><i className="las la-qrcode me-1" /> {t('Redeem an Invoice')}</div>
                        <div className="sl-card-body">
                            <form onSubmit={verify}>
                                <label className="sl-label" htmlFor="invoiceCodeInput">{t('Invoice Code')}</label>
                                <div className="d-flex gap-2 flex-wrap">
                                    <input
                                        ref={input}
                                        type="text"
                                        id="invoiceCodeInput"
                                        className="sl-input"
                                        style={{ flex: 1, minWidth: 220 }}
                                        placeholder={t('Enter invoice code')}
                                        value={query}
                                        onChange={(e) => setQuery(e.target.value)}
                                        required
                                        autoComplete="off"
                                    />
                                    <button type="submit" className="sl-btn sl-btn-primary" disabled={busy}>
                                        <i className="las la-search me-1" /> {t('Verify Invoice')}
                                    </button>
                                </div>
                            </form>

                            {invoice && (
                                <div className="mt-4">
                                    <div className="sl-card" style={{ borderColor: 'var(--sl-green)' }}>
                                        <div className="sl-card-header" style={{ background: 'var(--sl-green-lt)', color: 'var(--sl-green)' }}>
                                            <i className="las la-check-circle me-1" /> {t('Invoice Verified')}: {invoice.code}
                                            {invoice.customer && <span className="ms-2 fw-normal">({invoice.customer})</span>}
                                        </div>
                                        <div className="sl-card-body">
                                            <form onSubmit={process} className="mt-2">
                                                <h6 className="fw-600 mb-3">{t('Order Items')}</h6>
                                                <div className="table-responsive">
                                                    <table className="sl-table">
                                                        <thead>
                                                            <tr>
                                                                <th>{t('Product')}</th>
                                                                <th>{t('Original Qty')}</th>
                                                                <th>{t('Redeemed')}</th>
                                                                <th>{t('Remaining')}</th>
                                                                <th>{t('Redeem Qty')}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            {invoice.items.map((item) => (
                                                                <tr key={item.productId}>
                                                                    <td data-label={t('Product')}>{item.name} <Sku value={item.sku} /></td>
                                                                    <td data-label={t('Original Qty')}>{item.ordered}</td>
                                                                    <td data-label={t('Redeemed')}>{item.redeemed}</td>
                                                                    <td data-label={t('Remaining')}>{item.remaining}</td>
                                                                    <td data-label={t('Redeem Qty')}>
                                                                        <input
                                                                            type="number"
                                                                            className="sl-input"
                                                                            aria-label={t('Quantity to hand over for :name', { name: item.name })}
                                                                            min={0}
                                                                            max={item.remaining}
                                                                            disabled={item.remaining === 0}
                                                                            value={qty[item.productId] ?? '0'}
                                                                            onChange={(e) => setQty({ ...qty, [item.productId]: e.target.value })}
                                                                            style={{ width: 90, padding: '.4rem .6rem' }}
                                                                        />
                                                                    </td>
                                                                </tr>
                                                            ))}
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div className="mt-3">
                                                    <label className="sl-label" htmlFor="redemptionNotes">{t('Notes (optional)')}</label>
                                                    <textarea className="sl-input" id="redemptionNotes" rows={2} value={notes} onChange={(e) => setNotes(e.target.value)} placeholder={t('Add any notes about this redemption…')} />
                                                </div>
                                                <div className="sl-form-actions mt-3" style={{ justifyContent: 'flex-end' }}>
                                                    <button type="submit" className="sl-btn sl-btn-primary" disabled={busy || !anyToRedeem}>
                                                        {busy ? <i className="las la-spinner la-spin me-1" /> : <i className="las la-check me-1" />} {t('Process Redemption')}
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="sl-card">
                        <div className="sl-card-header d-flex justify-content-between align-items-center">
                            <span><i className="las la-history me-1" /> {t('Recent Redemptions')}</span>
                        </div>
                        <div className="sl-card-body p-0">
                            {recent.length > 0 ? (
                                <>
                                    <div className="table-responsive">
                                        <table className="sl-table" aria-label={t('Recent redemptions')}>
                                            <thead>
                                                <tr>
                                                    <th>{t('Invoice')}</th>
                                                    <th>{t('Customer')}</th>
                                                    <th>{t('Amount')}</th>
                                                    <th>{t('Date')}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {recent.map((r) => (
                                                    <tr key={r.id}>
                                                        <td data-label={t('Invoice')}><span className="fw-600">{r.invoice}</span></td>
                                                        <td data-label={t('Customer')}>{r.customer}</td>
                                                        <td data-label={t('Amount')} className="fw-600">{r.amount}</td>
                                                        <td data-label={t('Date')}>{r.date}</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                    <div className="text-center py-3">
                                        <Link href={historyUrl} className="sl-action-btn">{t('View Full History')} <i className="las la-arrow-right ms-1" /></Link>
                                    </div>
                                </>
                            ) : (
                                <div className="sl-empty-state">
                                    <div className="sl-empty-icon"><i className="las la-receipt" /></div>
                                    <p className="sl-empty-title">{t('No redemptions yet')}</p>
                                    <p className="sl-empty-sub">{t('Verified redemptions you process will show up here.')}</p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                <div className="col-12 col-lg-4">
                    <div className="sl-card mb-4">
                        <div className="sl-card-header"><i className="las la-bolt me-1" /> {t('Quick Actions')}</div>
                        <div className="sl-card-body d-grid gap-2">
                            <Link href={historyUrl} className="sl-btn sl-btn-outline"><i className="las la-history me-1" /> {t('Redemption History')}</Link>
                            <button type="button" className="sl-btn sl-btn-outline" onClick={clear}><i className="las la-broom me-1" /> {t('Clear Form')}</button>
                        </div>
                    </div>

                    <div className="sl-card">
                        <div className="sl-card-header"><i className="las la-info-circle me-1" /> {t('Redemption Guide')}</div>
                        <div className="sl-card-body">
                            <ol className="ps-3 mb-3" style={{ fontSize: '.85rem', color: 'var(--bk-text)' }}>
                                <li className="mb-2">{t("Enter the customer's invoice code")}</li>
                                <li className="mb-2">{t('Verify the invoice details')}</li>
                                <li className="mb-2">{t('Adjust quantities based on available stock')}</li>
                                <li className="mb-2">{t('Process partial or full redemption')}</li>
                                <li className="mb-0">{t('Provide remaining items to customer')}</li>
                            </ol>
                            <div className="alert sl-alert-warning mb-0" style={{ fontSize: '.8rem' }}>
                                <i className="las la-exclamation-triangle me-1" /> {t('Customers can redeem remaining items at other stockists')}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <Modal open={errorOpen} onClose={() => setErrorOpen(false)} labelledBy="invoice-error-title">
                <div className="modal-body text-center p-4">
                    <div className="mb-3" style={{ color: '#D97706' }}><i className="las la-exclamation-circle" style={{ fontSize: '3.5rem' }} /></div>
                    <h4 className="mb-3" id="invoice-error-title">{t('Note')}</h4>
                    <p className="text-muted mb-4">{invoiceError}</p>
                    <button type="button" className="sl-btn sl-btn-primary" onClick={() => setErrorOpen(false)}>{t('Continue')}</button>
                </div>
            </Modal>
        </div>
    );
}

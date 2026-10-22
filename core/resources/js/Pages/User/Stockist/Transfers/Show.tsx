import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import ConfirmDialog from '@/Components/ConfirmDialog';
import Sku from '@/Components/Shop/Sku';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type Party = { name: string; contact: string; phone: string | null };
type Item = { id: number; name: string; sku: string | null; image: string | null; quantity: number; price: string; total: string; onHand: number | null; stockBefore: number | null; stockAfter: number | null };

type Props = PageProps<{
    isSeller: boolean;
    transfer: {
        id: number;
        code: string;
        date: string;
        pending: boolean;
        completed: boolean;
        statusLabel: string;
        statusClass: string;
        units: number;
        total: string;
        rebateEarned: string;
        sellerAfter: string;
        buyerAfter: string;
        trx: string;
        buyerNote: string | null;
        decisionNote: string | null;
        closedBy: string | null;
        closedAt: string | null;
        buyer: Party;
        seller: Party;
        items: Item[];
    };
    rebate: string | null;
    urls: { index: string; confirm: string; decline: string; cancel: string };
}>;

export default function TransferShow({ isSeller, transfer, rebate, urls }: Props) {
    const t = useTrans();
    const [asking, setAsking] = useState<'confirm' | 'cancel' | null>(null);
    const [busy, setBusy] = useState(false);
    const decline = useForm({ reason: '' });
    const { pending, completed, buyer, seller } = transfer;
    const short = isSeller && pending ? transfer.items.filter((i) => (i.onHand ?? 0) < i.quantity) : [];
    const extraCols = (isSeller && pending ? 1 : 0) + (completed ? 1 : 0);

    const act = (url: string) =>
        router.post(url, {}, {
            preserveScroll: true,
            onStart: () => setBusy(true),
            onFinish: () => {
                setBusy(false);
                setAsking(null);
            },
        });

    return (
        <div className="nc-wrap stx" id="ncWrap">
            <Head title={t('Stock Request :code', { code: transfer.code })} />
            <br />
            <div className="sl-page-header d-flex flex-wrap align-items-center gap-3 mb-4">
                <Link href={urls.index} className="sl-back-btn" aria-label={t('Back to stock requests')}><i className="las la-arrow-left" /></Link>
                <div className="flex-grow-1">
                    <h4 className="sl-page-title mb-1">
                        {t('Stock request')} <span className="stx-code">{transfer.code}</span>{' '}
                        <span className={`sl-status ${transfer.statusClass} ms-1 align-middle`}>{t(transfer.statusLabel)}</span>
                    </h4>
                    <p className="sl-page-subtitle mb-0">
                        {isSeller ? t('From :name', { name: buyer.name }) : t('To :name', { name: seller.name })} &middot; {transfer.date}
                    </p>
                </div>
            </div>

            {isSeller && pending && (
                <div className={`stx-callout ${short.length ? 'stx-callout--warn' : ''}`}>
                    <i className={`las la-${short.length ? 'exclamation-triangle' : 'hand-holding-usd'}`} />
                    <div>
                        {short.length ? (
                            <>
                                <strong>{t("You don't have enough of :products.", { products: short.map((i) => i.name).join(', ') })}</strong>{' '}
                                {t('Restock first, or decline so :name gets their money back.', { name: buyer.name })}
                            </>
                        ) : (
                            <>
                                <strong>{t(':amount is already paid and held for you.', { amount: transfer.total })}</strong>{' '}
                                {t('Confirm to move the stock to :name; the money goes to your stockist wallet and you earn about :rebate in stockist rebate.', { name: buyer.name, rebate: rebate ?? '' })}
                            </>
                        )}
                    </div>
                </div>
            )}
            {!isSeller && pending && (
                <div className="stx-callout">
                    <i className="las la-hourglass-half" />
                    <div>
                        <strong>{t(':amount is held from your stockist wallet.', { amount: transfer.total })}</strong>{' '}
                        {t('Waiting for :name to confirm. You can cancel before they do and get it back at once.', { name: seller.name })}
                    </div>
                </div>
            )}

            <div className="row g-4">
                <div className="col-lg-8">
                    <div className="sl-card mb-4">
                        <div className="sl-card-header"><i className="las la-boxes" /> {t('Products')} <span className="sl-badge-count ms-auto">{t(':count units', { count: transfer.units.toLocaleString() })}</span></div>
                        <div className="table-responsive">
                            <table className="sl-table" aria-label={t('Products in this request')}>
                                <thead>
                                    <tr>
                                        <th>{t('Product')}</th>
                                        <th>{t('Quantity')}</th>
                                        <th>{t('Price')}</th>
                                        {isSeller && pending && <th>{t('You have')}</th>}
                                        {completed && <th>{t('Your stock')}</th>}
                                        <th className="text-end">{t('Subtotal')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {transfer.items.map((item) => (
                                        <tr key={item.id}>
                                            <td data-label={t('Product')}>
                                                <div className="sl-table-name">
                                                    {item.image && <img className="stx-thumb" alt="" loading="lazy" src={item.image} />}
                                                    <span>{item.name}<br /><Sku value={item.sku} /></span>
                                                </div>
                                            </td>
                                            <td data-label={t('Quantity')}><strong>{item.quantity.toLocaleString()}</strong></td>
                                            <td data-label={t('Price')}>{item.price}</td>
                                            {isSeller && pending && (
                                                <td data-label={t('You have')}>
                                                    <span className={`sl-status ${(item.onHand ?? 0) >= item.quantity ? 'sl-status-in-stock' : 'sl-status-out-of-stock'}`}>{(item.onHand ?? 0).toLocaleString()}</span>
                                                </td>
                                            )}
                                            {completed && (
                                                <td data-label={t('Your stock')}>
                                                    {item.stockBefore?.toLocaleString()} <i className="las la-long-arrow-alt-right" aria-label={t('to')} /> <strong>{item.stockAfter?.toLocaleString()}</strong>
                                                </td>
                                            )}
                                            <td data-label={t('Subtotal')} className="text-end"><strong>{item.total}</strong></td>
                                        </tr>
                                    ))}
                                </tbody>
                                <tfoot>
                                    <tr className="stx-total-row">
                                        <td colSpan={3 + extraCols}>{t('Total')}</td>
                                        <td className="text-end">{transfer.total}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {(transfer.buyerNote || transfer.decisionNote) && (
                        <div className="sl-card mb-4">
                            <div className="sl-card-header"><i className="las la-comment-alt" /> {t('Notes')}</div>
                            <div className="sl-card-body stx-notes">
                                {transfer.buyerNote && <p><strong>{buyer.name}:</strong> {transfer.buyerNote}</p>}
                                {transfer.decisionNote && <p><strong>{t(transfer.statusLabel)}:</strong> {transfer.decisionNote}</p>}
                            </div>
                        </div>
                    )}
                </div>

                <div className="col-lg-4">
                    <div className="sl-card mb-4">
                        <div className="sl-card-header"><i className="las la-people-carry" /> {t('Stockists')}</div>
                        <div className="sl-card-body">
                            <div className="stx-party">
                                <span className="stx-party__tag stx-party__tag--mega">{t('Mega · sells')}</span>
                                <strong>{seller.name}</strong>
                                <small>{seller.contact}{seller.phone && <> &middot; {seller.phone}</>}</small>
                            </div>
                            <div className="stx-party__arrow"><i className="las la-arrow-down" /> {t(':count units', { count: transfer.units.toLocaleString() })}</div>
                            <div className="stx-party">
                                <span className="stx-party__tag">{t('Mini · buys')}</span>
                                <strong>{buyer.name}</strong>
                                <small>{buyer.contact}{buyer.phone && <> &middot; {buyer.phone}</>}</small>
                            </div>
                        </div>
                    </div>

                    <div className="sl-card mb-4">
                        <div className="sl-card-header"><i className="las la-coins" /> {t('Money')}</div>
                        <div className="sl-card-body">
                            <dl className="stx-totals">
                                {isSeller ? (
                                    <>
                                        <div><dt>{t('Stock price')}</dt><dd>{transfer.total}</dd></div>
                                        {completed && (
                                            <>
                                                <div><dt>{t('Paid to your stockist wallet')}</dt><dd className="text-success">+{transfer.total}</dd></div>
                                                <div><dt>{t('Stockist rebate earned')}</dt><dd className="text-success">+{transfer.rebateEarned}</dd></div>
                                                <div><dt>{t('Wallet after')}</dt><dd>{transfer.sellerAfter}</dd></div>
                                            </>
                                        )}
                                        {pending && rebate && <div><dt>{t('Rebate if you confirm')}</dt><dd>≈ {rebate}</dd></div>}
                                    </>
                                ) : (
                                    <>
                                        <div><dt>{t('Paid from your stockist wallet')}</dt><dd>&minus;{transfer.total}</dd></div>
                                        <div><dt>{t('Wallet after paying')}</dt><dd>{transfer.buyerAfter}</dd></div>
                                        {!pending && !completed && <div><dt>{t('Refunded')}</dt><dd className="text-success">+{transfer.total}</dd></div>}
                                    </>
                                )}
                                <div className="stx-totals__ref"><dt>{t('Reference')}</dt><dd>{transfer.trx}</dd></div>
                            </dl>
                        </div>
                    </div>

                    {pending && (
                        <div className="sl-card mb-4">
                            <div className="sl-card-header"><i className="las la-tasks" /> {t('What next')}</div>
                            <div className="sl-card-body">
                                {isSeller ? (
                                    <>
                                        <button type="button" className="sl-btn sl-btn-primary w-100 justify-content-center" disabled={short.length > 0 || busy} onClick={() => setAsking('confirm')}>
                                            <i className="las la-check-circle" /> {t('Confirm & transfer stock')}
                                        </button>
                                        <details className="stx-decline mt-3">
                                            <summary>{t('Decline this request')}</summary>
                                            <form
                                                className="mt-2"
                                                onSubmit={(e) => {
                                                    e.preventDefault();
                                                    decline.post(urls.decline, { preserveScroll: true });
                                                }}
                                            >
                                                <label className="sl-label" htmlFor="declineReason">{t('Reason (sent to :name)', { name: buyer.name })}</label>
                                                <textarea className="sl-input" id="declineReason" rows={2} maxLength={500} required placeholder={t('e.g. Out of stock until next week')} value={decline.data.reason} onChange={(e) => decline.setData('reason', e.target.value)} aria-invalid={decline.errors.reason ? true : undefined} />
                                                {decline.errors.reason && <small className="d-block mt-1" style={{ color: '#DC2626' }}>{decline.errors.reason}</small>}
                                                <button type="submit" className="sl-btn sl-btn-outline stx-btn-danger w-100 justify-content-center mt-2" disabled={decline.processing}>
                                                    <i className="las la-times-circle" /> {t('Decline & refund')}
                                                </button>
                                            </form>
                                        </details>
                                    </>
                                ) : (
                                    <button type="button" className="sl-btn sl-btn-outline stx-btn-danger w-100 justify-content-center" disabled={busy} onClick={() => setAsking('cancel')}>
                                        <i className="las la-undo-alt" /> {t('Cancel request & get refund')}
                                    </button>
                                )}
                            </div>
                        </div>
                    )}

                    <div className="sl-card">
                        <div className="sl-card-header"><i className="las la-stream" /> {t('Timeline')}</div>
                        <div className="sl-card-body">
                            <div className="sl-timeline-item">
                                <div className="sl-timeline-icon stx-tl--blue"><i className="las la-paper-plane" /></div>
                                <div><strong>{t('Requested & paid')}</strong><small className="d-block text-muted">{transfer.date}</small></div>
                            </div>
                            {pending ? (
                                <div className="sl-timeline-item">
                                    <div className="sl-timeline-icon stx-tl--amber"><i className="las la-hourglass-half" /></div>
                                    <div><strong>{t('Waiting for :name', { name: seller.name })}</strong></div>
                                </div>
                            ) : (
                                <div className="sl-timeline-item">
                                    <div className={`sl-timeline-icon ${completed ? 'stx-tl--green' : 'stx-tl--red'}`}><i className={`las la-${completed ? 'check' : 'times'}`} /></div>
                                    <div>
                                        <strong>{t(transfer.statusLabel)}{transfer.closedBy === 'admin' ? ` ${t('by admin')}` : ''}</strong>
                                        {transfer.closedAt && <small className="d-block text-muted">{transfer.closedAt}</small>}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            <ConfirmDialog
                open={asking !== null}
                question={asking === 'confirm'
                    ? t("Move :units units to :name now? This can't be undone.", { units: transfer.units.toLocaleString(), name: buyer.name })
                    : t('Cancel :code? :amount goes back to your stockist wallet.', { code: transfer.code, amount: transfer.total })}
                onConfirm={() => act(asking === 'confirm' ? urls.confirm : urls.cancel)}
                onCancel={() => setAsking(null)}
                busy={busy}
            />
        </div>
    );
}

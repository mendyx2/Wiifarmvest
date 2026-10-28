import { Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import type { Badge } from '@/Components/Admin/Cells';
import { FieldError } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import ConfirmDialog from '@/Components/ConfirmDialog';
import { useTrans } from '@/lib/i18n';

type Party = { name: string; owner: { fullname: string; username: string; url: string } | null; phone: string; email: string; state: string; wallet: string; url: string } | null;
type Item = { id: number; name: string; productId: number; quantity: string; unit: string; line: string; rebate: string | null; rebateRule: string | null; seller: [string, string] | null; buyer: [string, string] | null };
type Ledger = { wallet: string; username: string | null; details: string; plus: boolean; amount: string; balance: string };

type Props = {
    transfer: {
        id: number;
        code: string;
        status: 'pending' | 'completed' | 'declined' | 'cancelled';
        badge: Badge;
        state: string | null;
        requested: string;
        closed: string | null;
        closedBy: string;
        amount: string;
        units: string;
        rebate: string | null;
        rebatePreview: string | null;
        trx: string;
        settleTrx: string;
        buyerAfter: string;
        sellerAfter: string;
        buyerNote: string | null;
        decisionNote: string | null;
        statusLabel: string;
    };
    seller: Party;
    buyer: Party;
    items: Item[];
    ledger: Ledger[];
    canCancel: boolean;
    urls: { cancel: string; index: string };
};

/** One stock transfer: both stockists, the stock that moved, the money, and Cancel & refund for a stuck request. */
export default function TransferDetail({ transfer: tr, seller, buyer, items, ledger, canCancel, urls }: Props) {
    const t = useTrans();
    const completed = tr.status === 'completed';
    const pending = tr.status === 'pending';

    return (
        <div className="db-wrap atx">
            <PageHeader title={t('Stock Transfer :code', { code: tr.code })}>
                <Link className="btn btn-sm btn-outline--dark" href={urls.index}>
                    <i className="las la-undo" /> {t('Back')}
                </Link>
            </PageHeader>

            <div className="atx-head">
                <div>
                    <p className="atx-head__tag">{t('Stock transfer')}</p>
                    <h3 className="atx-head__code">
                        {tr.code} <span className={`badge ${tr.badge.class}`}>{tr.badge.label}</span>
                    </h3>
                    <p className="atx-head__sub">
                        {seller?.name} <i className="las la-long-arrow-alt-right" /> {buyer?.name} · {tr.state} · {t('requested :date', { date: tr.requested })}
                    </p>
                </div>
                <dl className="atx-head__nums">
                    <div>
                        <dt>{t('Amount')}</dt>
                        <dd>{tr.amount}</dd>
                    </div>
                    <div>
                        <dt>{t('Units')}</dt>
                        <dd>{tr.units}</dd>
                    </div>
                    <div>
                        <dt>{completed ? t('Rebate paid') : t('Rebate if confirmed')}</dt>
                        <dd>{completed ? tr.rebate : pending ? `≈ ${tr.rebatePreview}` : '—'}</dd>
                    </div>
                </dl>
            </div>

            <div className="row gy-4">
                {(
                    [
                        [t('Mega stockist · sold'), 'mega', seller],
                        [t('Mini stockist · bought'), 'mini', buyer],
                    ] as const
                ).map(([role, tone, s]) => (
                    <div className="col-lg-6" key={tone}>
                        <section className={`atx-party atx-party--${tone}`}>
                            <p className="atx-party__role">{role}</p>
                            {s ? (
                                <>
                                    <h5 className="atx-party__name">{s.name}</h5>
                                    <dl className="atx-party__facts">
                                        <div>
                                            <dt>{t('Owner')}</dt>
                                            <dd>
                                                {s.owner ? (
                                                    <>
                                                        <Link href={s.owner.url}>{s.owner.fullname}</Link> <small>@{s.owner.username}</small>
                                                    </>
                                                ) : (
                                                    '—'
                                                )}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt>{t('Phone')}</dt>
                                            <dd>{s.phone}</dd>
                                        </div>
                                        <div>
                                            <dt>{t('Email')}</dt>
                                            <dd>{s.email}</dd>
                                        </div>
                                        <div>
                                            <dt>{t('State')}</dt>
                                            <dd>{s.state}</dd>
                                        </div>
                                        <div>
                                            <dt>{t('Stockist wallet now')}</dt>
                                            <dd>
                                                <strong>{s.wallet}</strong>
                                            </dd>
                                        </div>
                                    </dl>
                                    <Link href={s.url} className="atx-party__link">
                                        {t('Stockist profile')} <i className="las la-arrow-right" />
                                    </Link>
                                </>
                            ) : (
                                <p className="text-muted mb-0">{t('Stockist record not found.')}</p>
                            )}
                        </section>
                    </div>
                ))}

                <div className="col-12">
                    <div className="card atx-card">
                        <div className="card-header d-flex justify-content-between align-items-center">
                            <h6 className="card-title mb-0">
                                <i className="las la-boxes" /> {t('Stock breakdown')}
                            </h6>
                            <small className="text-muted">
                                {items.length} {t(items.length === 1 ? 'product' : 'products')} · {tr.units} {t('units')}
                            </small>
                        </div>
                        <div className="card-body p-0">
                            <div className="table-responsive--md table-responsive">
                                <table className="table table--light style--two mb-0 atx-items">
                                    <thead>
                                        <tr>
                                            <th>{t('Product')}</th>
                                            <th>{t('Qty')}</th>
                                            <th>{t('Unit price')}</th>
                                            <th>{t('Line total')}</th>
                                            <th>{t('Rebate')}</th>
                                            <th>{t('Mega stock')}</th>
                                            <th>{t('Mini stock')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {items.map((item) => (
                                            <tr key={item.id}>
                                                <td className="text-start" data-label={t('Product')}>
                                                    <strong>{item.name}</strong>
                                                    <small className="d-block text-muted">#{item.productId}</small>
                                                </td>
                                                <td data-label={t('Qty')}>{item.quantity}</td>
                                                <td data-label={t('Unit price')}>{item.unit}</td>
                                                <td data-label={t('Line total')}>
                                                    <strong>{item.line}</strong>
                                                </td>
                                                <td data-label={t('Rebate')}>
                                                    {item.rebate ?? '—'}
                                                    {item.rebateRule && <small className="d-block text-muted">{item.rebateRule}</small>}
                                                </td>
                                                <td data-label={t('Mega stock')}>{item.seller ? <Move from={item.seller[0]} to={item.seller[1]} change={`−${item.quantity}`} minus /> : '—'}</td>
                                                <td data-label={t('Mini stock')}>{item.buyer ? <Move from={item.buyer[0]} to={item.buyer[1]} change={`+${item.quantity}`} /> : '—'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th className="text-start">{t('Total')}</th>
                                            <th>{tr.units}</th>
                                            <th />
                                            <th>{tr.amount}</th>
                                            <th>{completed ? tr.rebate : '—'}</th>
                                            <th colSpan={2} />
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="col-lg-7">
                    <div className="card atx-card h-100">
                        <div className="card-header">
                            <h6 className="card-title mb-0">
                                <i className="las la-coins" /> {t('Money')}
                            </h6>
                        </div>
                        <div className="card-body">
                            <ol className="atx-money">
                                <Money tone="out" title={t(':who paid :amount', { who: buyer?.name ?? '', amount: tr.amount })} note={`${t("From the Mini's stockist wallet, held on the transfer")} · ${t('wallet after :b', { b: tr.buyerAfter })} · trx ${tr.trx}`} />
                                {completed ? (
                                    <>
                                        <Money tone="in" title={t(':who received :amount', { who: seller?.name ?? '', amount: tr.amount })} note={`${t("Product money to the Mega's stockist wallet")} · ${t('wallet after :b', { b: tr.sellerAfter })} · trx ${tr.settleTrx}`} />
                                        <Money tone="cost" title={t('Company paid :amount stockist rebate', { amount: tr.rebate ?? '' })} note={t("To the Mega's stockist_rebate, at the Mega % for :state", { state: tr.state ?? '' })} />
                                    </>
                                ) : pending ? (
                                    <Money tone="hold" title={t('Held, waiting for :who', { who: seller?.name ?? '' })} note={t('Paid to the Mega on confirmation, or refunded to the Mini if declined or cancelled')} />
                                ) : (
                                    <Money tone="in" title={t('Refunded :amount to :who', { amount: tr.amount, who: buyer?.name ?? '' })} note={`${tr.statusLabel} · trx ${tr.settleTrx}`} />
                                )}
                            </ol>
                            {ledger.length > 0 && (
                                <>
                                    <p className="atx-sub">{t('Ledger rows')}</p>
                                    <div className="table-responsive">
                                        <table className="table table-sm atx-ledger mb-0">
                                            <thead>
                                                <tr>
                                                    <th>{t('Wallet')}</th>
                                                    <th>{t('Member')}</th>
                                                    <th>{t('Details')}</th>
                                                    <th className="text-end">{t('Amount')}</th>
                                                    <th className="text-end">{t('Balance after')}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {ledger.map((row, i) => (
                                                    <tr key={i}>
                                                        <td>{t(row.wallet)}</td>
                                                        <td>{row.username ? `@${row.username}` : '—'}</td>
                                                        <td>{row.details}</td>
                                                        <td className={`text-end ${row.plus ? 'atx-plus' : 'atx-minus'}`}>
                                                            {row.plus ? '+' : '-'}
                                                            {row.amount}
                                                        </td>
                                                        <td className="text-end">{row.balance}</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </>
                            )}
                        </div>
                    </div>
                </div>

                <div className="col-lg-5">
                    <div className="card atx-card mb-4">
                        <div className="card-header">
                            <h6 className="card-title mb-0">
                                <i className="las la-stream" /> {t('Timeline')}
                            </h6>
                        </div>
                        <div className="card-body">
                            <ul className="atx-timeline">
                                <li>
                                    <b>{t('Requested & paid')}</b> {t('by :who', { who: buyer?.name ?? '' })}
                                    <small>{tr.requested}</small>
                                </li>
                                {tr.buyerNote && <li className="atx-timeline__note">“{tr.buyerNote}”</li>}
                                {completed ? (
                                    <li className="is-done">
                                        <b>{t('Confirmed')}</b> {t('by :who; stock moved and paid', { who: seller?.name ?? '' })}
                                        <small>{tr.closed}</small>
                                    </li>
                                ) : pending ? (
                                    <li className="is-wait">
                                        <b>{t('Waiting')}</b> {t('for :who to confirm', { who: seller?.name ?? '' })}
                                    </li>
                                ) : (
                                    <>
                                        <li className="is-stop">
                                            <b>{tr.statusLabel}</b> {tr.closedBy}
                                            <small>{tr.closed}</small>
                                        </li>
                                        {tr.decisionNote && <li className="atx-timeline__note">“{tr.decisionNote}”</li>}
                                    </>
                                )}
                            </ul>
                        </div>
                    </div>
                    {canCancel && <CancelCard code={tr.code} amount={tr.amount} buyer={buyer?.name ?? ''} url={urls.cancel} />}
                </div>
            </div>
        </div>
    );
}

function Move({ from, to, change, minus }: { from: string; to: string; change: string; minus?: boolean }) {
    return (
        <>
            <span className="atx-move">
                {from} <i className="las la-long-arrow-alt-right" /> <b>{to}</b>
            </span>
            <small className={`d-block ${minus ? 'atx-minus' : 'atx-plus'}`}>{change}</small>
        </>
    );
}

function Money({ tone, title, note }: { tone: string; title: string; note: string }) {
    return (
        <li>
            <span className={`atx-money__dot atx-money__dot--${tone}`} />
            <div>
                <strong>{title}</strong>
                <small>{note}</small>
            </div>
        </li>
    );
}

function CancelCard({ code, amount, buyer, url }: { code: string; amount: string; buyer: string; url: string }) {
    const t = useTrans();
    const form = useForm({ reason: '' });
    const [asking, setAsking] = useState(false);
    const submit = (e: FormEvent) => {
        e.preventDefault();
        setAsking(true);
    };
    return (
        <div className="card atx-card atx-danger">
            <div className="card-header">
                <h6 className="card-title mb-0">
                    <i className="las la-undo-alt" /> {t('Cancel & refund')}
                </h6>
            </div>
            <div className="card-body">
                <p className="atx-sub mb-2">{t("For a request the Mega stockist can't or won't act on. :amount goes back to :buyer's stockist wallet.", { amount, buyer })}</p>
                <form onSubmit={submit}>
                    <label htmlFor="cancel-reason" className="visually-hidden">
                        {t('Reason')}
                    </label>
                    <textarea id="cancel-reason" className="form-control mb-2" rows={2} maxLength={450} required placeholder={t('Reason, sent to both stockists')} value={form.data.reason} onChange={(e) => form.setData('reason', e.target.value)} />
                    <FieldError message={form.errors.reason} />
                    <button type="submit" className="btn btn--danger w-100" disabled={form.processing || !form.data.reason.trim()}>
                        <i className="las la-undo-alt" /> {t('Cancel request & refund')}
                    </button>
                </form>
            </div>
            <ConfirmDialog
                open={asking}
                busy={form.processing}
                question={t('Cancel :code and refund :amount?', { code, amount })}
                onCancel={() => setAsking(false)}
                onConfirm={() => form.post(url, { preserveScroll: true, onFinish: () => setAsking(false) })}
            />
        </div>
    );
}

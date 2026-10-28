import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { UserCell, WhenCell, type AdminUser, type When } from '@/Components/Admin/Cells';
import DataTable from '@/Components/Admin/DataTable';
import { withId } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import SearchForm from '@/Components/Admin/SearchForm';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';
import { initials } from '@/lib/initials';
import type { Paginated } from '@/types';
import './StageTwice.css';

type Refund = {
    originalTrx: string;
    refundTrx: string;
    amount: string;
    balanceBefore: string;
    balanceAfter: string;
    admin: string;
    date: string;
    note: string;
};

type Row = {
    id: number;
    trx: string;
    user: AdminUser;
    times: number;
    when: When;
    amount: string;
    postBalance: string;
    refund: Refund | null;
};

type Props = {
    stage: number;
    rows: Paginated<Row>;
    search: string;
    /** refund is null when this admin may not deduct */
    urls: { download: string; refund: string | null };
};

/**
 * Double Stage-Out Bonus: members paid a stage's step-out bonus more than
 * once. Ticked rows download as CSV; "Refund" deducts one bonus (once per
 * member and stage) and "Details" shows that deduction.
 */
export default function StageTwice({ stage, rows, search, urls }: Props) {
    const t = useTrans();
    const { url, props } = usePage<{ csrfToken: string }>();
    const [picked, setPicked] = useState<number[]>([]);
    const [refunding, setRefunding] = useState<Row | null>(null);
    const [viewing, setViewing] = useState<Row | null>(null);
    const [busy, setBusy] = useState(false);
    const [needPick, setNeedPick] = useState(false);
    const downloadForm = useRef<HTMLFormElement>(null);
    const base = url.split('?')[0];
    const ids = rows.data.map((r) => r.id);
    const allPicked = ids.length > 0 && ids.every((id) => picked.includes(id));

    useEffect(() => setPicked([]), [rows]);

    const toggle = (id: number) => setPicked((p) => (p.includes(id) ? p.filter((x) => x !== id) : [...p, id]));
    const download = () => {
        if (!picked.length) return setNeedPick(true);
        setNeedPick(false);
        downloadForm.current?.submit();
    };
    const refund = () => {
        if (!refunding || !urls.refund) return;
        setBusy(true);
        router.post(withId(urls.refund, refunding.id), {}, { preserveScroll: true, onFinish: () => (setBusy(false), setRefunding(null)) });
    };

    return (
        <div className="stage-twice-page">
            <PageHeader title={t('Double Stage-Out Bonus')}>
                <SearchForm url={url} value={search} placeholder="Search by username, name or TRX…" extra={{ stage: String(stage) }} />
            </PageHeader>

            <ul className="nav nav-tabs mb-3">
                {[1, 2, 3].map((s) => (
                    <li className="nav-item" key={s}>
                        <Link className={`nav-link ${stage === s ? 'active' : ''}`} href={`${base}?stage=${s}`} preserveScroll>
                            {t('Stage :n', { n: s })}
                            {stage === s && rows.total > 0 && <span className="badge bg--danger ms-1">{rows.total}</span>}
                        </Link>
                    </li>
                ))}
            </ul>

            <form ref={downloadForm} method="post" action={urls.download} target="_blank" hidden>
                <input type="hidden" name="_token" value={props.csrfToken} />
                <input type="hidden" name="stage" value={stage} />
                <input type="hidden" name="search" value={search} />
                <input type="hidden" name="ids" value={picked.join(',')} />
            </form>

            <div className="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h5 className="mb-0">
                    {t('Users Who Received Stage :n Bonus More Than Once', { n: stage })}
                    {rows.total > 0 && <span className="badge bg--danger ms-2">{t(':n records', { n: rows.total })}</span>}
                </h5>
                <div className="d-flex align-items-center gap-2">
                    {picked.length > 0 && <span className="text-muted small">{t(':n selected', { n: picked.length })}</span>}
                    {needPick && (
                        <span className="text--danger small" role="alert">
                            {t('Please check at least one row before downloading.')}
                        </span>
                    )}
                    <button type="button" className="btn btn-outline--primary btn-sm" onClick={download}>
                        <i className="las la-file-excel" /> {t('Download Checked')}
                    </button>
                </div>
            </div>

            <DataTable
                rows={rows.data}
                rowKey={(r) => r.id}
                links={rows.links}
                empty={search ? t('No results for ":search".', { search }) : t('No users have received the stage :n bonus more than once.', { n: stage })}
                columns={[
                    {
                        label: '',
                        cell: (r) => <input type="checkbox" className="form-check-input" aria-label={t('Select :trx', { trx: r.trx })} checked={picked.includes(r.id)} onChange={() => toggle(r.id)} />,
                    },
                    { label: t('User'), cell: (r) => <UserCell user={r.user} /> },
                    { label: t('TRX'), cell: (r) => <strong>{r.trx}</strong> },
                    { label: t('Times Received'), cell: (r) => <span className="badge bg--danger">{r.times}x</span> },
                    { label: t('Transacted'), cell: (r) => <WhenCell when={r.when} /> },
                    { label: t('Amount'), cell: (r) => <span className="fw-bold text--success">+ {r.amount}</span> },
                    { label: t('Post Balance'), cell: (r) => r.postBalance },
                    {
                        label: t('Action'),
                        cell: (r) =>
                            r.refund ? (
                                <div className="d-flex align-items-center gap-2 flex-wrap">
                                    <span className="badge bg--success">
                                        <i className="las la-check-circle" /> {t('Refunded')}
                                    </span>
                                    <button type="button" className="btn btn-sm rd-detail-trigger" onClick={() => setViewing(r)}>
                                        <i className="las la-receipt" /> {t('Details')}
                                    </button>
                                </div>
                            ) : urls.refund ? (
                                <button type="button" className="btn btn-outline--danger btn-sm" onClick={() => setRefunding(r)}>
                                    <i className="las la-undo-alt" /> {t('Refund')}
                                </button>
                            ) : (
                                <span className="text-muted">{t('Pending')}</span>
                            ),
                    },
                ]}
            />

            {ids.length > 0 && (
                <button type="button" className="btn btn-sm btn-link mt-2 p-0" onClick={() => setPicked(allPicked ? [] : ids)}>
                    {allPicked ? t('Deselect all') : t('Select all on this page')}
                </button>
            )}

            <Modal open={refunding !== null} onClose={() => !busy && setRefunding(null)} contentClassName="rd-card" labelledBy="rd-title">
                {refunding && (
                    <>
                        <button type="button" className="rd-close" aria-label={t('Close')} onClick={() => setRefunding(null)} disabled={busy}>
                            <i className="las la-times" />
                        </button>
                        <div className="rd-icon-zone">
                            <div className="rd-icon-ring rd-ring-1" />
                            <div className="rd-icon-ring rd-ring-2" />
                            <div className="rd-icon-wrap">
                                <i className="las la-exclamation-triangle" />
                            </div>
                        </div>
                        <h4 className="rd-title" id="rd-title">
                            {t('Confirm Deduction')}
                        </h4>
                        <p className="rd-sub">{t("This will permanently deduct funds from the user's account.")}</p>
                        <div className="rd-detail-grid">
                            <div className="rd-detail-row">
                                <span className="rd-detail-label">
                                    <i className="las la-user" /> {t('User')}
                                </span>
                                <span className="rd-detail-value">
                                    <span className="rd-val-primary">{refunding.user?.fullname}</span>
                                    <span className="rd-val-sub">@{refunding.user?.username}</span>
                                </span>
                            </div>
                            <div className="rd-detail-row">
                                <span className="rd-detail-label">
                                    <i className="las la-hashtag" /> {t('TRX')}
                                </span>
                                <span className="rd-detail-value">
                                    <code className="rd-code">{refunding.trx}</code>
                                </span>
                            </div>
                            <div className="rd-detail-row rd-detail-row--highlight">
                                <span className="rd-detail-label">
                                    <i className="las la-money-bill-wave" /> {t('Amount')}
                                </span>
                                <span className="rd-detail-value">
                                    <span className="rd-val-amount">{refunding.amount}</span>
                                </span>
                            </div>
                        </div>
                        <div className="rd-warning">
                            <i className="las la-shield-alt rd-warning-icon" />
                            <p className="rd-warning-text">{t('Original transaction is preserved. A new deduction record will be created. Balance may go negative.')}</p>
                        </div>
                        <div className="rd-actions">
                            <button type="button" className="rd-btn-cancel" onClick={() => setRefunding(null)} disabled={busy}>
                                {t('Cancel')}
                            </button>
                            <button type="button" className="rd-btn-confirm" onClick={refund} disabled={busy}>
                                {busy ? (
                                    <span>
                                        <span className="rd-spinner" /> {t('Processing…')}
                                    </span>
                                ) : (
                                    <span className="text-white">
                                        <i className="las la-undo-alt" /> {t('Yes, Deduct Now')}
                                    </span>
                                )}
                            </button>
                        </div>
                    </>
                )}
            </Modal>

            <Modal open={viewing !== null} onClose={() => setViewing(null)} contentClassName="rdt-card" labelledBy="rdt-title">
                {viewing?.refund && <RefundDetails row={viewing} refund={viewing.refund} stage={stage} onClose={() => setViewing(null)} />}
            </Modal>
        </div>
    );
}

function RefundDetails({ row, refund, stage, onClose }: { row: Row; refund: Refund; stage: number; onClose: () => void }) {
    const t = useTrans();
    return (
        <>
            <button type="button" className="rd-close" aria-label={t('Close')} onClick={onClose}>
                <i className="las la-times" />
            </button>
            <div className="rdt-header">
                <div className="rdt-stamp-ring">
                    <div className="rdt-stamp-icon">
                        <i className="las la-check-double" />
                    </div>
                </div>
                <div className="rdt-header-text">
                    <span className="rdt-label-tag">{t('Deduction Record')}</span>
                    <h4 className="rdt-title" id="rdt-title">
                        {t('Refund Details')}
                    </h4>
                    <p className="rdt-date">{refund.date}</p>
                </div>
                <div className="rdt-processed-stamp">{t('PROCESSED')}</div>
            </div>
            <div className="rdt-user-card">
                <div className="rdt-user-avatar">{initials(row.user?.fullname ?? '')}</div>
                <div className="rdt-user-info">
                    <span className="rdt-user-name">{row.user?.fullname}</span>
                    <span className="rdt-user-handle">@{row.user?.username}</span>
                </div>
                <div className="rdt-stage-pill">{t('Stage :n', { n: stage })}</div>
            </div>
            <div className="rdt-flow">
                <div className="rdt-flow-item">
                    <span className="rdt-flow-label">{t('Balance Before')}</span>
                    <span className="rdt-flow-value rdt-flow-before">{refund.balanceBefore}</span>
                </div>
                <div className="rdt-flow-arrow">
                    <i className="las la-long-arrow-alt-right" />
                    <span className="rdt-flow-deducted">− {refund.amount}</span>
                </div>
                <div className="rdt-flow-item">
                    <span className="rdt-flow-label">{t('Balance After')}</span>
                    <span className="rdt-flow-value rdt-flow-after">{refund.balanceAfter}</span>
                </div>
            </div>
            <div className="rdt-trx-grid">
                <div className="rdt-trx-row">
                    <span className="rdt-trx-label">
                        <i className="las la-receipt" /> {t('Original TRX')}
                    </span>
                    <code className="rdt-trx-code">{refund.originalTrx}</code>
                </div>
                <div className="rdt-trx-row">
                    <span className="rdt-trx-label">
                        <i className="las la-undo-alt" /> {t('Refund TRX')}
                    </span>
                    <code className="rdt-trx-code rdt-trx-code--refund">{refund.refundTrx}</code>
                </div>
                <div className="rdt-trx-row">
                    <span className="rdt-trx-label">
                        <i className="las la-user-shield" /> {t('Processed By')}
                    </span>
                    <span className="rdt-trx-val">{refund.admin}</span>
                </div>
                <div className="rdt-trx-row">
                    <span className="rdt-trx-label">
                        <i className="las la-sticky-note" /> {t('Note')}
                    </span>
                    <span className="rdt-trx-val rdt-note">{refund.note || '—'}</span>
                </div>
            </div>
            <div className="rdt-footer">
                <button type="button" className="rdt-btn-close" onClick={onClose}>
                    {t('Close')}
                </button>
            </div>
        </>
    );
}

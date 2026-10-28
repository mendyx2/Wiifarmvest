import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import BoxWidget from '@/Components/Admin/BoxWidget';
import { BadgeCell, UserCell, WhenCell, type AdminUser, type Badge, type When } from '@/Components/Admin/Cells';
import DataTable from '@/Components/Admin/DataTable';
import DateRangeField, { dateQuery, type DateRange } from '@/Components/Admin/DateRangeField';
import FilterCard from '@/Components/Admin/FilterCard';
import ConfirmDialog from '@/Components/ConfirmDialog';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';
import type { Paginated } from '@/types';

type Row = {
    id: number;
    trx: string;
    method: { id: number; name: string };
    user: AdminUser;
    balance: string;
    when: When;
    amount: string;
    charge: string;
    net: string;
    rate: string;
    final: string;
    status: Badge;
    pending: boolean;
    url: string;
};

type Props = {
    title: string;
    withdrawals: Paginated<Row>;
    summary: { successful: string; pending: string; rejected: string } | null;
    methods: { id: number; name: string }[];
    filters: { search: string; date: DateRange; method: string };
    urls: { bulk: string; approved: string; pending: string; rejected: string };
};

/**
 * Withdrawals: Pending, Approved, Rejected and All. Pending requests can be
 * ticked and approved or rejected together; each is checked again on the
 * server and one already processed is skipped.
 */
export default function Withdrawals({ title, withdrawals, summary, methods, filters, urls }: Props) {
    const t = useTrans();
    const url = usePage().url;
    const [search, setSearch] = useState(filters.search);
    const [date, setDate] = useState(filters.date);
    const [method, setMethod] = useState(filters.method);
    const [picked, setPicked] = useState<number[]>([]);
    const [asking, setAsking] = useState<'approve' | 'cancel' | null>(null);
    const [busy, setBusy] = useState(false);
    const pendingIds = withdrawals.data.filter((r) => r.pending).map((r) => r.id);

    // A new page of rows starts with nothing ticked.
    useEffect(() => setPicked([]), [withdrawals]);

    const toggle = (id: number) => setPicked((p) => (p.includes(id) ? p.filter((x) => x !== id) : [...p, id]));
    const allPicked = pendingIds.length > 0 && pendingIds.every((id) => picked.includes(id));
    const send = () => {
        if (!asking) return;
        setBusy(true);
        router.post(urls.bulk, { action: asking, withdrawal_ids: picked }, { preserveScroll: true, onFinish: () => (setBusy(false), setAsking(null)) });
    };

    return (
        <>
            <PageHeader title={t(title)} />
            {summary && (
                <div className="row gy-4 mb-4">
                    <div className="col-md-4">
                        <BoxWidget outline inertia bg="success" icon="las la-check-circle" title={t('Approved Withdrawal')} value={summary.successful} link={urls.approved} />
                    </div>
                    <div className="col-md-4">
                        <BoxWidget outline inertia bg="warning" icon="las la-spinner" title={t('Pending Withdrawals')} value={summary.pending} link={urls.pending} />
                    </div>
                    <div className="col-md-4">
                        <BoxWidget outline inertia bg="danger" icon="las la-ban" title={t('Rejected Withdrawals')} value={summary.rejected} link={urls.rejected} />
                    </div>
                </div>
            )}
            <FilterCard onSubmit={() => visitWithQuery(url, { search: search.trim(), date: dateQuery(date), method })}>
                <div className="flex-grow-1">
                    <label htmlFor="wd-search">{t('Search')}</label>
                    <input id="wd-search" type="search" className="form-control" placeholder={t('Username / TRX')} value={search} onChange={(e) => setSearch(e.target.value)} />
                </div>
                <div className="flex-grow-1">
                    <label htmlFor="wd-method">{t('Method')}</label>
                    <select id="wd-method" className="form-control" value={method} onChange={(e) => setMethod(e.target.value)}>
                        <option value="">{t('All')}</option>
                        {methods.map((m) => (
                            <option key={m.id} value={m.id}>
                                {m.name}
                            </option>
                        ))}
                    </select>
                </div>
                <div className="flex-grow-1">
                    <label htmlFor="wd-date">{t('Date')}</label>
                    <DateRangeField id="wd-date" value={date} onChange={setDate} />
                </div>
            </FilterCard>

            {pendingIds.length > 0 && (
                <div className="d-flex flex-wrap gap-2 align-items-center mb-3" role="toolbar" aria-label={t('Bulk actions')}>
                    <span className="text-muted small">{t(':n selected', { n: picked.length })}</span>
                    <button type="button" className="btn btn-sm btn-outline--success" disabled={!picked.length} onClick={() => setAsking('approve')}>
                        <i className="las la-check" /> {t('Approve Selected')}
                    </button>
                    <button type="button" className="btn btn-sm btn-outline--danger" disabled={!picked.length} onClick={() => setAsking('cancel')}>
                        <i className="las la-ban" /> {t('Reject Selected')}
                    </button>
                </div>
            )}

            <DataTable
                rows={withdrawals.data}
                rowKey={(r) => r.id}
                links={withdrawals.links}
                empty={t('Withdrawal not found')}
                columns={[
                    ...(pendingIds.length
                        ? [
                              {
                                  label: '',
                                  cell: (r: Row) =>
                                      r.pending ? <input type="checkbox" className="form-check-input" aria-label={t('Select :trx', { trx: r.trx })} checked={picked.includes(r.id)} onChange={() => toggle(r.id)} /> : null,
                              },
                          ]
                        : []),
                    {
                        label: t('Gateway | Transaction'),
                        cell: (r) => (
                            <>
                                <span className="fw-bold">{r.method.name}</span>
                                <br />
                                <small>{r.trx}</small>
                                <br />
                                <small title={t('Money Box now')}>{r.balance}</small>
                            </>
                        ),
                    },
                    { label: t('Initiated'), cell: (r) => <WhenCell when={r.when} /> },
                    { label: t('User'), cell: (r) => <UserCell user={r.user} /> },
                    {
                        label: t('Amount'),
                        cell: (r) => (
                            <>
                                {r.amount} - <span className="text--danger" title={t('charge')}>{r.charge}</span>
                                <br />
                                <strong title={t('Amount after charge')}>{r.net}</strong>
                            </>
                        ),
                    },
                    {
                        label: t('Conversion'),
                        cell: (r) => (
                            <>
                                1 = {r.rate}
                                <br />
                                <strong>{r.final}</strong>
                            </>
                        ),
                    },
                    { label: t('Status'), cell: (r) => <BadgeCell badge={r.status} /> },
                    {
                        label: t('Action'),
                        cell: (r) => (
                            <Link href={r.url} className="btn btn-sm btn-outline--primary">
                                <i className="la la-desktop" /> {t('Details')}
                            </Link>
                        ),
                    },
                ]}
            />
            {pendingIds.length > 0 && (
                <button type="button" className="btn btn-sm btn-link mt-2 p-0" onClick={() => setPicked(allPicked ? [] : pendingIds)}>
                    {allPicked ? t('Deselect all') : t('Select all pending on this page')}
                </button>
            )}
            <ConfirmDialog
                open={asking !== null}
                busy={busy}
                question={asking === 'approve' ? t('Approve :n withdrawal(s)? Make sure the money was sent.', { n: picked.length }) : t('Reject :n withdrawal(s)? The amounts go back to the members.', { n: picked.length })}
                onConfirm={send}
                onCancel={() => setAsking(null)}
            />
        </>
    );
}

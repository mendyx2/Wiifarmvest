import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import BoxWidget from '@/Components/Admin/BoxWidget';
import { BadgeCell, UserCell, WhenCell, type AdminUser, type Badge, type When } from '@/Components/Admin/Cells';
import DataTable from '@/Components/Admin/DataTable';
import DateRangeField, { dateQuery, type DateRange } from '@/Components/Admin/DateRangeField';
import FilterCard from '@/Components/Admin/FilterCard';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';
import type { Paginated } from '@/types';

type Row = {
    id: number;
    trx: string;
    method: string;
    methodKey: string;
    user: AdminUser;
    when: When;
    amount: string;
    charge: string;
    total: string;
    rate: string;
    final: string;
    status: Badge;
    url: string;
};

type Props = {
    title: string;
    deposits: Paginated<Row>;
    summary: { successful: string; pending: string; rejected: string; initiated: string } | null;
    filters: { search: string; date: DateRange; method: string };
    urls: { successful: string; pending: string; rejected: string; initiated: string };
};

/** Deposits: Pending, Approved, Successful, Rejected, Initiated and All (one page, one list per sidebar link). */
export default function Deposits({ title, deposits, summary, filters, urls }: Props) {
    const t = useTrans();
    const url = usePage().url;
    const [search, setSearch] = useState(filters.search);
    const [date, setDate] = useState(filters.date);
    const go = (extra: Record<string, string> = {}) => visitWithQuery(url, { search: search.trim(), date: dateQuery(date), method: filters.method, ...extra });

    return (
        <>
            <PageHeader title={t(title)} />
            {summary && (
                <div className="row gy-4 mb-4">
                    <div className="col-xl-3 col-sm-6">
                        <BoxWidget outline inertia bg="success" icon="las la-check-circle" title={t('Successful Deposit')} value={summary.successful} link={urls.successful} />
                    </div>
                    <div className="col-xl-3 col-sm-6">
                        <BoxWidget outline inertia bg="warning" icon="las la-spinner" title={t('Pending Deposit')} value={summary.pending} link={urls.pending} />
                    </div>
                    <div className="col-xl-3 col-sm-6">
                        <BoxWidget outline inertia bg="danger" icon="las la-ban" title={t('Rejected Deposit')} value={summary.rejected} link={urls.rejected} />
                    </div>
                    <div className="col-xl-3 col-sm-6">
                        <BoxWidget outline inertia bg="dark" icon="las la-money-check-alt" title={t('Initiated Deposit')} value={summary.initiated} link={urls.initiated} />
                    </div>
                </div>
            )}
            <FilterCard onSubmit={() => go()}>
                <div className="flex-grow-1">
                    <label htmlFor="dep-search">{t('Search')}</label>
                    <input id="dep-search" type="search" className="form-control" placeholder={t('Username / TRX')} value={search} onChange={(e) => setSearch(e.target.value)} />
                </div>
                <div className="flex-grow-1">
                    <label htmlFor="dep-date">{t('Date')}</label>
                    <DateRangeField id="dep-date" value={date} onChange={setDate} />
                </div>
            </FilterCard>
            {filters.method && (
                <p className="mb-3">
                    <span className="badge badge--primary">
                        {t('Method')}: {deposits.data[0]?.method ?? filters.method}
                    </span>{' '}
                    <button type="button" className="btn btn-link btn-sm p-0" onClick={() => go({ method: '' })}>
                        {t('Show all methods')}
                    </button>
                </p>
            )}
            <DataTable
                rows={deposits.data}
                rowKey={(r) => r.id}
                links={deposits.links}
                empty={t('Deposit not found')}
                columns={[
                    {
                        label: t('Gateway | Transaction'),
                        cell: (r) => (
                            <>
                                <button type="button" className="btn btn-link p-0 fw-bold" title={t('Only this method')} onClick={() => go({ method: r.methodKey })}>
                                    {r.method}
                                </button>
                                <br />
                                <small>{r.trx}</small>
                            </>
                        ),
                    },
                    { label: t('Initiated'), cell: (r) => <WhenCell when={r.when} /> },
                    { label: t('User'), cell: (r) => <UserCell user={r.user} /> },
                    {
                        label: t('Amount'),
                        cell: (r) => (
                            <>
                                {r.amount} + <span className="text--danger" title={t('charge')}>{r.charge}</span>
                                <br />
                                <strong title={t('Amount with charge')}>{r.total}</strong>
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
        </>
    );
}

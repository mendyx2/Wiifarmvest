import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import { BadgeCell, UserCell, WhenCell, type AdminUser, type Badge, type When } from '@/Components/Admin/Cells';
import DataTable from '@/Components/Admin/DataTable';
import DateRangeField, { dateQuery, type DateRange } from '@/Components/Admin/DateRangeField';
import FilterCard from '@/Components/Admin/FilterCard';
import PageHeader from '@/Components/Admin/PageHeader';
import StatWidget from '@/Components/Admin/StatWidget';
import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';
import type { Paginated } from '@/types';

type Row = { id: number; user: AdminUser; plan: string; units: string; unitCost: string; total: string; trx: string; status: Badge; when: When };

type Props = {
    investments: Paginated<Row>;
    /** For the filtered list, not just this page */
    totals: { units: string; amount: string; count: string };
    filters: { search: string; date: DateRange };
};

/** Report → Invest History: share purchases, with totals for the current filter. */
export default function Investments({ investments, totals, filters }: Props) {
    const t = useTrans();
    const url = usePage().url;
    const [search, setSearch] = useState(filters.search);
    const [date, setDate] = useState(filters.date);

    return (
        <>
            <PageHeader title={t('Investment Logs')} />
            <div className="row mb-3 g-3">
                <div className="col-md-6 col-xl-3">
                    <StatWidget value={totals.units} title={t('Total Units Invested')} icon="las la-layer-group" color="primary" />
                </div>
                <div className="col-md-6 col-xl-3">
                    <StatWidget value={totals.amount} title={t('Total Amount Invested')} icon="las la-coins" color="success" />
                </div>
                <div className="col-md-6 col-xl-3">
                    <StatWidget value={totals.count} title={t('Total Investments')} icon="las la-receipt" color="warning" />
                </div>
            </div>
            <FilterCard onSubmit={() => visitWithQuery(url, { search: search.trim(), date: dateQuery(date) })}>
                <div className="flex-grow-1">
                    <label htmlFor="inv-search">{t('TRX/Username/Plan')}</label>
                    <input id="inv-search" className="form-control" type="search" value={search} onChange={(e) => setSearch(e.target.value)} placeholder={t('Search')} />
                </div>
                <div className="flex-grow-1">
                    <DateRangeField value={date} onChange={setDate} />
                </div>
            </FilterCard>
            <DataTable
                rows={investments.data}
                rowKey={(r) => r.id}
                links={investments.links}
                empty={t('No investments found')}
                columns={[
                    { label: t('User'), cell: (r) => <UserCell user={r.user} searchUrl={url} /> },
                    { label: t('Plan'), cell: (r) => r.plan },
                    { label: t('Units'), cell: (r) => r.units },
                    { label: t('Unit Price'), className: 'budget', cell: (r) => r.unitCost },
                    { label: t('Total'), className: 'budget', cell: (r) => <span className="fw-bold text--success">{r.total}</span> },
                    { label: t('TRX'), cell: (r) => <strong>{r.trx}</strong> },
                    { label: t('Status'), cell: (r) => <BadgeCell badge={r.status} /> },
                    { label: t('Date'), cell: (r) => <WhenCell when={r.when} /> },
                ]}
            />
        </>
    );
}

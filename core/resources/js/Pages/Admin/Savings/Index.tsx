import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import BoxWidget from '@/Components/Admin/BoxWidget';
import { BadgeCell, UserCell, type AdminUser, type Badge } from '@/Components/Admin/Cells';
import DataTable from '@/Components/Admin/DataTable';
import FilterCard from '@/Components/Admin/FilterCard';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';
import type { Paginated } from '@/types';

type Row = {
    id: number;
    user: AdminUser;
    email: string;
    reference: string;
    type: Badge;
    principal: string;
    rate: string;
    interest: string;
    maturity: string | null;
    status: Badge;
    url: string;
};

type Props = {
    savings: Paginated<Row>;
    summary: { total: number; active: number; principal: string; interest: string };
    filters: { search: string; type: string; status: string };
    urls: { settings: string; cycles: string };
};

/** Savings & Loans → All Savings: every member's savings products. */
export default function SavingsIndex({ savings, summary, filters, urls }: Props) {
    const t = useTrans();
    const url = usePage().url;
    const [search, setSearch] = useState(filters.search);
    const [type, setType] = useState(filters.type);
    const [status, setStatus] = useState(filters.status);

    return (
        <>
            <PageHeader title={t('All Savings Products')}>
                <Link href={urls.settings} className="btn btn-outline--warning">
                    <i className="las la-cog" /> {t('Settings')}
                </Link>
                <Link href={urls.cycles} className="btn btn-outline--success">
                    <i className="las la-seedling" /> {t('Farm Cycles')}
                </Link>
            </PageHeader>
            <div className="row gy-4 mb-4">
                <div className="col-sm-6 col-xl-3">
                    <BoxWidget outline bg="primary" icon="las la-piggy-bank" title={t('Total Products')} value={String(summary.total)} />
                </div>
                <div className="col-sm-6 col-xl-3">
                    <BoxWidget outline bg="success" icon="las la-check-circle" title={t('Active')} value={String(summary.active)} />
                </div>
                <div className="col-sm-6 col-xl-3">
                    <BoxWidget outline bg="warning" icon="las la-clock" title={t('Total Principal')} value={summary.principal} />
                </div>
                <div className="col-sm-6 col-xl-3">
                    <BoxWidget outline bg="info" icon="las la-percentage" title={t('Total Interest')} value={summary.interest} />
                </div>
            </div>
            <FilterCard onSubmit={() => visitWithQuery(url, { search: search.trim(), type, status })}>
                <div className="flex-grow-1">
                    <label htmlFor="sav-search">{t('Search')}</label>
                    <input id="sav-search" type="search" className="form-control" placeholder={t('Username, email or reference')} value={search} onChange={(e) => setSearch(e.target.value)} />
                </div>
                <div className="flex-grow-1">
                    <label htmlFor="sav-type">{t('Type')}</label>
                    <select id="sav-type" className="form-control" value={type} onChange={(e) => setType(e.target.value)}>
                        <option value="">{t('All Types')}</option>
                        <option value="target">{t('Target')}</option>
                        <option value="fixed">{t('Fixed Box')}</option>
                        <option value="farm">{t('Farm Yield')}</option>
                    </select>
                </div>
                <div className="flex-grow-1">
                    <label htmlFor="sav-status">{t('Status')}</label>
                    <select id="sav-status" className="form-control" value={status} onChange={(e) => setStatus(e.target.value)}>
                        <option value="">{t('All Status')}</option>
                        <option value="active">{t('Active')}</option>
                        <option value="matured">{t('Matured')}</option>
                        <option value="closed">{t('Closed')}</option>
                    </select>
                </div>
            </FilterCard>
            <DataTable
                rows={savings.data}
                rowKey={(r) => r.id}
                links={savings.links}
                empty={t('No savings products found.')}
                columns={[
                    { label: t('User'), cell: (r) => <UserCell user={r.user} /> },
                    { label: t('Reference'), cell: (r) => <code>{r.reference}</code> },
                    { label: t('Type'), cell: (r) => <BadgeCell badge={r.type} /> },
                    { label: t('Principal'), cell: (r) => <strong>{r.principal}</strong> },
                    { label: t('Interest Rate'), cell: (r) => r.rate },
                    { label: t('Interest Earned'), cell: (r) => <span className="text--success">{r.interest}</span> },
                    { label: t('Maturity'), cell: (r) => r.maturity ?? '—' },
                    { label: t('Status'), cell: (r) => <BadgeCell badge={r.status} /> },
                    {
                        label: t('Action'),
                        cell: (r) => (
                            <Link href={r.url} className="btn btn-sm btn-outline--primary" aria-label={t('View :ref', { ref: r.reference })}>
                                <i className="las la-eye" /> {t('View')}
                            </Link>
                        ),
                    },
                ]}
            />
        </>
    );
}

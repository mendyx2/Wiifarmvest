import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import BoxWidget from '@/Components/Admin/BoxWidget';
import { UserCell, type AdminUser } from '@/Components/Admin/Cells';
import DataTable from '@/Components/Admin/DataTable';
import FilterCard from '@/Components/Admin/FilterCard';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';
import type { Paginated } from '@/types';

type Row = {
    id: number;
    name: string;
    user: AdminUser;
    state: string | null;
    mega: boolean;
    active: boolean;
    wallet: string;
    url: string;
    email: string;
    phone: string;
    units: number;
    verified: string | boolean;
};

type Filters = { search: string; status: string; type: string; state: string };

type Props = {
    stats: { total: number; active: number; mega: number; units: string; wallets: string };
    stockists: Paginated<Row>;
    states: { id: number; name: string }[];
    filters: Filters;
    urls: { activate: string };
};

/**
 * Manage Stockist → Dashboard: totals and every stockist. It also replaces
 * the old All / Active / Inactive Stockists lists (use the Status filter).
 */
export default function StockistDashboard({ stats, stockists, states, filters, urls }: Props) {
    const t = useTrans();
    const url = usePage().url;
    const [form, setForm] = useState<Filters>(filters);

    return (
        <>
            <PageHeader title={t('Stockist Dashboard')}>
                <Link href={urls.activate} className="btn btn--primary">
                    <i className="las la-user-plus" /> {t('Activate New Stockist')}
                </Link>
            </PageHeader>
            <div className="row gy-4 mb-4">
                <div className="col-xl col-sm-6">
                    <BoxWidget outline bg="primary" icon="las la-users" title={t('Total Stockists')} value={String(stats.total)} note={t(':n Mega stores', { n: stats.mega })} />
                </div>
                <div className="col-xl col-sm-6">
                    <BoxWidget outline bg="success" icon="las la-store" title={t('Active Stockists')} value={String(stats.active)} note={t(':n inactive', { n: stats.total - stats.active })} />
                </div>
                <div className="col-xl col-sm-6">
                    <BoxWidget outline bg="warning" icon="las la-boxes" title={t('Units in Stock')} value={stats.units} note={t('Across all stockists')} />
                </div>
                <div className="col-xl col-sm-6">
                    <BoxWidget outline bg="info" icon="las la-wallet" title={t('Stockist Wallets')} value={stats.wallets} note={t('Total balance')} />
                </div>
            </div>
            <FilterCard onSubmit={() => visitWithQuery(url, { ...form, search: form.search.trim() })}>
                <div className="flex-grow-1">
                    <label htmlFor="sk-search">{t('Search')}</label>
                    <input id="sk-search" className="form-control" type="search" placeholder={t('Business, username, email or phone')} value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} />
                </div>
                <div className="flex-grow-1">
                    <label htmlFor="sk-status">{t('Status')}</label>
                    <select id="sk-status" className="form-control" value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })}>
                        <option value="">{t('All')}</option>
                        <option value="active">{t('Active')}</option>
                        <option value="inactive">{t('Inactive')}</option>
                    </select>
                </div>
                <div className="flex-grow-1">
                    <label htmlFor="sk-type">{t('Store Type')}</label>
                    <select id="sk-type" className="form-control" value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })}>
                        <option value="">{t('All')}</option>
                        <option value="mega">{t('Mega Store')}</option>
                        <option value="mini">{t('Mini Store')}</option>
                    </select>
                </div>
                <div className="flex-grow-1">
                    <label htmlFor="sk-state">{t('State')}</label>
                    <select id="sk-state" className="form-control" value={form.state} onChange={(e) => setForm({ ...form, state: e.target.value })}>
                        <option value="">{t('All')}</option>
                        {states.map((s) => (
                            <option key={s.id} value={s.id}>
                                {s.name}
                            </option>
                        ))}
                    </select>
                </div>
            </FilterCard>
            <DataTable
                rows={stockists.data}
                rowKey={(r) => r.id}
                links={stockists.links}
                empty={t('No stockists found')}
                columns={[
                    {
                        label: t('Business'),
                        cell: (r) => (
                            <>
                                <Link href={r.url} className="fw-bold">
                                    {r.name}
                                </Link>
                                <br />
                                <small className="text-muted">
                                    {r.email || '—'} · {r.phone || '—'}
                                </small>
                            </>
                        ),
                    },
                    { label: t('Owner'), cell: (r) => <UserCell user={r.user} /> },
                    { label: t('State'), cell: (r) => r.state ?? '—' },
                    { label: t('Store Type'), cell: (r) => <span className={`badge ${r.mega ? 'badge--success' : 'badge--primary'}`}>{r.mega ? t('Mega Store') : t('Mini Store')}</span> },
                    { label: t('Wallet'), cell: (r) => <span className="fw-bold">{r.wallet}</span> },
                    { label: t('Units'), cell: (r) => r.units.toLocaleString() },
                    {
                        label: t('Status'),
                        cell: (r) => (
                            <>
                                <span className={`badge ${r.active ? 'badge--success' : 'badge--danger'}`}>{r.active ? t('Active') : t('Inactive')}</span>
                                {r.verified ? (
                                    <small className="d-block text-muted">
                                        {t('Verified')}
                                        {typeof r.verified === 'string' ? ` ${r.verified}` : ''}
                                    </small>
                                ) : (
                                    <small className="d-block text--warning">{t('Not verified')}</small>
                                )}
                            </>
                        ),
                    },
                    {
                        label: t('Action'),
                        cell: (r) => (
                            <div className="button--group">
                                <Link href={r.url} className="btn btn-sm btn-outline--primary">
                                    <i className="las la-desktop" /> {t('Details')}
                                </Link>
                                <Link href={`${r.url}?topup=1`} className="btn btn-sm btn-outline--success">
                                    <i className="las la-wallet" /> {t('Top up')}
                                </Link>
                            </div>
                        ),
                    },
                ]}
            />
        </>
    );
}

import { Link, usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import BoxWidget from '@/Components/Admin/BoxWidget';
import type { Badge } from '@/Components/Admin/Cells';
import PageHeader from '@/Components/Admin/PageHeader';
import Pagination from '@/Components/Pagination';
import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';
import type { Paginated } from '@/types';

type Party = { name: string; username: string | null };
type Row = { id: number; code: string; date: string; seller: Party; buyer: Party; state: string | null; units: number; products: number; amount: string; rebate: string | null; status: Badge; statusLabel: string; url: string };
type Filters = { status: string; search: string; date_from: string; date_to: string };

type Props = {
    transfers: Paginated<Row>;
    stats: { pending: { n: number; held: string }; completed: { n: number; total: string; units: string; rebate: string }; closed: number };
    statuses: { value: string; label: string; count: number }[];
    filters: Filters;
};

/** Manage Stockist → Stock Transfers: Mini stockists buying from a Mega stockist in their state. */
export default function Transfers({ transfers, stats, statuses, filters }: Props) {
    const t = useTrans();
    const url = usePage().url;
    const [form, setForm] = useState(filters);
    const go = (next: Filters) => visitWithQuery(url, next);
    const submit = (e: FormEvent) => {
        e.preventDefault();
        go({ ...form, search: form.search.trim() });
    };

    return (
        <div className="db-wrap atx">
            <PageHeader title={t('Stock Transfers')} />
            <p className="text-muted">{t("The Mini pays when they ask; the money is held until the Mega confirms, or refunded if it doesn't happen.")}</p>
            <div className="row gy-4 fr-boxes">
                <div className="col-xl-3 col-sm-6">
                    <BoxWidget outline bg="warning" icon="las la-hourglass-half" title={t('Waiting for Mega')} value={String(stats.pending.n)} note={t(':amount held', { amount: stats.pending.held })} />
                </div>
                <div className="col-xl-3 col-sm-6">
                    <BoxWidget outline bg="success" icon="las la-exchange-alt" title={t('Stock sold Mega → Mini')} value={stats.completed.total} note={t(':n completed', { n: stats.completed.n })} />
                </div>
                <div className="col-xl-3 col-sm-6">
                    <BoxWidget outline bg="info" icon="las la-boxes" title={t('Units moved')} value={stats.completed.units} note={t(':n declined or cancelled', { n: stats.closed })} />
                </div>
                <div className="col-xl-3 col-sm-6">
                    <BoxWidget outline bg="1" icon="las la-gift" title={t('Rebate paid to Megas')} value={stats.completed.rebate} note={t('Company cost, in stockist_rebate')} />
                </div>
            </div>

            <div className="atx-bar mt-4">
                <nav className="atx-tabs" aria-label={t('Filter by status')}>
                    <button type="button" className={`atx-tab ${!filters.status ? 'is-active' : ''}`} onClick={() => go({ ...filters, status: '' })}>
                        {t('All')}
                    </button>
                    {statuses.map((s) => (
                        <button type="button" key={s.value} className={`atx-tab ${filters.status === s.value ? 'is-active' : ''}`} onClick={() => go({ ...filters, status: s.value })}>
                            {t(s.label)} <span>{s.count}</span>
                        </button>
                    ))}
                </nav>
                <form className="atx-search" onSubmit={submit} role="search">
                    <input type="search" className="form-control" aria-label={t('Search')} placeholder={t('Code, store or username')} value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} />
                    <input type="date" className="form-control" aria-label={t('From')} value={form.date_from} onChange={(e) => setForm({ ...form, date_from: e.target.value })} />
                    <input type="date" className="form-control" aria-label={t('To')} value={form.date_to} onChange={(e) => setForm({ ...form, date_to: e.target.value })} />
                    <button type="submit" className="btn btn--primary" aria-label={t('Search')}>
                        <i className="las la-search" />
                    </button>
                </form>
            </div>

            <div className="card atx-card">
                <div className="card-body p-0">
                    <div className="table-responsive--md table-responsive">
                        <table className="table table--light style--two mb-0">
                            <thead>
                                <tr>
                                    <th>{t('Transfer')}</th>
                                    <th>
                                        {t('Mega')} <i className="las la-long-arrow-alt-right" /> {t('Mini')}
                                    </th>
                                    <th>{t('State')}</th>
                                    <th>{t('Stock')}</th>
                                    <th>{t('Amount')}</th>
                                    <th>{t('Rebate')}</th>
                                    <th>{t('Status')}</th>
                                    <th aria-label={t('Action')} />
                                </tr>
                            </thead>
                            <tbody>
                                {transfers.data.length ? (
                                    transfers.data.map((r) => (
                                        <tr key={r.id}>
                                            <td data-label={t('Transfer')}>
                                                <span className="atx-code">{r.code}</span>
                                                <small className="d-block text-muted">{r.date}</small>
                                            </td>
                                            <td className="text-start" data-label={`${t('Mega')} → ${t('Mini')}`}>
                                                <div className="atx-flow">
                                                    <span>
                                                        <strong>{r.seller.name}</strong>
                                                        {r.seller.username && <small>@{r.seller.username}</small>}
                                                    </span>
                                                    <i className="las la-long-arrow-alt-right" />
                                                    <span>
                                                        <strong>{r.buyer.name}</strong>
                                                        {r.buyer.username && <small>@{r.buyer.username}</small>}
                                                    </span>
                                                </div>
                                            </td>
                                            <td data-label={t('State')}>{r.state ?? '—'}</td>
                                            <td data-label={t('Stock')}>
                                                {r.units.toLocaleString()} {t(r.units === 1 ? 'unit' : 'units')}
                                                <small className="d-block text-muted">
                                                    {r.products} {t(r.products === 1 ? 'product' : 'products')}
                                                </small>
                                            </td>
                                            <td data-label={t('Amount')}>
                                                <strong>{r.amount}</strong>
                                            </td>
                                            <td data-label={t('Rebate')}>{r.rebate ?? '—'}</td>
                                            <td data-label={t('Status')}>
                                                <span className={`badge ${r.status.class}`} title={r.statusLabel}>
                                                    {r.status.label}
                                                </span>
                                            </td>
                                            <td>
                                                <Link href={r.url} className="btn btn-sm btn-outline--primary">
                                                    <i className="las la-eye" /> {t('Details')}
                                                </Link>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={8} className="text-center py-5 text-muted">
                                            <i className="las la-exchange-alt d-block fs-1 mb-2" />
                                            {filters.status || filters.search ? t('No transfers match these filters.') : t('No stock transfers yet. They appear here when a Mini stockist buys from a Mega stockist.')}
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
                {transfers.last_page > 1 && (
                    <div className="card-footer py-3">
                        <Pagination links={transfers.links} />
                    </div>
                )}
            </div>
        </div>
    );
}

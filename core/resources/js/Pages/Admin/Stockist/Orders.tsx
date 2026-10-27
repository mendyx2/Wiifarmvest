import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import BoxWidget from '@/Components/Admin/BoxWidget';
import { BadgeCell, WhenCell, type Badge, type When } from '@/Components/Admin/Cells';
import DataTable from '@/Components/Admin/DataTable';
import FilterCard from '@/Components/Admin/FilterCard';
import { ConfirmButton } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';
import type { Paginated } from '@/types';

type Row = {
    id: number;
    number: string;
    stockist: { name: string; owner: string | null; url: string | null };
    date: When;
    products: number;
    units: number;
    total: string;
    status: Badge;
    next: { label: string; url: string } | null;
    url: string;
};

type Filters = { status: string; search: string; date_from: string; date_to: string };

type Props = {
    orders: Paginated<Row>;
    counts: Record<'pending' | 'approved' | 'processing' | 'shipped' | 'delivered' | 'cancelled' | 'total', number>;
    filters: Filters;
};

const STATUSES = ['pending', 'approved', 'processing', 'shipped', 'delivered', 'cancelled'] as const;

/**
 * Manage Stockist → Stockist Orders: stock bought from the company. It
 * also replaces the retired "Stockist Order Request" and "Supplies" pages.
 */
export default function StockistOrders({ orders, counts, filters }: Props) {
    const t = useTrans();
    const url = usePage().url;
    const [form, setForm] = useState(filters);

    return (
        <>
            <PageHeader title={t('Stockist Orders')} />
            <div className="row gy-4 mb-4">
                {(
                    [
                        ['total', 'las la-list', 'primary', t('Total Orders')],
                        ['pending', 'las la-clock', 'warning', t('Pending')],
                        ['approved', 'las la-check', 'info', t('Approved')],
                        ['shipped', 'las la-shipping-fast', 'primary', t('Shipped')],
                        ['delivered', 'las la-check-circle', 'success', t('Delivered')],
                    ] as const
                ).map(([key, icon, bg, label]) => (
                    <div className="col-xl col-sm-4 col-6" key={key}>
                        <BoxWidget outline bg={bg} icon={icon} title={label} value={String(counts[key])} />
                    </div>
                ))}
            </div>
            <FilterCard onSubmit={() => visitWithQuery(url, { ...form, search: form.search.trim() })}>
                <div className="flex-grow-1">
                    <label htmlFor="so-search">{t('Search')}</label>
                    <input id="so-search" className="form-control" type="search" placeholder={t('Order # or stockist')} value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} />
                </div>
                <div className="flex-grow-1">
                    <label htmlFor="so-status">{t('Status')}</label>
                    <select id="so-status" className="form-control" value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })}>
                        <option value="">{t('All Statuses')}</option>
                        {STATUSES.map((s) => (
                            <option key={s} value={s}>
                                {t(s.charAt(0).toUpperCase() + s.slice(1))} ({counts[s]})
                            </option>
                        ))}
                    </select>
                </div>
                <div className="flex-grow-1">
                    <label htmlFor="so-from">{t('From Date')}</label>
                    <input id="so-from" className="form-control" type="date" value={form.date_from} onChange={(e) => setForm({ ...form, date_from: e.target.value })} />
                </div>
                <div className="flex-grow-1">
                    <label htmlFor="so-to">{t('To Date')}</label>
                    <input id="so-to" className="form-control" type="date" value={form.date_to} onChange={(e) => setForm({ ...form, date_to: e.target.value })} />
                </div>
            </FilterCard>
            <DataTable
                rows={orders.data}
                rowKey={(r) => r.id}
                links={orders.links}
                empty={t('No orders match your current filters.')}
                columns={[
                    { label: t('Order #'), cell: (r) => <strong>{r.number}</strong> },
                    {
                        label: t('Stockist'),
                        cell: (r) => (
                            <>
                                {r.stockist.url ? <Link href={r.stockist.url}>{r.stockist.name}</Link> : r.stockist.name}
                                {r.stockist.owner && <small className="d-block text-muted">{r.stockist.owner}</small>}
                            </>
                        ),
                    },
                    { label: t('Date'), cell: (r) => <WhenCell when={r.date} /> },
                    {
                        label: t('Items'),
                        cell: (r) => (
                            <>
                                {t(':n item(s)', { n: r.products })}
                                <small className="d-block text-muted">{t(':n units', { n: r.units })}</small>
                            </>
                        ),
                    },
                    { label: t('Amount'), cell: (r) => <strong className="text--primary">{r.total}</strong> },
                    { label: t('Status'), cell: (r) => <BadgeCell badge={r.status} /> },
                    {
                        label: t('Action'),
                        cell: (r) => (
                            <div className="button--group">
                                <Link href={r.url} className="btn btn-sm btn-outline--primary">
                                    <i className="las la-desktop" /> {t('Details')}
                                </Link>
                                {r.next && (
                                    <ConfirmButton url={r.next.url} className="btn btn-sm btn-outline--success" question={t(':action order :number?', { action: t(r.next.label), number: r.number })}>
                                        {t(r.next.label)}
                                    </ConfirmButton>
                                )}
                            </div>
                        ),
                    },
                ]}
            />
        </>
    );
}

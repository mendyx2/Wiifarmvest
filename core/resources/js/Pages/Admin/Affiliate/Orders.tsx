import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { BadgeCell, type AdminUser, type Badge } from '@/Components/Admin/Cells';
import DataTable from '@/Components/Admin/DataTable';
import FilterCard from '@/Components/Admin/FilterCard';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';
import type { Paginated } from '@/types';

export type AffiliateOrderRow = {
    id: number;
    code: string;
    buyer: { name: string; email: string };
    affiliate: AdminUser;
    state: string | null;
    payment: string;
    total: string;
    status: Badge;
    date: string;
    url: string;
};

type Filters = { search: string; status: string; payment_method: string; state_id: string };

type Props = {
    orders: Paginated<AffiliateOrderRow>;
    statuses: { value: string; label: string }[];
    states: { id: number; name: string }[];
    filters: Filters;
};

/** Affiliate Program → Affiliate Orders: /shop orders, with or without an affiliate. */
export default function AffiliateOrders({ orders, statuses, states, filters }: Props) {
    const t = useTrans();
    const url = usePage().url;
    const [form, setForm] = useState(filters);

    return (
        <>
            <PageHeader title={t('Affiliate Orders')} />
            <FilterCard onSubmit={() => visitWithQuery(url, { ...form, search: form.search.trim() })}>
                <div className="flex-grow-1">
                    <label htmlFor="ao-search">{t('Search')}</label>
                    <input id="ao-search" type="search" className="form-control" placeholder={t('Code, buyer or affiliate')} value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} />
                </div>
                <div className="flex-grow-1">
                    <label htmlFor="ao-status">{t('Status')}</label>
                    <select id="ao-status" className="form-control" value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })}>
                        <option value="">{t('All')}</option>
                        {statuses.map((s) => (
                            <option key={s.value} value={s.value}>
                                {t(s.label)}
                            </option>
                        ))}
                    </select>
                </div>
                <div className="flex-grow-1">
                    <label htmlFor="ao-payment">{t('Payment Method')}</label>
                    <select id="ao-payment" className="form-control" value={form.payment_method} onChange={(e) => setForm({ ...form, payment_method: e.target.value })}>
                        <option value="">{t('All')}</option>
                        <option value="paystack">Paystack</option>
                        <option value="cash_on_pickup">{t('Cash on Pickup')}</option>
                    </select>
                </div>
                <div className="flex-grow-1">
                    <label htmlFor="ao-state">{t('State')}</label>
                    <select id="ao-state" className="form-control" value={form.state_id} onChange={(e) => setForm({ ...form, state_id: e.target.value })}>
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
                rows={orders.data}
                rowKey={(o) => o.id}
                links={orders.links}
                empty={t('No orders found.')}
                columns={[
                    { label: t('Code'), cell: (o) => <code>{o.code}</code> },
                    {
                        label: t('Buyer'),
                        cell: (o) => (
                            <>
                                {o.buyer.name}
                                <small className="d-block text-muted">{o.buyer.email}</small>
                            </>
                        ),
                    },
                    { label: t('Affiliate'), cell: (o) => (o.affiliate ? <Link href={o.affiliate.url}>{o.affiliate.fullname}</Link> : t('Direct')) },
                    { label: t('State'), cell: (o) => o.state ?? '—' },
                    { label: t('Payment'), cell: (o) => t(o.payment) },
                    { label: t('Total'), cell: (o) => <strong>{o.total}</strong> },
                    { label: t('Status'), cell: (o) => <BadgeCell badge={o.status} /> },
                    { label: t('Date'), cell: (o) => o.date },
                    {
                        label: t('Action'),
                        cell: (o) => (
                            <Link href={o.url} className="btn btn-sm btn-outline--primary">
                                <i className="las la-desktop" /> {t('View')}
                            </Link>
                        ),
                    },
                ]}
            />
        </>
    );
}

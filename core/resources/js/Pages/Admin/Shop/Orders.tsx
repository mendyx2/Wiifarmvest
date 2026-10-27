import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import BoxWidget from '@/Components/Admin/BoxWidget';
import { BadgeCell, UserCell, WhenCell, type AdminUser, type Badge, type When } from '@/Components/Admin/Cells';
import DataTable from '@/Components/Admin/DataTable';
import FilterCard from '@/Components/Admin/FilterCard';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';
import type { Paginated } from '@/types';

type Row = {
    id: number;
    user: AdminUser;
    invoice: string;
    state: string | null;
    items: number;
    total: string;
    date: When;
    status: Badge;
    redeemed: Badge;
    url: string;
};

type Filters = { search: string; status: string; redeemed: string };

type Props = {
    title: string;
    orders: Paginated<Row>;
    stats: { orders: number; pending: number; redeemed: number; revenue: string };
    filters: Filters;
};

/**
 * Shop → Orders & Sales: member product orders. One page for what were
 * "Manage Order" (ship / cancel) and "Manage Sales" (collection at
 * stockists); the order page has both.
 */
export default function Orders({ title, orders, stats, filters }: Props) {
    const t = useTrans();
    const url = usePage().url;
    const [form, setForm] = useState<Filters>(filters);

    return (
        <>
            <PageHeader title={t(title)} />
            <div className="row gy-4 mb-4">
                <div className="col-xl-3 col-sm-6">
                    <BoxWidget outline bg="primary" icon="las la-shopping-bag" title={t('Orders Today')} value={String(stats.orders)} />
                </div>
                <div className="col-xl-3 col-sm-6">
                    <BoxWidget outline bg="warning" icon="las la-clock" title={t('Pending Today')} value={String(stats.pending)} />
                </div>
                <div className="col-xl-3 col-sm-6">
                    <BoxWidget outline bg="success" icon="las la-check-double" title={t('Fully Collected Today')} value={String(stats.redeemed)} />
                </div>
                <div className="col-xl-3 col-sm-6">
                    <BoxWidget outline bg="info" icon="las la-money-bill-wave" title={t("Today's Revenue")} value={stats.revenue} note={t('Cancelled orders not counted')} />
                </div>
            </div>
            <FilterCard onSubmit={() => visitWithQuery(url, { ...form, search: form.search.trim() })}>
                <div className="flex-grow-1">
                    <label htmlFor="order-search">{t('Search')}</label>
                    <input id="order-search" className="form-control" type="search" placeholder={t('Invoice, TRX or member')} value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} />
                </div>
                <div className="flex-grow-1">
                    <label htmlFor="order-status">{t('Status')}</label>
                    <select id="order-status" className="form-control" value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })}>
                        <option value="">{t('All')}</option>
                        <option value="pending">{t('Pending')}</option>
                        <option value="shipped">{t('Shipped / Collected')}</option>
                        <option value="cancelled">{t('Cancelled')}</option>
                    </select>
                </div>
                <div className="flex-grow-1">
                    <label htmlFor="order-redeemed">{t('Collected at stockist')}</label>
                    <select id="order-redeemed" className="form-control" value={form.redeemed} onChange={(e) => setForm({ ...form, redeemed: e.target.value })}>
                        <option value="">{t('All')}</option>
                        <option value="none">{t('None')}</option>
                        <option value="partial">{t('Partly')}</option>
                        <option value="full">{t('Fully')}</option>
                    </select>
                </div>
            </FilterCard>
            <DataTable
                rows={orders.data}
                rowKey={(r) => r.id}
                links={orders.links}
                empty={t('Order not found')}
                columns={[
                    { label: t('User'), cell: (r) => <UserCell user={r.user} /> },
                    { label: t('Invoice'), cell: (r) => <code>{r.invoice}</code> },
                    { label: t('State'), cell: (r) => r.state ?? '—' },
                    { label: t('Items'), cell: (r) => r.items },
                    { label: t('Total'), cell: (r) => <span className="fw-bold">{r.total}</span> },
                    { label: t('Date'), cell: (r) => <WhenCell when={r.date} /> },
                    { label: t('Status'), cell: (r) => <BadgeCell badge={r.status} /> },
                    { label: t('Collected'), cell: (r) => <BadgeCell badge={r.redeemed} /> },
                    {
                        label: t('Action'),
                        cell: (r) => (
                            <Link href={r.url} className="btn btn-sm btn-outline--primary">
                                <i className="las la-desktop" /> {t('Details')}
                            </Link>
                        ),
                    },
                ]}
            />
        </>
    );
}

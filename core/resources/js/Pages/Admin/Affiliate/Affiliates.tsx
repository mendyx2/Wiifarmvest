import { Link, usePage } from '@inertiajs/react';
import { UserCell, type AdminUser } from '@/Components/Admin/Cells';
import DataTable from '@/Components/Admin/DataTable';
import PageHeader from '@/Components/Admin/PageHeader';
import SearchForm from '@/Components/Admin/SearchForm';
import { useTrans } from '@/lib/i18n';
import type { Paginated } from '@/types';

type Row = { id: number; user: AdminUser; clicks: number; orders: number; bonus: string; ordersUrl: string };

/** Affiliate Program → Active Affiliates: members whose links got clicks or orders. */
export default function Affiliates({ affiliates, search }: { affiliates: Paginated<Row>; search: string }) {
    const t = useTrans();
    const url = usePage().url;
    return (
        <>
            <PageHeader title={t('Affiliates')}>
                <SearchForm url={url} value={search} placeholder="Username or name" />
            </PageHeader>
            <DataTable
                responsive="sm"
                rows={affiliates.data}
                rowKey={(r) => r.id}
                links={affiliates.links}
                empty={t('No affiliate activity yet.')}
                columns={[
                    { label: t('Member'), cell: (r) => <UserCell user={r.user} /> },
                    { label: t('Clicks'), cell: (r) => r.clicks.toLocaleString() },
                    { label: t('Orders'), cell: (r) => (r.orders ? <Link href={r.ordersUrl}>{r.orders.toLocaleString()}</Link> : '0') },
                    { label: t('Bonus Balance'), cell: (r) => r.bonus },
                ]}
            />
        </>
    );
}

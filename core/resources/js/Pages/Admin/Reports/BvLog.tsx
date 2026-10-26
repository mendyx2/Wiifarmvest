import { usePage } from '@inertiajs/react';
import { BadgeCell, UserCell, type AdminUser, type Badge } from '@/Components/Admin/Cells';
import DataTable from '@/Components/Admin/DataTable';
import PageHeader from '@/Components/Admin/PageHeader';
import SearchForm from '@/Components/Admin/SearchForm';
import { useTrans } from '@/lib/i18n';
import type { Paginated } from '@/types';

type Row = { id: number; user: AdminUser; amount: string; credit: boolean; position: Badge; date: string; details: string };

type Props = { logs: Paginated<Row>; title: string; filters: { search: string; type: string } };

/**
 * BV Log (?type=leftBV | rightBV | cutBV | paidBV), opened from the old
 * partner dashboard and a member's admin page. Searching keeps the type.
 */
export default function BvLog({ logs, title, filters }: Props) {
    const t = useTrans();
    const url = usePage().url;

    return (
        <>
            <PageHeader title={t(title)}>
                {/* Searching keeps ?type= */}
                <SearchForm url={url} value={filters.search} placeholder="Username" extra={filters.type ? { type: filters.type } : undefined} />
            </PageHeader>
            <DataTable
                rows={logs.data}
                rowKey={(r) => r.id}
                links={logs.links}
                columns={[
                    { label: t('Username'), cell: (r) => <UserCell user={r.user} /> },
                    { label: t('BV'), className: 'budget', cell: (r) => <strong className={r.credit ? 'text-success' : 'text-danger'}>{r.amount}</strong> },
                    { label: t('Position'), cell: (r) => <BadgeCell badge={r.position} /> },
                    { label: t('Date'), cell: (r) => r.date },
                    { label: t('Detail'), cell: (r) => r.details },
                ]}
            />
        </>
    );
}

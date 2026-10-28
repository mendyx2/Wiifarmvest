import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { UserCell, WhenCell, type AdminUser, type When } from '@/Components/Admin/Cells';
import DataTable from '@/Components/Admin/DataTable';
import DateRangeField, { dateQuery, type DateRange } from '@/Components/Admin/DateRangeField';
import FilterCard from '@/Components/Admin/FilterCard';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';
import type { Paginated } from '@/types';

type Row = { id: number; user: AdminUser; when: When; ip: string; ipUrl: string | null; location: string; browser: string; os: string };

type Props = {
    title: string;
    logs: Paginated<Row>;
    /** Set on "Login by - <ip>" */
    ip: string | null;
    filters: { search: string; date: DateRange };
    urls: { all: string };
};

/** Report → Login History, and the logins from one IP address. */
export default function Logins({ title, logs, ip, filters, urls }: Props) {
    const t = useTrans();
    const url = usePage().url;
    const [search, setSearch] = useState(filters.search);
    const [date, setDate] = useState(filters.date);

    return (
        <>
            <PageHeader title={title}>
                {ip && (
                    <Link href={urls.all} className="btn btn-sm btn-outline--primary">
                        <i className="las la-undo" /> {t('All logins')}
                    </Link>
                )}
            </PageHeader>
            {/* The Blade page had no filters, though the server took ?search= and ?date= (the member page linked with ?search=). */}
            {!ip && (
                <FilterCard onSubmit={() => visitWithQuery(url, { search: search.trim(), date: dateQuery(date) })}>
                    <div className="flex-grow-1">
                        <label htmlFor="login-search">{t('Username')}</label>
                        <input id="login-search" className="form-control" type="search" value={search} onChange={(e) => setSearch(e.target.value)} placeholder={t('Search')} />
                    </div>
                    <div className="flex-grow-1">
                        <DateRangeField value={date} onChange={setDate} />
                    </div>
                </FilterCard>
            )}
            <DataTable
                responsive="sm"
                rows={logs.data}
                rowKey={(r) => r.id}
                links={logs.links}
                columns={[
                    { label: t('User'), cell: (r) => <UserCell user={r.user} /> },
                    { label: t('Login at'), cell: (r) => <WhenCell when={r.when} /> },
                    {
                        label: t('IP'),
                        cell: (r) => (
                            <span className="fw-bold">{r.ipUrl && r.ip !== ip ? <Link href={r.ipUrl}>{r.ip}</Link> : r.ip}</span>
                        ),
                    },
                    { label: t('Location'), cell: (r) => r.location || '—' },
                    {
                        label: t('Browser | OS'),
                        cell: (r) => (
                            <>
                                {r.browser}
                                <br />
                                {r.os}
                            </>
                        ),
                    },
                ]}
            />
        </>
    );
}

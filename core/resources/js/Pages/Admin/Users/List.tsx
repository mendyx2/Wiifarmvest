import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import { UserCell, WhenCell, type AdminUser, type When } from '@/Components/Admin/Cells';
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
    mobile: string;
    sponsor: string | null;
    joined: When;
    balance: string;
    /** On "KYC Pending" only */
    kycUrl: string | null;
};

type Props = { title: string; users: Paginated<Row>; filters: { search: string; status: string } };

/**
 * Manage Users: All, Active, Banned, Email/Mobile (un)verified, KYC
 * unverified/pending, With Balance, Paid, Free, and "Referred By <member>".
 */
export default function UsersList({ title, users, filters }: Props) {
    const t = useTrans();
    const url = usePage().url;
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);

    return (
        <>
            <PageHeader title={t(title)} />
            <FilterCard onSubmit={() => visitWithQuery(url, { search: search.trim(), status })}>
                <div className="flex-grow-1">
                    <label htmlFor="user-search">{t('Search')}</label>
                    <input id="user-search" className="form-control" type="search" value={search} onChange={(e) => setSearch(e.target.value)} placeholder={t('Name, email or username')} />
                </div>
                <div className="flex-grow-1">
                    <label htmlFor="user-status">{t('Status')}</label>
                    <select id="user-status" className="form-control" value={status} onChange={(e) => setStatus(e.target.value)}>
                        <option value="">{t('All Status')}</option>
                        <option value="1">{t('Active')}</option>
                        <option value="0">{t('Banned')}</option>
                    </select>
                </div>
            </FilterCard>
            <DataTable
                responsive="md"
                rows={users.data}
                rowKey={(r) => r.id}
                links={users.links}
                columns={[
                    { label: t('User'), cell: (r) => <UserCell user={r.user} /> },
                    {
                        label: t('Email-Mobile'),
                        cell: (r) => (
                            <>
                                {r.email}
                                <br />
                                {r.mobile}
                            </>
                        ),
                    },
                    { label: t('Sponsor'), cell: (r) => (r.sponsor ? `@${r.sponsor}` : <span className="text-muted">{t('N/A')}</span>) },
                    { label: t('Joined At'), cell: (r) => <WhenCell when={r.joined} /> },
                    { label: t('Balance'), cell: (r) => <span className="fw-bold">{r.balance}</span> },
                    {
                        label: t('Action'),
                        cell: (r) => (
                            <div className="button--group">
                                {/* The member page is still Blade: a normal link. */}
                                {r.user && (
                                    <a href={r.user.url} className="btn btn-sm btn-outline--primary">
                                        <i className="las la-desktop" /> {t('Details')}
                                    </a>
                                )}
                                {r.kycUrl && (
                                    <a href={r.kycUrl} target="_blank" rel="noopener noreferrer" className="btn btn-sm btn-outline--dark">
                                        <i className="las la-user-check" /> {t('KYC Data')}
                                    </a>
                                )}
                            </div>
                        ),
                    },
                ]}
            />
        </>
    );
}

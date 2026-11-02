import { Link } from '@inertiajs/react';
import DataTable from '@/Components/Admin/DataTable';
import PageHeader from '@/Components/Admin/PageHeader';
import SearchForm from '@/Components/Admin/SearchForm';
import { useTrans } from '@/lib/i18n';
import type { Paginated } from '@/types';

type Investor = { id: number; name: string; username: string; email: string; units: string; investments: number; credits: string; paid: string; url: string; member: string };

type Props = {
    search: string;
    investors: Paginated<Investor>;
    urls: { self: string; batches: string };
};

/** FFO investors: members with active holdings, their units and the dividends they received. */
export default function Investors({ search, investors, urls }: Props) {
    const t = useTrans();
    return (
        <>
            <PageHeader title={t('FFO Investors - Shares & Dividends')}>
                <SearchForm url={urls.self} value={search} placeholder="Name, username or email" />
                <Link href={urls.batches} className="btn btn-sm btn-outline--primary">
                    <i className="las la-hand-holding-usd" /> {t('Dividend Payments')}
                </Link>
            </PageHeader>
            <DataTable
                rows={investors.data}
                rowKey={(i) => i.id}
                links={investors.links}
                empty={search ? t('No investor matches this search.') : t('No investors yet.')}
                columns={[
                    {
                        label: t('Member'),
                        cell: (i) => (
                            <Link href={i.member}>
                                {i.name}
                                <small className="text-muted d-block">@{i.username}</small>
                            </Link>
                        ),
                    },
                    { label: t('Email'), cell: (i) => i.email },
                    { label: t('Units'), cell: (i) => i.units },
                    { label: t('Holdings'), cell: (i) => i.investments },
                    { label: t('Share Credits'), cell: (i) => i.credits },
                    { label: t('Dividends Paid'), cell: (i) => <strong>{i.paid}</strong> },
                    {
                        label: t('Action'),
                        cell: (i) => (
                            <Link href={i.url} className="btn btn-sm btn-outline--primary">
                                <i className="las la-list" /> {t('Dividends')}
                            </Link>
                        ),
                    },
                ]}
            />
        </>
    );
}

import { Link, router } from '@inertiajs/react';
import DataTable from '@/Components/Admin/DataTable';
import PageHeader from '@/Components/Admin/PageHeader';
import SearchForm from '@/Components/Admin/SearchForm';
import { useTrans } from '@/lib/i18n';
import type { Paginated } from '@/types';

type Row = { id: string; member: { name: string; username: string; email: string; url: string } | null; amount: string; note: string; date: string; trx: string };

type Props = {
    title: string;
    stats: { label: string; value: string; icon: string }[];
    tabs: { key: string; label: string; url: string }[];
    tab: string;
    search: string;
    rows: Paginated<Row>;
    url: string;
    months?: string[];
    month?: string | null;
};

/** Autoship and Key-In Bonus: what members hold, what was credited and (autoship) what was swept. One page for both. */
export default function BonusWallet({ title, stats, tabs, tab, search, rows, url, months, month }: Props) {
    const t = useTrans();
    const holding = tab === 'current';
    return (
        <>
            <PageHeader title={t(title)}>
                {months && (
                    <select
                        className="form-select form-select-sm w-auto"
                        aria-label={t('Month')}
                        value={month ?? ''}
                        onChange={(e) => router.get(url, { tab, search: search || undefined, month: e.target.value || undefined }, { preserveScroll: true })}
                    >
                        <option value="">{t('All months')}</option>
                        {months.map((m) => (
                            <option key={m} value={m}>
                                {m}
                            </option>
                        ))}
                    </select>
                )}
                <SearchForm url={url} value={search} placeholder="Username or email" extra={{ tab, ...(month ? { month } : {}) }} />
            </PageHeader>
            <div className="row gy-3 mb-4">
                {stats.map((s) => (
                    <div className={`col-sm-6 ${stats.length > 2 ? 'col-xl-3' : ''}`} key={s.label}>
                        <div className="card h-100">
                            <div className="card-body d-flex align-items-center gap-3">
                                <i className={`${s.icon} fs-2 text--primary`} aria-hidden />
                                <div>
                                    <h5 className="mb-0">{s.value}</h5>
                                    <small className="text-muted">{t(s.label)}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
            <ul className="nav nav-tabs mb-3">
                {tabs.map((x) => (
                    <li className="nav-item" key={x.key}>
                        <Link className={`nav-link ${x.key === tab ? 'active' : ''}`} href={x.url} preserveScroll>
                            {t(x.label)}
                        </Link>
                    </li>
                ))}
            </ul>
            <DataTable
                rows={rows.data}
                rowKey={(r) => r.id}
                links={rows.links}
                empty={search ? t('Nothing matches this search.') : t('Nothing here yet.')}
                columns={[
                    {
                        label: t('Member'),
                        cell: (r) =>
                            r.member ? (
                                <Link href={r.member.url}>
                                    {r.member.name}
                                    <small className="text-muted d-block">@{r.member.username}</small>
                                </Link>
                            ) : (
                                '—'
                            ),
                    },
                    ...(holding
                        ? [{ label: t('Email'), cell: (r: Row) => r.member?.email ?? '' }]
                        : [
                              { label: tab === 'lost' ? t('Month') : t('Details'), cell: (r: Row) => <small>{r.note}</small> },
                              { label: t('Date'), cell: (r: Row) => r.date },
                          ]),
                    { label: holding ? t('Balance') : t('Amount'), cell: (r) => <strong>{r.amount}</strong> },
                ]}
            />
        </>
    );
}

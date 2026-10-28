import { Link, usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import type { AdminUser } from '@/Components/Admin/Cells';
import PageHeader from '@/Components/Admin/PageHeader';
import Pagination from '@/Components/Pagination';
import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';
import type { Paginated } from '@/types';
import './Index.css';

type Row = { id: number; user: AdminUser; code: string; source: string; status: string; amount: string; stockist: string | null; date: string; time: string };
type Filters = { tab: string; search: string; user: string };

type Props = {
    packages: Paginated<Row>;
    stats: { pending: string; pendingCount: number; redeemed: string; redeemedCount: number };
    filters: Filters;
};

/** Welcome Pack → All Welcome Packages: registration and upgrade cash-back codes, and who redeemed them. */
export default function WelcomePackages({ packages, stats, filters }: Props) {
    const t = useTrans();
    const url = usePage().url;
    const [search, setSearch] = useState(filters.search);
    const go = (next: Partial<Filters>) => visitWithQuery(url, { ...filters, ...next });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        go({ search: search.trim() });
    };
    const redeemed = filters.tab === 'redeemed';
    const first = (packages.current_page - 1) * packages.per_page + 1;

    return (
        <div className="page-welcome-pack">
            <PageHeader title={t('Welcome Packages')} />
            <div className="wp-wrap">
                <div className="wp-hero">
                    <div className="wp-hero-icon">
                        <i className="las la-gift" />
                    </div>
                    <div className="wp-hero-body">
                        <h2 className="text-white">{t('Welcome Packages')}</h2>
                        <p className="text-white">{t('Registration & upgrade cash-back held as redeemable codes until a stockist redeems them in person.')}</p>
                    </div>
                </div>
                <div className="wp-stats">
                    <div className="wp-stat">
                        <div className="wp-stat-icon" style={{ background: '#fef9c3', color: '#854d0e' }}>
                            <i className="las la-hourglass-half" />
                        </div>
                        <div>
                            <div className="wp-stat-val">{stats.pending}</div>
                            <div className="wp-stat-lbl">{t('Pending (:n packages)', { n: stats.pendingCount })}</div>
                        </div>
                    </div>
                    <div className="wp-stat">
                        <div className="wp-stat-icon" style={{ background: '#f0fdf4', color: '#16a34a' }}>
                            <i className="las la-check-circle" />
                        </div>
                        <div>
                            <div className="wp-stat-val">{stats.redeemed}</div>
                            <div className="wp-stat-lbl">{t('Redeemed (:n packages)', { n: stats.redeemedCount })}</div>
                        </div>
                    </div>
                </div>
                {filters.user && (
                    <div className="mb-3">
                        <span className="wp-filter-chip">
                            <i className="las la-filter" /> {t('Showing packages for @:user', { user: filters.user })}
                            <button type="button" className="border-0 bg-transparent p-0 text--success" aria-label={t('Clear member filter')} onClick={() => go({ user: '' })}>
                                <i className="las la-times" />
                            </button>
                        </span>
                    </div>
                )}
                <div className="wp-tabs" role="tablist">
                    {(
                        [
                            ['pending', 'las la-hourglass-half', t('Pending'), stats.pendingCount],
                            ['redeemed', 'las la-check-circle', t('Redeemed'), stats.redeemedCount],
                        ] as const
                    ).map(([key, icon, label, count]) => (
                        <button key={key} type="button" role="tab" aria-selected={filters.tab === key} className={`wp-tab border-0 ${filters.tab === key ? 'active' : ''}`} onClick={() => go({ tab: key, search: '' })}>
                            <i className={icon} /> {label}
                            {count > 0 && <span className="wp-tab-count">{count}</span>}
                        </button>
                    ))}
                </div>
                <div className="wp-card">
                    <div className="wp-card-head">
                        <h6>
                            <i className="las la-list me-1" /> {redeemed ? t('Redemption History') : t('Awaiting Redemption')}
                        </h6>
                        <form onSubmit={submit} className="wp-filters" role="search">
                            <input type="search" aria-label={t('Search')} placeholder={t('Member or code…')} className="wp-input" value={search} onChange={(e) => setSearch(e.target.value)} />
                            <button type="submit" className="wp-btn wp-btn-primary">
                                <i className="las la-search" /> {t('Search')}
                            </button>
                            {filters.search && (
                                <button type="button" className="wp-btn wp-btn-muted" onClick={() => (setSearch(''), go({ search: '' }))}>
                                    <i className="las la-times" /> {t('Clear')}
                                </button>
                            )}
                        </form>
                    </div>
                    {packages.data.length ? (
                        <>
                            <div className="table-responsive">
                                <table className="wp-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{t('User')}</th>
                                            <th>{t('Code')}</th>
                                            <th>{t('Source')}</th>
                                            <th>{t('Amount')}</th>
                                            {redeemed && <th>{t('Redeemed By')}</th>}
                                            <th>{redeemed ? t('Redeemed On') : t('Created')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {packages.data.map((p, i) => (
                                            <tr key={p.id}>
                                                <td className="text-muted small">{first + i}</td>
                                                <td>
                                                    <div className="wp-user">
                                                        <div className="wp-av">
                                                            {(p.user?.fullname ?? '?')
                                                                .split(/\s+/)
                                                                .map((w) => w[0] ?? '')
                                                                .join('')
                                                                .slice(0, 2)
                                                                .toUpperCase()}
                                                        </div>
                                                        <div>
                                                            <div className="wp-uname">{p.user ? <Link href={p.user.url}>{p.user.fullname}</Link> : '—'}</div>
                                                            <div className="wp-uid">@ {p.user?.username ?? ''}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span className="wp-code">{p.code}</span>
                                                </td>
                                                <td>
                                                    <span className={`wp-chip wp-chip--${p.source}`}>{t(p.source)}</span>
                                                </td>
                                                <td>
                                                    <span className="wp-amt">{p.amount}</span>
                                                </td>
                                                {redeemed && <td>{p.stockist ?? <span className="text-muted">—</span>}</td>}
                                                <td className="wp-date">
                                                    {p.date}
                                                    <br />
                                                    <small>{p.time}</small>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                            {packages.last_page > 1 && (
                                <div className="p-3 border-top">
                                    <Pagination links={packages.links} />
                                </div>
                            )}
                        </>
                    ) : (
                        <div className="wp-empty">
                            <i className="las la-gift" />
                            <p>{filters.search ? t('No packages match your search.') : redeemed ? t('No welcome packages have been redeemed yet.') : t('No welcome packages are currently pending.')}</p>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

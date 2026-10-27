import { Link, usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import type { AdminUser } from '@/Components/Admin/Cells';
import PageHeader from '@/Components/Admin/PageHeader';
import Pagination from '@/Components/Pagination';
import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';
import type { Paginated } from '@/types';

export type ChannelTotal = { type: string; label: string; qty: number; amount: string; count: number };

type Row = { id: number; name: string; user: AdminUser; state: string | null; units: number; amount: string; reportUrl: string };

type Props = { totals: ChannelTotal[]; stockists: Paginated<Row>; search: string };

export const CHANNEL_ICON: Record<string, string> = {
    cash: 'las la-money-bill-wave',
    invoice_code: 'las la-file-invoice',
    welcome_pack: 'las la-gift',
};

/** Manage Stockist → Redemption Report: stock and money every stockist handed over, by channel. */
export default function Report({ totals, stockists, search }: Props) {
    const t = useTrans();
    const url = usePage().url;
    const [text, setText] = useState(search);
    const first = (stockists.current_page - 1) * stockists.per_page + 1;
    const submit = (e: FormEvent) => {
        e.preventDefault();
        visitWithQuery(url, { search: text.trim() });
    };

    return (
        <div className="str-wrap">
            <PageHeader title={t('Stockist Redemption Report')} />
            <div className="str-hero">
                <div className="str-hero-icon">
                    <i className="las la-clipboard-list" />
                </div>
                <div className="str-hero-body">
                    <h2 className="text-white">{t('Stockist Redemption Report')}</h2>
                    <p className="text-white">{t('Stock and money moved by every stockist, broken down by cash, invoice code, welcome pack code and affiliate invoice code.')}</p>
                </div>
            </div>
            <div className="str-stats">
                {totals.map((c) => (
                    <div className={`str-stat str-stat--${c.type}`} key={c.type}>
                        <div className="str-stat-icon">
                            <i className={CHANNEL_ICON[c.type] ?? 'las la-share-alt'} />
                        </div>
                        <div>
                            <div className="str-stat-val">{c.amount}</div>
                            <div className="str-stat-lbl">
                                {t(c.label)} · {t(':n unit(s)', { n: c.qty })}
                            </div>
                        </div>
                    </div>
                ))}
            </div>
            <div className="str-card">
                <div className="str-card-head">
                    <h6>
                        <i className="las la-store me-1" /> {t('Stockists')}
                    </h6>
                    <form onSubmit={submit} className="str-filters" role="search">
                        <input type="search" aria-label={t('Search stockist')} placeholder={t('Search stockist…')} className="str-input" value={text} onChange={(e) => setText(e.target.value)} />
                        <button type="submit" className="str-btn str-btn-primary">
                            <i className="las la-search" /> {t('Search')}
                        </button>
                        {search && (
                            <Link href={url.split('?')[0]} className="str-btn str-btn-muted">
                                <i className="las la-times" /> {t('Clear')}
                            </Link>
                        )}
                    </form>
                </div>
                {stockists.data.length ? (
                    <>
                        <div className="table-responsive">
                            <table className="str-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{t('Stockist')}</th>
                                        <th>{t('State')}</th>
                                        <th>{t('Total Units Redeemed')}</th>
                                        <th>{t('Total Amount')}</th>
                                        <th aria-label={t('Action')} />
                                    </tr>
                                </thead>
                                <tbody>
                                    {stockists.data.map((s, i) => (
                                        <tr key={s.id}>
                                            <td className="text-muted small">{first + i}</td>
                                            <td>
                                                <div className="str-user">
                                                    <div className="str-av">{s.name.charAt(0).toUpperCase()}</div>
                                                    <div>
                                                        <div className="str-uname">{s.name}</div>
                                                        <div className="str-uid">@ {s.user?.username ?? ''}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{s.state ?? '—'}</td>
                                            <td>{s.units.toLocaleString()}</td>
                                            <td>
                                                <span className="str-amt">{s.amount}</span>
                                            </td>
                                            <td>
                                                <Link href={s.reportUrl} className="str-btn str-btn-primary">
                                                    <i className="las la-chart-bar" /> {t('View Report')}
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {stockists.last_page > 1 && (
                            <div className="p-3 border-top">
                                <Pagination links={stockists.links} />
                            </div>
                        )}
                    </>
                ) : (
                    <div className="str-empty">
                        <i className="las la-store-slash" />
                        <p>{search ? t('No stockists match your search.') : t('No stockists found.')}</p>
                    </div>
                )}
            </div>
        </div>
    );
}

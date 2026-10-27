import { Link } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import type { AdminUser } from '@/Components/Admin/Cells';
import PageHeader from '@/Components/Admin/PageHeader';
import Pagination from '@/Components/Pagination';
import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';
import type { Paginated } from '@/types';
import { CHANNEL_ICON, type ChannelTotal } from './Report';

type Redemption = { id: number; type: string; typeLabel: string; reference: string; customer: string | null; items: { name: string; quantity: number }[]; quantity: number; amount: string; date: string; time: string };

type Props = {
    title: string;
    stockist: { id: number; name: string; user: AdminUser; state: string | null; url: string };
    grand: { amount: string; units: number };
    totals: ChannelTotal[];
    redemptions: Paginated<Redemption>;
    filters: { type: string; from: string; to: string };
    urls: { self: string; index: string };
};

/** One stockist's redemptions: totals by channel and the history, by channel and date. */
export default function ReportShow({ title, stockist, grand, totals, redemptions, filters, urls }: Props) {
    const t = useTrans();
    const [dates, setDates] = useState({ from: filters.from, to: filters.to });
    const [open, setOpen] = useState<number | null>(null);
    const first = (redemptions.current_page - 1) * redemptions.per_page + 1;
    const go = (next: Partial<typeof filters>) => visitWithQuery(urls.self, { ...filters, ...next });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        go(dates);
    };
    const current = totals.find((c) => c.type === filters.type);

    return (
        <div className="str-wrap">
            <PageHeader title={title}>
                <Link href={urls.index} className="btn btn-sm btn-outline--info">
                    <i className="las la-arrow-left" /> {t('All Stockists')}
                </Link>
                <Link href={stockist.url} className="btn btn-sm btn-outline--primary">
                    <i className="las la-store" /> {t('Stockist page')}
                </Link>
            </PageHeader>
            <div className="str-hero">
                <div className="str-hero-icon">
                    <i className="las la-store" />
                </div>
                <div className="str-hero-body">
                    <h2 className="text-white">{stockist.name}</h2>
                    <p className="text-white">
                        @ {stockist.user?.username ?? ''} · {stockist.state ?? t('No state set')}
                    </p>
                </div>
            </div>
            <div className="str-grand">
                <div className="str-grand-lbl">{t('Grand Total')}</div>
                <div className="str-grand-amt">{grand.amount}</div>
                <div className="str-grand-qty">{t(':n unit(s) redeemed across all channels', { n: grand.units })}</div>
            </div>
            <div className="str-stats str-stats--clickable">
                <button type="button" className={`str-stat str-stat--all ${!filters.type ? 'is-active' : ''}`} onClick={() => go({ type: '' })} aria-pressed={!filters.type}>
                    <div className="str-stat-icon">
                        <i className="las la-layer-group" />
                    </div>
                    <div>
                        <div className="str-stat-val">{grand.amount}</div>
                        <div className="str-stat-lbl">
                            {t('All Channels')} · {t(':n unit(s)', { n: grand.units })}
                        </div>
                    </div>
                </button>
                {totals.map((c) => (
                    <button type="button" key={c.type} className={`str-stat str-stat--${c.type} ${filters.type === c.type ? 'is-active' : ''}`} onClick={() => go({ type: c.type })} aria-pressed={filters.type === c.type}>
                        <div className="str-stat-icon">
                            <i className={CHANNEL_ICON[c.type] ?? 'las la-share-alt'} />
                        </div>
                        <div>
                            <div className="str-stat-val">{c.amount}</div>
                            <div className="str-stat-lbl">
                                {t(c.label)} · {t(':n unit(s)', { n: c.qty })} · {t(':n redemption(s)', { n: c.count })}
                            </div>
                        </div>
                    </button>
                ))}
            </div>
            <div className="str-card">
                <div className="str-card-head">
                    <h6>
                        <i className="las la-list me-1" /> {t('Redemption History')}
                        {current && ` — ${t(current.label)}`}
                    </h6>
                    <form onSubmit={submit} className="str-filters">
                        <input type="date" className="str-input" aria-label={t('From')} value={dates.from} onChange={(e) => setDates({ ...dates, from: e.target.value })} />
                        <input type="date" className="str-input" aria-label={t('To')} value={dates.to} onChange={(e) => setDates({ ...dates, to: e.target.value })} />
                        <button type="submit" className="str-btn str-btn-primary">
                            <i className="las la-filter" /> {t('Filter')}
                        </button>
                        {(filters.from || filters.to) && (
                            <button type="button" className="str-btn str-btn-muted" onClick={() => (setDates({ from: '', to: '' }), go({ from: '', to: '' }))}>
                                <i className="las la-times" /> {t('Clear Dates')}
                            </button>
                        )}
                    </form>
                </div>
                {redemptions.data.length ? (
                    <>
                        <div className="table-responsive">
                            <table className="str-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{t('Type')}</th>
                                        <th>{t('Reference')}</th>
                                        <th>{t('Customer')}</th>
                                        <th>{t('Items')}</th>
                                        <th>{t('Qty')}</th>
                                        <th>{t('Amount')}</th>
                                        <th>{t('Date')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {redemptions.data.map((r, i) => (
                                        <tr key={r.id}>
                                            <td className="text-muted small">{first + i}</td>
                                            <td>
                                                <span className={`str-chip str-chip--${r.type}`}>{t(r.typeLabel)}</span>
                                            </td>
                                            <td>
                                                <span className="str-code">{r.reference}</span>
                                            </td>
                                            <td>{r.customer ?? <span className="text-muted">{t('Walk-in')}</span>}</td>
                                            <td>
                                                {r.items.length ? (
                                                    <>
                                                        <button type="button" className="str-items-toggle border-0 bg-transparent p-0" aria-expanded={open === r.id} onClick={() => setOpen(open === r.id ? null : r.id)}>
                                                            {t(':n product(s)', { n: r.items.length })} <i className={`las la-angle-${open === r.id ? 'up' : 'down'}`} />
                                                        </button>
                                                        {open === r.id && (
                                                            <div className="str-items-detail">
                                                                {r.items.map((item, j) => (
                                                                    <div key={j}>
                                                                        {item.quantity} × {item.name}
                                                                    </div>
                                                                ))}
                                                            </div>
                                                        )}
                                                    </>
                                                ) : (
                                                    <span className="text-muted">—</span>
                                                )}
                                            </td>
                                            <td>{r.quantity}</td>
                                            <td>
                                                <span className="str-amt">{r.amount}</span>
                                            </td>
                                            <td className="str-date">
                                                {r.date}
                                                <br />
                                                <small>{r.time}</small>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {redemptions.last_page > 1 && (
                            <div className="p-3 border-top">
                                <Pagination links={redemptions.links} />
                            </div>
                        )}
                    </>
                ) : (
                    <div className="str-empty">
                        <i className="las la-inbox" />
                        <p>{filters.type ? t('No redemptions found for this channel.') : t('No redemptions found.')}</p>
                    </div>
                )}
            </div>
        </div>
    );
}

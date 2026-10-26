import { Link, usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import type { AdminUser } from '@/Components/Admin/Cells';
import PageHeader from '@/Components/Admin/PageHeader';
import Pagination from '@/Components/Pagination';
import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';
import type { Paginated } from '@/types';
import './PvLog.css';

type Row = {
    id: number;
    number: number;
    user: AdminUser;
    initials: string;
    left: boolean;
    credit: boolean;
    amount: string;
    details: string;
    date: string;
    time: string;
};

type Filters = { search: string; position: string; type: string };

type Props = {
    logs: Paginated<Row>;
    /** Over the whole log, whatever the filter */
    totals: { left: string; right: string; cut: string; records: string };
    filters: Filters;
};

/** Binary → PV Logs (/admin/pv-logs): every point-volume credit and cut. */
export default function PvLog({ logs, totals, filters }: Props) {
    const t = useTrans();
    const url = usePage().url;
    const [form, setForm] = useState<Filters>(filters);
    const filtered = Boolean(filters.search || filters.position || filters.type);

    const apply = (next: Filters) => visitWithQuery(url, next);
    const submit = (e: FormEvent) => {
        e.preventDefault();
        apply({ ...form, search: form.search.trim() });
    };
    const remove = (key: keyof Filters) => apply({ ...filters, [key]: '' });

    return (
        <div className="page-pvlog">
            <PageHeader title={t('PV Logs')} />

            <div className="pvl-hero">
                <div className="pvl-hero-icon">
                    <i className="las la-project-diagram" />
                </div>
                <div className="pvl-hero-body">
                    <h2>{t('PV Logs')}</h2>
                    <p>{t('Full audit trail of every point-volume movement across all binary legs — credits, cuts, and position records.')}</p>
                </div>
            </div>

            <div className="pvl-stats">
                <Stat value={totals.left} label={t('Total Left PV Credited')} icon="las la-arrow-alt-circle-left" color="#1d4ed8" bg="#eff6ff" />
                <Stat value={totals.right} label={t('Total Right PV Credited')} icon="las la-arrow-alt-circle-right" color="#7c3aed" bg="#faf5ff" />
                <Stat value={totals.cut} label={t('Total PV Deducted')} icon="las la-minus-circle" color="#dc2626" bg="#fef2f2" />
                <Stat value={totals.records} label={t('Total Log Entries')} icon="las la-list-alt" color="#64748b" bg="#f8fafc" />
            </div>

            <form onSubmit={submit}>
                <div className="pvl-toolbar">
                    <div className="pvl-field pvl-field--grow">
                        <label className="pvl-label" htmlFor="pvl-search">{t('Search')}</label>
                        <div className="pvl-search-wrap">
                            <i className="las la-search pvl-search-icon" />
                            <input
                                id="pvl-search"
                                type="text"
                                className="pvl-input w-100"
                                placeholder={t('Username, first or last name…')}
                                value={form.search}
                                onChange={(e) => setForm({ ...form, search: e.target.value })}
                            />
                        </div>
                    </div>
                    <div className="pvl-field">
                        <label className="pvl-label" htmlFor="pvl-position">{t('Position')}</label>
                        <select id="pvl-position" className="pvl-select" value={form.position} onChange={(e) => setForm({ ...form, position: e.target.value })}>
                            <option value="">{t('All Positions')}</option>
                            <option value="1">{t('Left')}</option>
                            <option value="2">{t('Right')}</option>
                        </select>
                    </div>
                    <div className="pvl-field">
                        <label className="pvl-label" htmlFor="pvl-type">{t('Type')}</label>
                        <select id="pvl-type" className="pvl-select" value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })}>
                            <option value="">{t('All Types')}</option>
                            <option value="+">{t('Credit (+)')}</option>
                            <option value="-">{t('Debit (−)')}</option>
                        </select>
                    </div>
                    <div className="pvl-toolbar-actions">
                        <button type="submit" className="pvl-btn pvl-btn-primary">
                            <i className="las la-search" /> {t('Filter')}
                        </button>
                        {filtered && (
                            <Link href={url.split('?')[0]} className="pvl-btn pvl-btn-ghost">
                                <i className="las la-times" /> {t('Clear')}
                            </Link>
                        )}
                    </div>
                </div>
            </form>

            {filtered && (
                <div className="pvl-active-filters">
                    {filters.search && (
                        <Chip onRemove={() => remove('search')} label={t('Remove')}>
                            <i className="las la-user pvl-chip-icon" /> “{filters.search}”
                        </Chip>
                    )}
                    {filters.position && (
                        <Chip onRemove={() => remove('position')} label={t('Remove')}>
                            <i className={`las la-${filters.position === '1' ? 'arrow-alt-circle-left' : 'arrow-alt-circle-right'} pvl-chip-icon`} />{' '}
                            {filters.position === '1' ? t('Left Leg') : t('Right Leg')}
                        </Chip>
                    )}
                    {filters.type && (
                        <Chip onRemove={() => remove('type')} label={t('Remove')}>
                            {filters.type === '+' ? `＋ ${t('Credit')}` : `− ${t('Debit')}`}
                        </Chip>
                    )}
                </div>
            )}

            <div className="pvl-card">
                <div className="pvl-card-head">
                    <h6 className="pvl-card-head-title">
                        <i className="las la-table" /> {t('Log Entries')} <span className="pvl-count-badge">{logs.total.toLocaleString()}</span>
                    </h6>
                    <span className="pvl-page-of">{t('Page :current of :last', { current: logs.current_page, last: logs.last_page })}</span>
                </div>

                {logs.data.length ? (
                    <>
                        <div className="table-responsive">
                            <table className="pvl-table">
                                <thead>
                                    <tr>
                                        <th className="pvl-col-num">#</th>
                                        <th>{t('User')}</th>
                                        <th>{t('Position')}</th>
                                        <th>{t('Amount')}</th>
                                        <th>{t('Details')}</th>
                                        <th>{t('Date')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {logs.data.map((log) => (
                                        <tr key={log.id}>
                                            <td>
                                                <span className="pvl-num">{log.number}</span>
                                            </td>
                                            <td>
                                                {log.user ? (
                                                    <div className="pvl-user">
                                                        <div className="pvl-av">{log.initials}</div>
                                                        <div>
                                                            <div className="pvl-uname">{log.user.fullname}</div>
                                                            <div className="pvl-uid">@ {log.user.username}</div>
                                                        </div>
                                                    </div>
                                                ) : (
                                                    <span className="pvl-deleted">— {t('deleted')} —</span>
                                                )}
                                            </td>
                                            <td>
                                                {log.left ? (
                                                    <span className="pvl-pos pvl-pos-left">
                                                        <i className="las la-arrow-alt-circle-left" /> {t('Left')}
                                                    </span>
                                                ) : (
                                                    <span className="pvl-pos pvl-pos-right">
                                                        <i className="las la-arrow-alt-circle-right" /> {t('Right')}
                                                    </span>
                                                )}
                                            </td>
                                            <td>
                                                <div className="pvl-type-row">
                                                    <div className={`pvl-type-dot ${log.credit ? 'pvl-type-dot--credit' : 'pvl-type-dot--debit'}`} />
                                                    <span className={`pvl-amount ${log.credit ? 'pvl-amount--credit' : 'pvl-amount--debit'}`}>
                                                        <span className="pvl-amount-sign">{log.amount.charAt(0)}</span>
                                                        {log.amount.slice(1)} PV
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <span className="pvl-detail" title={log.details}>
                                                    {log.details || '—'}
                                                </span>
                                            </td>
                                            <td>
                                                <div className="pvl-date">
                                                    {log.date}
                                                    <small>{log.time}</small>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="pvl-pagination">
                            <Pagination links={logs.links} />
                        </div>
                    </>
                ) : (
                    <div className="pvl-empty">
                        <div className="pvl-empty-icon">
                            <i className="las la-search" />
                        </div>
                        <h5>{filtered ? t('No records match your filter') : t('No PV logs yet')}</h5>
                        <p>{filtered ? t('Try adjusting your search terms or clearing the filters.') : t('PV movements will appear here once users start earning.')}</p>
                    </div>
                )}
            </div>
        </div>
    );
}

function Stat({ value, label, icon, color, bg }: { value: string; label: string; icon: string; color: string; bg: string }) {
    return (
        <div className="pvl-stat">
            <div className="pvl-stat-accent" style={{ background: color }} />
            <div className="pvl-stat-icon" style={{ background: bg, color }}>
                <i className={icon} />
            </div>
            <div className="pvl-stat-body">
                <div className="pvl-stat-val">{value}</div>
                <div className="pvl-stat-lbl">{label}</div>
            </div>
        </div>
    );
}

function Chip({ children, onRemove, label }: { children: React.ReactNode; onRemove: () => void; label: string }) {
    return (
        <span className="pvl-filter-chip">
            {children}
            <button type="button" className="pvl-chip-remove" onClick={onRemove} aria-label={label} title={label}>
                ×
            </button>
        </span>
    );
}

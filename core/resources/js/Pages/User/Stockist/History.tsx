import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import Pagination from '@/Components/Pagination';
import Modal from '@/Components/Modal';
import Sku from '@/Components/Shop/Sku';
import { useCopy } from '@/lib/clipboard';
import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';
import type { PageProps, Paginated } from '@/types';

type Redemption = {
    id: number;
    invoice: string;
    customer: string;
    username: string;
    amount: string;
    date: string;
    short: string;
    notes: string | null;
    items: { name: string; sku: string | null; quantity: number; price: string; subtotal: string }[];
};

type Filters = { range: string; sort: string; search: string; from: string; to: string };

type Props = PageProps<{
    redemptions: Paginated<Redemption>;
    stats: { count: number; value: string; today: number; invoices: number };
    filters: Filters;
    dashboardUrl: string;
}>;

export default function StockistHistory({ redemptions, stats, filters, dashboardUrl }: Props) {
    const t = useTrans();
    const base = usePage().url.split('?')[0];
    const { copy } = useCopy();
    const [copied, setCopied] = useState<string | null>(null);
    const [search, setSearch] = useState(filters.search);
    const [range, setRange] = useState(filters.range);
    const [from, setFrom] = useState(filters.from);
    const [to, setTo] = useState(filters.to);

    // Everything is in the URL and applied by the server.
    const query = (changes: Partial<Filters> = {}) => {
        const next = { ...filters, search, ...changes };
        return {
            range: next.range === 'all' ? '' : next.range,
            sort: next.sort === 'newest' ? '' : next.sort,
            search: next.search,
            from: next.range === 'custom' ? next.from : '',
            to: next.range === 'custom' ? next.to : '',
        };
    };
    const apply = (changes: Partial<Filters>) => visitWithQuery(base, query(changes));

    const first = useRef(true);
    useEffect(() => {
        if (first.current) {
            first.current = false;
            return;
        }
        const timer = setTimeout(() => apply({ search }), 400);
        return () => clearTimeout(timer);
    }, [search]);

    const exportUrl = `${base}?${new URLSearchParams(Object.entries({ ...query(), export: 'csv' }).filter(([, v]) => v !== '')).toString()}`;

    const statCards = [
        { tone: 'info', icon: 'la-receipt', label: t('Total Redemptions'), value: String(stats.count) },
        { tone: 'success', icon: 'la-coins', label: t('Total Value'), value: stats.value },
        { tone: 'gold', icon: 'la-calendar-day', label: t("Today's Redemptions"), value: String(stats.today) },
        { tone: 'primary', icon: 'la-file-invoice', label: t('Unique Invoices'), value: String(stats.invoices) },
    ];

    return (
        <div className="nc-wrap" id="ncWrap">
            <Head title={t('Redemption History')} />
            <br />
            <div className="sl-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div>
                    <h4 className="sl-page-title mb-1">{t('Redemption History')}</h4>
                    <p className="sl-page-subtitle mb-0">{t('Track all your product redemption activities.')}</p>
                </div>
                <Link href={dashboardUrl} className="sl-btn sl-btn-outline"><i className="las la-arrow-left me-1" /> {t('Back to Dashboard')}</Link>
            </div>

            <div className="row g-3 mb-4">
                {statCards.map((card) => (
                    <div className="col-6 col-sm-6 col-xl-3" key={card.label}>
                        <div className={`sl-stat-card sl-stat-${card.tone}`}>
                            <div className="sl-stat-icon"><i className={`las ${card.icon}`} /></div>
                            <div className="sl-stat-body">
                                <p className="sl-stat-label">{card.label}</p>
                                <h3 className="sl-stat-value">{card.value}</h3>
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            <div className="sl-card mb-4">
                <div className="sl-card-body">
                    <div className="row g-3 align-items-end">
                        <div className="col-12 col-md-3">
                            <label className="sl-label" htmlFor="dateFilter">{t('Date Range')}</label>
                            <select
                                className="sl-select"
                                id="dateFilter"
                                value={range}
                                onChange={(e) => {
                                    setRange(e.target.value);
                                    if (e.target.value !== 'custom') apply({ range: e.target.value });
                                }}
                            >
                                <option value="all">{t('All Time')}</option>
                                <option value="today">{t('Today')}</option>
                                <option value="week">{t('This Week')}</option>
                                <option value="month">{t('This Month')}</option>
                                <option value="custom">{t('Custom Range')}</option>
                            </select>
                        </div>
                        <div className="col-12 col-md-3">
                            <label className="sl-label" htmlFor="sortFilter">{t('Sort By')}</label>
                            <select className="sl-select" id="sortFilter" value={filters.sort} onChange={(e) => apply({ sort: e.target.value })}>
                                <option value="newest">{t('Newest First')}</option>
                                <option value="oldest">{t('Oldest First')}</option>
                                <option value="amount_high">{t('Amount: High to Low')}</option>
                                <option value="amount_low">{t('Amount: Low to High')}</option>
                            </select>
                        </div>
                        <div className="col-12 col-md-4">
                            <label className="sl-label" htmlFor="searchInput">{t('Search')}</label>
                            <input type="text" className="sl-input" id="searchInput" placeholder={t('Search by invoice code, customer name…')} value={search} onChange={(e) => setSearch(e.target.value)} />
                        </div>
                        <div className="col-12 col-md-2">
                            <a className="sl-btn sl-btn-outline w-100" href={exportUrl} download>
                                <i className="las la-download me-1" /> {t('Export')}
                            </a>
                        </div>
                    </div>
                    {range === 'custom' && (
                        <div className="row g-3 mt-1">
                            <div className="col-12 col-md-3">
                                <label className="sl-label" htmlFor="fromDate">{t('From Date')}</label>
                                <input type="date" className="sl-input" id="fromDate" value={from} onChange={(e) => setFrom(e.target.value)} />
                            </div>
                            <div className="col-12 col-md-3">
                                <label className="sl-label" htmlFor="toDate">{t('To Date')}</label>
                                <input type="date" className="sl-input" id="toDate" value={to} onChange={(e) => setTo(e.target.value)} />
                            </div>
                            <div className="col-12 col-md-2">
                                <button type="button" className="sl-btn sl-btn-primary w-100" onClick={() => apply({ range: 'custom', from, to })}>
                                    <i className="las la-check me-1" /> {t('Apply')}
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {redemptions.data.length === 0 ? (
                <div className="sl-card">
                    <div className="sl-empty-state">
                        <div className="sl-empty-icon"><i className="las la-receipt" /></div>
                        {filters.range !== 'all' || filters.search ? (
                            <p className="sl-empty-title">{t('No redemptions match your filters.')}</p>
                        ) : (
                            <>
                                <p className="sl-empty-title">{t('No Redemptions Yet')}</p>
                                <p className="sl-empty-sub">{t("You haven't processed any redemptions yet. They will appear here once you start redeeming customer invoices.")}</p>
                                <Link href={dashboardUrl} className="sl-btn sl-btn-primary mt-3"><i className="las la-qrcode me-1" /> {t('Start Redeeming')}</Link>
                            </>
                        )}
                    </div>
                </div>
            ) : (
                <>
                    {redemptions.data.map((r) => (
                        <div className="sl-card sl-redemption-card mb-4" key={r.id}>
                            <div className="sl-card-header" style={{ background: '#F9FAFB' }}>
                                <div className="row align-items-center g-2 w-100">
                                    <div className="col-12 col-md-5 d-flex align-items-center gap-3">
                                        <div className="sl-table-avatar" style={{ background: 'var(--sl-green-lt)', color: 'var(--sl-green)', width: 44, height: 44 }}><i className="las la-check" /></div>
                                        <div>
                                            <p className="fw-700 mb-0" style={{ color: 'var(--bk-text)' }}>{t('Redemption #:id', { id: r.id })}</p>
                                            <small className="text-muted"><i className="las la-calendar me-1" />{r.date}</small>
                                        </div>
                                    </div>
                                    <div className="col-6 col-md-3 text-md-center">
                                        <h5 className="fw-700 mb-0" style={{ color: 'var(--sl-green)' }}>{r.amount}</h5>
                                        <small className="text-muted">{t('Total Amount')}</small>
                                    </div>
                                    <div className="col-6 col-md-4 text-md-end">
                                        <span className="sl-type-badge" style={{ background: 'var(--sl-green-lt)', color: 'var(--sl-green)' }}><i className="las la-receipt me-1" />{r.invoice}</span>
                                        <br />
                                        <small className="text-muted"><i className="las la-user me-1" />{r.username}</small>
                                    </div>
                                </div>
                            </div>
                            <div className="sl-card-body">
                                <div className="row g-4">
                                    <div className="col-12 col-md-8">
                                        <h6 className="fw-600 mb-3"><i className="las la-boxes me-1" /> {t('Redeemed Items (:count)', { count: r.items.length })}</h6>
                                        <div className="table-responsive">
                                            <table className="sl-table">
                                                <thead>
                                                    <tr>
                                                        <th>{t('Product')}</th>
                                                        <th>{t('Quantity')}</th>
                                                        <th>{t('Price')}</th>
                                                        <th>{t('Subtotal')}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {r.items.map((item, i) => (
                                                        <tr key={i}>
                                                            <td data-label={t('Product')}>
                                                                <div className="sl-table-name">
                                                                    <div className="sl-table-avatar" style={{ background: '#F3F4F6', color: '#9CA3AF' }}><i className="las la-box" /></div>
                                                                    <div>
                                                                        <p className="fw-600 mb-0">{item.name}</p>
                                                                        <Sku value={item.sku} />
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td data-label={t('Quantity')}>{item.quantity}</td>
                                                            <td data-label={t('Price')}>{item.price}</td>
                                                            <td data-label={t('Subtotal')} className="fw-600" style={{ color: 'var(--sl-green)' }}>{item.subtotal}</td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colSpan={3} className="text-end fw-700">{t('Total:')}</td>
                                                        <td className="fw-700" style={{ color: 'var(--sl-green)' }}>{r.amount}</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                        {r.notes && (
                                            <div className="mt-3">
                                                <h6 className="fw-600 mb-2"><i className="las la-sticky-note me-1" /> {t('Notes')}</h6>
                                                <div className="alert sl-alert-warning" style={{ background: '#F9FAFB', borderColor: 'var(--sl-border)', color: 'var(--bk-text)' }}>{r.notes}</div>
                                            </div>
                                        )}
                                    </div>
                                    <div className="col-12 col-md-4">
                                        <div className="sl-card" style={{ background: '#F9FAFB', boxShadow: 'none', height: '100%' }}>
                                            <div className="sl-card-body">
                                                <h6 className="fw-600 mb-3">{t('Transaction Details')}</h6>
                                                <div className="d-flex justify-content-between align-items-center mb-2 p-2 bg-white rounded-2">
                                                    <small className="fw-600"><i className="las la-user-circle me-1" style={{ color: 'var(--sl-green)' }} />{t('Customer')}</small>
                                                    <small>{r.customer}</small>
                                                </div>
                                                <div className="d-flex justify-content-between align-items-center mb-2 p-2 bg-white rounded-2">
                                                    <small className="fw-600"><i className="las la-calendar me-1" style={{ color: 'var(--sl-blue)' }} />{t('Date & Time')}</small>
                                                    <small>{r.short}</small>
                                                </div>
                                                <div className="d-flex justify-content-between align-items-center mb-2 p-2 bg-white rounded-2">
                                                    <small className="fw-600"><i className="las la-coins me-1" style={{ color: 'var(--sl-gold)' }} />{t('Amount')}</small>
                                                    <strong style={{ color: 'var(--sl-green)' }}>{r.amount}</strong>
                                                </div>
                                                <div className="mt-3 pt-3 d-grid" style={{ borderTop: '1px solid var(--sl-border)' }}>
                                                    <button
                                                        type="button"
                                                        className="sl-btn sl-btn-outline"
                                                        onClick={async () => ((await copy(r.invoice)) ? setCopied(r.invoice) : window.notify?.('error', t('Failed to copy invoice code.')))}
                                                    >
                                                        <i className="las la-copy me-1" /> {t('Copy Invoice Code')}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))}
                    <div className="sl-card">
                        <div className="sl-card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <small className="text-muted">{t('Showing :from to :to of :total redemptions', { from: redemptions.from ?? 0, to: redemptions.to ?? 0, total: redemptions.total })}</small>
                            <Pagination links={redemptions.links} />
                        </div>
                    </div>
                </>
            )}

            <Modal open={copied !== null} onClose={() => setCopied(null)} labelledBy="copied-title">
                <div className="modal-body text-center p-4">
                    <div className="mb-3" style={{ color: 'var(--sl-green)' }}><i className="las la-check-circle" style={{ fontSize: '3.5rem' }} /></div>
                    <h4 className="mb-3" id="copied-title">{t('Copied to Clipboard!')}</h4>
                    <p className="text-muted mb-4">{t('Invoice code :code has been copied to clipboard.', { code: copied ?? '' })}</p>
                    <button type="button" className="sl-btn sl-btn-primary" onClick={() => setCopied(null)}>{t('Continue')}</button>
                </div>
            </Modal>
        </div>
    );
}

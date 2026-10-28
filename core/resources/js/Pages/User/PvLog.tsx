import { Head, Link, usePage } from '@inertiajs/react';
import Pagination from '@/Components/Pagination';
import { useTrans } from '@/lib/i18n';
import type { PageProps, Paginated } from '@/types';
import './PvLog.css';

type PvEntry = {
    id: number;
    number: number;
    details: string;
    isCredit: boolean;
    kind: 'left' | 'right' | 'cut';
    amount: number;
    date: string;
};

type PvType = '' | 'leftPV' | 'rightPV' | 'cutPV';

type Props = PageProps<{
    logs: Paginated<PvEntry>;
    type: PvType;
    totals: { left: number; right: number; cut: number; paid: number };
}>;

const KIND_ICON = { left: 'la-arrow-left', right: 'la-arrow-right', cut: 'la-cut' };

export default function PvLog({ logs, type, totals }: Props) {
    const t = useTrans();
    // Links stay on this page and only change ?type= (works wherever the app is installed).
    const base = usePage().url.split('?')[0];
    const href = (to: PvType) => (to ? `${base}?type=${to}` : base);

    const kindLabel = { left: t('Left Leg'), right: t('Right Leg'), cut: t('Cut') };
    const feedTitle: Record<PvType, { icon: string; text: string }> = {
        '': { icon: 'la-history', text: t('All PV Entries') },
        leftPV: { icon: 'la-arrow-circle-left', text: t('Left PV Entries') },
        rightPV: { icon: 'la-arrow-circle-right', text: t('Right PV Entries') },
        cutPV: { icon: 'la-cut', text: t('Cut PV Entries') },
    };
    const pageTitle = { '': t('PV Log'), leftPV: t('Left PV Log'), rightPV: t('Right PV Log'), cutPV: t('Cut PV Log') }[type];

    // The "Cut PV" card and tab are hidden, as they were (commented out) in the Blade view.
    const stats = [
        { cls: 'pv-stat--left', icon: 'la-arrow-circle-left', label: t('Total Left PV'), value: totals.left, link: href('leftPV'), linkText: t('View') },
        { cls: 'pv-stat--right', icon: 'la-arrow-circle-right', label: t('Total Right PV'), value: totals.right, link: href('rightPV'), linkText: t('View') },
        { cls: 'pv-stat--total', icon: 'la-star', label: t('Total Paid PV'), value: totals.paid, link: href(''), linkText: t('View All') },
    ];
    const tabs: { type: PvType; cls: string; icon: string; label: string }[] = [
        { type: '', cls: '', icon: 'la-list', label: t('All') },
        { type: 'leftPV', cls: 'pv-tab--left', icon: 'la-arrow-left', label: t('Left PV') },
        { type: 'rightPV', cls: 'pv-tab--right', icon: 'la-arrow-right', label: t('Right PV') },
    ];

    return (
        <div className="pv-page">
            <Head title={pageTitle} />

            <div className="pv-hero">
                <div className="pv-hero-glow" />
                <div className="pv-hero-inner">
                    <div className="pv-hero-left">
                        <div className="pv-hero-eyebrow">
                            <span className="pv-live-dot" />
                            {t('Binary Network')}
                        </div>
                        <h2 className="pv-hero-title">{t('Point Value Log')}</h2>
                    </div>
                    <div className="pv-hero-badge">
                        <i className="las la-chart-line pv-hero-badge-icon" />
                    </div>
                </div>
            </div>

            <div className="pv-stats-grid">
                {stats.map((stat) => (
                    <div className={`pv-stat-card ${stat.cls}`} key={stat.cls}>
                        <div className="pv-stat-icon"><i className={`las ${stat.icon}`} /></div>
                        <div className="pv-stat-body">
                            <p className="pv-stat-label">{stat.label}</p>
                            <p className="pv-stat-val">{stat.value}</p>
                        </div>
                        <Link href={stat.link} className="pv-stat-link" preserveScroll>
                            {stat.linkText} <i className="las la-arrow-right" />
                        </Link>
                    </div>
                ))}
            </div>

            <div className="pv-tabs-wrap" role="tablist">
                {tabs.map((tab) => (
                    <Link
                        key={tab.type || 'all'}
                        href={href(tab.type)}
                        className={`pv-tab ${tab.cls} ${type === tab.type ? 'pv-tab--active' : ''}`}
                        role="tab"
                        aria-selected={type === tab.type}
                        preserveScroll
                    >
                        <i className={`las ${tab.icon}`} /> {tab.label}
                    </Link>
                ))}
            </div>

            <div className="pv-feed-card">
                <div className="pv-feed-head">
                    <h6 className="pv-feed-title">
                        <i className={`las ${feedTitle[type].icon}`} /> {feedTitle[type].text}
                    </h6>
                    {logs.total > 0 && (
                        <span className="pv-count-pill">{t(':count records', { count: logs.total.toLocaleString('en-US') })}</span>
                    )}
                </div>

                {logs.data.length === 0 ? (
                    <div className="pv-empty">
                        <div className="pv-empty-icon"><i className="las la-chart-bar" /></div>
                        <p className="pv-empty-title">{t('No PV Entries')}</p>
                        <p className="pv-empty-sub">{t('Data not found')}</p>
                        <Link href={href('')} className="pv-empty-btn">{t('View All PV')}</Link>
                    </div>
                ) : (
                    logs.data.map((log) => (
                        <div className="pv-row" key={log.id}>
                            <div className={`pv-row-dot pv-dot--${log.kind}`}>
                                <i className={`las ${KIND_ICON[log.kind]}`} />
                            </div>
                            <div className="pv-row-body">
                                <p className="pv-row-desc">{t(log.details)}</p>
                                <div className="pv-row-meta">
                                    <span className={`pv-leg-badge pv-leg--${log.kind}`}>{kindLabel[log.kind]}</span>
                                    <span className="pv-meta-dot">·</span>
                                    <span className="pv-meta-num">#{log.number}</span>
                                    <span className="pv-meta-dot">·</span>
                                    <span>{log.date}</span>
                                </div>
                            </div>
                            <div className={`pv-row-amount pv-amt--${log.isCredit ? 'credit' : 'debit'}`}>
                                <span className="pv-amt-sign">{log.isCredit ? '+' : '−'}</span>
                                <span className="pv-amt-val">{log.amount}</span>
                                <span className="pv-amt-unit">PV</span>
                            </div>
                        </div>
                    ))
                )}
            </div>

            <Pagination links={logs.links} className="pv-pagination" />
        </div>
    );
}

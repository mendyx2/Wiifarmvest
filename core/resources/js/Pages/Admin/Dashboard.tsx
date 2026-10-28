import { Link } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import LineChart, { type Series } from '@/Components/Admin/LineChart';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';

type Chart = { labels: string[]; series: Series[] };
type Group = { title: string; icon: string; url: string | null; stats: [label: string, value: string, tone?: string][] };
type Top = { label: string; count: number };

type Props = {
    admin: string;
    today: string;
    pools: { company_reserve: number; executive_pool: number; operational: number; compensation_reserve: number; total_accumulated: number };
    groups: Group[];
    logins: { browsers: Top[]; os: Top[]; countries: Top[] };
    charts: { from: string; to: string; money: Chart; transactions: Chart };
    currency: string;
    urls: { money: string; transactions: string };
};

const RANGES: [string, number][] = [
    ['Last 7 days', 7],
    ['Last 30 days', 30],
    ['Last 90 days', 90],
    ['Last 12 months', 365],
];

const iso = (d: Date) => d.toISOString().slice(0, 10);

/** Dashboard: headline figures, the reserve pools, money over time and recent logins. */
export default function Dashboard({ admin, today, pools, groups, logins, charts, currency, urls }: Props) {
    const t = useTrans();
    const money = (n: number) => `${currency}${n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    const [days, setDays] = useState(30);
    const [data, setData] = useState({ money: charts.money, transactions: charts.transactions });
    const [loading, setLoading] = useState(false);
    const first = useRef(true);

    // The range scopes both charts; the 30-day view comes with the page
    useEffect(() => {
        if (first.current) {
            first.current = false;
            return;
        }
        const end = new Date();
        const start = new Date(end.getTime() - (days - 1) * 86400000);
        const query = `?start_date=${iso(start)}&end_date=${iso(end)}`;
        const ctrl = new AbortController();
        const get = (url: string) => fetch(url + query, { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: ctrl.signal }).then((r) => (r.ok ? (r.json() as Promise<Chart>) : Promise.reject(new Error(String(r.status)))));
        setLoading(true);
        Promise.all([get(urls.money), get(urls.transactions)])
            .then(([m, tr]) => setData({ money: m, transactions: tr }))
            .catch(() => {})
            .finally(() => !ctrl.signal.aborted && setLoading(false));
        return () => ctrl.abort();
    }, [days, urls.money, urls.transactions]);

    return (
        <>
            <PageHeader title={t('Dashboard')} />
            <div className="mb-4">
                <h4 className="mb-1">{t('Welcome back, :name', { name: admin })}</h4>
                <p className="text-muted mb-0">{today}</p>
            </div>

            <section aria-labelledby="pools-h" className="mb-4">
                <h5 id="pools-h" className="mb-3">
                    {t('Reserve pools')}
                </h5>
                <div className="row gy-3">
                    {(
                        [
                            ['Company reserve', pools.company_reserve],
                            ['Executive pool', pools.executive_pool],
                            ['Operational', pools.operational],
                            ['Compensation reserve', pools.compensation_reserve],
                        ] as const
                    ).map(([label, value]) => (
                        <div className="col-xl-3 col-sm-6" key={label}>
                            <div className="card h-100">
                                <div className="card-body">
                                    <small className="text-muted d-block">{t(label)}</small>
                                    <span className="fs-4 fw-bold">{money(value)}</span>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
                <p className="small text-muted mt-2 mb-0">
                    {t('Total accumulated')}: <b>{money(pools.total_accumulated)}</b>
                </p>
            </section>

            <div className="row gy-4 mb-4">
                {groups.map((g) => (
                    <div className="col-xxl-3 col-xl-4 col-md-6" key={g.title}>
                        <div className="card h-100">
                            <div className="card-header d-flex justify-content-between align-items-center">
                                <h6 className="mb-0">
                                    <i className={`${g.icon} text--primary me-1`} aria-hidden /> {t(g.title)}
                                </h6>
                                {g.url && (
                                    <Link href={g.url} className="small">
                                        {t('Open')} <i className="las la-arrow-right" aria-hidden />
                                    </Link>
                                )}
                            </div>
                            <ul className="list-group list-group-flush">
                                {g.stats.map(([label, value, tone]) => (
                                    <li key={label} className="list-group-item d-flex justify-content-between gap-2">
                                        <span className="text-muted">{t(label)}</span>
                                        <b className={tone === 'warning' && !/^0\b/.test(value) ? 'text--warning' : ''}>{value}</b>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                ))}
            </div>

            <section aria-labelledby="charts-h" className="mb-4">
                <div className="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <h5 id="charts-h" className="mb-0 me-2">
                        {t('Money over time')}
                    </h5>
                    <div className="btn-group btn-group-sm" role="group" aria-label={t('Date range')}>
                        {RANGES.map(([label, d]) => (
                            <button key={d} type="button" className={`btn ${days === d ? 'btn--primary' : 'btn-outline--primary'}`} aria-pressed={days === d} onClick={() => setDays(d)}>
                                {t(label)}
                            </button>
                        ))}
                    </div>
                </div>
                <div className="row gy-4">
                    <div className="col-xl-7">
                        <LineChart title={t('Deposits, withdrawals and share purchases')} labels={data.money.labels} series={data.money.series} format={money} loading={loading} />
                    </div>
                    <div className="col-xl-5">
                        <LineChart title={t('Credits and debits')} labels={data.transactions.labels} series={data.transactions.series} format={money} loading={loading} />
                    </div>
                </div>
            </section>

            <section aria-labelledby="logins-h">
                <h5 id="logins-h" className="mb-3">
                    {t('Member logins')} <small className="text-muted fw-normal">· {t('last 30 days')}</small>
                </h5>
                <div className="row gy-4">
                    <TopList title={t('Browsers')} items={logins.browsers} />
                    <TopList title={t('Operating systems')} items={logins.os} />
                    <TopList title={t('Countries')} items={logins.countries} />
                </div>
            </section>
        </>
    );
}

/** The five most common values, as bars of one hue scaled to the largest (they replaced three pie charts). */
function TopList({ title, items }: { title: string; items: Top[] }) {
    const t = useTrans();
    const max = Math.max(1, ...items.map((i) => i.count));
    return (
        <div className="col-lg-4">
            <div className="card h-100">
                <div className="card-header">
                    <h6 className="mb-0">{title}</h6>
                </div>
                <div className="card-body">
                    {items.length === 0 ? (
                        <p className="text-muted mb-0">{t('No logins yet.')}</p>
                    ) : (
                        <ul className="list-unstyled mb-0">
                            {items.map((i) => (
                                <li key={i.label} className="mb-2" title={`${i.label}: ${i.count}`}>
                                    <div className="d-flex justify-content-between small mb-1">
                                        <span>{i.label}</span>
                                        <b style={{ fontVariantNumeric: 'tabular-nums' }}>{i.count.toLocaleString()}</b>
                                    </div>
                                    <div style={{ height: 8, background: '#e8eef8', borderRadius: 4 }} aria-hidden>
                                        <div style={{ width: `${(i.count / max) * 100}%`, height: 8, background: '#2a78d6', borderRadius: 4 }} />
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </div>
    );
}

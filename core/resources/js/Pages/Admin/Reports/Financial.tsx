import { Link, usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import BoxWidget from '@/Components/Admin/BoxWidget';
import DateRangeField, { dateQuery, type DateRange } from '@/Components/Admin/DateRangeField';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';

type Box = { label: string; icon: string; link: string | null; value: string; note: string | null };
type Group = { label: string; icon: string; total: string | null; bg: string; boxes: Box[]; empty: string[]; note: string | null };

type Props = {
    period: string;
    allTime: boolean;
    totals: { income: string; outflow: string; net: string };
    income: Group[];
    cashIn: Group;
    outflow: Group[];
    cashOut: Group;
    unclassified: Group | null;
    owedMembers: Group;
    owedCompany: Group;
    standing: {
        verdict: 'profit' | 'loss' | 'even';
        trend: 'up' | 'down' | 'flat';
        net: string;
        netBreakdown: string;
        margin: string;
        trendNote: string;
        compareLabel: string;
        owedMembers: string;
        owedCompany: string;
    };
    date: DateRange;
};

const STATUS = {
    profit: { label: 'Making money', icon: 'las la-arrow-circle-up', bg: 'success' },
    loss: { label: 'Losing money', icon: 'las la-arrow-circle-down', bg: 'danger' },
    even: { label: 'Breaking even', icon: 'las la-equals', bg: 'secondary' },
};

const TREND = {
    up: { label: 'Growing', icon: 'las la-chart-line', bg: 'success' },
    down: { label: 'Shrinking', icon: 'las la-chart-area', bg: 'danger' },
    flat: { label: 'Flat', icon: 'las la-minus', bg: 'secondary' },
};

/**
 * Report → Financial Report: income, outflow and the company's standing for
 * a period (FinancialReportService). Print uses the browser's print dialog;
 * app.css hides the sidebar and top bar when printing.
 */
export default function Financial(props: Props) {
    const t = useTrans();
    const url = usePage().url;
    const error = (usePage().props.errors as Record<string, string> | undefined)?.date;
    const [date, setDate] = useState(props.date);
    const { standing } = props;
    const status = STATUS[standing.verdict];
    const trend = TREND[standing.trend];

    const apply = (e: FormEvent) => {
        e.preventDefault();
        visitWithQuery(url, { date: dateQuery(date) });
    };

    return (
        <div className="fr-page">
            <PageHeader title={t('Financial Report')}>
                <form className="d-flex flex-wrap gap-2 align-items-end fr-date-form" onSubmit={apply}>
                    <DateRangeField value={date} onChange={setDate} id="fr-date" />
                    <button className="btn btn--primary" type="submit" aria-label={t('Apply')}>
                        <i className="la la-search" />
                    </button>
                </form>
                <button className="btn btn-outline--primary fr-print" type="button" onClick={() => window.print()}>
                    <i className="las la-print" /> {t('Print')}
                </button>
            </PageHeader>
            {error && (
                <div className="alert alert-danger" role="alert">
                    {error}
                </div>
            )}

            <div className="fr-period">
                <span>
                    <i className="las la-calendar-alt" /> {props.period}
                </span>
                {!props.allTime && <Link href={url.split('?')[0]}>{t('Show all time')}</Link>}
            </div>

            <div className="row gy-4 fr-boxes">
                <div className="col-xxl-4 col-md-6">
                    <BoxWidget bg="success" icon="las la-arrow-down" title={t('Total Income')} value={props.totals.income} note={t('Money the company earned')} />
                </div>
                <div className="col-xxl-4 col-md-6">
                    <BoxWidget bg="9" icon="las la-arrow-up" title={t('Total Outflow')} value={props.totals.outflow} note={t('Paid to members & stockists')} />
                </div>
                <div className="col-xxl-4 col-md-12">
                    <BoxWidget bg={status.bg} icon={status.icon} title={t('Net Result')} value={props.totals.net} note={t(status.label)} />
                </div>
            </div>

            <SectionHead icon="las la-arrow-down" label={t('Income')} sub={t('Money the company earned')} />
            {props.income.map((g) => (
                <Panel key={g.label} group={g} />
            ))}
            <div className="fr-total-box fr-total-box--income">
                <span>
                    <i className="las la-arrow-down" /> {t('Total Income')}
                </span>
                <strong>{props.totals.income}</strong>
            </div>
            <Panel group={props.cashIn} />

            <SectionHead icon="las la-arrow-up" label={t('Outflow')} sub={t('Bonuses, dividends and payouts to members and stockists')} />
            {props.outflow.map((g) => (
                <Panel key={g.label} group={g} />
            ))}
            <div className="db-finance-block fr-boxes">
                <div className="db-finance-block__head fr-block-head">
                    <span>
                        <i className="las la-briefcase" /> {t('Operating Expenses')}
                    </span>
                </div>
                <div className="row gy-4">
                    <div className="col-xxl-4 col-md-6">
                        <BoxWidget outline bg="secondary" icon="las la-briefcase" title={t('Salaries, Rent & Logistics')} value={t('Not recorded')} note={t('Not tracked in the system yet')} />
                    </div>
                </div>
            </div>
            <div className="fr-total-box fr-total-box--outflow">
                <span>
                    <i className="las la-arrow-up" /> {t('Total Outflow')}
                </span>
                <strong>{props.totals.outflow}</strong>
            </div>
            <Panel group={props.cashOut} />
            {props.unclassified && <Panel group={props.unclassified} />}

            <SectionHead icon="las la-balance-scale" label={t('Right Now')} sub={t('Balances as of today, whatever period is selected')} />
            <Panel group={props.owedMembers} />
            <Panel group={props.owedCompany} />

            <SectionHead icon="las la-chart-line" label={t('Company Financial Standing')} sub={props.period} />
            <div className={`db-finance-block fr-boxes fr-standing fr-standing--${standing.verdict}`}>
                <div className="row gy-4">
                    <div className="col-md-6">
                        <BoxWidget bg={status.bg} icon={status.icon} title={t('Company Status')} value={t(status.label)} note={t('Income minus outflow')} />
                    </div>
                    <div className="col-md-6">
                        <BoxWidget bg={status.bg} icon="las la-balance-scale" title={t('Net Result')} value={standing.net} note={standing.netBreakdown} />
                    </div>
                    <div className="col-md-6">
                        <BoxWidget bg={status.bg} icon="las la-percentage" title={t('Profit Margin')} value={standing.margin} note={t('Kept from every naira earned')} />
                    </div>
                    <div className="col-md-6">
                        <BoxWidget bg={trend.bg} icon={trend.icon} title={t('Trend')} value={t(trend.label)} note={standing.trendNote} />
                    </div>
                </div>
                <p className="fr-note">
                    {t('Trend compares the net result for the')} {standing.compareLabel}.{' '}
                    {t('Net result is before operating expenses, which are not recorded in the system yet. Right now the company owes members and stockists')}{' '}
                    <strong>{standing.owedMembers}</strong> {t('and is owed')} <strong>{standing.owedCompany}</strong>.
                </p>
            </div>
        </div>
    );
}

function SectionHead({ icon, label, sub }: { icon: string; label: string; sub: string }) {
    return (
        <div className="db-sec-head">
            <span className="db-sec-head__icon">
                <i className={icon} />
            </span>
            <span className="db-sec-head__label">{label}</span>
            <span className="db-sec-head__sub">{sub}</span>
        </div>
    );
}

/** One panel of boxes: reports/partials/financial_boxes.blade.php. */
function Panel({ group }: { group: Group }) {
    const t = useTrans();
    return (
        <div className="db-finance-block fr-boxes">
            <div className="db-finance-block__head fr-block-head">
                <span>
                    <i className={group.icon} /> {group.label}
                </span>
                {group.total !== null && <span className="fr-block-head__total">{group.total}</span>}
            </div>
            <div className="row gy-4">
                {group.boxes.length ? (
                    group.boxes.map((box) => (
                        <div className="col-xxl-4 col-md-6" key={box.label}>
                            <BoxWidget outline bg={group.bg} icon={box.icon} link={box.link} title={box.label} value={box.value} note={box.note} />
                        </div>
                    ))
                ) : (
                    <div className="col-12">
                        <p className="fr-idle mb-0">{t('Nothing recorded for this period.')}</p>
                    </div>
                )}
            </div>
            {group.empty.length > 0 && (
                <p className="fr-idle">
                    <strong>{t('Nothing recorded')}:</strong> {group.empty.join(' · ')}
                </p>
            )}
            {group.note && <p className="fr-note">{group.note}</p>}
        </div>
    );
}

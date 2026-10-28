import { usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import BoxWidget from '@/Components/Admin/BoxWidget';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';

type Props = {
    period: { from: string; to: string; label: string };
    funnel: { label: string; value: number; icon: string; bg: string }[];
    conversion: string;
    money: { paystack: string; cash: string; owed: string; bonus: string; revenue: string; net: string };
};

/** Affiliate Audit: the funnel (clicks → fulfilled) and the money for one period. Was two pages with the same date form. */
export default function AffiliateAudit({ period, funnel, conversion, money }: Props) {
    const t = useTrans();
    const url = usePage().url;
    const [dates, setDates] = useState({ from: period.from, to: period.to });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        visitWithQuery(url, dates);
    };
    return (
        <>
            <PageHeader title={t('Affiliate Funnel & Financial Audit')}>
                <form className="d-flex flex-wrap gap-2 align-items-end" onSubmit={submit}>
                    <div>
                        <label htmlFor="aa-from" className="form-label mb-0 small">
                            {t('From')}
                        </label>
                        <input id="aa-from" type="date" className="form-control" required value={dates.from} max={dates.to} onChange={(e) => setDates({ ...dates, from: e.target.value })} />
                    </div>
                    <div>
                        <label htmlFor="aa-to" className="form-label mb-0 small">
                            {t('To')}
                        </label>
                        <input id="aa-to" type="date" className="form-control" required value={dates.to} min={dates.from} onChange={(e) => setDates({ ...dates, to: e.target.value })} />
                    </div>
                    <button type="submit" className="btn btn--primary">
                        {t('Apply')}
                    </button>
                </form>
            </PageHeader>
            <p className="text-muted">
                <i className="las la-calendar" /> {period.label}
            </p>

            <h6 className="mb-3">{t('Funnel')}</h6>
            <div className="row gy-4">
                {funnel.map((stage, i) => {
                    const prev = funnel[i - 1]?.value ?? 0;
                    return (
                        <div className="col-lg-3 col-sm-6" key={stage.label}>
                            <BoxWidget outline bg={stage.bg} icon={stage.icon} title={t(stage.label)} value={stage.value.toLocaleString()} note={i > 0 && prev > 0 ? t(':p% of previous stage', { p: ((stage.value / prev) * 100).toFixed(1) }) : null} />
                        </div>
                    );
                })}
            </div>
            <p className="mt-3">
                {t('Overall conversion (clicks → fulfilled orders)')}: <strong>{conversion}</strong>
            </p>

            <h6 className="mb-3 mt-4">{t('Money')}</h6>
            <div className="row gy-4">
                <div className="col-lg-3 col-sm-6">
                    <BoxWidget outline bg="primary" icon="las la-credit-card" title={t('Revenue — Paystack')} value={money.paystack} />
                </div>
                <div className="col-lg-3 col-sm-6">
                    <BoxWidget outline bg="success" icon="las la-money-bill-wave" title={t('Revenue — Cash Collected')} value={money.cash} />
                </div>
                <div className="col-lg-3 col-sm-6">
                    <BoxWidget outline bg="danger" icon="las la-hourglass-half" title={t('Outstanding Cash Liability')} value={money.owed} />
                </div>
                <div className="col-lg-3 col-sm-6">
                    <BoxWidget outline bg="warning" icon="las la-gift" title={t('Affiliate Bonus Paid')} value={money.bonus} />
                </div>
            </div>
            <div className="card mt-4">
                <div className="card-header">
                    <h5 className="card-title mb-0">{t('Net Summary')}</h5>
                </div>
                <ul className="list-group list-group-flush">
                    <li className="list-group-item d-flex justify-content-between">
                        <span>{t('Total Revenue Collected')}</span>
                        <strong>{money.revenue}</strong>
                    </li>
                    <li className="list-group-item d-flex justify-content-between">
                        <span>{t('Less: Affiliate Bonus Paid')}</span>
                        <span className="text--danger">− {money.bonus}</span>
                    </li>
                    <li className="list-group-item d-flex justify-content-between">
                        <strong>{t('Net Revenue (excl. stockist fees)')}</strong>
                        <strong>{money.net}</strong>
                    </li>
                    <li className="list-group-item d-flex justify-content-between">
                        <span>{t('Outstanding Cash-on-Pickup (not yet collected)')}</span>
                        <span>{money.owed}</span>
                    </li>
                </ul>
                <div className="card-body">
                    <p className="text-muted small mb-0">{t('Stockist handling fees are logged individually as stockist transactions and are not netted here.')}</p>
                </div>
            </div>
        </>
    );
}

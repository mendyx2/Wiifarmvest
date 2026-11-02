import { Link, useForm } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';
import ConfirmDialog from '@/Components/ConfirmDialog';
import { FieldError } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';

type Props = {
    plans: { id: number; name: string; units: string }[];
    currency: string;
    urls: { store: string; preview: string; back: string };
};

type Form = { amount_per_unit: string; scope: 'all-time' | 'date-range'; start_date: string; end_date: string; plan_id: string; notes: string };
type Preview = { investments: number; users: number; units: string; amount: string };

/**
 * New dividend payment: an amount per unit for every active holding (all
 * plans or one, bought any time or in a date range). The page shows who
 * would be paid and the total before anything is created.
 */
export default function Create({ plans, currency, urls }: Props) {
    const t = useTrans();
    const form = useForm<Form>({ amount_per_unit: '', scope: 'all-time', start_date: '', end_date: '', plan_id: '', notes: '' });
    const [preview, setPreview] = useState<Preview | null>(null);
    const [checking, setChecking] = useState(false);
    const [confirm, setConfirm] = useState(false);
    const { amount_per_unit, scope, start_date, end_date, plan_id } = form.data;
    const complete = Number(amount_per_unit) > 0 && (scope === 'all-time' || (start_date && end_date));

    // The preview follows the choices (after a short pause)
    useEffect(() => {
        setPreview(null);
        if (!complete) return;
        const ctrl = new AbortController();
        const timer = setTimeout(() => {
            setChecking(true);
            const query = new URLSearchParams({ amount_per_unit, scope, plan_id, ...(scope === 'date-range' ? { start_date, end_date } : {}) });
            fetch(`${urls.preview}?${query}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: ctrl.signal })
                .then((r) => (r.ok ? r.json() : Promise.reject(new Error(String(r.status)))))
                .then((p: Preview) => setPreview(p))
                .catch(() => {})
                .finally(() => !ctrl.signal.aborted && setChecking(false));
        }, 350);
        return () => {
            clearTimeout(timer);
            ctrl.abort();
        };
    }, [complete, amount_per_unit, scope, start_date, end_date, plan_id, urls.preview]);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        setConfirm(true);
    };
    const send = () => form.post(urls.store, { onFinish: () => setConfirm(false) });
    const empty = preview !== null && preview.investments === 0;

    return (
        <>
            <PageHeader title={t('Create Dividend Payment')}>
                <Link href={urls.back} className="btn btn-sm btn-outline--primary">
                    <i className="la la-undo" /> {t('Back')}
                </Link>
            </PageHeader>
            <div className="row gy-4">
                <div className="col-lg-7">
                    <div className="card">
                        <form onSubmit={submit} className="card-body">
                            <div className="form-group">
                                <label htmlFor="dv-amount">{t('Amount per unit')}</label>
                                <div className="input-group">
                                    <span className="input-group-text">{currency}</span>
                                    <input id="dv-amount" type="number" step="0.01" min="0.01" className="form-control" required value={amount_per_unit} onChange={(e) => form.setData('amount_per_unit', e.target.value)} />
                                </div>
                                <FieldError message={form.errors.amount_per_unit} />
                            </div>
                            <div className="form-group">
                                <label htmlFor="dv-plan">{t('Plan')}</label>
                                <select id="dv-plan" className="form-select" value={plan_id} onChange={(e) => form.setData('plan_id', e.target.value)}>
                                    <option value="">{t('All plans')}</option>
                                    {plans.map((p) => (
                                        <option key={p.id} value={p.id}>
                                            {p.name} ({t(':n units', { n: p.units })})
                                        </option>
                                    ))}
                                </select>
                                <FieldError message={form.errors.plan_id} />
                            </div>
                            <fieldset className="form-group">
                                <legend className="fs-6 fw-normal mb-1">{t('Holdings bought')}</legend>
                                <div className="d-flex gap-4">
                                    {(
                                        [
                                            ['all-time', 'Any time'],
                                            ['date-range', 'Between two dates'],
                                        ] as const
                                    ).map(([value, label]) => (
                                        <div className="form-check" key={value}>
                                            <input id={`dv-scope-${value}`} type="radio" name="scope" className="form-check-input" checked={scope === value} onChange={() => form.setData('scope', value)} />
                                            <label className="form-check-label" htmlFor={`dv-scope-${value}`}>
                                                {t(label)}
                                            </label>
                                        </div>
                                    ))}
                                </div>
                            </fieldset>
                            {scope === 'date-range' && (
                                <div className="row">
                                    <div className="col-sm-6 form-group">
                                        <label htmlFor="dv-start">{t('From')}</label>
                                        <input id="dv-start" type="date" className="form-control" required value={start_date} onChange={(e) => form.setData('start_date', e.target.value)} />
                                        <FieldError message={form.errors.start_date} />
                                    </div>
                                    <div className="col-sm-6 form-group">
                                        <label htmlFor="dv-end">{t('To')}</label>
                                        <input id="dv-end" type="date" className="form-control" required min={start_date || undefined} value={end_date} onChange={(e) => form.setData('end_date', e.target.value)} />
                                        <FieldError message={form.errors.end_date} />
                                    </div>
                                </div>
                            )}
                            <div className="form-group">
                                <label htmlFor="dv-notes">
                                    {t('Notes')} <small className="text-muted">({t('optional')})</small>
                                </label>
                                <textarea id="dv-notes" className="form-control" rows={3} maxLength={1000} value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)} />
                            </div>
                            <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing || !preview || empty}>
                                {t('Pay Dividend')}
                            </button>
                        </form>
                    </div>
                </div>
                <div className="col-lg-5">
                    <div className="card" aria-live="polite">
                        <div className="card-header">
                            <h5 className="card-title mb-0">{t('This payment')}</h5>
                        </div>
                        <div className="card-body">
                            {!complete ? (
                                <p className="text-muted mb-0">{t('Enter an amount per unit (and the dates) to see who is paid.')}</p>
                            ) : checking || !preview ? (
                                <p className="text-muted mb-0">{t('Counting')}...</p>
                            ) : empty ? (
                                <p className="text--danger mb-0">{t('No active holding matches these choices.')}</p>
                            ) : (
                                <ul className="list-group list-group-flush">
                                    <li className="list-group-item d-flex justify-content-between px-0">
                                        <span>{t('Members')}</span> <b>{preview.users}</b>
                                    </li>
                                    <li className="list-group-item d-flex justify-content-between px-0">
                                        <span>{t('Holdings')}</span> <b>{preview.investments}</b>
                                    </li>
                                    <li className="list-group-item d-flex justify-content-between px-0">
                                        <span>{t('Units')}</span> <b>{preview.units}</b>
                                    </li>
                                    <li className="list-group-item d-flex justify-content-between px-0">
                                        <span>{t('Total paid')}</span> <b className="text--primary fs-5">{preview.amount}</b>
                                    </li>
                                </ul>
                            )}
                        </div>
                    </div>
                </div>
            </div>
            <ConfirmDialog
                open={confirm}
                busy={form.processing}
                question={preview ? t('Pay :amount to :n members? The payment starts at once; only reversing the batch takes it back.', { amount: preview.amount, n: preview.users }) : ''}
                onConfirm={send}
                onCancel={() => setConfirm(false)}
            />
        </>
    );
}

import { useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { BadgeCell, type Badge } from '@/Components/Admin/Cells';
import DataTable from '@/Components/Admin/DataTable';
import { ConfirmButton, DialogHeader, FieldError, withId } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';
import type { Paginated } from '@/types';

type CycleForm = {
    name: string;
    min_contribution: string;
    max_contribution: string;
    min_yield: string;
    max_yield: string;
    actual_yield: string;
    subscription_opens_at: string;
    maturity_date: string;
    status: string;
};

type Cycle = {
    id: number;
    name: string;
    contribution: string;
    yield: string;
    actualYield: number | null;
    opens: string | null;
    maturity: string | null;
    daysLeft: number | null;
    subscribers: number;
    total: string;
    status: Badge;
    state: 'open' | 'closed' | 'matured';
    paidOut: boolean;
    reached: boolean;
    form: CycleForm;
};

type Props = {
    cycles: Paginated<Cycle>;
    currency: string;
    urls: { store: string; update: string; close: string; mature: string };
};

const blank: CycleForm = { name: '', min_contribution: '', max_contribution: '', min_yield: '', max_yield: '', actual_yield: '', subscription_opens_at: '', maturity_date: '', status: 'open' };

/**
 * Savings & Loans → Farm Cycles: create and edit cycles, close one to new
 * contributions, and pay out a closed one once its maturity date comes.
 */
export default function Cycles({ cycles, currency, urls }: Props) {
    const t = useTrans();
    // null: closed; 0: new cycle; otherwise the cycle being edited
    const [editing, setEditing] = useState<Cycle | 0 | null>(null);
    const [paying, setPaying] = useState<Cycle | null>(null);

    return (
        <>
            <PageHeader title={t('Farm Cycles')}>
                <button type="button" className="btn btn-outline--primary" onClick={() => setEditing(0)}>
                    <i className="las la-plus" /> {t('New Cycle')}
                </button>
            </PageHeader>
            <DataTable
                rows={cycles.data}
                rowKey={(c) => c.id}
                links={cycles.links}
                empty={t('No farm cycles yet.')}
                columns={[
                    { label: t('Name'), cell: (c) => <strong>{c.name}</strong> },
                    { label: t('Contribution Range'), cell: (c) => c.contribution },
                    {
                        label: t('Yield Range'),
                        cell: (c) => (
                            <>
                                {c.yield}
                                {c.actualYield !== null && (
                                    <>
                                        <br />
                                        <small className="text--success fw-bold">{t('Actual: :n%', { n: c.actualYield })}</small>
                                    </>
                                )}
                            </>
                        ),
                    },
                    { label: t('Opens At'), cell: (c) => c.opens ?? '—' },
                    {
                        label: t('Maturity Date'),
                        cell: (c) => (
                            <>
                                {c.maturity ?? '—'}
                                {c.daysLeft !== null && (
                                    <>
                                        <br />
                                        <small className="text--warning">
                                            <i className="las la-clock" /> {t(':n days left', { n: c.daysLeft })}
                                        </small>
                                    </>
                                )}
                            </>
                        ),
                    },
                    {
                        label: t('Subscribers'),
                        cell: (c) => (
                            <>
                                <span className="badge badge--primary">{c.subscribers}</span>
                                {c.subscribers > 0 && (
                                    <>
                                        <br />
                                        <small className="text-muted">{c.total}</small>
                                    </>
                                )}
                            </>
                        ),
                    },
                    {
                        label: t('Status'),
                        cell: (c) => (
                            <>
                                <BadgeCell badge={c.status} />
                                {c.paidOut && (
                                    <>
                                        <br />
                                        <small className="text--success">
                                            <i className="las la-check-circle" /> {t('Paid out')}
                                        </small>
                                    </>
                                )}
                            </>
                        ),
                    },
                    {
                        label: t('Actions'),
                        cell: (c) => (
                            <div className="d-flex gap-1 flex-wrap justify-content-end justify-content-lg-start">
                                {c.state !== 'matured' && !c.paidOut && (
                                    <button type="button" className="btn btn-sm btn-outline--primary" title={t('Edit')} aria-label={t('Edit :name', { name: c.name })} onClick={() => setEditing(c)}>
                                        <i className="las la-pen" />
                                    </button>
                                )}
                                {c.state === 'open' && (
                                    <ConfirmButton
                                        url={withId(urls.close, c.id)}
                                        question={t('Close :name? :n active savings account(s) are linked to it. No new contributions will be accepted; existing savings stay until maturity.', { name: c.name, n: c.subscribers })}
                                        className="btn btn-sm btn-outline--warning"
                                        title={t('Close this cycle')}
                                    >
                                        <i className="las la-lock" />
                                    </ConfirmButton>
                                )}
                                {c.state === 'closed' && !c.paidOut && (
                                    <button
                                        type="button"
                                        className={`btn btn-sm ${c.reached ? 'btn--success' : 'btn--secondary'}`}
                                        disabled={!c.reached}
                                        title={c.reached ? t('Process maturity payout') : t('Maturity date (:date) not yet reached', { date: c.maturity ?? '' })}
                                        onClick={() => setPaying(c)}
                                    >
                                        <i className={`las ${c.reached ? 'la-seedling' : 'la-clock'}`} /> {t('Pay Out')}
                                    </button>
                                )}
                            </div>
                        ),
                    },
                ]}
            />
            {editing !== null && <CycleDialog cycle={editing || null} url={editing ? withId(urls.update, editing.id) : urls.store} currency={currency} onClose={() => setEditing(null)} />}
            {paying && <PayOutDialog cycle={paying} url={withId(urls.mature, paying.id)} onClose={() => setPaying(null)} />}
        </>
    );
}

function CycleDialog({ cycle, url, currency, onClose }: { cycle: Cycle | null; url: string; currency: string; onClose: () => void }) {
    const t = useTrans();
    const form = useForm<CycleForm>(cycle ? cycle.form : blank);
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, onSuccess: onClose });
    };
    const field = (key: keyof CycleForm, label: string, opts: { type?: string; money?: boolean; percent?: boolean; required?: boolean; placeholder?: string; col?: string } = {}) => {
        const { type = 'number', money = false, percent = false, required = true, placeholder, col = 'col-sm-6' } = opts;
        const input = (
            <input
                id={`cycle-${key}`}
                type={type}
                className="form-control"
                step={type === 'number' ? '0.01' : undefined}
                min={type === 'number' ? '0' : undefined}
                max={percent ? '100' : undefined}
                maxLength={type === 'text' ? 100 : undefined}
                required={required}
                placeholder={placeholder}
                value={form.data[key]}
                onChange={(e) => form.setData(key, e.target.value)}
            />
        );
        return (
            <div className={col}>
                <label className="form-label fw-bold" htmlFor={`cycle-${key}`}>
                    {label} {required && <span className="text--danger">*</span>}
                </label>
                {money || percent ? (
                    <div className="input-group">
                        {money && <span className="input-group-text">{currency}</span>}
                        {input}
                        {percent && <span className="input-group-text">%</span>}
                    </div>
                ) : (
                    input
                )}
                <FieldError message={form.errors[key]} />
            </div>
        );
    };
    return (
        <Modal open onClose={onClose} labelledBy="cycle-title" size="lg">
            <DialogHeader id="cycle-title" title={cycle ? t('Edit Farm Cycle') : t('New Farm Cycle')} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    <div className="row g-3">
                        {field('name', t('Cycle Name'), { type: 'text', placeholder: t('e.g. 2026 Season 1'), col: 'col-12' })}
                        {field('min_contribution', t('Min Contribution'), { money: true })}
                        {field('max_contribution', t('Max Contribution'), { money: true })}
                        {field('min_yield', t('Min Yield %'), { percent: true })}
                        {field('max_yield', t('Max Yield %'), { percent: true })}
                        {field('actual_yield', t('Actual Yield % (set before maturity)'), { percent: true, required: false, placeholder: t('e.g. 18.5') })}
                        <div className="col-sm-6">
                            <label className="form-label fw-bold" htmlFor="cycle-status">
                                {t('Status')} <span className="text--danger">*</span>
                            </label>
                            <select id="cycle-status" className="form-control" value={form.data.status} onChange={(e) => form.setData('status', e.target.value)}>
                                <option value="open">{t('Open')}</option>
                                <option value="closed">{t('Closed')}</option>
                            </select>
                            <FieldError message={form.errors.status} />
                        </div>
                        {field('subscription_opens_at', t('Subscription Opens At'), { type: 'date' })}
                        {field('maturity_date', t('Maturity Date'), { type: 'date' })}
                    </div>
                </div>
                <div className="modal-footer">
                    <button type="button" className="btn btn-outline--dark" onClick={onClose}>
                        {t('Cancel')}
                    </button>
                    <button type="submit" className="btn btn--primary" disabled={form.processing}>
                        <i className="las la-save" /> {cycle ? t('Update') : t('Create')}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

function PayOutDialog({ cycle, url, onClose }: { cycle: Cycle; url: string; onClose: () => void }) {
    const t = useTrans();
    const form = useForm({ actual_yield: cycle.actualYield !== null ? String(cycle.actualYield) : '' });
    const valid = Number(form.data.actual_yield) > 0 && Number(form.data.actual_yield) <= 100;
    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (valid) form.post(url, { preserveScroll: true, onSuccess: onClose });
    };
    return (
        <Modal open onClose={() => !form.processing && onClose()} labelledBy="payout-title">
            <DialogHeader id="payout-title" title={t('Process Maturity Payout')} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    <p>
                        {t('You are about to process the maturity payout for:')} <strong>{cycle.name}</strong>
                    </p>
                    <div className="alert alert-info">
                        <i className="las la-info-circle me-2" />
                        {t(":n savings account(s) totalling :total in principal will be credited with principal + interest to each user's Money Box.", { n: cycle.subscribers, total: cycle.total })}
                    </div>
                    <label className="form-label fw-bold" htmlFor="payout-yield">
                        {t('Actual Yield %')} <span className="text--danger">*</span>
                    </label>
                    <div className="input-group">
                        <input id="payout-yield" type="number" className="form-control" step="0.01" min="0.01" max="100" required placeholder={t('e.g. 18.5')} value={form.data.actual_yield} onChange={(e) => form.setData('actual_yield', e.target.value)} />
                        <span className="input-group-text">%</span>
                    </div>
                    <small className="text-muted">{t('Estimated range: :range', { range: cycle.yield })}</small>
                    <FieldError message={form.errors.actual_yield} />
                    <p className="text--danger mt-3 mb-0">
                        <strong>{t('This action cannot be undone.')}</strong>
                    </p>
                </div>
                <div className="modal-footer">
                    <button type="button" className="btn btn-outline--dark" onClick={onClose} disabled={form.processing}>
                        {t('Cancel')}
                    </button>
                    <button type="submit" className="btn btn--success" disabled={!valid || form.processing}>
                        <i className="las la-seedling" /> {t('Confirm & Pay Out')}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

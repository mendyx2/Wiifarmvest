import { Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import DataTable from '@/Components/Admin/DataTable';
import { ConfirmButton, DialogHeader, FieldError, withId } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';

type PlanForm = { name: string; price: string; ref_com: string; lev2: string; lev3: string; lev4: string };
type Plan = { id: number; name: string; price: string; refCom: string; lev2: string; lev3: string; lev4: string; holders: number; units: string; active: boolean; form: PlanForm; url: string };

type Props = {
    plans: Plan[];
    currency: string;
    urls: { store: string; update: string; status: string };
};

/** Manage FFO → Project Plans: the share plans, their price per unit and commissions. */
export default function Plans({ plans, currency, urls }: Props) {
    const t = useTrans();
    const [editing, setEditing] = useState<Plan | 0 | null>(null);

    return (
        <>
            <PageHeader title={t('Project Plans')}>
                <button type="button" className="btn btn-sm btn-outline--primary" onClick={() => setEditing(0)}>
                    <i className="las la-plus" /> {t('Add New')}
                </button>
            </PageHeader>
            <DataTable
                rows={plans}
                rowKey={(p) => p.id}
                empty={t('No plans yet')}
                columns={[
                    { label: t('Name'), cell: (p) => <span className="fw-bold">{p.name}</span> },
                    { label: t('Price/unit'), cell: (p) => p.price },
                    { label: t('Referral Commission'), cell: (p) => p.refCom },
                    { label: t('Level 2'), cell: (p) => p.lev2 },
                    { label: t('Level 3'), cell: (p) => p.lev3 },
                    { label: t('Level 4'), cell: (p) => p.lev4 },
                    {
                        label: t('Holders'),
                        cell: (p) => (
                            <>
                                {p.holders}
                                <small className="text-muted d-block">{t(':n units', { n: p.units })}</small>
                            </>
                        ),
                    },
                    { label: t('Status'), cell: (p) => (p.active ? <span className="badge badge--success">{t('Enabled')}</span> : <span className="badge badge--warning">{t('Disabled')}</span>) },
                    {
                        label: t('Action'),
                        cell: (p) => (
                            <div className="button--group">
                                <Link href={p.url} className="btn btn-sm btn-outline--success">
                                    <i className="las la-users" /> {t('Holders')}
                                </Link>
                                <button type="button" className="btn btn-sm btn-outline--primary" onClick={() => setEditing(p)}>
                                    <i className="la la-pencil" /> {t('Edit')}
                                </button>
                                {p.active ? (
                                    <ConfirmButton url={withId(urls.status, p.id)} data={{ state: 'disable' }} question={t('Disable the plan :name? Members can no longer buy it.', { name: p.name })} className="btn btn-sm btn-outline--danger">
                                        <i className="la la-eye-slash" /> {t('Disable')}
                                    </ConfirmButton>
                                ) : (
                                    <ConfirmButton url={withId(urls.status, p.id)} data={{ state: 'enable' }} question={t('Enable the plan :name?', { name: p.name })} className="btn btn-sm btn-outline--success">
                                        <i className="la la-eye" /> {t('Enable')}
                                    </ConfirmButton>
                                )}
                            </div>
                        ),
                    },
                ]}
            />
            {editing !== null && <PlanDialog plan={editing || null} currency={currency} urls={urls} onClose={() => setEditing(null)} />}
        </>
    );
}

function PlanDialog({ plan, currency, urls, onClose }: { plan: Plan | null; currency: string; urls: Props['urls']; onClose: () => void }) {
    const t = useTrans();
    const form = useForm<PlanForm>(plan?.form ?? { name: '', price: '', ref_com: '', lev2: '', lev3: '', lev4: '' });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(plan ? withId(urls.update, plan.id) : urls.store, { preserveScroll: true, onSuccess: onClose });
    };
    const money = (key: keyof PlanForm, label: string, suffix = currency) => (
        <div className="col-sm-6 form-group" key={key}>
            <label htmlFor={`plan-${key}`}>{label}</label>
            <div className="input-group">
                <input id={`plan-${key}`} type="number" step="any" min="0" className="form-control" required value={form.data[key]} onChange={(e) => form.setData(key, e.target.value)} />
                <span className="input-group-text">{suffix}</span>
            </div>
            <FieldError message={form.errors[key]} />
        </div>
    );
    return (
        <Modal open onClose={onClose} labelledBy="plan-title">
            <DialogHeader id="plan-title" title={plan ? t('Edit Plan') : t('Add Plan')} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    <div className="form-group">
                        <label htmlFor="plan-name">{t('Name')}</label>
                        <input id="plan-name" className="form-control" required maxLength={40} value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                        <FieldError message={form.errors.name} />
                    </div>
                    <div className="row">
                        {money('price', t('Price per unit'))}
                        {money('ref_com', t('Referral Commission'))}
                        {money('lev2', t('Level 2'))}
                        {money('lev3', t('Level 3'))}
                        {money('lev4', t('Level 4'))}
                    </div>
                    {plan && <p className="text-muted small mb-0">{t('A new price applies to new purchases; existing holdings keep the unit cost they were bought at.')}</p>}
                </div>
                <div className="modal-footer">
                    <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing}>
                        {t('Submit')}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

import { useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { ConfirmButton, DialogHeader, FieldError, withId } from '@/Components/Admin/Form';
import IconPicker from '@/Components/Admin/IconPicker';
import PageHeader from '@/Components/Admin/PageHeader';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';

type Money = 'amount' | 'direct_commission' | 'indirect_commission' | 'pv' | 'pairing_per_day' | 'cash_back' | 'monthly_maintenance' | 'upgrade_bonus' | 'unilevel_bonus';
type ProjectForm = Record<Money, string> & { title: string; description: string; icon: string; color: string; max_pairing_slots: string; sort_order: string; upgrade_allowed: boolean; is_default: boolean; status: boolean };
type Project = {
    id: number;
    title: string;
    slug: string;
    icon: string;
    color: string;
    description: string;
    users: number;
    active: boolean;
    default: boolean;
    show: Record<string, string | null>;
    form: ProjectForm;
};

type Props = {
    projects: Project[];
    currency: string;
    urls: { store: string; update: string; destroy: string; toggle: string };
};

const DETAILS: [string, string][] = [
    ['direct_commission', 'Direct commission'],
    ['indirect_commission', 'Indirect commission'],
    ['pairing_per_day', 'Pairing per day'],
    ['upgrade_bonus', 'Upgrade bonus'],
    ['unilevel_bonus', 'Unilevel bonus'],
    ['monthly_maintenance', 'Monthly maintenance'],
];

/** MLM Projects: the membership tiers members join, with their price, PV and commissions. */
export default function Projects({ projects, currency, urls }: Props) {
    const t = useTrans();
    const [editing, setEditing] = useState<Project | 0 | null>(null);

    return (
        <>
            <PageHeader title={t('Projects')}>
                <button type="button" className="btn btn-sm btn-outline--primary" onClick={() => setEditing(0)}>
                    <i className="las la-plus" /> {t('Add Project')}
                </button>
            </PageHeader>
            <div className="row gy-4">
                {projects.map((p) => (
                    <div className="col-xl-4 col-md-6" key={p.id}>
                        <div className="card h-100" style={{ borderTop: `4px solid ${p.color}` }}>
                            <div className="card-body">
                                <div className="d-flex align-items-start gap-3 mb-3">
                                    <span className="d-inline-flex align-items-center justify-content-center rounded text-white flex-shrink-0" style={{ background: p.color, width: 44, height: 44, fontSize: 22 }} aria-hidden>
                                        <i className={p.icon} />
                                    </span>
                                    <div className="flex-grow-1">
                                        <h5 className="mb-0">{p.title}</h5>
                                        <small className="text-muted">{p.slug}</small>
                                    </div>
                                    <div className="text-end">
                                        <span className={`badge badge--${p.active ? 'success' : 'warning'}`}>{p.active ? t('Active') : t('Inactive')}</span>
                                        {p.default && <span className="badge badge--primary d-block mt-1">{t('Default')}</span>}
                                    </div>
                                </div>
                                <div className="fs-4 fw-bold text-nowrap mb-1">{p.show.amount}</div>
                                <div className="d-flex flex-wrap gap-2 mb-3">
                                    <span className="badge badge--dark">
                                        {t('PV')} {p.show.pv}
                                    </span>
                                    <span className="badge badge--success">
                                        {t('Cash back')} {p.show.cash_back}
                                    </span>
                                </div>
                                <ul className="list-unstyled small mb-3">
                                    {DETAILS.filter(([k]) => p.show[k] !== null).map(([k, label]) => (
                                        <li key={k} className="d-flex justify-content-between border-bottom py-1">
                                            <span className="text-muted">{t(label)}</span>
                                            <b className={k === 'monthly_maintenance' ? 'text--danger' : ''}>{t(p.show[k] ?? '')}</b>
                                        </li>
                                    ))}
                                </ul>
                                {p.description && <p className="small text-muted">{p.description}</p>}
                                <p className="small mb-3">
                                    <i className="las la-users" /> {t(':n subscribers', { n: p.users })}
                                </p>
                                <div className="d-flex flex-wrap gap-2">
                                    <button type="button" className="btn btn-sm btn-outline--primary" onClick={() => setEditing(p)}>
                                        <i className="la la-pen" /> {t('Edit')}
                                    </button>
                                    <ConfirmButton
                                        url={withId(urls.toggle, p.id)}
                                        data={{ state: p.active ? 'disable' : 'enable' }}
                                        question={p.active ? t('Deactivate :name?', { name: p.title }) : t('Activate :name?', { name: p.title })}
                                        className={`btn btn-sm ${p.active ? 'btn-outline--warning' : 'btn-outline--success'}`}
                                    >
                                        <i className={`la ${p.active ? 'la-eye-slash' : 'la-eye'}`} /> {p.active ? t('Deactivate') : t('Activate')}
                                    </ConfirmButton>
                                    {p.users === 0 && (
                                        <ConfirmButton url={withId(urls.destroy, p.id)} method="delete" question={t('Delete the project :name?', { name: p.title })} className="btn btn-sm btn-outline--danger">
                                            <i className="la la-trash" /> {t('Delete')}
                                        </ConfirmButton>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                ))}
                {projects.length === 0 && <p className="text-muted">{t('No projects yet.')}</p>}
            </div>
            {editing !== null && <ProjectDialog project={editing || null} currency={currency} urls={urls} onClose={() => setEditing(null)} />}
        </>
    );
}

const BLANK: ProjectForm = {
    title: '',
    description: '',
    icon: 'las la-briefcase',
    color: '#059669',
    amount: '',
    direct_commission: '0',
    indirect_commission: '0',
    pv: '0',
    pairing_per_day: '0',
    cash_back: '0',
    monthly_maintenance: '0',
    upgrade_bonus: '0',
    unilevel_bonus: '0',
    max_pairing_slots: '0',
    sort_order: '0',
    upgrade_allowed: true,
    is_default: false,
    status: true,
};

function ProjectDialog({ project, currency, urls, onClose }: { project: Project | null; currency: string; urls: Props['urls']; onClose: () => void }) {
    const t = useTrans();
    const form = useForm<ProjectForm>(project?.form ?? BLANK);
    const errors = form.errors as Partial<Record<keyof ProjectForm, string>>;
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(project ? withId(urls.update, project.id) : urls.store, { preserveScroll: true, onSuccess: onClose });
    };
    const num = (key: Money | 'max_pairing_slots' | 'sort_order', label: string, suffix?: string, hint?: string) => (
        <div className="col-sm-6 col-lg-4 form-group" key={key}>
            <label htmlFor={`pj-${key}`}>{label}</label>
            <div className="input-group">
                <input
                    id={`pj-${key}`}
                    type="number"
                    step={key === 'max_pairing_slots' || key === 'sort_order' ? '1' : 'any'}
                    min="0"
                    max={key === 'upgrade_bonus' ? 100 : undefined}
                    className="form-control"
                    required
                    value={form.data[key]}
                    onChange={(e) => form.setData(key, e.target.value)}
                />
                {suffix && <span className="input-group-text">{suffix}</span>}
            </div>
            {hint && <small className="text-muted">{hint}</small>}
            <FieldError message={errors[key]} />
        </div>
    );
    const check = (key: 'status' | 'upgrade_allowed' | 'is_default', label: string) => (
        <div className="form-check form-switch" key={key}>
            <input id={`pj-${key}`} type="checkbox" role="switch" className="form-check-input" checked={form.data[key]} onChange={(e) => form.setData(key, e.target.checked)} />
            <label className="form-check-label" htmlFor={`pj-${key}`}>
                {label}
            </label>
        </div>
    );
    return (
        <Modal open onClose={onClose} labelledBy="pj-title-h" size="lg">
            <DialogHeader id="pj-title-h" title={project ? t('Edit Project') : t('Add Project')} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    <div className="row">
                        <div className="col-sm-8 form-group">
                            <label htmlFor="pj-title">{t('Title')}</label>
                            <input id="pj-title" className="form-control" required maxLength={150} value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} />
                            <FieldError message={errors.title} />
                        </div>
                        <div className="col-sm-4 form-group">
                            <label htmlFor="pj-color">{t('Colour')}</label>
                            <input id="pj-color" type="color" className="form-control form-control-color w-100" value={form.data.color || '#059669'} onChange={(e) => form.setData('color', e.target.value)} />
                            <FieldError message={errors.color} />
                        </div>
                    </div>
                    <div className="form-group">
                        <label htmlFor="pj-icon">{t('Icon')}</label>
                        <IconPicker id="pj-icon" value={form.data.icon} onChange={(v) => form.setData('icon', v)} />
                        <FieldError message={errors.icon} />
                    </div>
                    <div className="row">
                        {num('amount', t('Price'), currency)}
                        {num('pv', t('PV'))}
                        {num('cash_back', t('Cash back'), currency)}
                        {num('direct_commission', t('Direct commission'), currency)}
                        {num('indirect_commission', t('Indirect commission'), currency)}
                        {num('pairing_per_day', t('Pairing per day'), currency, t('0 = unlimited'))}
                        {num('upgrade_bonus', t('Upgrade bonus'), '%')}
                        {num('unilevel_bonus', t('Unilevel bonus'), currency)}
                        {num('monthly_maintenance', t('Monthly maintenance'), currency)}
                        {num('max_pairing_slots', t('Max pairing slots'))}
                        {num('sort_order', t('Sort order'))}
                    </div>
                    <div className="form-group">
                        <label htmlFor="pj-description">
                            {t('Description')} <small className="text-muted">({t('optional')})</small>
                        </label>
                        <textarea id="pj-description" className="form-control" rows={2} maxLength={1000} value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} />
                    </div>
                    <div className="d-flex flex-wrap gap-4">
                        {check('status', t('Active'))}
                        {check('upgrade_allowed', t('Upgrade allowed'))}
                        {check('is_default', t('Default project'))}
                    </div>
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

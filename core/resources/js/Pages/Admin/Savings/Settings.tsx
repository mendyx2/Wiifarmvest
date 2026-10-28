import { Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import DataTable from '@/Components/Admin/DataTable';
import { DialogHeader, FieldError, withId } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';

type Setting = {
    id: number;
    label: string;
    description: string;
    type: 'target' | 'fixed' | 'farm';
    duration: number | null;
    rate: number;
    min: number;
    max: number | null;
    minLabel: string;
    maxLabel: string | null;
    active: boolean;
};

type Props = {
    settings: Setting[];
    currency: string;
    urls: { store: string; update: string; index: string };
};

const typeClass = { target: 'badge--info', fixed: 'badge--warning', farm: 'badge--success' } as const;

/** Savings & Loans → Savings Settings: the rate and limits of each savings product members can open. */
export default function SavingsSettings({ settings, currency, urls }: Props) {
    const t = useTrans();
    // null: closed; 0: adding; otherwise the setting being edited
    const [editing, setEditing] = useState<Setting | 0 | null>(null);

    return (
        <>
            <PageHeader title={t('Savings Settings')}>
                <button type="button" className="btn btn-outline--primary" onClick={() => setEditing(0)}>
                    <i className="las la-plus" /> {t('Add Setting')}
                </button>
                <Link href={urls.index} className="btn btn-outline--dark">
                    <i className="las la-list" /> {t('All Savings')}
                </Link>
            </PageHeader>
            <DataTable
                rows={settings}
                rowKey={(s) => s.id}
                empty={t('No savings settings yet.')}
                columns={[
                    {
                        label: t('Product'),
                        cell: (s) => (
                            <>
                                <strong>{s.label}</strong>
                                {s.description && (
                                    <>
                                        <br />
                                        <small className="text-muted">{s.description}</small>
                                    </>
                                )}
                            </>
                        ),
                    },
                    { label: t('Type'), cell: (s) => <span className={`badge ${typeClass[s.type] ?? 'badge--dark'}`}>{t(s.type.charAt(0).toUpperCase() + s.type.slice(1))}</span> },
                    { label: t('Duration'), cell: (s) => (s.duration ? t(':n months', { n: s.duration }) : '—') },
                    { label: t('Interest Rate (% p.a.)'), cell: (s) => <span className="fw-bold text--success">{s.rate.toFixed(2)}%</span> },
                    { label: t('Min Amount'), cell: (s) => s.minLabel },
                    { label: t('Max Amount'), cell: (s) => s.maxLabel ?? t('Unlimited') },
                    { label: t('Status'), cell: (s) => <span className={`badge ${s.active ? 'badge--success' : 'badge--danger'}`}>{s.active ? t('Active') : t('Inactive')}</span> },
                    {
                        label: t('Action'),
                        cell: (s) => (
                            <button type="button" className="btn btn-sm btn-outline--primary" onClick={() => setEditing(s)}>
                                <i className="las la-pen" /> {t('Edit')}
                            </button>
                        ),
                    },
                ]}
            />
            {editing === 0 && <AddSetting url={urls.store} currency={currency} onClose={() => setEditing(null)} />}
            {editing && <EditSetting setting={editing} url={withId(urls.update, editing.id)} currency={currency} onClose={() => setEditing(null)} />}
        </>
    );
}

function Money({ id, currency, value, onChange, required = false, placeholder }: { id: string; currency: string; value: string; onChange: (v: string) => void; required?: boolean; placeholder?: string }) {
    return (
        <div className="input-group">
            <span className="input-group-text">{currency}</span>
            <input id={id} type="number" className="form-control" step="0.01" min="0" required={required} placeholder={placeholder} value={value} onChange={(e) => onChange(e.target.value)} />
        </div>
    );
}

function AddSetting({ url, currency, onClose }: { url: string; currency: string; onClose: () => void }) {
    const t = useTrans();
    const form = useForm({ type: '', duration_months: '', label: '', interest_rate: '', min_amount: '', max_amount: '', description: '', is_active: true });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, onSuccess: onClose });
    };
    return (
        <Modal open onClose={onClose} labelledBy="add-setting-title" size="lg">
            <DialogHeader id="add-setting-title" title={t('Add New Savings Setting')} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    <div className="row g-3">
                        <div className="col-md-6">
                            <label className="form-label fw-bold" htmlFor="set-type">
                                {t('Type')} <span className="text--danger">*</span>
                            </label>
                            <select id="set-type" className="form-control" required value={form.data.type} onChange={(e) => form.setData('type', e.target.value)}>
                                <option value="">{t('— Select Type —')}</option>
                                <option value="target">{t('Target')}</option>
                                <option value="fixed">{t('Fixed')}</option>
                                <option value="farm">{t('Farm')}</option>
                            </select>
                            <FieldError message={form.errors.type} />
                        </div>
                        {form.data.type === 'fixed' && (
                            <div className="col-md-6">
                                <label className="form-label fw-bold" htmlFor="set-duration">
                                    {t('Duration (months)')} <span className="text--danger">*</span>
                                </label>
                                <input id="set-duration" type="number" className="form-control" min="1" required placeholder={t('e.g. 3, 6, 12')} value={form.data.duration_months} onChange={(e) => form.setData('duration_months', e.target.value)} />
                                <FieldError message={form.errors.duration_months} />
                            </div>
                        )}
                        <div className="col-12">
                            <label className="form-label fw-bold" htmlFor="set-label">
                                {t('Label')} <span className="text--danger">*</span>
                            </label>
                            <input id="set-label" className="form-control" maxLength={100} required placeholder={t('e.g. Fixed Box — 24 Months')} value={form.data.label} onChange={(e) => form.setData('label', e.target.value)} />
                            <FieldError message={form.errors.label} />
                        </div>
                        <SettingFields form={form} currency={currency} prefix="set" />
                    </div>
                </div>
                <div className="modal-footer">
                    <button type="button" className="btn btn-outline--dark" onClick={onClose}>
                        {t('Cancel')}
                    </button>
                    <button type="submit" className="btn btn--primary" disabled={form.processing}>
                        <i className="las la-plus" /> {t('Add Setting')}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

function EditSetting({ setting, url, currency, onClose }: { setting: Setting; url: string; currency: string; onClose: () => void }) {
    const t = useTrans();
    const form = useForm({ interest_rate: String(setting.rate), min_amount: String(setting.min), max_amount: setting.max === null ? '' : String(setting.max), description: setting.description, is_active: setting.active });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, onSuccess: onClose });
    };
    return (
        <Modal open onClose={onClose} labelledBy="edit-setting-title" size="lg">
            <DialogHeader id="edit-setting-title" title={`${t('Edit')} — ${setting.label}`} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    <div className="row g-3">
                        <SettingFields form={form} currency={currency} prefix="edit" />
                    </div>
                    <div className="alert alert-info mt-3 mb-0">
                        <i className="las la-info-circle me-1" />
                        {t('Changes apply to new savings products. Existing products retain their original interest rate.')}
                    </div>
                </div>
                <div className="modal-footer">
                    <button type="button" className="btn btn-outline--dark" onClick={onClose}>
                        {t('Cancel')}
                    </button>
                    <button type="submit" className="btn btn--primary" disabled={form.processing}>
                        <i className="las la-save" /> {t('Save Changes')}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

type SettingForm = {
    data: { interest_rate: string; min_amount: string; max_amount: string; description: string; is_active: boolean };
    errors: Partial<Record<string, string>>;
    setData: (key: 'interest_rate' | 'min_amount' | 'max_amount' | 'description' | 'is_active', value: string | boolean) => void;
};

/** Rate, limits, description and the Active switch: shared by Add and Edit. */
function SettingFields({ form, currency, prefix }: { form: SettingForm; currency: string; prefix: string }) {
    const t = useTrans();
    return (
        <>
            <div className="col-md-4">
                <label className="form-label fw-bold" htmlFor={`${prefix}-rate`}>
                    {t('Interest Rate (% p.a.)')} <span className="text--danger">*</span>
                </label>
                <div className="input-group">
                    <input id={`${prefix}-rate`} type="number" className="form-control" step="0.01" min="0" max="100" required value={form.data.interest_rate} onChange={(e) => form.setData('interest_rate', e.target.value)} />
                    <span className="input-group-text">% p.a.</span>
                </div>
                <FieldError message={form.errors.interest_rate} />
            </div>
            <div className="col-md-4">
                <label className="form-label fw-bold" htmlFor={`${prefix}-min`}>
                    {t('Minimum Amount')} <span className="text--danger">*</span>
                </label>
                <Money id={`${prefix}-min`} currency={currency} required value={form.data.min_amount} onChange={(v) => form.setData('min_amount', v)} />
                <FieldError message={form.errors.min_amount} />
            </div>
            <div className="col-md-4">
                <label className="form-label fw-bold" htmlFor={`${prefix}-max`}>
                    {t('Maximum Amount')} <small className="text-muted">({t('optional')})</small>
                </label>
                <Money id={`${prefix}-max`} currency={currency} placeholder={t('Leave blank for unlimited')} value={form.data.max_amount} onChange={(v) => form.setData('max_amount', v)} />
                <FieldError message={form.errors.max_amount} />
            </div>
            <div className="col-12">
                <label className="form-label fw-bold" htmlFor={`${prefix}-description`}>
                    {t('Description')}
                </label>
                <textarea id={`${prefix}-description`} className="form-control" rows={2} maxLength={255} value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} />
                <FieldError message={form.errors.description} />
            </div>
            <div className="col-12">
                <div className="form-check form-switch">
                    <input id={`${prefix}-active`} className="form-check-input" type="checkbox" checked={form.data.is_active} onChange={(e) => form.setData('is_active', e.target.checked)} />
                    <label className="form-check-label fw-bold" htmlFor={`${prefix}-active`}>
                        {t('Active (users can create this product type)')}
                    </label>
                </div>
            </div>
        </>
    );
}

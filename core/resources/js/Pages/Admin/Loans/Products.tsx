import { useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import DataTable from '@/Components/Admin/DataTable';
import { ConfirmButton, DialogHeader, FieldError, withId } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';
import type { Paginated } from '@/types';

type ProductForm = {
    name: string;
    description: string;
    min_membership_days: string;
    min_savings_threshold: string;
    savings_multiple: string;
    max_loan_amount: string;
    min_loan_amount: string;
    interest_rate: string;
    tenure_options: string;
    auto_approve: boolean;
    is_active: boolean;
};

type Product = {
    id: number;
    name: string;
    description: string;
    rate: string;
    min: string;
    max: string | null;
    multiple: string;
    minSavings: string;
    tenures: { label: string; weeks: boolean }[];
    autoApprove: boolean;
    active: boolean;
    form: ProductForm;
};

type Props = {
    products: Paginated<Product>;
    currency: string;
    urls: { store: string; update: string; toggle: string };
};

const blank: ProductForm = { name: '', description: '', min_membership_days: '', min_savings_threshold: '', savings_multiple: '', max_loan_amount: '', min_loan_amount: '', interest_rate: '', tenure_options: '', auto_approve: false, is_active: true };

/** Savings & Loans → Loan Products: what members can borrow, and on what terms. */
export default function LoanProducts({ products, currency, urls }: Props) {
    const t = useTrans();
    // null: closed; 0: new product; otherwise the product being edited
    const [editing, setEditing] = useState<Product | 0 | null>(null);

    return (
        <>
            <PageHeader title={t('Loan Products')}>
                <button type="button" className="btn btn-outline--primary" onClick={() => setEditing(0)}>
                    <i className="las la-plus" /> {t('New Product')}
                </button>
            </PageHeader>
            <DataTable
                rows={products.data}
                rowKey={(p) => p.id}
                links={products.links}
                empty={t('No loan products yet. Create one to get started.')}
                columns={[
                    {
                        label: t('Name'),
                        cell: (p) => (
                            <>
                                <strong>{p.name}</strong>
                                {p.description && (
                                    <>
                                        <br />
                                        <small className="text-muted">{p.description}</small>
                                    </>
                                )}
                            </>
                        ),
                    },
                    { label: t('Interest Rate'), cell: (p) => <span className="fw-bold text--success">{p.rate}</span> },
                    { label: t('Min Amount'), cell: (p) => p.min },
                    { label: t('Max Amount'), cell: (p) => p.max ?? t('Unlimited') },
                    { label: t('Savings Multiple'), cell: (p) => p.multiple },
                    { label: t('Min Savings'), cell: (p) => p.minSavings },
                    {
                        label: t('Tenures'),
                        cell: (p) => (
                            <span className="d-inline-flex flex-wrap gap-1">
                                {p.tenures.map((x, i) => (
                                    <span key={i} className={`badge ${x.weeks ? 'badge--warning' : 'badge--info'}`}>
                                        {x.label}
                                    </span>
                                ))}
                            </span>
                        ),
                    },
                    { label: t('Auto-Approve'), cell: (p) => <span className={`badge ${p.autoApprove ? 'badge--success' : 'badge--warning'}`}>{p.autoApprove ? t('Yes') : t('No')}</span> },
                    { label: t('Status'), cell: (p) => <span className={`badge ${p.active ? 'badge--success' : 'badge--danger'}`}>{p.active ? t('Active') : t('Inactive')}</span> },
                    {
                        label: t('Action'),
                        cell: (p) => (
                            <div className="button--group">
                                <button type="button" className="btn btn-sm btn-outline--primary" aria-label={t('Edit :name', { name: p.name })} onClick={() => setEditing(p)}>
                                    <i className="las la-pen" /> {t('Edit')}
                                </button>
                                {p.active ? (
                                    <ConfirmButton url={withId(urls.toggle, p.id)} data={{ state: 'disable' }} question={t('Disable :name? Members will not be able to apply for it.', { name: p.name })} className="btn btn-sm btn-outline--danger">
                                        <i className="las la-eye-slash" /> {t('Disable')}
                                    </ConfirmButton>
                                ) : (
                                    <ConfirmButton url={withId(urls.toggle, p.id)} data={{ state: 'enable' }} question={t('Enable :name?', { name: p.name })} className="btn btn-sm btn-outline--success">
                                        <i className="las la-eye" /> {t('Enable')}
                                    </ConfirmButton>
                                )}
                            </div>
                        ),
                    },
                ]}
            />
            {editing !== null && <ProductDialog product={editing || null} url={editing ? withId(urls.update, editing.id) : urls.store} currency={currency} onClose={() => setEditing(null)} />}
        </>
    );
}

function ProductDialog({ product, url, currency, onClose }: { product: Product | null; url: string; currency: string; onClose: () => void }) {
    const t = useTrans();
    const form = useForm<ProductForm>(product ? product.form : blank);
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, onSuccess: onClose });
    };
    type TextKey = Exclude<keyof ProductForm, 'auto_approve' | 'is_active'>;
    const input = (key: TextKey, props: { type?: string; step?: string; min?: string; max?: string; required?: boolean; placeholder?: string; maxLength?: number } = {}) => (
        <input id={`lp-${key}`} className="form-control" type={props.type ?? 'number'} step={props.step} min={props.min} max={props.max} required={props.required ?? true} placeholder={props.placeholder} maxLength={props.maxLength} value={form.data[key]} onChange={(e) => form.setData(key, e.target.value)} />
    );
    const label = (key: string, text: string, required = true, hint?: string) => (
        <label className="form-label fw-bold" htmlFor={`lp-${key}`}>
            {text} {required && <span className="text--danger">*</span>} {hint && <small className="text-muted fw-normal">({hint})</small>}
        </label>
    );
    const toggle = (key: 'auto_approve' | 'is_active', text: string, help: string) => (
        <div className="form-check form-switch mt-2">
            <input id={`lp-${key}`} className="form-check-input" type="checkbox" checked={form.data[key]} onChange={(e) => form.setData(key, e.target.checked)} />
            <label className="form-check-label fw-bold" htmlFor={`lp-${key}`}>
                {text}
                <small className="text-muted fw-normal d-block">{help}</small>
            </label>
        </div>
    );

    return (
        <Modal open onClose={onClose} labelledBy="lp-title" size="xl">
            <DialogHeader id="lp-title" title={product ? t('Edit Loan Product') : t('New Loan Product')} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    <div className="row g-3">
                        <div className="col-sm-8">
                            {label('name', t('Product Name'))}
                            {input('name', { type: 'text', maxLength: 100, placeholder: t('e.g. Standard Cooperative Loan') })}
                            <FieldError message={form.errors.name} />
                        </div>
                        <div className="col-sm-4">
                            {label('interest_rate', t('Interest Rate (% p.a.)'))}
                            <div className="input-group">
                                {input('interest_rate', { step: '0.01', min: '0', max: '100' })}
                                <span className="input-group-text">%</span>
                            </div>
                            <FieldError message={form.errors.interest_rate} />
                        </div>
                        <div className="col-12">
                            {label('description', t('Description'), false)}
                            {input('description', { type: 'text', required: false, maxLength: 255, placeholder: t('Brief description shown to members') })}
                            <FieldError message={form.errors.description} />
                        </div>
                        <div className="col-sm-6">
                            {label('min_loan_amount', t('Minimum Loan Amount'))}
                            <div className="input-group">
                                <span className="input-group-text">{currency}</span>
                                {input('min_loan_amount', { step: '0.01', min: '0' })}
                            </div>
                            <FieldError message={form.errors.min_loan_amount} />
                        </div>
                        <div className="col-sm-6">
                            {label('max_loan_amount', t('Maximum Loan Amount'), false, t('blank = unlimited'))}
                            <div className="input-group">
                                <span className="input-group-text">{currency}</span>
                                {input('max_loan_amount', { step: '0.01', min: '0', required: false, placeholder: t('Leave blank for no cap') })}
                            </div>
                            <FieldError message={form.errors.max_loan_amount} />
                        </div>
                        <div className="col-sm-4">
                            {label('savings_multiple', t('Savings Multiple'), true, t('max loan = savings × multiple'))}
                            <div className="input-group">
                                {input('savings_multiple', { step: '0.1', min: '1', max: '20' })}
                                <span className="input-group-text">×</span>
                            </div>
                            <FieldError message={form.errors.savings_multiple} />
                        </div>
                        <div className="col-sm-4">
                            {label('min_savings_threshold', t('Min Savings Wallet Balance'))}
                            <div className="input-group">
                                <span className="input-group-text">{currency}</span>
                                {input('min_savings_threshold', { step: '0.01', min: '0' })}
                            </div>
                            <FieldError message={form.errors.min_savings_threshold} />
                        </div>
                        <div className="col-sm-4">
                            {label('min_membership_days', t('Min Membership Days'))}
                            <div className="input-group">
                                {input('min_membership_days', { min: '0', step: '1' })}
                                <span className="input-group-text">{t('days')}</span>
                            </div>
                            <FieldError message={form.errors.min_membership_days} />
                        </div>
                        <div className="col-12">
                            {label('tenure_options', t('Tenure Options'))}
                            {input('tenure_options', { type: 'text', placeholder: '2w,4w,1m,3m,6m,12m' })}
                            <small className="text-muted">{t('Comma-separated. Use w for weeks, m for months, e.g. 2w,4w,1m,3m,6m,12m')}</small>
                            <FieldError message={form.errors.tenure_options} />
                        </div>
                        <div className="col-sm-6">{toggle('auto_approve', t('Auto-Approve & Disburse'), t('Skip admin review; funds credited immediately.'))}</div>
                        <div className="col-sm-6">{toggle('is_active', t('Active'), t('Members can apply for this product.'))}</div>
                    </div>
                </div>
                <div className="modal-footer">
                    <button type="button" className="btn btn-outline--dark" onClick={onClose}>
                        {t('Cancel')}
                    </button>
                    <button type="submit" className="btn btn--primary" disabled={form.processing}>
                        <i className="las la-save" /> {product ? t('Update Product') : t('Create Product')}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

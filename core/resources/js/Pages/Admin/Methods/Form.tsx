import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FieldError } from '@/Components/Admin/Form';
import FormBuilder, { toGenerator, type BuilderField } from '@/Components/Admin/FormBuilder';
import GatewayTabs from '@/Components/Admin/GatewayTabs';
import ImageUploader from '@/Components/Admin/ImageUploader';
import PageHeader from '@/Components/Admin/PageHeader';
import RichTextEditor from '@/Components/Admin/RichTextEditor';
import { useTrans } from '@/lib/i18n';

type MethodData = { name: string; currency: string; rate: string; min_limit: string; max_limit: string; fixed_charge: string; percent_charge: string; instruction: string };

type Props = {
    /** A manual deposit gateway or a withdrawal method: the same form */
    kind: 'gateway' | 'withdraw';
    title: string;
    method: MethodData;
    image: string;
    fields: BuilderField[];
    currency: string;
    extensions: string[];
    urls: { tabs: { automatic: string; manual: string } | null; submit: string; back: string };
};

/**
 * Add / edit a manual gateway or a withdrawal method: name, picture,
 * currency and rate, limits, charges, the instruction members see, and the
 * form they fill in. (gateways/manual/{create,edit} and withdraw/{create,edit}
 * were four copies of this page.)
 */
export default function MethodForm({ kind, title, method, image, fields, currency, extensions, urls }: Props) {
    const t = useTrans();
    const isNew = method.name === '';
    const form = useForm<MethodData & { image: File | null; fields: BuilderField[] }>({ ...method, image: null, fields });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.transform(({ fields: f, ...rest }) => ({ ...rest, form_generator: toGenerator(f) }));
        form.post(urls.submit, { preserveScroll: true, forceFormData: true });
    };
    const money = (key: keyof MethodData, label: string, suffix = currency) => (
        <div className="form-group">
            <label htmlFor={`mf-${key}`}>{label}</label>
            <div className="input-group">
                <input id={`mf-${key}`} type="number" step="any" min="0" className="form-control" required value={form.data[key]} onChange={(e) => form.setData(key, e.target.value)} />
                <span className="input-group-text">{suffix}</span>
            </div>
            <FieldError message={form.errors[key]} />
        </div>
    );

    return (
        <>
            <PageHeader title={t(title)}>
                <Link href={urls.back} className="btn btn-outline--dark">
                    <i className="las la-undo" /> {t('Back')}
                </Link>
            </PageHeader>
            {urls.tabs && <GatewayTabs tabs={urls.tabs} active="manual" />}
            <form onSubmit={submit} className="card">
                <div className="card-body">
                    <div className="row">
                        <div className="col-lg-3 form-group">
                            <label>{t('Image')}</label>
                            <ImageUploader image={image} required={isNew} onChange={(f) => form.setData('image', f)} className="w-100" />
                            <FieldError message={form.errors.image} />
                        </div>
                        <div className="col-lg-9">
                            <div className="row">
                                <div className="col-xl-5 col-md-12 form-group">
                                    <label htmlFor="mf-name">{kind === 'gateway' ? t('Gateway Name') : t('Name')}</label>
                                    <input id="mf-name" className="form-control" required maxLength={40} value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                                    <FieldError message={form.errors.name} />
                                </div>
                                <div className="col-xl-3 col-md-4 form-group">
                                    <label htmlFor="mf-currency">{t('Currency')}</label>
                                    <input id="mf-currency" className="form-control" required maxLength={40} value={form.data.currency} onChange={(e) => form.setData('currency', e.target.value.toUpperCase())} />
                                    <FieldError message={form.errors.currency} />
                                </div>
                                <div className="col-xl-4 col-md-8 form-group">
                                    <label htmlFor="mf-rate">{t('Rate')}</label>
                                    <div className="input-group">
                                        <span className="input-group-text">1 {currency} =</span>
                                        <input id="mf-rate" type="number" step="any" min="0" className="form-control" required value={form.data.rate} onChange={(e) => form.setData('rate', e.target.value)} />
                                        <span className="input-group-text">{form.data.currency || '—'}</span>
                                    </div>
                                    <FieldError message={form.errors.rate} />
                                </div>
                            </div>
                            <div className="row">
                                <div className="col-md-6">
                                    <div className="card border--primary mb-3">
                                        <h5 className="card-header bg--primary text-white">{t('Range')}</h5>
                                        <div className="card-body">
                                            {money('min_limit', t('Minimum Amount'))}
                                            {money('max_limit', t('Maximum Amount'))}
                                        </div>
                                    </div>
                                </div>
                                <div className="col-md-6">
                                    <div className="card border--primary mb-3">
                                        <h5 className="card-header bg--primary text-white">{t('Charge')}</h5>
                                        <div className="card-body">
                                            {money('fixed_charge', t('Fixed Charge'))}
                                            {money('percent_charge', t('Percent Charge'), '%')}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div className="form-group">
                        <label htmlFor="mf-instruction">{kind === 'gateway' ? t('Deposit Instruction') : t('Withdraw Instruction')}</label>
                        <RichTextEditor id="mf-instruction" label={kind === 'gateway' ? t('Deposit Instruction') : t('Withdraw Instruction')} value={form.data.instruction} onChange={(html) => form.setData('instruction', html)} />
                        <FieldError message={form.errors.instruction} />
                    </div>
                    <FormBuilder fields={form.data.fields} onChange={(f) => form.setData('fields', f)} extensions={extensions} errors={form.errors as Record<string, string>} />
                </div>
                <div className="card-footer">
                    <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing}>
                        {t('Submit')}
                    </button>
                </div>
            </form>
        </>
    );
}

import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FieldError } from '@/Components/Admin/Form';
import ImageUploader from '@/Components/Admin/ImageUploader';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';

type AwardForm = { name: string; description: string; required_total_pv: string; required_left_pv: string; required_right_pv: string; prerequisite_award_id: string; payment_amount: string; sort_order: string; status: boolean };

type Props = {
    title: string;
    award: AwardForm;
    image: string;
    size: string;
    awards: { id: number; name: string }[];
    currency: string;
    urls: { submit: string; back: string };
};

/** Add or edit an award: what PV earns it, what it pays, and the award it requires first. */
export default function AwardFormPage({ title, award, image, size, awards, currency, urls }: Props) {
    const t = useTrans();
    const form = useForm<AwardForm & { image: File | null }>({ ...award, image: null });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({ ...d, status: d.status ? 1 : 0 }));
        form.post(urls.submit, { forceFormData: true, preserveScroll: true });
    };
    const pv = (key: 'required_total_pv' | 'required_left_pv' | 'required_right_pv', label: string) => (
        <div className="col-sm-4 form-group" key={key}>
            <label htmlFor={`aw-${key}`}>{label}</label>
            <input id={`aw-${key}`} type="number" step="any" min="0" className="form-control" required value={form.data[key]} onChange={(e) => form.setData(key, e.target.value)} />
            <FieldError message={form.errors[key]} />
        </div>
    );
    return (
        <>
            <PageHeader title={t(title)}>
                <Link href={urls.back} className="btn btn-sm btn-outline--primary">
                    <i className="la la-undo" /> {t('Back')}
                </Link>
            </PageHeader>
            <div className="card">
                <form onSubmit={submit} className="card-body">
                    <div className="row">
                        <div className="col-lg-4 form-group">
                            <label>{t('Image')}</label>
                            <ImageUploader image={image} size={size} accept=".png, .jpg, .jpeg, .gif" onChange={(f) => form.setData('image', f)} className="w-100" />
                            <FieldError message={form.errors.image} />
                        </div>
                        <div className="col-lg-8">
                            <div className="row">
                                <div className="col-sm-8 form-group">
                                    <label htmlFor="aw-name">{t('Name')}</label>
                                    <input id="aw-name" className="form-control" required maxLength={150} value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                                    <FieldError message={form.errors.name} />
                                </div>
                                <div className="col-sm-4 form-group">
                                    <label htmlFor="aw-order">{t('Order')}</label>
                                    <input id="aw-order" type="number" min="0" step="1" className="form-control" required value={form.data.sort_order} onChange={(e) => form.setData('sort_order', e.target.value)} />
                                    <FieldError message={form.errors.sort_order} />
                                </div>
                            </div>
                            <div className="row">
                                {pv('required_total_pv', t('Total PV needed'))}
                                {pv('required_left_pv', t('Left leg PV'))}
                                {pv('required_right_pv', t('Right leg PV'))}
                            </div>
                            <div className="row">
                                <div className="col-sm-6 form-group">
                                    <label htmlFor="aw-payment">{t('Payment')}</label>
                                    <div className="input-group">
                                        <input id="aw-payment" type="number" step="any" min="0" className="form-control" required value={form.data.payment_amount} onChange={(e) => form.setData('payment_amount', e.target.value)} />
                                        <span className="input-group-text">{currency}</span>
                                    </div>
                                    <small className="text-muted">{t("Paid into the member's awards wallet when you pay it.")}</small>
                                    <FieldError message={form.errors.payment_amount} />
                                </div>
                                <div className="col-sm-6 form-group">
                                    <label htmlFor="aw-prereq">{t('Requires first')}</label>
                                    <select id="aw-prereq" className="form-select" value={form.data.prerequisite_award_id} onChange={(e) => form.setData('prerequisite_award_id', e.target.value)}>
                                        <option value="">{t('Nothing')}</option>
                                        {awards.map((a) => (
                                            <option key={a.id} value={a.id}>
                                                {a.name}
                                            </option>
                                        ))}
                                    </select>
                                    <FieldError message={form.errors.prerequisite_award_id} />
                                </div>
                            </div>
                            <div className="form-group">
                                <label htmlFor="aw-description">
                                    {t('Description')} <small className="text-muted">({t('optional')})</small>
                                </label>
                                <textarea id="aw-description" className="form-control" rows={3} maxLength={2000} value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} />
                            </div>
                            <div className="form-check form-switch mb-3">
                                <input id="aw-status" type="checkbox" role="switch" className="form-check-input" checked={form.data.status} onChange={(e) => form.setData('status', e.target.checked)} />
                                <label className="form-check-label" htmlFor="aw-status">
                                    {t('Active: members can earn it')}
                                </label>
                            </div>
                            <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing}>
                                {t('Submit')}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </>
    );
}

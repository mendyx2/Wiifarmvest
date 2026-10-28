import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FieldError } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import RichTextEditor from '@/Components/Admin/RichTextEditor';
import { useTrans } from '@/lib/i18n';

type Props = { form: { status: boolean; short_desc: string; description: string }; url: string };

/** Settings → GDPR Cookie: the cookie notice and the policy behind its link. */
export default function Cookie({ form: initial, url }: Props) {
    const t = useTrans();
    const form = useForm(initial);
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true });
    };

    return (
        <>
            <PageHeader title={t('GDPR Cookie')} />
            <div className="card">
                <form onSubmit={submit}>
                    <div className="card-body">
                        <div className="form-check form-switch mb-4" style={{ fontSize: '1.2rem' }}>
                            <input id="ck-status" type="checkbox" role="switch" className="form-check-input" checked={form.data.status} onChange={(e) => form.setData('status', e.target.checked)} />
                            <label className="form-check-label fw-bold" htmlFor="ck-status">
                                {form.data.status ? t('Visitors are asked to accept cookies') : t('The cookie notice is off')}
                            </label>
                        </div>
                        <div className="form-group">
                            <label htmlFor="ck-short">{t('Short Description')}</label>
                            <textarea id="ck-short" className="form-control" rows={4} required maxLength={255} value={form.data.short_desc} onChange={(e) => form.setData('short_desc', e.target.value)} />
                            <FieldError message={form.errors.short_desc} />
                        </div>
                        <div className="form-group">
                            <label htmlFor="ck-description">{t('Description')}</label>
                            <RichTextEditor id="ck-description" label={t('Description')} value={form.data.description} onChange={(html) => form.setData('description', html)} />
                            <FieldError message={form.errors.description} />
                        </div>
                    </div>
                    <div className="card-footer">
                        <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing}>
                            {t('Submit')}
                        </button>
                    </div>
                </form>
            </div>
        </>
    );
}

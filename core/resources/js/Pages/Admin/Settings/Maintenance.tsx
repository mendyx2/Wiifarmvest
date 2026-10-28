import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FieldError } from '@/Components/Admin/Form';
import ImageUploader from '@/Components/Admin/ImageUploader';
import PageHeader from '@/Components/Admin/PageHeader';
import RichTextEditor from '@/Components/Admin/RichTextEditor';
import { useTrans } from '@/lib/i18n';

type Props = {
    form: { status: boolean; description: string };
    image: string;
    size: string;
    url: string;
};

/** Settings → Maintenance Mode: switch the site off for members, with a message and a picture. */
export default function Maintenance({ form: initial, image, size, url }: Props) {
    const t = useTrans();
    const form = useForm<{ status: boolean; description: string; image: File | null }>({ ...initial, image: null });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, forceFormData: true });
    };

    return (
        <>
            <PageHeader title={t('Maintenance Mode')} />
            <div className="card">
                <form onSubmit={submit}>
                    <div className="card-body">
                        <div className="form-check form-switch mb-4" style={{ fontSize: '1.2rem' }}>
                            <input id="mm-status" type="checkbox" role="switch" className="form-check-input" checked={form.data.status} onChange={(e) => form.setData('status', e.target.checked)} />
                            <label className="form-check-label fw-bold" htmlFor="mm-status">
                                {form.data.status ? t('Maintenance mode is ON: members see the message below') : t('Maintenance mode is off')}
                            </label>
                        </div>
                        <div className="row">
                            <div className="col-xl-4 form-group">
                                <label>{t('Image')}</label>
                                <ImageUploader image={image} size={size} onChange={(f) => form.setData('image', f)} className="w-100" />
                                <FieldError message={form.errors.image} />
                            </div>
                            <div className="col-xl-8 form-group">
                                <label htmlFor="mm-description">{t('Description')}</label>
                                <RichTextEditor id="mm-description" label={t('Description')} value={form.data.description} onChange={(html) => form.setData('description', html)} />
                                <FieldError message={form.errors.description} />
                            </div>
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

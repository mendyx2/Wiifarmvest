import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FieldError } from '@/Components/Admin/Form';
import ImageUploader from '@/Components/Admin/ImageUploader';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';

type Props = {
    images: { logo: string; logo_dark: string; favicon: string };
    urls: { update: string; clearCache: string };
};

/** Settings → Logo and Favicon. */
export default function LogoIcon({ images, urls }: Props) {
    const t = useTrans();
    const form = useForm<{ logo: File | null; logo_dark: File | null; favicon: File | null }>({ logo: null, logo_dark: null, favicon: null });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(urls.update, { preserveScroll: true, forceFormData: true, onSuccess: () => form.reset() });
    };
    const field = (key: 'logo' | 'logo_dark' | 'favicon', label: string, accept: string, dark = false) => (
        <div className="form-group col-md-4">
            <label>{label}</label>
            <div className={dark ? 'bg--dark rounded p-2' : ''}>
                <ImageUploader image={images[key]} accept={accept} onChange={(f) => form.setData(key, f)} className="w-100" />
            </div>
            <FieldError message={form.errors[key]} />
        </div>
    );

    return (
        <>
            <PageHeader title={t('Logo & Favicon')} />
            <div className="card bl--5 border--primary mb-4">
                <div className="card-body">
                    <p className="text--primary mb-0">
                        {t('If the logo and favicon do not change after you update them here, please')} <a href={urls.clearCache}>{t('clear the cache')}</a>{' '}
                        {t('and your browser cache. The file name stays the same after an update, so a cached copy of the old image may still be shown.')}
                    </p>
                </div>
            </div>
            <div className="card">
                <div className="card-body">
                    <form onSubmit={submit}>
                        <div className="row justify-content-center">
                            {field('logo', t('Logo'), '.png, .jpg, .jpeg')}
                            {field('logo_dark', t('Logo Dark'), '.png, .jpg, .jpeg', true)}
                            {field('favicon', t('Favicon'), '.png')}
                        </div>
                        <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing || (!form.data.logo && !form.data.logo_dark && !form.data.favicon)}>
                            {t('Submit')}
                        </button>
                    </form>
                </div>
            </div>
        </>
    );
}

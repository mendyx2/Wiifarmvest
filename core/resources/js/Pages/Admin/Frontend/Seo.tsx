import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FieldError } from '@/Components/Admin/Form';
import ImageUploader from '@/Components/Admin/ImageUploader';
import PageHeader from '@/Components/Admin/PageHeader';
import TagInput from '@/Components/Admin/TagInput';
import { useTrans } from '@/lib/i18n';

type Form = { keywords: string[]; description: string; social_title: string; social_description: string };

type Props = {
    title: string;
    /** The site-wide SEO needs every field; a page's or an item's is optional */
    required: boolean;
    image: string;
    size: string;
    form: Form;
    urls: { submit: string; back: string | null; global: string | null };
};

/**
 * SEO Configuration: the site-wide SEO, a page's SEO (Manage Pages) and an
 * item's SEO (blog posts, policy pages). The three Blade pages were copies.
 */
export default function Seo({ title, required, image, size, form: initial, urls }: Props) {
    const t = useTrans();
    const form = useForm<Form & { image: File | null }>({ ...initial, image: null });
    const errors = form.errors as Record<string, string | undefined>;
    const keywordError = errors.keywords ?? Object.entries(errors).find(([k]) => k.startsWith('keywords.'))?.[1];
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(urls.submit, { preserveScroll: true, forceFormData: true, onSuccess: () => form.setData('image', null) });
    };

    return (
        <>
            <PageHeader title={t(title)}>
                {urls.back && (
                    <Link href={urls.back} className="btn btn-sm btn-outline--primary">
                        <i className="la la-undo" /> {t('Back')}
                    </Link>
                )}
            </PageHeader>
            {urls.global && (
                <div className="card bl--5 border--warning mb-4">
                    <div className="card-body">
                        <p className="text--warning mb-0">
                            {t("The SEO setting is optional for this page. If you don't configure SEO here, the global SEO contents will work for this page, which you can configure from")}{' '}
                            <Link href={urls.global}>{t('System Setting > SEO Configuration')}</Link>.
                        </p>
                    </div>
                </div>
            )}
            <div className="card">
                <form onSubmit={submit}>
                    <div className="card-body">
                        <div className="row">
                            <div className="col-xl-4 form-group">
                                <label>{t('SEO Image')}</label>
                                <ImageUploader image={image} size={size} onChange={(f) => form.setData('image', f)} className="w-100" />
                                <FieldError message={form.errors.image} />
                            </div>
                            <div className="col-xl-8 mt-xl-0 mt-4">
                                <div className="form-group">
                                    <label htmlFor="seo-keywords">{t('Meta Keywords')}</label>{' '}
                                    <small className="ms-2">
                                        {t('Separate multiple keywords by')} <code>,</code> ({t('comma')}) {t('or')} <code>{t('enter')}</code> {t('key')}
                                    </small>
                                    <TagInput id="seo-keywords" value={form.data.keywords} onChange={(k) => form.setData('keywords', k)} />
                                    <FieldError message={keywordError} />
                                </div>
                                <div className="form-group">
                                    <label htmlFor="seo-description">{t('Meta Description')}</label>
                                    <textarea id="seo-description" rows={3} className="form-control" required={required} maxLength={1000} value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} />
                                    <FieldError message={form.errors.description} />
                                </div>
                                <div className="form-group">
                                    <label htmlFor="seo-social-title">{t('Social Title')}</label>
                                    <input id="seo-social-title" type="text" className="form-control" required={required} maxLength={255} value={form.data.social_title} onChange={(e) => form.setData('social_title', e.target.value)} />
                                    <FieldError message={form.errors.social_title} />
                                </div>
                                <div className="form-group">
                                    <label htmlFor="seo-social-description">{t('Social Description')}</label>
                                    <textarea
                                        id="seo-social-description"
                                        rows={3}
                                        className="form-control"
                                        required={required}
                                        maxLength={1000}
                                        value={form.data.social_description}
                                        onChange={(e) => form.setData('social_description', e.target.value)}
                                    />
                                    <FieldError message={form.errors.social_description} />
                                </div>
                                <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing || (required && form.data.keywords.length === 0)}>
                                    {t('Submit')}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </>
    );
}

import { Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import ContentFields, { contentPayload, type ContentData, type ContentField, type ContentImage } from '@/Components/Admin/ContentFields';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';

type Props = {
    name: string;
    /** null while adding */
    id: number | null;
    fields: ContentField[];
    images: ContentImage[];
    values: Record<string, string>;
    urls: { save: string; back: string; slugCheck: string };
};

/** One item of a section on its own page: the items with long text (blog posts, policy pages, FAQ, team). */
export default function Element({ name, id, fields, images, values, urls }: Props) {
    const t = useTrans();
    const form = useForm<ContentData>({ values, image_input: {} });
    const [slugState, setSlugState] = useState<string>('idle');
    const hasSlug = fields.some((f) => f.type === 'slug');
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.transform((d) => contentPayload(d, id ? { type: 'element', id } : { type: 'element' }));
        form.post(urls.save, { preserveScroll: true, forceFormData: true, onSuccess: () => form.setData('image_input', {}) });
    };

    return (
        <>
            <PageHeader title={id ? t('Update :name Item', { name }) : t('Add New :name Item', { name })}>
                <Link href={urls.back} className="btn btn-sm btn-outline--primary">
                    <i className="la la-undo" /> {t('Back')}
                </Link>
            </PageHeader>
            <div className="card">
                <form onSubmit={submit}>
                    <div className="card-body">
                        <ContentFields
                            idPrefix="element"
                            fields={fields}
                            images={images}
                            data={form.data}
                            setValue={(n, v) => form.setData('values', { ...form.data.values, [n]: v })}
                            setImage={(k, f) => form.setData('image_input', { ...form.data.image_input, [k]: f })}
                            errors={form.errors as Record<string, string | undefined>}
                            slugCheck={urls.slugCheck}
                            initialSlug={values.slug ?? ''}
                            onSlugState={setSlugState}
                        />
                        <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing || (hasSlug && (slugState === 'taken' || slugState === 'checking'))}>
                            {t('Submit')}
                        </button>
                    </div>
                </form>
            </div>
        </>
    );
}

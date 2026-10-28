import { Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import ContentFields, { contentPayload, type ContentData, type ContentField, type ContentImage } from '@/Components/Admin/ContentFields';
import DataTable from '@/Components/Admin/DataTable';
import { ConfirmButton, DialogHeader, withId } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';

type Item = { id: number; image: string | null; values: Record<string, string>; images: Record<string, string> };

type Props = {
    name: string;
    content: { fields: ContentField[]; images: ContentImage[]; values: Record<string, string> } | null;
    element: { fields: ContentField[]; images: ContentImage[]; modal: boolean; seo: boolean; columns: ContentField[] } | null;
    elements: Item[];
    urls: { save: string; create: string; edit: string; seo: string; remove: string; slugCheck: string; back: string | null };
};

/** Frontend Content → a section: its texts and pictures, and its list of items. */
export default function Section({ name, content, element, elements, urls }: Props) {
    const t = useTrans();
    // null: closed; 0: adding; otherwise the item being edited
    const [editing, setEditing] = useState<Item | 0 | null>(null);

    return (
        <>
            <PageHeader title={name}>
                {element &&
                    (element.modal ? (
                        <button type="button" className="btn btn-sm btn-outline--primary" onClick={() => setEditing(0)}>
                            <i className="las la-plus" /> {t('Add New')}
                        </button>
                    ) : (
                        <Link href={urls.create} className="btn btn-sm btn-outline--primary">
                            <i className="las la-plus" /> {t('Add New')}
                        </Link>
                    ))}
                {urls.back && (
                    <Link href={urls.back} className="btn btn-sm btn-outline--primary">
                        <i className="la la-undo" /> {t('Back')}
                    </Link>
                )}
            </PageHeader>

            {content && <ContentForm content={content} url={urls.save} />}

            {element && (
                <DataTable
                    rows={elements}
                    rowKey={(r) => r.id}
                    empty={t('Data not found')}
                    columns={[
                        { label: t('SL'), cell: (r) => elements.indexOf(r) + 1 },
                        ...(element.images.length ? [{ label: t('Image'), cell: (r: Item) => (r.image ? <img src={r.image} alt="" style={{ width: 60, height: 45, objectFit: 'cover', borderRadius: 4 }} /> : null) }] : []),
                        ...element.columns.map((c) => ({
                            label: c.label,
                            cell: (r: Item) => {
                                const v = r.values[c.name] ?? '';
                                if (c.type === 'icon') return <i className={v} style={{ fontSize: 22 }} aria-label={v} />;
                                if (c.type === 'select') return c.options?.find((o) => o.value === v)?.label ?? v;
                                return v;
                            },
                        })),
                        {
                            label: t('Action'),
                            cell: (r) => (
                                <div className="button--group">
                                    {element.seo && (
                                        <Link href={withId(urls.seo, r.id)} className="btn btn-sm btn-outline--info">
                                            <i className="la la-cog" /> {t('SEO Setting')}
                                        </Link>
                                    )}
                                    {element.modal ? (
                                        <button type="button" className="btn btn-sm btn-outline--primary" onClick={() => setEditing(r)}>
                                            <i className="la la-pencil-alt" /> {t('Edit')}
                                        </button>
                                    ) : (
                                        <Link href={withId(urls.edit, r.id)} className="btn btn-sm btn-outline--primary">
                                            <i className="la la-pencil-alt" /> {t('Edit')}
                                        </Link>
                                    )}
                                    <ConfirmButton url={withId(urls.remove, r.id)} question={t('Are you sure to remove this item?')} className="btn btn-sm btn-outline--danger">
                                        <i className="la la-trash" /> {t('Remove')}
                                    </ConfirmButton>
                                </div>
                            ),
                        },
                    ]}
                />
            )}

            {element && editing !== null && <ItemDialog name={name} element={element} item={editing || null} urls={urls} onClose={() => setEditing(null)} />}
        </>
    );
}

function ContentForm({ content, url }: { content: NonNullable<Props['content']>; url: string }) {
    const t = useTrans();
    const form = useForm<ContentData>({ values: content.values, image_input: {} });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.transform((d) => contentPayload(d, { type: 'content' }));
        form.post(url, { preserveScroll: true, forceFormData: true, onSuccess: () => form.setData('image_input', {}) });
    };
    return (
        <div className="card mb-4">
            <form onSubmit={submit}>
                <div className="card-body">
                    <ContentFields
                        idPrefix="content"
                        fields={content.fields}
                        images={content.images}
                        data={form.data}
                        setValue={(n, v) => form.setData('values', { ...form.data.values, [n]: v })}
                        setImage={(k, f) => form.setData('image_input', { ...form.data.image_input, [k]: f })}
                        errors={form.errors as Record<string, string | undefined>}
                    />
                    <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing}>
                        {t('Submit')}
                    </button>
                </div>
            </form>
        </div>
    );
}

function ItemDialog({ name, element, item, urls, onClose }: { name: string; element: NonNullable<Props['element']>; item: Item | null; urls: Props['urls']; onClose: () => void }) {
    const t = useTrans();
    const blank = Object.fromEntries(element.fields.map((f) => [f.name, f.type === 'select' ? (f.options?.[0]?.value ?? '') : '']));
    const form = useForm<ContentData>({ values: item ? item.values : blank, image_input: {} });
    const images = element.images.map((img) => ({ ...img, url: item?.images[img.key] ?? img.url }));
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.transform((d) => contentPayload(d, item ? { type: 'element', id: item.id } : { type: 'element' }));
        form.post(urls.save, { preserveScroll: true, forceFormData: true, onSuccess: onClose });
    };
    return (
        <Modal open onClose={onClose} labelledBy="item-dialog-title">
            <DialogHeader id="item-dialog-title" title={item ? t('Update :name Item', { name }) : t('Add New :name Item', { name })} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    <ContentFields
                        idPrefix="item"
                        layout="dialog"
                        fields={element.fields}
                        images={images}
                        data={form.data}
                        setValue={(n, v) => form.setData('values', { ...form.data.values, [n]: v })}
                        setImage={(k, f) => form.setData('image_input', { ...form.data.image_input, [k]: f })}
                        errors={form.errors as Record<string, string | undefined>}
                        slugCheck={urls.slugCheck + (item ? `/${item.id}` : '')}
                        initialSlug={item?.values.slug ?? ''}
                    />
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

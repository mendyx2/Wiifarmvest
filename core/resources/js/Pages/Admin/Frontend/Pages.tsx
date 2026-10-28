import { Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { SlugStatus, toSlug, useSlugCheck } from '@/Components/Admin/ContentFields';
import DataTable from '@/Components/Admin/DataTable';
import { ConfirmButton, DialogHeader, FieldError, withId } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';

type Page = { id: number; name: string; slug: string; default: boolean };

type Props = {
    pages: Page[];
    urls: { store: string; sections: string; seo: string; delete: string; slugCheck: string };
};

/** Manage Pages: the website's pages, with their sections and SEO. */
export default function Pages({ pages, urls }: Props) {
    const t = useTrans();
    const [adding, setAdding] = useState(false);

    return (
        <>
            <PageHeader title={t('Manage Pages')}>
                <button type="button" className="btn btn-sm btn-outline--primary" onClick={() => setAdding(true)}>
                    <i className="las la-plus" /> {t('Add New')}
                </button>
            </PageHeader>
            <DataTable
                rows={pages}
                rowKey={(p) => p.id}
                empty={t('Data not found')}
                columns={[
                    { label: t('Name'), cell: (p) => p.name },
                    { label: t('Slug'), cell: (p) => <code>{p.slug}</code> },
                    {
                        label: t('Action'),
                        cell: (p) => (
                            <div className="button--group">
                                <Link href={withId(urls.seo, p.id)} className="btn btn-sm btn-outline--info">
                                    <i className="la la-cog" /> {t('SEO Setting')}
                                </Link>
                                <Link href={withId(urls.sections, p.id)} className="btn btn-sm btn-outline--primary">
                                    <i className="la la-pen" /> {t('Edit')}
                                </Link>
                                {!p.default && (
                                    <ConfirmButton url={withId(urls.delete, p.id)} question={t('Are you sure to remove this page?')} className="btn btn-sm btn-outline--danger">
                                        <i className="las la-trash" /> {t('Delete')}
                                    </ConfirmButton>
                                )}
                            </div>
                        ),
                    },
                ]}
            />
            {adding && <AddPage url={urls.store} slugCheck={urls.slugCheck} onClose={() => setAdding(false)} />}
        </>
    );
}

/** The name and slug fields with the slug check, shared with the page's own edit form. */
export function usePageNameFields({ form, slugCheck, initialSlug, idPrefix }: { form: ReturnType<typeof useForm<{ name: string; slug: string }>>; slugCheck: string; initialSlug: string; idPrefix: string }) {
    const t = useTrans();
    const state = useSlugCheck(slugCheck, form.data.slug, initialSlug);
    return {
        state,
        name: (
            <div className="form-group">
                <div className="d-flex justify-content-between">
                    <label htmlFor={`${idPrefix}-name`}>{t('Page Name')}</label>
                    <button type="button" className="btn btn-link p-0 small" onClick={() => form.setData('slug', toSlug(form.data.name))}>
                        <i className="las la-link" /> {t('Make Slug')}
                    </button>
                </div>
                <input id={`${idPrefix}-name`} className="form-control" required minLength={3} maxLength={40} value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                <FieldError message={form.errors.name} />
            </div>
        ),
        slug: (
            <div className="form-group">
                <div className="d-flex justify-content-between">
                    <label htmlFor={`${idPrefix}-slug`}>{t('Slug')}</label>
                    <SlugStatus state={state} />
                </div>
                <input id={`${idPrefix}-slug`} className="form-control" required minLength={3} maxLength={40} value={form.data.slug} onChange={(e) => form.setData('slug', toSlug(e.target.value))} />
                <FieldError message={form.errors.slug} />
            </div>
        ),
    };
}

function AddPage({ url, slugCheck, onClose }: { url: string; slugCheck: string; onClose: () => void }) {
    const t = useTrans();
    const form = useForm({ name: '', slug: '' });
    const fields = usePageNameFields({ form, slugCheck, initialSlug: '', idPrefix: 'page' });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true });
    };
    return (
        <Modal open onClose={onClose} labelledBy="page-title">
            <DialogHeader id="page-title" title={t('Add New Page')} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    {fields.name}
                    {fields.slug}
                </div>
                <div className="modal-footer">
                    <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing || fields.state !== 'free'}>
                        {t('Submit')}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

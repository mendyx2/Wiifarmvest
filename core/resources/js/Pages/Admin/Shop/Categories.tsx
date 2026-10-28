import { useForm, usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import DataTable from '@/Components/Admin/DataTable';
import { ConfirmButton, DialogHeader, FieldError, withId } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import SearchForm from '@/Components/Admin/SearchForm';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';
import type { Paginated } from '@/types';

type Category = { id: number; name: string; enabled: boolean; products: number };

type Props = {
    categories: Paginated<Category>;
    search: string;
    urls: { store: string; status: string };
};

/** Shop → Manage Category: the product categories of the shop. */
export default function Categories({ categories, search, urls }: Props) {
    const t = useTrans();
    const url = usePage().url;
    // null: closed; {id: 0}: adding; otherwise the category being edited
    const [editing, setEditing] = useState<Pick<Category, 'id' | 'name'> | null>(null);
    const first = (categories.current_page - 1) * categories.per_page + 1;

    return (
        <>
            <PageHeader title={t('Categories')}>
                <SearchForm url={url} value={search} />
                <button type="button" className="btn btn-outline--primary h-45" onClick={() => setEditing({ id: 0, name: '' })}>
                    <i className="las la-plus" /> {t('Add New')}
                </button>
            </PageHeader>
            <DataTable
                responsive="sm"
                rows={categories.data}
                rowKey={(c) => c.id}
                links={categories.links}
                empty={t('No category found')}
                columns={[
                    { label: t('S.N'), cell: (c) => first + categories.data.indexOf(c) },
                    { label: t('Name'), cell: (c) => c.name },
                    { label: t('Products'), cell: (c) => c.products },
                    { label: t('Status'), cell: (c) => <span className={`badge ${c.enabled ? 'badge--success' : 'badge--warning'}`}>{c.enabled ? t('Enabled') : t('Disabled')}</span> },
                    {
                        label: t('Action'),
                        cell: (c) => (
                            <div className="button--group">
                                <button type="button" className="btn btn-sm btn-outline--primary" onClick={() => setEditing(c)}>
                                    <i className="las la-pen" /> {t('Edit')}
                                </button>
                                {c.enabled ? (
                                    <ConfirmButton url={withId(urls.status, c.id)} data={{ state: 'disable' }} question={t('Are you sure to disable this category?')} className="btn btn-sm btn-outline--danger">
                                        <i className="las la-eye-slash" /> {t('Disable')}
                                    </ConfirmButton>
                                ) : (
                                    <ConfirmButton url={withId(urls.status, c.id)} data={{ state: 'enable' }} question={t('Are you sure to enable this category?')} className="btn btn-sm btn-outline--success">
                                        <i className="las la-eye" /> {t('Enable')}
                                    </ConfirmButton>
                                )}
                            </div>
                        ),
                    },
                ]}
            />
            {editing && <CategoryDialog category={editing} url={editing.id ? `${urls.store}/${editing.id}` : urls.store} onClose={() => setEditing(null)} />}
        </>
    );
}

function CategoryDialog({ category, url, onClose }: { category: Pick<Category, 'id' | 'name'>; url: string; onClose: () => void }) {
    const t = useTrans();
    const form = useForm({ name: category.name });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, onSuccess: onClose });
    };
    return (
        <Modal open onClose={onClose} labelledBy="category-title">
            <DialogHeader id="category-title" title={category.id ? t('Update Category') : t('Add Category')} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    <div className="form-group">
                        <label htmlFor="category-name">{t('Name')}</label>
                        <input id="category-name" className="form-control" required maxLength={255} value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                        <FieldError message={form.errors.name} />
                    </div>
                </div>
                <div className="modal-footer">
                    <button className="btn btn--primary w-100 h-45" type="submit" disabled={form.processing || !form.data.name.trim()}>
                        {t('Submit')}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

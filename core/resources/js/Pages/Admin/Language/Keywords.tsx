import { Link, useForm, usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import DataTable from '@/Components/Admin/DataTable';
import { ConfirmButton, DialogHeader, FieldError } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import SearchForm from '@/Components/Admin/SearchForm';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';
import type { Paginated } from '@/types';

type Key = { key: string; value: string };

type Props = {
    language: { id: number; name: string; code: string };
    keys: Paginated<Key>;
    search: string;
    others: { id: number; name: string }[];
    urls: { store: string; update: string; delete: string; import: string; back: string };
};

/** One language's keywords: search, translate, add, remove, and import missing ones. */
export default function Keywords({ language, keys, search, others, urls }: Props) {
    const t = useTrans();
    const url = usePage().url;
    // 'add' | 'import' | the keyword being edited
    const [dialog, setDialog] = useState<'add' | 'import' | Key | null>(null);

    return (
        <>
            <PageHeader title={t('Update :name Keywords', { name: language.name })}>
                <SearchForm url={url} value={search} placeholder="Search keyword" />
                <button type="button" className="btn btn-outline--primary" onClick={() => setDialog('add')}>
                    <i className="las la-plus" /> {t('Add New Key')}
                </button>
                <button type="button" className="btn btn-outline--info" onClick={() => setDialog('import')}>
                    <i className="la la-download" /> {t('Import Keywords')}
                </button>
                <Link href={urls.back} className="btn btn-outline--dark">
                    <i className="las la-undo" /> {t('Back')}
                </Link>
            </PageHeader>
            <DataTable
                rows={keys.data}
                rowKey={(k) => k.key}
                links={keys.links}
                empty={search ? t('No keyword matches ":search".', { search }) : t('No data found')}
                columns={[
                    { label: t('Key'), cell: (k) => <span className="text-break">{k.key}</span> },
                    { label: language.name, cell: (k) => <span className="text-break">{k.value}</span> },
                    {
                        label: t('Action'),
                        cell: (k) => (
                            <div className="button--group">
                                <button type="button" className="btn btn-sm btn-outline--primary" onClick={() => setDialog(k)}>
                                    <i className="la la-pencil" /> {t('Edit')}
                                </button>
                                <ConfirmButton url={urls.delete} data={{ key: k.key }} question={t('Are you sure to delete this key from this language?')} className="btn btn-sm btn-outline--danger">
                                    <i className="la la-trash" /> {t('Remove')}
                                </ConfirmButton>
                            </div>
                        ),
                    },
                ]}
            />
            {dialog === 'add' && <KeyDialog url={urls.store} onClose={() => setDialog(null)} />}
            {dialog && typeof dialog === 'object' && <KeyDialog url={urls.update} entry={dialog} onClose={() => setDialog(null)} />}
            {dialog === 'import' && <ImportDialog url={urls.import} languageId={language.id} others={others} onClose={() => setDialog(null)} />}
        </>
    );
}

function KeyDialog({ url, entry, onClose }: { url: string; entry?: Key; onClose: () => void }) {
    const t = useTrans();
    const form = useForm({ key: entry?.key ?? '', value: entry?.value ?? '' });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, onSuccess: onClose });
    };
    return (
        <Modal open onClose={onClose} labelledBy="key-title">
            <DialogHeader id="key-title" title={entry ? t('Edit') : t('Add New')} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    <div className="form-group">
                        <label htmlFor="kw-key">{t('Key')}</label>
                        {entry ? (
                            <p id="kw-key" className="form-control-plaintext fw-bold text-break">
                                {entry.key}
                            </p>
                        ) : (
                            <input id="kw-key" className="form-control" required value={form.data.key} onChange={(e) => form.setData('key', e.target.value)} />
                        )}
                        <FieldError message={form.errors.key} />
                    </div>
                    <div className="form-group">
                        <label htmlFor="kw-value">{t('Value')}</label>
                        <textarea id="kw-value" className="form-control" rows={3} required value={form.data.value} onChange={(e) => form.setData('value', e.target.value)} />
                        <FieldError message={form.errors.value} />
                    </div>
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

function ImportDialog({ url, languageId, others, onClose }: { url: string; languageId: number; others: { id: number; name: string }[]; onClose: () => void }) {
    const t = useTrans();
    const form = useForm({ toLangid: String(languageId), id: '' });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, onSuccess: onClose });
    };
    return (
        <Modal open onClose={onClose} labelledBy="import-title">
            <DialogHeader id="import-title" title={t('Import Keywords')} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    <label htmlFor="import-from">{t('Import From')}</label>
                    <select id="import-from" className="form-control" required value={form.data.id} onChange={(e) => form.setData('id', e.target.value)}>
                        <option value="">{t('Select One')}</option>
                        <option value="999">{t('System')}</option>
                        {others.map((o) => (
                            <option key={o.id} value={o.id}>
                                {o.name}
                            </option>
                        ))}
                    </select>
                    <small className="text-muted d-block mt-2">{t('Only keywords this language does not have yet are added; existing translations stay.')}</small>
                </div>
                <div className="modal-footer">
                    <button type="button" className="btn btn--dark" onClick={onClose}>
                        {t('Close')}
                    </button>
                    <button type="submit" className="btn btn--primary" disabled={form.processing || !form.data.id}>
                        {t('Import Now')}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

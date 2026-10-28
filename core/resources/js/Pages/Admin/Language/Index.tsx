import { Link, useForm } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';
import DataTable from '@/Components/Admin/DataTable';
import { ConfirmButton, DialogHeader, FieldError, withId } from '@/Components/Admin/Form';
import ImageUploader from '@/Components/Admin/ImageUploader';
import PageHeader from '@/Components/Admin/PageHeader';
import Modal from '@/Components/Modal';
import { useCopy } from '@/lib/clipboard';
import { useTrans } from '@/lib/i18n';

type Lang = { id: number; name: string; code: string; default: boolean; image: string; removable: boolean };

type Props = {
    languages: Lang[];
    size: string;
    urls: { store: string; update: string; delete: string; translate: string; keys: string };
};

/** Language Manager: the languages members can choose, the default one, and their flags. */
export default function Languages({ languages, size, urls }: Props) {
    const t = useTrans();
    // null: closed; 0: adding; otherwise the language being edited
    const [editing, setEditing] = useState<Lang | 0 | null>(null);
    const [showKeys, setShowKeys] = useState(false);

    return (
        <>
            <PageHeader title={t('Language Manager')}>
                <button type="button" className="btn btn-outline--primary" onClick={() => setEditing(0)}>
                    <i className="las la-plus" /> {t('Add New')}
                </button>
                <button type="button" className="btn btn-outline--info" onClick={() => setShowKeys(true)}>
                    <i className="las la-code" /> {t('Language Keywords')}
                </button>
            </PageHeader>
            <div className="card bl--5 border--primary mb-4">
                <div className="card-body">
                    <p className="text--primary mb-0">{t('While you are adding a new keyword, it will only add to this current language only. Please be careful on entering a keyword, please make sure there is no extra space. It needs to be exact and case-sensitive.')}</p>
                </div>
            </div>
            <DataTable
                rows={languages}
                rowKey={(l) => l.id}
                empty={t('No data found')}
                columns={[
                    {
                        label: t('Name'),
                        cell: (l) => (
                            <span className="d-inline-flex align-items-center gap-2">
                                <img src={l.image} alt="" width={28} height={20} style={{ objectFit: 'cover', borderRadius: 3 }} />
                                {l.name}
                            </span>
                        ),
                    },
                    { label: t('Code'), cell: (l) => <code>{l.code}</code> },
                    { label: t('Default'), cell: (l) => (l.default ? <span className="badge badge--success">{t('Default')}</span> : <span className="badge badge--warning">{t('Selectable')}</span>) },
                    {
                        label: t('Actions'),
                        cell: (l) => (
                            <div className="button--group">
                                <Link href={withId(urls.translate, l.id)} className="btn btn-sm btn-outline--success">
                                    <i className="la la-language" /> {t('Translate')}
                                </Link>
                                <button type="button" className="btn btn-sm btn-outline--primary" onClick={() => setEditing(l)}>
                                    <i className="la la-pen" /> {t('Edit')}
                                </button>
                                {l.removable && (
                                    <ConfirmButton url={withId(urls.delete, l.id)} question={t('Are you sure to remove this language from this system?')} className="btn btn-sm btn-outline--danger">
                                        <i className="la la-trash" /> {t('Remove')}
                                    </ConfirmButton>
                                )}
                            </div>
                        ),
                    },
                ]}
            />
            {editing !== null && <LanguageDialog language={editing || null} url={editing ? withId(urls.update, editing.id) : urls.store} size={size} onClose={() => setEditing(null)} />}
            {showKeys && <KeywordsDialog url={urls.keys} onClose={() => setShowKeys(false)} />}
        </>
    );
}

function LanguageDialog({ language, url, size, onClose }: { language: Lang | null; url: string; size: string; onClose: () => void }) {
    const t = useTrans();
    const form = useForm<{ name: string; code: string; is_default: boolean; image: File | null }>({ name: language?.name ?? '', code: '', is_default: language?.default ?? false, image: null });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, forceFormData: true, onSuccess: onClose });
    };
    return (
        <Modal open onClose={onClose} labelledBy="lang-title">
            <DialogHeader id="lang-title" title={language ? t('Edit Language') : t('Add New Language')} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    <div className="form-group">
                        <label>{t('Flag')}</label>
                        <ImageUploader image={language?.image ?? ''} size={size} required={!language} onChange={(f) => form.setData('image', f)} />
                        <FieldError message={form.errors.image} />
                    </div>
                    <div className="form-group">
                        <label htmlFor="lang-name">{t('Language Name')}</label>
                        <input id="lang-name" className="form-control" required maxLength={40} value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                        <FieldError message={form.errors.name} />
                    </div>
                    {!language && (
                        <div className="form-group">
                            <label htmlFor="lang-code">{t('Language Code')}</label>
                            <input id="lang-code" className="form-control" required maxLength={12} pattern="[A-Za-z]{2,3}([_\-][A-Za-z0-9]{2,8})?" placeholder="fr, pt-br" value={form.data.code} onChange={(e) => form.setData('code', e.target.value)} />
                            <small className="text-muted">{t('Two or three letters, optionally a region: fr, pt-br, zh_cn.')}</small>
                            <FieldError message={form.errors.code} />
                        </div>
                    )}
                    <div className="form-check form-switch">
                        <input id="lang-default" type="checkbox" role="switch" className="form-check-input" checked={form.data.is_default} onChange={(e) => form.setData('is_default', e.target.checked)} />
                        <label className="form-check-label" htmlFor="lang-default">
                            {t('Default Language')}
                        </label>
                    </div>
                </div>
                <div className="modal-footer">
                    <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing || (!language && !form.data.image)}>
                        {t('Submit')}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

function KeywordsDialog({ url, onClose }: { url: string; onClose: () => void }) {
    const t = useTrans();
    const [text, setText] = useState<string | null>(null);
    const [failed, setFailed] = useState(false);
    const { copied, copy } = useCopy(2000);
    useEffect(() => {
        let live = true;
        fetch(url, { headers: { Accept: 'text/plain' }, credentials: 'same-origin' })
            .then((r) => (r.ok ? r.text() : Promise.reject(new Error(String(r.status)))))
            .then((body) => live && setText(body))
            .catch(() => live && setFailed(true));
        return () => {
            live = false;
        };
    }, [url]);
    return (
        <Modal open onClose={onClose} labelledBy="keys-title" size="lg">
            <DialogHeader id="keys-title" title={t('Language Keywords')} onClose={onClose} />
            <div className="modal-body">
                <p className="mb-3">{t('All of the possible language keywords are available here. However, some keywords may be missing due to variations in the database. If you encounter any missing keywords, you can add them manually.')}</p>
                <p className="text--primary mb-3">{t('You can import these keywords from the translate page of any language as well.')}</p>
                {failed ? (
                    <p className="text--danger">{t('The keywords could not be loaded.')}</p>
                ) : (
                    <textarea className="form-control" rows={18} readOnly aria-label={t('Language Keywords')} value={text ?? t('Loading…')} />
                )}
                <button type="button" className="btn btn--primary w-100 h-45 mt-3" disabled={!text} onClick={() => text && copy(text)}>
                    <i className={copied ? 'las la-check' : 'las la-copy'} /> {copied ? t('Copied') : t('Copy')}
                </button>
            </div>
        </Modal>
    );
}

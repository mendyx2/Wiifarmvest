import { useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import DataTable from '@/Components/Admin/DataTable';
import { ConfirmButton, FieldError, withId } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';
import type { Paginated } from '@/types';

type Card = { id: number; title: string; description: string; type: 'image' | 'video'; url: string; active: boolean; seen: number; date: string };
type Kind = 'image' | 'video';

type Props = {
    flashcards: Paginated<Card>;
    types: Record<Kind, string[]>;
    urls: { store: string; toggle: string; destroy: string };
};

/** Flashcards: pictures and videos each member sees once on their dashboard. */
export default function Flashcards({ flashcards, types, urls }: Props) {
    const t = useTrans();
    const form = useForm<{ title: string; description: string; type: Kind; file: File | null }>({ title: '', description: '', type: 'image', file: null });
    // A new key empties the file input after an upload
    const [round, setRound] = useState(0);
    const accept = types[form.data.type].map((e) => '.' + e).join(',');
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(urls.store, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setRound((r) => r + 1);
            },
        });
    };

    return (
        <>
            <PageHeader title={t('Flashcards')} />
            <div className="row gy-4">
                <div className="col-xl-4">
                    <div className="card">
                        <div className="card-header">
                            <h5 className="card-title mb-0">{t('Upload Flashcard')}</h5>
                        </div>
                        <form onSubmit={submit} className="card-body">
                            <div className="form-group">
                                <label htmlFor="fc-title">
                                    {t('Title')} <small className="text-muted">({t('optional')})</small>
                                </label>
                                <input id="fc-title" className="form-control" maxLength={255} placeholder={t('e.g. Welcome Video')} value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} />
                                <FieldError message={form.errors.title} />
                            </div>
                            <div className="form-group">
                                <label htmlFor="fc-description">
                                    {t('Description')} <small className="text-muted">({t('optional')})</small>
                                </label>
                                <textarea id="fc-description" className="form-control" rows={3} maxLength={1000} value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} />
                                <FieldError message={form.errors.description} />
                            </div>
                            <div className="form-group">
                                <label htmlFor="fc-type">{t('Type')}</label>
                                <select id="fc-type" className="form-select" value={form.data.type} onChange={(e) => form.setData({ ...form.data, type: e.target.value as Kind, file: null })}>
                                    <option value="image">{t('Image')}</option>
                                    <option value="video">{t('Video')}</option>
                                </select>
                            </div>
                            <div className="form-group">
                                <label htmlFor="fc-file">{t('File')}</label>
                                <input key={`${form.data.type}-${round}`} id="fc-file" type="file" className="form-control" required accept={accept} onChange={(e) => form.setData('file', e.target.files?.[0] ?? null)} />
                                <small className="text-muted">
                                    {types[form.data.type].join(', ')} · {t('max 50 MB')}
                                </small>
                                <FieldError message={form.errors.file} />
                            </div>
                            <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing || !form.data.file}>
                                {form.processing ? t('Uploading') + '...' : t('Upload')}
                            </button>
                        </form>
                    </div>
                </div>
                <div className="col-xl-8">
                    <DataTable
                        rows={flashcards.data}
                        rowKey={(f) => f.id}
                        links={flashcards.links}
                        empty={t('No flashcards yet')}
                        responsive="md"
                        columns={[
                            {
                                label: t('Preview'),
                                cell: (f) =>
                                    f.type === 'image' ? (
                                        <img src={f.url} alt="" style={{ width: 80, height: 50, objectFit: 'cover', borderRadius: 4 }} />
                                    ) : (
                                        <video src={f.url} style={{ width: 80, height: 50, borderRadius: 4, background: '#000' }} muted preload="metadata" aria-label={t('Video')} />
                                    ),
                            },
                            {
                                label: t('Title / Description'),
                                cell: (f) => (
                                    <div style={{ maxWidth: 260 }} className="ms-auto ms-md-0">
                                        <strong className="d-block">{f.title || '—'}</strong>
                                        {f.description && <small className="text-muted d-block text-truncate">{f.description}</small>}
                                    </div>
                                ),
                            },
                            {
                                label: t('Status'),
                                cell: (f) => (
                                    <>
                                        {f.active ? <span className="badge badge--success">{t('Active')}</span> : <span className="badge badge--dark">{t('Hidden')}</span>}
                                        <small className="text-muted d-block">{t('Seen by :n', { n: f.seen })}</small>
                                    </>
                                ),
                            },
                            { label: t('Uploaded'), cell: (f) => f.date },
                            {
                                label: t('Action'),
                                cell: (f) => (
                                    <div className="d-flex flex-column align-items-end align-items-md-stretch gap-1">
                                        <ConfirmButton
                                            url={withId(urls.toggle, f.id)}
                                            data={{ state: f.active ? 'disable' : 'enable', _method: 'patch' }}
                                            question={f.active ? t('Hide this flashcard from members?') : t('Show this flashcard to members?')}
                                            className={`btn btn-sm ${f.active ? 'btn-outline--warning' : 'btn-outline--success'}`}
                                        >
                                            <i className={`las ${f.active ? 'la-eye-slash' : 'la-eye'}`} /> {f.active ? t('Hide') : t('Show')}
                                        </ConfirmButton>
                                        <ConfirmButton url={withId(urls.destroy, f.id)} method="delete" question={t('Delete this flashcard?')} className="btn btn-sm btn-outline--danger">
                                            <i className="la la-trash" /> {t('Delete')}
                                        </ConfirmButton>
                                    </div>
                                ),
                            },
                        ]}
                    />
                </div>
            </div>
        </>
    );
}

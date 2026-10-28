import { Link, router, useForm } from '@inertiajs/react';
import { useState, type DragEvent, type FormEvent } from 'react';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';
import { usePageNameFields } from './Pages';

type Section = { key: string; name: string; url: string | null };

type Props = {
    page: { id: number; name: string; slug: string; default: boolean; secs: string[] };
    sections: Section[];
    urls: { save: string; update: string; seo: string; back: string; slugCheck: string };
};

/**
 * Manage Pages → a page: its name and slug, and the sections it shows in
 * order. Sections are added with their button or by dragging them onto the
 * page, and ordered with the arrows or by dragging (jQuery UI before).
 */
export default function PageSections({ page, sections, urls }: Props) {
    const t = useTrans();
    const [secs, setSecs] = useState<string[]>(page.secs);
    const [saving, setSaving] = useState(false);
    // What is being dragged: a section from the list, or a row of the page
    const [drag, setDrag] = useState<{ key: string; from: number | null } | null>(null);
    const [over, setOver] = useState<number | null>(null);
    const byKey = Object.fromEntries(sections.map((s) => [s.key, s]));
    const changed = JSON.stringify(secs) !== JSON.stringify(page.secs);

    const move = (from: number, to: number) => {
        if (to < 0 || to >= secs.length) return;
        const next = [...secs];
        const [item] = next.splice(from, 1);
        next.splice(to, 0, item);
        setSecs(next);
    };
    const insert = (key: string, at: number) => setSecs((s) => (s.includes(key) ? s : [...s.slice(0, at), key, ...s.slice(at)]));

    const drop = (e: DragEvent, at: number) => {
        e.preventDefault();
        if (drag) {
            if (drag.from === null) insert(drag.key, at);
            else move(drag.from, at > drag.from ? at - 1 : at);
        }
        setDrag(null);
        setOver(null);
    };
    const dragOver = (e: DragEvent, at: number) => {
        if (!drag) return;
        e.preventDefault();
        setOver(at);
    };

    const save = () => {
        setSaving(true);
        router.post(urls.save, { secs }, { preserveScroll: true, onFinish: () => setSaving(false) });
    };

    return (
        <>
            <PageHeader title={t('Manage Section of :name', { name: page.name })}>
                <Link href={urls.seo} className="btn btn-sm btn-outline--info">
                    <i className="la la-cog" /> {t('SEO Setting')}
                </Link>
                <Link href={urls.back} className="btn btn-sm btn-outline--primary">
                    <i className="la la-undo" /> {t('Back')}
                </Link>
            </PageHeader>

            {!page.default && <PageForm page={page} urls={urls} />}

            <div className="row g-3">
                <div className="col-lg-7 col-xl-8">
                    <div className="card h-100">
                        <div className="card-header">
                            <h5 className="card-title mb-0">{t(':name Page', { name: page.name })}</h5>
                        </div>
                        <div className="card-body">
                            {changed && (
                                <div className="alert alert-warning py-2" role="status">
                                    <i className="fas fa-exclamation-triangle" /> {t("You've to click on the Update Now button to apply the changes")}
                                </div>
                            )}
                            <ol className="list-unstyled mb-3" aria-label={t('Sections on this page')} onDragOver={(e) => dragOver(e, secs.length)} onDrop={(e) => drop(e, secs.length)}>
                                {secs.map((key, i) => (
                                    <li
                                        key={key}
                                        draggable
                                        onDragStart={() => setDrag({ key, from: i })}
                                        onDragEnd={() => (setDrag(null), setOver(null))}
                                        onDragOver={(e) => (e.stopPropagation(), dragOver(e, i))}
                                        onDrop={(e) => (e.stopPropagation(), drop(e, i))}
                                        className="d-flex align-items-center gap-2 border rounded p-2 mb-2 bg-white"
                                        style={{ cursor: 'move', borderTop: over === i ? '3px solid var(--bs-primary, #4634ff)' : undefined, opacity: drag?.from === i ? 0.5 : 1 }}
                                    >
                                        <i className="las la-grip-vertical text-muted" aria-hidden />
                                        <span className="me-auto">{byKey[key]?.name ?? key}</span>
                                        <button type="button" className="btn btn-sm btn-outline--dark" aria-label={t('Move up')} disabled={i === 0} onClick={() => move(i, i - 1)}>
                                            <i className="las la-arrow-up me-0" />
                                        </button>
                                        <button type="button" className="btn btn-sm btn-outline--dark" aria-label={t('Move down')} disabled={i === secs.length - 1} onClick={() => move(i, i + 1)}>
                                            <i className="las la-arrow-down me-0" />
                                        </button>
                                        <button type="button" className="btn btn-sm btn-outline--danger" aria-label={t('Remove :name', { name: byKey[key]?.name ?? key })} onClick={() => setSecs(secs.filter((k) => k !== key))}>
                                            <i className="la la-trash me-0" />
                                        </button>
                                    </li>
                                ))}
                                <li className={`border rounded p-4 text-center text-muted ${over === secs.length ? 'border-primary' : ''}`} style={{ borderStyle: 'dashed' }}>
                                    {t('Drag & drop your section here')}
                                </li>
                            </ol>
                            <button type="button" className="btn btn--primary w-100 h-45" disabled={saving} onClick={save}>
                                {t('Update Now')}
                            </button>
                        </div>
                    </div>
                </div>
                <div className="col-lg-5 col-xl-4">
                    <div className="card h-100">
                        <div className="card-header">
                            <h5 className="card-title mb-1">{t('Sections')}</h5>
                            <small className="text--primary">{t('Drag a section to the page, or use its add button.')}</small>
                        </div>
                        <div className="card-body">
                            <ul className="list-unstyled mb-0">
                                {sections.map((s) => {
                                    const added = secs.includes(s.key);
                                    return (
                                        <li
                                            key={s.key}
                                            draggable={!added}
                                            onDragStart={() => setDrag({ key: s.key, from: null })}
                                            onDragEnd={() => (setDrag(null), setOver(null))}
                                            className="d-flex align-items-center gap-2 border rounded p-2 mb-2 bg-white"
                                            style={{ cursor: added ? 'default' : 'move', opacity: added ? 0.55 : 1 }}
                                        >
                                            <i className="las la-grip-vertical text-muted" aria-hidden />
                                            <span className="me-auto">{s.name}</span>
                                            {s.url && (
                                                <a href={s.url} target="_blank" rel="noreferrer" className="btn btn-sm btn-outline--dark" aria-label={t('Edit :name content', { name: s.name })} title={t('Edit content')}>
                                                    <i className="la la-cog me-0" />
                                                </a>
                                            )}
                                            <button type="button" className="btn btn-sm btn-outline--primary" disabled={added} aria-label={t('Add :name', { name: s.name })} onClick={() => insert(s.key, secs.length)}>
                                                <i className={`las ${added ? 'la-check' : 'la-plus'} me-0`} />
                                            </button>
                                        </li>
                                    );
                                })}
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

function PageForm({ page, urls }: { page: Props['page']; urls: Props['urls'] }) {
    const t = useTrans();
    const form = useForm({ name: page.name, slug: page.slug });
    const fields = usePageNameFields({ form, slugCheck: urls.slugCheck, initialSlug: page.slug, idPrefix: 'edit-page' });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(urls.update, { preserveScroll: true });
    };
    return (
        <div className="card mb-4">
            <form onSubmit={submit}>
                <div className="card-body">
                    <div className="row align-items-end">
                        <div className="col-md-4">{fields.name}</div>
                        <div className="col-md-4">{fields.slug}</div>
                        <div className="col-md-4">
                            <div className="form-group">
                                <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing || fields.state === 'taken' || fields.state === 'checking'}>
                                    {t('Submit')}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    );
}

import { useEffect, useState } from 'react';
import { FieldError } from '@/Components/Admin/Form';
import IconPicker from '@/Components/Admin/IconPicker';
import ImageUploader from '@/Components/Admin/ImageUploader';
import RichTextEditor from '@/Components/Admin/RichTextEditor';
import { useTrans } from '@/lib/i18n';

/** A field of a frontend section, as FrontendController::fields() describes it. */
export type ContentField = {
    name: string;
    type: 'text' | 'textarea' | 'html' | 'icon' | 'select' | 'slug';
    label: string;
    options?: { value: string; label: string }[];
    /** slug: the field it is made from */
    from?: string;
};

export type ContentImage = { key: string; label: string; size: string; url: string };

export type ContentData = { values: Record<string, string>; image_input: Record<string, File | null> };

/** The form data as the server reads it: the fields at the top, the pictures under image_input. */
export function contentPayload(d: ContentData, extra: Record<string, string | number>) {
    const images = Object.fromEntries(Object.entries(d.image_input).filter(([, f]) => f));
    return { ...d.values, ...extra, image_input: images };
}

/** "My First Post!" → "my-first-post" (as the old pages did; the server slugs again). */
export const toSlug = (s: string) =>
    s
        .toLowerCase()
        .replace(/\s+/g, '-')
        .replace(/[^\w-]+/g, '')
        .replace(/-{2,}/g, '-');

type SlugState = 'idle' | 'checking' | 'free' | 'taken';

/** Ask the server whether a slug is free; null while there is nothing to check. */
export function useSlugCheck(url: string, slug: string, initial: string): SlugState {
    const [state, setState] = useState<SlugState>('idle');
    useEffect(() => {
        if (!slug || slug === initial) {
            setState('idle');
            return;
        }
        setState('checking');
        const ctrl = new AbortController();
        const timer = setTimeout(() => {
            fetch(`${url}?slug=${encodeURIComponent(slug)}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: ctrl.signal })
                .then((r) => (r.ok ? r.json() : Promise.reject(new Error(String(r.status)))))
                .then((r: { exists: boolean }) => setState(r.exists ? 'taken' : 'free'))
                .catch(() => !ctrl.signal.aborted && setState('idle'));
        }, 300);
        return () => {
            clearTimeout(timer);
            ctrl.abort();
        };
    }, [url, slug, initial]);
    return state;
}

export function SlugStatus({ state }: { state: SlugState }) {
    const t = useTrans();
    if (state === 'checking')
        return (
            <small className="text--info">
                <i className="las la-spinner la-spin" /> {t('Checking')}
            </small>
        );
    if (state === 'free')
        return (
            <small className="text--success">
                <i className="las la-check" /> {t('Available')}
            </small>
        );
    if (state === 'taken')
        return (
            <small className="text--danger">
                <i className="las la-times" /> {t('Slug already exists')}
            </small>
        );
    return null;
}

type Props = {
    idPrefix: string;
    fields: ContentField[];
    images: ContentImage[];
    data: ContentData;
    setValue: (name: string, value: string) => void;
    setImage: (key: string, file: File | null) => void;
    errors: Record<string, string | undefined>;
    /** For the slug field */
    slugCheck?: string;
    initialSlug?: string;
    onSlugState?: (state: SlugState) => void;
    /** Images beside the text (a page) or above it (a dialog) */
    layout?: 'page' | 'dialog';
};

/**
 * The fields of a section's content or of one of its items, drawn from
 * sections.json: text, long text, rich text, icon, a choice, a slug, and
 * pictures.
 */
export default function ContentFields({ idPrefix, fields, images, data, setValue, setImage, errors, slugCheck, initialSlug = '', onSlugState, layout = 'page' }: Props) {
    const t = useTrans();
    const slugField = fields.find((f) => f.type === 'slug');
    const slugState = useSlugCheck(slugCheck ?? '', slugField && slugCheck ? (data.values.slug ?? '') : '', initialSlug);
    useEffect(() => onSlugState?.(slugState), [slugState, onSlugState]);

    const input = (f: ContentField) => {
        const id = `${idPrefix}-${f.name}`;
        const value = data.values[f.name] ?? '';
        const error = <FieldError message={errors[f.name]} />;
        switch (f.type) {
            case 'textarea':
                return (
                    <div className="form-group" key={f.name}>
                        <label htmlFor={id}>{f.label}</label>
                        <textarea id={id} rows={layout === 'page' ? 8 : 4} className="form-control" required value={value} onChange={(e) => setValue(f.name, e.target.value)} />
                        {error}
                    </div>
                );
            case 'html':
                return (
                    <div className="form-group" key={f.name}>
                        <label htmlFor={id}>{f.label}</label>
                        <RichTextEditor id={id} label={f.label} value={value} onChange={(html) => setValue(f.name, html)} minHeight={layout === 'page' ? 320 : 180} />
                        {error}
                    </div>
                );
            case 'icon':
                return (
                    <div className="form-group" key={f.name}>
                        <label htmlFor={id}>{f.label}</label>
                        <IconPicker id={id} required value={value} onChange={(v) => setValue(f.name, v)} />
                        {error}
                    </div>
                );
            case 'select':
                return (
                    <div className="form-group" key={f.name}>
                        <label htmlFor={id}>{f.label}</label>
                        <select id={id} className="form-control form-select" required value={value} onChange={(e) => setValue(f.name, e.target.value)}>
                            {(f.options ?? []).map((o) => (
                                <option key={o.value} value={o.value}>
                                    {o.label}
                                </option>
                            ))}
                        </select>
                        {error}
                    </div>
                );
            case 'slug':
                return (
                    <div className="form-group" key={f.name}>
                        <div className="d-flex justify-content-between">
                            <label htmlFor={id}>{f.label}</label>
                            <SlugStatus state={slugState} />
                        </div>
                        <input id={id} type="text" className="form-control" required maxLength={255} value={value} onChange={(e) => setValue('slug', toSlug(e.target.value))} />
                        {error}
                    </div>
                );
            default:
                return (
                    <div className="form-group" key={f.name}>
                        <div className="d-flex justify-content-between">
                            <label htmlFor={id}>{f.label}</label>
                            {slugField?.from === f.name && (
                                <button type="button" className="btn btn-link p-0 small" onClick={() => setValue('slug', toSlug(value))}>
                                    <i className="las la-link" /> {t('Make Slug')}
                                </button>
                            )}
                        </div>
                        <input id={id} type="text" className="form-control" required value={value} onChange={(e) => setValue(f.name, e.target.value)} />
                        {error}
                    </div>
                );
        }
    };

    const pictures = images.map((img) => (
        <div className={layout === 'page' ? (images.length > 1 ? 'col-md-4' : 'col-12') : 'col-12'} key={img.key}>
            <div className="form-group">
                <label>{img.label}</label>
                <ImageUploader image={img.url} size={img.size} onChange={(f) => setImage(img.key, f)} className="w-100" />
                <FieldError message={errors[`image_input.${img.key}`]} />
            </div>
        </div>
    ));

    if (layout === 'dialog' || images.length !== 1) {
        return (
            <>
                {images.length > 0 && <div className="row">{pictures}</div>}
                {fields.map(input)}
            </>
        );
    }
    // One picture: beside the text, like the Blade pages
    return (
        <div className="row">
            <div className="col-md-4">{pictures}</div>
            <div className="col-md-8">{fields.map(input)}</div>
        </div>
    );
}

import { useEffect, useId, useRef, useState } from 'react';
import { useTrans } from '@/lib/i18n';

type Props = {
    id?: string;
    /** The icon's class: "las la-home" */
    value: string;
    onChange: (value: string) => void;
    required?: boolean;
};

const SHOWN = 120;

/**
 * An icon field: the class can be typed, or picked from the Line Awesome
 * icons the site loads (the list is fetched the first time it is opened).
 * Replaces the jQuery fontawesome-iconpicker.
 */
export default function IconPicker({ id, value, onChange, required = false }: Props) {
    const t = useTrans();
    const listId = useId();
    const [open, setOpen] = useState(false);
    const [icons, setIcons] = useState<string[] | null>(null);
    const [query, setQuery] = useState('');
    const box = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!open || icons) return;
        let live = true;
        import('@/data/lineIcons.json').then((m) => live && setIcons(m.default as string[]));
        return () => {
            live = false;
        };
    }, [open, icons]);

    // Close when clicking outside
    useEffect(() => {
        if (!open) return;
        const close = (e: MouseEvent) => box.current && !box.current.contains(e.target as Node) && setOpen(false);
        document.addEventListener('mousedown', close);
        return () => document.removeEventListener('mousedown', close);
    }, [open]);

    const q = query.trim().toLowerCase();
    const matches = (icons ?? []).filter((i) => !q || i.includes(q));

    return (
        <div className="position-relative" ref={box}>
            <div className="input-group">
                <span className="input-group-text" style={{ minWidth: 46, justifyContent: 'center' }} aria-hidden>
                    <i className={value || 'las la-icons'} style={{ fontSize: 20 }} />
                </span>
                <input id={id} type="text" className="form-control" autoComplete="off" required={required} maxLength={100} pattern="[A-Za-z0-9][A-Za-z0-9 _\-]*" placeholder="las la-home" value={value} onChange={(e) => onChange(e.target.value)} />
                <button type="button" className="btn btn--primary" aria-expanded={open} aria-controls={listId} onClick={() => setOpen((o) => !o)}>
                    {t('Choose')}
                </button>
            </div>
            {open && (
                <div id={listId} className="card position-absolute w-100 shadow" style={{ zIndex: 20, top: '100%', marginTop: 4 }}>
                    <div className="card-body p-2">
                        <input type="search" className="form-control form-control-sm mb-2" placeholder={t('Search icons')} aria-label={t('Search icons')} value={query} onChange={(e) => setQuery(e.target.value)} autoFocus />
                        {!icons ? (
                            <p className="text-muted text-center my-3">{t('Loading')}...</p>
                        ) : (
                            <>
                                <div className="d-flex flex-wrap gap-1" style={{ maxHeight: 220, overflowY: 'auto' }}>
                                    {matches.slice(0, SHOWN).map((icon) => (
                                        <button
                                            key={icon}
                                            type="button"
                                            title={icon}
                                            aria-label={icon}
                                            className={`btn btn-sm ${icon === value ? 'btn--primary' : 'btn-outline--dark'}`}
                                            style={{ width: 40, height: 40, padding: 0 }}
                                            onClick={() => {
                                                onChange(icon);
                                                setOpen(false);
                                            }}
                                        >
                                            <i className={icon} style={{ fontSize: 20 }} />
                                        </button>
                                    ))}
                                </div>
                                <small className="text-muted d-block mt-2">
                                    {matches.length > SHOWN ? t('Showing :shown of :total icons: search to narrow down.', { shown: SHOWN, total: matches.length }) : t(':total icons', { total: matches.length })}
                                </small>
                            </>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}

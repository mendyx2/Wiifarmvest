import { useTrans } from '@/lib/i18n';

export type Opens = { pattern: string; access: 'all' | 'view' | 'change' };

/** "admin.users.*" → "users.*" */
const short = (pattern: string) => pattern.replace(/^admin\./, '');

/**
 * What a permission opens in the admin panel, from config/admin_access.php:
 * the route names it unlocks, and whether for viewing, changing or both.
 * Many seeded permissions open nothing; that is said plainly.
 */
export default function PermissionOpens({ opens }: { opens: Opens[] }) {
    const t = useTrans();
    if (!opens.length) return <small className="text-muted fst-italic">{t('opens nothing')}</small>;
    return (
        <small className="d-inline-flex flex-wrap gap-1">
            {opens.map((o) => (
                <code key={o.pattern + o.access} className="px-1 rounded" style={{ background: 'rgba(0,0,0,.05)' }}>
                    {short(o.pattern)}
                    {o.access !== 'all' && <span className="text-muted"> ({t(o.access)})</span>}
                </code>
            ))}
        </small>
    );
}

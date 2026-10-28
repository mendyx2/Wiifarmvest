import { useTrans } from '@/lib/i18n';

export type FormAnswer = { name: string; type: string; value: string | null; file: { view: string; download: string; image: boolean } | null };

/** What a member filled in on an admin-built form (manual deposit, withdraw method): one row per field, files as links. */
export default function FormAnswers({ fields }: { fields: FormAnswer[] }) {
    const t = useTrans();
    if (!fields.length) return null;
    return (
        <ul className="list-group">
            {fields.map((f, i) => (
                <li key={i} className="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span className="text-muted">{f.name}</span>
                    {f.type === 'file' ? (
                        f.file ? (
                            <span className="d-flex gap-2">
                                <a href={f.file.view} target="_blank" rel="noopener noreferrer" className="btn btn-sm btn-outline--primary">
                                    <i className="las la-eye" /> {t('View')}
                                </a>
                                <a href={f.file.download} className="btn btn-sm btn-outline--dark">
                                    <i className="las la-download" /> {t('Download')}
                                </a>
                            </span>
                        ) : (
                            <span className="text-muted">{t('No File')}</span>
                        )
                    ) : (
                        <span className="fw-bold text-break">{f.value || '—'}</span>
                    )}
                </li>
            ))}
        </ul>
    );
}

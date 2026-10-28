import { ConfirmButton } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';

type Row = { label: string; value: string };
type Update = { id: number; version: string; date: string | null; changes: string[] };

type Props = {
    app: Row[];
    server: Row[];
    version: { current: string; available: string };
    updates: Update[];
    urls: { clear: string };
};

/** System Information: versions, server details, clear cache, and the update log. */
export default function SystemInfo({ app, server, version, updates, urls }: Props) {
    const t = useTrans();
    const list = (title: string, icon: string, rows: Row[]) => (
        <div className="card h-100">
            <div className="card-header">
                <h5 className="card-title m-0">
                    <i className={icon} /> {title}
                </h5>
            </div>
            <ul className="list-group list-group-flush">
                {rows.map((r) => (
                    <li key={r.label} className="list-group-item d-flex justify-content-between flex-wrap gap-2">
                        <span className="text-muted">{t(r.label)}</span>
                        <strong className="text-break">{r.value || '—'}</strong>
                    </li>
                ))}
            </ul>
        </div>
    );

    return (
        <>
            <PageHeader title={t('System Information')} />
            <div className="row g-4 mb-4">
                <div className="col-lg-6">{list(t('Application'), 'las la-cube', app)}</div>
                <div className="col-lg-6">{list(t('Server'), 'las la-server', server)}</div>
            </div>
            <div className="row g-4">
                <div className="col-lg-6">
                    <div className="card h-100">
                        <div className="card-header">
                            <h5 className="card-title m-0">
                                <i className="las la-broom" /> {t('Clear System Cache')}
                            </h5>
                        </div>
                        <div className="card-body">
                            <ul className="mb-3">
                                {['Compiled views will be cleared', 'Application cache will be cleared', 'Route cache will be cleared', 'Configuration cache will be cleared', 'Compiled services and packages files will be removed'].map((x) => (
                                    <li key={x}>{t(x)}</li>
                                ))}
                            </ul>
                            <ConfirmButton url={urls.clear} question={t('Clear all caches now?')} className="btn btn--primary w-100 h-45">
                                <i className="las la-broom" /> {t('Click to clear')}
                            </ConfirmButton>
                        </div>
                    </div>
                </div>
                <div className="col-lg-6">
                    <div className="card h-100">
                        <div className="card-header">
                            <h5 className="card-title m-0">
                                <i className="las la-code-branch" /> {t('Version & updates')}
                            </h5>
                        </div>
                        <div className="card-body">
                            <p className="mb-1">
                                {t('Your Version')}: <strong>{version.current}</strong>
                                {version.available && version.available !== version.current && (
                                    <>
                                        {' '}
                                        · {t('Vendor release')}: <strong>{version.available}</strong>
                                    </>
                                )}
                            </p>
                            <p className="text--warning small">
                                <i className="las la-exclamation-triangle" /> {t('Automatic vendor updates are switched off: this installation is customised and an update would overwrite it. Updates are deployed from the code repository.')}
                            </p>
                            <h6 className="mt-3">{t('Update Log')}</h6>
                            {updates.length === 0 ? (
                                <p className="text-muted mb-0">{t('No update log found yet!')}</p>
                            ) : (
                                updates.map((u) => (
                                    <div key={u.id} className="mb-3">
                                        <strong>
                                            {t('Version')} {u.version}
                                        </strong>{' '}
                                        <small className="text-muted">{u.date}</small>
                                        <ul className="mb-0">
                                            {u.changes.map((c, i) => (
                                                <li key={i}>{c}</li>
                                            ))}
                                        </ul>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

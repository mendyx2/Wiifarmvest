import { Link, useForm } from '@inertiajs/react';
import { useTrans } from '@/lib/i18n';

export type NotificationTabs = { global: string; email: string; sms: string; push: string; templates: string };
type Tab = keyof NotificationTabs;

/** The tab bar on every Notification Setting page (admin/notification/top_bar.blade.php). */
export function NotificationNav({ tabs, active }: { tabs: NotificationTabs; active: Tab }) {
    const t = useTrans();
    const items: [Tab, string, string][] = [
        ['global', 'las la-globe', t('Global Template')],
        ['email', 'las la-envelope', t('Email Setting')],
        ['sms', 'las la-sms', t('SMS Setting')],
        ['push', 'las la-bell', t('Push Notification Setting')],
        ['templates', 'las la-list', t('Notification Templates')],
    ];
    return (
        <ul className="nav nav-tabs mb-4 flex-nowrap overflow-auto" style={{ whiteSpace: 'nowrap' }}>
            {items.map(([key, icon, label]) => (
                <li className="nav-item" key={key}>
                    <Link href={tabs[key]} className={`nav-link text-dark ${active === key ? 'active' : ''}`} aria-current={active === key ? 'page' : undefined}>
                        <i className={icon} /> {label}
                    </Link>
                </li>
            ))}
        </ul>
    );
}

/** The "short code → meaning" table above a template editor. */
export function Shortcodes({ codes }: { codes: Record<string, string> }) {
    const t = useTrans();
    return (
        <div className="card overflow-hidden mb-4">
            <div className="card-body p-0">
                <div className="table-responsive">
                    <table className="table align-items-center table--light mb-0">
                        <thead>
                            <tr>
                                <th>{t('Short Code')}</th>
                                <th>{t('Description')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {Object.entries(codes).map(([code, meaning]) => (
                                <tr key={code}>
                                    <td data-label={t('Short Code')}>
                                        <span className="short-codes">{`{{${code}}}`}</span>
                                    </td>
                                    <td data-label={t('Description')}>{t(meaning)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}

/**
 * A rendered preview of admin-written email HTML. Sandboxed: scripts in the
 * template cannot run (the old preview loaded it as a data: URL, unsandboxed).
 */
export function EmailPreview({ html, title }: { html: string; title: string }) {
    return <iframe title={title} sandbox="" srcDoc={html} style={{ width: '100%', height: 420, border: '1px solid #eee', borderRadius: 6, background: '#fff' }} />;
}

/**
 * A password / API secret field. The stored value is never sent to the
 * browser: when one is stored the box is empty with a note, and leaving it
 * empty keeps it.
 */
export function SecretInput({ id, label, value, stored, onChange, error }: { id: string; label: string; value: string; stored: boolean; onChange: (v: string) => void; error?: string }) {
    const t = useTrans();
    return (
        <div className="form-group">
            <label htmlFor={id}>{label}</label>
            <input id={id} type="password" autoComplete="new-password" className="form-control" placeholder={stored ? t('Saved: leave blank to keep it') : ''} value={value} onChange={(e) => onChange(e.target.value)} />
            {stored && (
                <small className="text--success">
                    <i className="las la-lock" /> {t('A value is saved. Type a new one only to replace it.')}
                </small>
            )}
            <FieldErrorInline message={error} />
        </div>
    );
}

function FieldErrorInline({ message }: { message?: string }) {
    return message ? <small className="text--danger d-block mt-1">{message}</small> : null;
}

/** "Send a test" box under the email and SMS settings. */
export function TestSend({ url, field, label, type, placeholder }: { url: string; field: string; label: string; type: string; placeholder: string }) {
    const t = useTrans();
    const form = useForm<Record<string, string>>({ [field]: '' });
    return (
        <div className="card mt-4">
            <div className="card-header">
                <h5 className="card-title m-0">{label}</h5>
            </div>
            <form
                className="card-body"
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post(url, { preserveScroll: true });
                }}
            >
                <div className="d-flex flex-wrap gap-2 align-items-start">
                    <div className="flex-grow-1">
                        <label htmlFor={`test-${field}`} className="visually-hidden">
                            {placeholder}
                        </label>
                        <input id={`test-${field}`} type={type} className="form-control" required placeholder={placeholder} value={form.data[field]} onChange={(e) => form.setData(field, e.target.value)} />
                        <FieldErrorInline message={form.errors[field]} />
                    </div>
                    <button type="submit" className="btn btn--primary h-45" disabled={form.processing}>
                        <i className="las la-paper-plane" /> {t('Send')}
                    </button>
                </div>
            </form>
        </div>
    );
}

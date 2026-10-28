import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FieldError } from '@/Components/Admin/Form';
import { EmailPreview, NotificationNav, Shortcodes, type NotificationTabs } from '@/Components/Admin/NotificationNav';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';

type Channel = 'email' | 'sms' | 'push';

type Props = {
    channel: Channel;
    title: string;
    form: Record<string, string>;
    shortcodes: Record<string, string>;
    urls: { tabs: NotificationTabs; global: Record<Channel, string>; update: string };
};

/** Notification Setting → Global Template: the wrapper every email, SMS and push notification is sent in. */
export default function GlobalTemplate({ channel, title, form: initial, shortcodes, urls }: Props) {
    const t = useTrans();
    const form = useForm<Record<string, string>>(initial);
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(urls.update, { preserveScroll: true });
    };
    const input = (key: string, label: string, type = 'text') => (
        <div className="form-group">
            <label htmlFor={`gt-${key}`}>{label}</label>
            <input id={`gt-${key}`} type={type} className="form-control" required value={form.data[key]} onChange={(e) => form.setData(key, e.target.value)} />
            <FieldError message={form.errors[key]} />
        </div>
    );
    const cards: [Channel, string, string][] = [
        ['email', 'las la-envelope', t('Email Template')],
        ['sms', 'las la-mobile-alt', t('SMS Template')],
        ['push', 'las la-bell', t('Push Notification Template')],
    ];

    return (
        <>
            <PageHeader title={t(title)} />
            <NotificationNav tabs={urls.tabs} active="global" />
            <div className="row">
                {cards.map(([key, icon, label]) => (
                    <div className="col-xxl-3 col-xl-4 col-md-4 col-sm-6" key={key}>
                        <Link href={urls.global[key]} className={`notification-via d-block mb-4 ${channel === key ? 'active' : ''}`} aria-current={channel === key ? 'page' : undefined}>
                            <span className="active-badge">
                                <i className="las la-check" />
                            </span>
                            <div className="send-via-method">
                                <i className={icon} />
                                <h5>{label}</h5>
                            </div>
                        </Link>
                    </div>
                ))}
            </div>
            <Shortcodes codes={shortcodes} />
            <div className="card">
                <div className="card-body">
                    <form onSubmit={submit}>
                        {channel === 'email' && (
                            <div className="row">
                                <div className="col-md-6">{input('email_from_name', t('Email Sent From - Name'))}</div>
                                <div className="col-md-6">{input('email_from', t('Email Sent From - Email'), 'email')}</div>
                                <div className="col-md-6 form-group">
                                    <label htmlFor="gt-email_template">{t('Email Body')}</label>
                                    <textarea id="gt-email_template" className="form-control font-monospace" style={{ height: 420, fontSize: 13 }} spellCheck={false} required value={form.data.email_template} onChange={(e) => form.setData('email_template', e.target.value)} />
                                    <FieldError message={form.errors.email_template} />
                                </div>
                                <div className="col-md-6 form-group">
                                    <span className="d-block mb-2">{t('Preview')}</span>
                                    <EmailPreview html={form.data.email_template} title={t('Email preview')} />
                                </div>
                            </div>
                        )}
                        {channel === 'sms' && (
                            <>
                                {input('sms_from', t('SMS Sent From'))}
                                <div className="form-group">
                                    <label htmlFor="gt-sms_template">{t('SMS Body')}</label>
                                    <textarea id="gt-sms_template" className="form-control" rows={4} required value={form.data.sms_template} onChange={(e) => form.setData('sms_template', e.target.value)} />
                                    <FieldError message={form.errors.sms_template} />
                                </div>
                            </>
                        )}
                        {channel === 'push' && (
                            <>
                                {input('push_title', t('Notification Title'))}
                                <div className="form-group">
                                    <label htmlFor="gt-push_template">{t('Push Notification Body')}</label>
                                    <textarea id="gt-push_template" className="form-control" rows={4} required value={form.data.push_template} onChange={(e) => form.setData('push_template', e.target.value)} />
                                    <FieldError message={form.errors.push_template} />
                                </div>
                            </>
                        )}
                        <button type="submit" className="btn w-100 btn--primary h-45" disabled={form.processing}>
                            {t('Submit')}
                        </button>
                    </form>
                </div>
            </div>
        </>
    );
}

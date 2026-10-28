import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FieldError } from '@/Components/Admin/Form';
import { NotificationNav, Shortcodes, type NotificationTabs } from '@/Components/Admin/NotificationNav';
import PageHeader from '@/Components/Admin/PageHeader';
import RichTextEditor from '@/Components/Admin/RichTextEditor';
import { useTrans } from '@/lib/i18n';

type Channel = 'email' | 'sms' | 'push';

type Template = {
    id: number;
    name: string;
    subject: string;
    email_body: string;
    email_sent_from_name: string;
    email_sent_from_address: string;
    email_status: boolean;
    sms_body: string;
    sms_sent_from: string;
    sms_status: boolean;
    push_title: string;
    push_body: string;
    push_status: boolean;
};

type Props = {
    channel: Channel;
    template: Template;
    shortcodes: Record<string, string>;
    urls: { tabs: NotificationTabs; update: string; tab: string };
};

/** One notification's email, SMS or push template (a tab each), with its short codes. */
export default function TemplateEdit({ channel, template, shortcodes, urls }: Props) {
    const t = useTrans();
    const fields: Record<Channel, (keyof Template)[]> = {
        email: ['subject', 'email_body', 'email_sent_from_name', 'email_sent_from_address', 'email_status'],
        sms: ['sms_body', 'sms_sent_from', 'sms_status'],
        push: ['push_title', 'push_body', 'push_status'],
    };
    const form = useForm<Record<string, string | boolean>>(Object.fromEntries(fields[channel].map((k) => [k, template[k] as string | boolean])));
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(urls.update, { preserveScroll: true });
    };
    const text = (key: string, label: string, hint?: string, required = false, type = 'text') => (
        <div className="form-group">
            <label htmlFor={`te-${key}`}>{label}</label>
            <input id={`te-${key}`} type={type} className="form-control" required={required} value={String(form.data[key] ?? '')} onChange={(e) => form.setData(key, e.target.value)} />
            {hint && (
                <small className="text--primary">
                    <i className="las la-info-circle" /> {hint}
                </small>
            )}
            <FieldError message={form.errors[key]} />
        </div>
    );
    const statusKey = `${channel}_status`;
    const statusLabel = { email: t('Send Email'), sms: t('Send SMS'), push: t('Send Push Notify') }[channel];
    const tabs: [Channel, string][] = [
        ['email', t('Email Template')],
        ['sms', t('SMS Template')],
        ['push', t('Push Notification Template')],
    ];

    return (
        <>
            <PageHeader title={template.name}>
                <Link href={urls.tabs.templates} className="btn btn-outline--dark">
                    <i className="las la-undo" /> {t('Back')}
                </Link>
            </PageHeader>
            <NotificationNav tabs={urls.tabs} active="templates" />
            <ul className="nav nav-pills mb-3 gap-2">
                {tabs.map(([key, label]) => (
                    <li className="nav-item" key={key}>
                        <Link href={urls.tab.replace(':type', key)} className={`nav-link ${channel === key ? 'active' : ''}`} aria-current={channel === key ? 'page' : undefined}>
                            {label}
                        </Link>
                    </li>
                ))}
            </ul>
            <Shortcodes codes={shortcodes} />
            <form onSubmit={submit} className="card">
                <div className="card-header bg--primary">
                    <h5 className="card-title text-white m-0">{tabs.find(([k]) => k === channel)?.[1]}</h5>
                </div>
                <div className="card-body">
                    <div className="form-check form-switch mb-3" style={{ fontSize: '1.15rem' }}>
                        <input id="te-status" type="checkbox" role="switch" className="form-check-input" checked={Boolean(form.data[statusKey])} onChange={(e) => form.setData(statusKey, e.target.checked)} />
                        <label className="form-check-label fw-bold" htmlFor="te-status">
                            {form.data[statusKey] ? statusLabel : t("Don't Send")}
                        </label>
                    </div>
                    {channel === 'email' && (
                        <>
                            {text('subject', t('Subject'), undefined, true)}
                            <div className="row">
                                <div className="col-md-6">{text('email_sent_from_name', t('Email Sent From - Name'), t("Leave empty to use the global template's name."))}</div>
                                <div className="col-md-6">{text('email_sent_from_address', t('Email Sent From - Email'), t("Leave empty to use the global template's email."), false, 'email')}</div>
                            </div>
                            <div className="form-group">
                                <RichTextEditor id="te-email_body" label={t('Message')} value={String(form.data.email_body)} onChange={(html) => form.setData('email_body', html)} />
                                <FieldError message={form.errors.email_body} />
                            </div>
                        </>
                    )}
                    {channel === 'sms' && (
                        <>
                            {text('sms_sent_from', t('SMS Sent From'), t("Leave empty to use the global template's sender."))}
                            <div className="form-group">
                                <label htmlFor="te-sms_body">{t('Message')}</label>
                                <textarea id="te-sms_body" className="form-control" rows={8} required placeholder={t('Your message using short-codes')} value={String(form.data.sms_body)} onChange={(e) => form.setData('sms_body', e.target.value)} />
                                <FieldError message={form.errors.sms_body} />
                            </div>
                        </>
                    )}
                    {channel === 'push' && (
                        <>
                            {text('push_title', t('Notification Title'), t("Leave empty to use the global template's title."))}
                            <div className="form-group">
                                <label htmlFor="te-push_body">{t('Message')}</label>
                                <textarea id="te-push_body" className="form-control" rows={6} required placeholder={t('Your message using short-codes')} value={String(form.data.push_body)} onChange={(e) => form.setData('push_body', e.target.value)} />
                                <FieldError message={form.errors.push_body} />
                            </div>
                        </>
                    )}
                    <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing}>
                        {t('Submit')}
                    </button>
                </div>
            </form>
        </>
    );
}

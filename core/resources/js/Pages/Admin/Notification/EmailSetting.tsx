import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FieldError } from '@/Components/Admin/Form';
import { NotificationNav, SecretInput, TestSend, type NotificationTabs } from '@/Components/Admin/NotificationNav';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';

type Form = { email_method: string; host: string; port: string; enc: string; username: string; password: string; appkey: string; public_key: string; secret_key: string };

type Props = {
    form: Form;
    stored: { password: boolean; appkey: boolean; secret_key: boolean };
    urls: { tabs: NotificationTabs; update: string; test: string };
};

/** Notification Setting → Email Setting: how mail is sent (PHP mail, SMTP, SendGrid, Mailjet), and a test mail. */
export default function EmailSetting({ form: initial, stored, urls }: Props) {
    const t = useTrans();
    const form = useForm<Form>(initial);
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(urls.update, { preserveScroll: true, onSuccess: () => form.setData((d) => ({ ...d, password: '', appkey: '', secret_key: '' })) });
    };
    const text = (key: keyof Form, label: string, placeholder = '', type = 'text') => (
        <div className="form-group">
            <label htmlFor={`em-${key}`}>{label}</label>
            <input id={`em-${key}`} type={type} className="form-control" placeholder={placeholder} value={form.data[key]} onChange={(e) => form.setData(key, e.target.value)} />
            <FieldError message={form.errors[key]} />
        </div>
    );
    const method = form.data.email_method;

    return (
        <>
            <PageHeader title={t('Email Notification Settings')} />
            <NotificationNav tabs={urls.tabs} active="email" />
            <div className="card">
                <form onSubmit={submit}>
                    <div className="card-body">
                        <div className="form-group">
                            <label htmlFor="em-method">{t('Email Send Method')}</label>
                            <select id="em-method" className="form-control" value={method} onChange={(e) => form.setData('email_method', e.target.value)}>
                                <option value="php">{t('PHP Mail')}</option>
                                <option value="smtp">{t('SMTP')}</option>
                                <option value="sendgrid">{t('SendGrid API')}</option>
                                <option value="mailjet">{t('Mailjet API')}</option>
                            </select>
                        </div>
                        {method === 'smtp' && (
                            <fieldset className="row mt-4">
                                <legend className="h6 mb-3">{t('SMTP Configuration')}</legend>
                                <div className="col-md-4">{text('host', t('Host'), 'smtp.googlemail.com')}</div>
                                <div className="col-md-4">{text('port', t('Port'), t('Available port'), 'number')}</div>
                                <div className="col-md-4 form-group">
                                    <label htmlFor="em-enc">{t('Encryption')}</label>
                                    <select id="em-enc" className="form-control" value={form.data.enc} onChange={(e) => form.setData('enc', e.target.value)}>
                                        <option value="ssl">{t('SSL')}</option>
                                        <option value="tls">{t('TLS')}</option>
                                    </select>
                                    <FieldError message={form.errors.enc} />
                                </div>
                                <div className="col-md-6">{text('username', t('Username'), t('Normally your email'))}</div>
                                <div className="col-md-6">
                                    <SecretInput id="em-password" label={t('Password')} value={form.data.password} stored={stored.password} onChange={(v) => form.setData('password', v)} error={form.errors.password} />
                                </div>
                            </fieldset>
                        )}
                        {method === 'sendgrid' && (
                            <fieldset className="mt-4">
                                <legend className="h6 mb-3">{t('SendGrid API Configuration')}</legend>
                                <SecretInput id="em-appkey" label={t('App Key')} value={form.data.appkey} stored={stored.appkey} onChange={(v) => form.setData('appkey', v)} error={form.errors.appkey} />
                            </fieldset>
                        )}
                        {method === 'mailjet' && (
                            <fieldset className="row mt-4">
                                <legend className="h6 mb-3">{t('Mailjet API Configuration')}</legend>
                                <div className="col-md-6">{text('public_key', t('Api Public Key'))}</div>
                                <div className="col-md-6">
                                    <SecretInput id="em-secret" label={t('Api Secret Key')} value={form.data.secret_key} stored={stored.secret_key} onChange={(v) => form.setData('secret_key', v)} error={form.errors.secret_key} />
                                </div>
                            </fieldset>
                        )}
                    </div>
                    <div className="card-footer">
                        <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing}>
                            {t('Submit')}
                        </button>
                    </div>
                </form>
            </div>
            <TestSend url={urls.test} field="email" type="email" label={t('Test Mail Setup')} placeholder={t('Email Address')} />
        </>
    );
}

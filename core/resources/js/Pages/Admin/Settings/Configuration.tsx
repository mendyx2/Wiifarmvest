import { useForm } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';

type Modules = Record<'registration' | 'force_ssl' | 'agree' | 'secure_password' | 'kv' | 'ev' | 'en' | 'sv' | 'sn' | 'pn' | 'multi_language', boolean>;

type Props = {
    modules: Modules;
    urls: { update: string; policies: string; kyc: string; push: string };
};

/** Settings → System Configuration: switch the site's modules on and off. */
export default function Configuration({ modules, urls }: Props) {
    const t = useTrans();
    const form = useForm<Modules>(modules);
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(urls.update, { preserveScroll: true });
    };
    const rows: [keyof Modules, string, ReactNode][] = [
        ['registration', t('User Registration'), t('If you disable this module, no one can register on this system.')],
        ['force_ssl', t('Force SSL'), t('By enabling Force SSL (Secure Sockets Layer) the system will force visitors to use the secure (https) address.')],
        [
            'agree',
            t('Agree Policy'),
            <>
                {t("If you enable this module, a user must agree with your system's")} <a href={urls.policies}>{t('policies')}</a> {t('during registration.')}
            </>,
        ],
        ['secure_password', t('Force Secure Password'), t('By enabling this module, a user must set a secure password while signing up or changing the password.')],
        [
            'kv',
            t('KYC Verification'),
            <>
                {t('If you enable KYC (Know Your Client), users must submit')} <a href={urls.kyc}>{t('the required data')}</a>. {t('Otherwise, any money out transaction will be prevented by this system.')}
            </>,
        ],
        ['ev', t('Email Verification'), t('Users have to verify their email to access the dashboard; a 6-digit code is sent to their email. Make sure that Email Notification is enabled.')],
        ['en', t('Email Notification'), t("If you enable this module, the system will send emails to users where needed. Otherwise, no email will be sent. Be sure the system doesn't need to send any emails before disabling it.")],
        ['sv', t('Mobile Verification'), t('Users have to verify their mobile to access the dashboard; a 6-digit code is sent by SMS. Make sure that SMS Notification is enabled.')],
        ['sn', t('SMS Notification'), t("If you enable this module, the system will send SMS to users where needed. Otherwise, no SMS will be sent. Be sure the system doesn't need to send any SMS before disabling it.")],
        [
            'pn',
            t('Push Notification'),
            <>
                {t('If you enable this module, the system will send push notifications to users.')} <a href={urls.push}>{t('Setting here')}</a>
            </>,
        ],
        ['multi_language', t('Language Option'), t('If you enable this module, users can change the language according to their needs.')],
    ];

    return (
        <>
            <PageHeader title={t('System Configuration')} />
            <div className="card">
                <form onSubmit={submit}>
                    <div className="card-body">
                        <ul className="list-group">
                            {rows.map(([key, title, help]) => (
                                <li key={key} className="list-group-item d-flex flex-wrap flex-sm-nowrap gap-3 justify-content-between align-items-center">
                                    <div>
                                        <label className="fw-bold mb-0 d-block" htmlFor={`mod-${key}`}>
                                            {title}
                                        </label>
                                        <small>{help}</small>
                                    </div>
                                    <div className="form-check form-switch flex-shrink-0 m-0" style={{ fontSize: '1.4rem' }}>
                                        <input id={`mod-${key}`} type="checkbox" role="switch" className="form-check-input" style={{ width: '2.8em', height: '1.5em', cursor: 'pointer' }} checked={form.data[key]} onChange={(e) => form.setData(key, e.target.checked)} />
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </div>
                    <div className="card-footer">
                        <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing}>
                            {t('Submit')}
                        </button>
                    </div>
                </form>
            </div>
        </>
    );
}

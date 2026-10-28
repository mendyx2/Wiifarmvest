import { Head, Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import Captcha, { type CaptchaConfig, type CaptchaFields } from '@/Components/Captcha';
import { AdminAuthCard, withAdminAuthLayout } from '@/Layouts/AdminAuthLayout';
import { useTrans } from '@/lib/i18n';

type Props = { logo: string | null; captcha: CaptchaConfig; urls: { submit: string; login: string } };

/** Password reset, step 1 of 3: the admin's email (Admin\Auth\ForgotPasswordController). */
export default function Forgot({ logo, captcha, urls }: Props) {
    const t = useTrans();
    const form = useForm({ email: '' });
    const [captchaFields, setCaptchaFields] = useState<CaptchaFields>({});
    const [captchaKey, setCaptchaKey] = useState(0);
    const errors = form.errors as Record<string, string | undefined>;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.transform((data) => ({ ...data, ...captchaFields }));
        form.post(urls.submit, { onError: () => setCaptchaKey((k) => k + 1) });
    };

    return (
        <AdminAuthCard logo={logo} tag={t('Account Recovery')} title={t('Forgot your password?')} text={t('Enter the email on your admin account and we will send you a 6-digit code.')}>
            <Head title={t('Account Recovery')} />
            <form onSubmit={submit} noValidate>
                <div className="al-field">
                    <div className="al-label">
                        <label htmlFor="al-email">{t('Email')}</label>
                    </div>
                    <input
                        id="al-email"
                        type="email"
                        className={`al-input${errors.email ? ' is-invalid' : ''}`}
                        value={form.data.email}
                        onChange={(e) => form.setData('email', e.target.value)}
                        autoComplete="email"
                        autoFocus
                        required
                        aria-invalid={errors.email ? true : undefined}
                        aria-describedby={errors.email ? 'al-email-error' : undefined}
                    />
                    {errors.email && (
                        <div className="al-error" id="al-email-error">
                            {errors.email}
                        </div>
                    )}
                </div>
                <div className="al-captcha">
                    <Captcha key={captchaKey} config={captcha} onChange={setCaptchaFields} />
                    {errors.captcha && <div className="al-error">{errors.captcha}</div>}
                </div>
                <button type="submit" className="al-btn" disabled={form.processing}>
                    {form.processing ? <i className="las la-spinner la-spin" aria-label={t('Sending')} /> : t('Send code')}
                </button>
            </form>
            <div className="al-foot">
                <Link href={urls.login} className="al-link">
                    <i className="las la-arrow-left" aria-hidden /> {t('Back to Login')}
                </Link>
            </div>
        </AdminAuthCard>
    );
}

Forgot.layout = withAdminAuthLayout;

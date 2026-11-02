import { Head, Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import Captcha, { type CaptchaConfig, type CaptchaFields } from '@/Components/Captcha';
import { AdminAuthCard, withAdminAuthLayout } from '@/Layouts/AdminAuthLayout';
import { useTrans } from '@/lib/i18n';

type Props = { logo: string | null; captcha: CaptchaConfig; urls: { login: string; forgot: string } };

/** Admin sign-in (Admin\Auth\LoginController). */
export default function Login({ logo, captcha, urls }: Props) {
    const t = useTrans();
    const form = useForm({ username: '', password: '' });
    const [captchaFields, setCaptchaFields] = useState<CaptchaFields>({});
    const [captchaKey, setCaptchaKey] = useState(0);
    const [shown, setShown] = useState(false);
    const errors = form.errors as Record<string, string | undefined>;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.transform((data) => ({ ...data, ...captchaFields }));
        form.post(urls.login, {
            // A failed attempt needs a fresh captcha; the password is cleared either way
            onError: () => setCaptchaKey((k) => k + 1),
            onFinish: () => form.reset('password'),
        });
    };

    return (
        <AdminAuthCard logo={logo} tag={t('Secure Access')} title={t('Welcome back')} text={t('Sign in to your admin account to continue')}>
            <Head title={t('Admin Login')} />
            {errors.username && (
                <div className="al-alert" role="alert">
                    {errors.username}
                </div>
            )}
            <form onSubmit={submit} noValidate>
                <div className="al-field">
                    <div className="al-label">
                        <label htmlFor="al-username">{t('Username')}</label>
                    </div>
                    <input
                        id="al-username"
                        className={`al-input${errors.username ? ' is-invalid' : ''}`}
                        value={form.data.username}
                        onChange={(e) => form.setData('username', e.target.value)}
                        placeholder={t('Enter your username')}
                        autoComplete="username"
                        spellCheck={false}
                        autoFocus
                        required
                        aria-invalid={errors.username ? true : undefined}
                    />
                </div>
                <div className="al-field">
                    <div className="al-label">
                        <label htmlFor="al-password">{t('Password')}</label>
                        <Link href={urls.forgot} className="al-link">
                            {t('Forgot password?')}
                        </Link>
                    </div>
                    <div className="al-input-wrap">
                        <input
                            id="al-password"
                            type={shown ? 'text' : 'password'}
                            className={`al-input has-right${errors.password ? ' is-invalid' : ''}`}
                            value={form.data.password}
                            onChange={(e) => form.setData('password', e.target.value)}
                            placeholder={t('Enter your password')}
                            autoComplete="current-password"
                            required
                            aria-invalid={errors.password ? true : undefined}
                            aria-describedby={errors.password ? 'al-password-error' : undefined}
                        />
                        <button type="button" className="al-eye" aria-label={shown ? t('Hide password') : t('Show password')} aria-pressed={shown} onClick={() => setShown((s) => !s)}>
                            <i className={`las ${shown ? 'la-eye-slash' : 'la-eye'}`} aria-hidden />
                        </button>
                    </div>
                    {errors.password && (
                        <div className="al-error" id="al-password-error">
                            {errors.password}
                        </div>
                    )}
                </div>
                <div className="al-captcha">
                    <Captcha key={captchaKey} config={captcha} onChange={setCaptchaFields} />
                    {errors.captcha && <div className="al-error">{errors.captcha}</div>}
                </div>
                <button type="submit" className="al-btn" disabled={form.processing}>
                    {form.processing ? <i className="las la-spinner la-spin" aria-label={t('Signing in')} /> : t('Sign In')} {!form.processing && <i className="las la-arrow-right" aria-hidden />}
                </button>
            </form>
        </AdminAuthCard>
    );
}

Login.layout = withAdminAuthLayout;

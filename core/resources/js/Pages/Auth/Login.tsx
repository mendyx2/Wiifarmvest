import { Head, Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import Captcha, { type CaptchaConfig, type CaptchaFields } from '@/Components/Captcha';
import { AuthCard, withAuthLayout } from '@/Layouts/AuthLayout';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type Props = PageProps<{
    logo: string;
    captcha: CaptchaConfig;
    urls: { login: string; forgot: string; register: string | null };
}>;

export default function Login({ logo, captcha, urls }: Props) {
    const t = useTrans();
    const form = useForm({ username: '', password: '', remember: false });
    const [captchaFields, setCaptchaFields] = useState<CaptchaFields>({});
    const [captchaKey, setCaptchaKey] = useState(0);
    const [shown, setShown] = useState(false);
    // LoginController checks "email" or "username" depending on what was typed.
    const errors = form.errors as Record<string, string | undefined>;
    const firstError = errors.username ?? errors.email ?? errors.password ?? Object.values(errors)[0];

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.transform((data) => ({ ...data, ...captchaFields }));
        form.post(urls.login, {
            onFinish: () => {
                form.reset('password');
                setCaptchaKey((k) => k + 1);
            },
        });
    };

    return (
        <AuthCard title={t('Welcome Back')} subtitle={t('Sign in to your account to continue')} logo={logo}>
            <Head title={t('Login')} />
            {firstError && (
                <div className="alert alert-danger">
                    <i className="fas fa-exclamation-circle me-2" />
                    {firstError}
                </div>
            )}

            <form onSubmit={submit}>
                <div className="form-group">
                    <label htmlFor="username" className="form-label">
                        <i className="fas fa-user me-2" />
                        {t('Username')}
                    </label>
                    <div className="input-group">
                        <input
                            type="text"
                            className={`form-control${errors.username || errors.email ? ' is-invalid' : ''}`}
                            id="username"
                            value={form.data.username}
                            onChange={(e) => form.setData('username', e.target.value)}
                            placeholder={t('Enter your username')}
                            required
                            autoComplete="username"
                            autoFocus
                        />
                    </div>
                </div>

                <div className="form-group">
                    <label htmlFor="password" className="form-label">
                        <i className="fas fa-lock me-2" />
                        {t('Password')}
                    </label>
                    <div className="input-group">
                        <input
                            type={shown ? 'text' : 'password'}
                            className={`form-control${errors.password ? ' is-invalid' : ''}`}
                            id="password"
                            value={form.data.password}
                            onChange={(e) => form.setData('password', e.target.value)}
                            placeholder={t('Enter your password')}
                            required
                            autoComplete="current-password"
                        />
                        <button type="button" className="password-toggle" aria-label={shown ? t('Hide password') : t('Show password')} onClick={() => setShown((s) => !s)}>
                            <i className={`fas ${shown ? 'fa-eye-slash' : 'fa-eye'}`} />
                        </button>
                    </div>
                </div>

                <Captcha key={captchaKey} config={captcha} onChange={setCaptchaFields} />

                <div className="form-options">
                    <div className="custom-checkbox">
                        <input className="form--control" id="remember" type="checkbox" checked={form.data.remember} onChange={(e) => form.setData('remember', e.target.checked)} />
                        <span className="checkmark" />
                        <label htmlFor="remember" className="form-check-label">
                            {t('Remember me')}
                        </label>
                    </div>
                    <Link href={urls.forgot} className="forgot-password">
                        {t('Forgot Password?')}
                    </Link>
                </div>

                <button type="submit" className={`btn btn-login${form.processing ? ' btn-loading' : ''}`} disabled={form.processing}>
                    <i className="fas fa-sign-in-alt me-2" />
                    {t('Sign In')}
                </button>
            </form>

            {urls.register && (
                <div className="login-footer">
                    <p className="text-dark">
                        {t("Don't have an account?")}{' '}
                        <a href={urls.register} className="signup-link">
                            {t('Sign up now')}
                        </a>
                    </p>
                </div>
            )}
        </AuthCard>
    );
}

Login.layout = withAuthLayout;

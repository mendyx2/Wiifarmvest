import { Head, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { AuthCard, withAuthLayout } from '@/Layouts/AuthLayout';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type Props = PageProps<{ token: string; securePassword: boolean; urls: { submit: string } }>;

/** The "secure password" rules (General Settings), checked as you type like secure_password.js did. */
const RULES: [string, (p: string) => boolean][] = [
    ['At least one capital letter', (p) => /[A-Z]/.test(p)],
    ['At least one small letter', (p) => /[a-z]/.test(p)],
    ['At least one number', (p) => /\d/.test(p)],
    ['At least one special character', (p) => /[^A-Za-z0-9]/.test(p)],
    ['Minimum 6 characters', (p) => p.length >= 6],
];

export default function ResetPassword({ token, securePassword, urls }: Props) {
    const t = useTrans();
    const form = useForm({ token, password: '', password_confirmation: '' });
    const [shown, setShown] = useState(false);
    const mismatch = form.data.password_confirmation !== '' && form.data.password !== form.data.password_confirmation;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(urls.submit, { onFinish: () => form.reset('password', 'password_confirmation') });
    };

    return (
        <AuthCard title={t('Reset Password')} subtitle={t("Your account is verified successfully. Now you can change your password. Please enter a strong password and don't share it with anyone.")} icon="fas fa-unlock-alt">
            <Head title={t('Reset Password')} />
            <form onSubmit={submit}>
                {(['password', 'password_confirmation'] as const).map((key) => (
                    <div className="form-group" key={key}>
                        <label className="form-label" htmlFor={key}>
                            <i className="fas fa-lock me-2" />
                            {key === 'password' ? t('Password') : t('Confirm Password')}
                        </label>
                        <div className="input-group">
                            <input
                                id={key}
                                type={shown ? 'text' : 'password'}
                                className={`form-control${form.errors[key] || (key === 'password_confirmation' && mismatch) ? ' is-invalid' : ''}`}
                                value={form.data[key]}
                                onChange={(e) => form.setData(key, e.target.value)}
                                autoComplete="new-password"
                                required
                            />
                            {key === 'password' && (
                                <button type="button" className="password-toggle" aria-label={shown ? t('Hide password') : t('Show password')} onClick={() => setShown((s) => !s)}>
                                    <i className={`fas ${shown ? 'fa-eye-slash' : 'fa-eye'}`} />
                                </button>
                            )}
                        </div>
                        {form.errors[key] && <div className="invalid-feedback d-block">{form.errors[key]}</div>}
                        {key === 'password' && securePassword && form.data.password && (
                            <ul className="pw-rules">
                                {RULES.map(([label, ok]) => (
                                    <li key={label} className={ok(form.data.password) ? 'ok' : ''}>
                                        <i className={`fas ${ok(form.data.password) ? 'fa-check' : 'fa-times'} me-1`} />
                                        {t(label)}
                                    </li>
                                ))}
                            </ul>
                        )}
                        {key === 'password_confirmation' && mismatch && <div className="invalid-feedback d-block">{t('Passwords do not match')}</div>}
                    </div>
                ))}
                {form.errors.token && <div className="alert alert-danger">{form.errors.token}</div>}
                <button type="submit" className={`btn btn-login${form.processing ? ' btn-loading' : ''}`} disabled={form.processing || mismatch}>
                    {t('Submit')}
                </button>
            </form>
        </AuthCard>
    );
}

ResetPassword.layout = withAuthLayout;

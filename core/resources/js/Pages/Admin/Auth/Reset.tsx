import { Head, Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { AdminAuthCard, withAdminAuthLayout } from '@/Layouts/AdminAuthLayout';
import { useTrans } from '@/lib/i18n';

type Props = { logo: string | null; token: string; urls: { submit: string; login: string } };

/** Password reset, step 3 of 3: the new password (Admin\Auth\ResetPasswordController). */
export default function Reset({ logo, token, urls }: Props) {
    const t = useTrans();
    const form = useForm({ token, password: '', password_confirmation: '' });
    const [shown, setShown] = useState(false);
    const mismatch = form.data.password_confirmation !== '' && form.data.password !== form.data.password_confirmation;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(urls.submit, { onFinish: () => form.reset('password', 'password_confirmation') });
    };

    const fields = [
        ['password', t('New password'), form.errors.password],
        ['password_confirmation', t('Confirm new password'), mismatch ? t('Passwords do not match') : undefined],
    ] as const;

    return (
        <AdminAuthCard logo={logo} tag={t('Account Recovery')} title={t('Choose a new password')} text={t('At least 8 characters.')}>
            <Head title={t('Account Recovery')} />
            <form onSubmit={submit} noValidate>
                {fields.map(([key, label, error]) => (
                    <div className="al-field" key={key}>
                        <div className="al-label">
                            <label htmlFor={`al-${key}`}>{label}</label>
                        </div>
                        <div className="al-input-wrap">
                            <input
                                id={`al-${key}`}
                                type={shown ? 'text' : 'password'}
                                className={`al-input${key === 'password' ? ' has-right' : ''}${error ? ' is-invalid' : ''}`}
                                value={form.data[key]}
                                onChange={(e) => form.setData(key, e.target.value)}
                                autoComplete="new-password"
                                minLength={8}
                                required
                                autoFocus={key === 'password'}
                                aria-invalid={error ? true : undefined}
                                aria-describedby={error ? `al-${key}-error` : undefined}
                            />
                            {key === 'password' && (
                                <button type="button" className="al-eye" aria-label={shown ? t('Hide password') : t('Show password')} aria-pressed={shown} onClick={() => setShown((s) => !s)}>
                                    <i className={`las ${shown ? 'la-eye-slash' : 'la-eye'}`} aria-hidden />
                                </button>
                            )}
                        </div>
                        {error && (
                            <div className="al-error" id={`al-${key}-error`}>
                                {error}
                            </div>
                        )}
                    </div>
                ))}
                {form.errors.token && (
                    <div className="al-alert" role="alert">
                        {form.errors.token}
                    </div>
                )}
                <button type="submit" className="al-btn" disabled={form.processing || mismatch}>
                    {form.processing ? <i className="las la-spinner la-spin" aria-label={t('Saving')} /> : t('Change password')}
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

Reset.layout = withAdminAuthLayout;

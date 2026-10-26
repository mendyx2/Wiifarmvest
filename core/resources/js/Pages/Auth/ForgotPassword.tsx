import { Head, Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import Captcha, { type CaptchaConfig, type CaptchaFields } from '@/Components/Captcha';
import { AuthCard, withAuthLayout } from '@/Layouts/AuthLayout';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type Props = PageProps<{ captcha: CaptchaConfig; urls: { submit: string; login: string } }>;

export default function ForgotPassword({ captcha, urls }: Props) {
    const t = useTrans();
    const form = useForm({ value: '' });
    const [captchaFields, setCaptchaFields] = useState<CaptchaFields>({});
    const [captchaKey, setCaptchaKey] = useState(0);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.transform((data) => ({ ...data, ...captchaFields }));
        form.post(urls.submit, { onFinish: () => setCaptchaKey((k) => k + 1) });
    };

    return (
        <AuthCard title={t('Account Recovery')} subtitle={t('To recover your account please provide your email or username to find your account.')} icon="fas fa-key">
            <Head title={t('Account Recovery')} />
            <form onSubmit={submit}>
                <div className="form-group">
                    <label className="form-label" htmlFor="value">
                        <i className="fas fa-user me-2" />
                        {t('Email or Username')}
                    </label>
                    <input id="value" className={`form-control${form.errors.value ? ' is-invalid' : ''}`} type="text" value={form.data.value} onChange={(e) => form.setData('value', e.target.value)} required autoFocus />
                    {form.errors.value && <div className="invalid-feedback d-block">{form.errors.value}</div>}
                </div>
                <Captcha key={captchaKey} config={captcha} onChange={setCaptchaFields} />
                <button className={`btn btn-login${form.processing ? ' btn-loading' : ''}`} type="submit" disabled={form.processing}>
                    {t('Submit')}
                </button>
            </form>
            <div className="login-footer">
                <p className="text-dark">
                    <Link href={urls.login} className="signup-link">
                        <i className="fas fa-arrow-left me-1" /> {t('Back to login')}
                    </Link>
                </p>
            </div>
        </AuthCard>
    );
}

ForgotPassword.layout = withAuthLayout;

import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';
import VerificationCode from '@/Components/VerificationCode';
import { AuthCard, withAuthLayout } from '@/Layouts/AuthLayout';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

/**
 * One page for every 6-digit code: email and mobile verification, 2FA at
 * login (AuthorizationController) and the password-reset code
 * (ForgotPasswordController). It replaces four nearly identical Blade views.
 */
type Props = PageProps<{
    kind: 'email' | 'sms' | '2fa' | 'reset';
    title: string;
    text: string;
    submit: string;
    /** Seconds until a new code may be sent (email / sms) */
    resendIn?: number | null;
    resend?: string | null;
    logout?: string;
    /** Password reset: start over */
    again?: string;
}>;

const ICONS = { email: 'fas fa-envelope-open-text', sms: 'fas fa-mobile-alt', '2fa': 'fas fa-shield-alt', reset: 'fas fa-key' } as const;

export default function VerifyCode({ kind, title, text, submit, resendIn, resend, logout, again }: Props) {
    const t = useTrans();
    const form = useForm({ code: '' });
    const [left, setLeft] = useState(resendIn ?? 0);

    useEffect(() => setLeft(resendIn ?? 0), [resendIn]);
    useEffect(() => {
        if (left <= 0) return;
        const timer = setTimeout(() => setLeft((s) => s - 1), 1000);
        return () => clearTimeout(timer);
    }, [left]);

    const send = (code = form.data.code) => {
        if (form.processing) return;
        form.transform(() => ({ code }));
        form.post(submit, { preserveScroll: true, onError: () => form.setData('code', '') });
    };
    const onSubmit = (e: FormEvent) => {
        e.preventDefault();
        send();
    };

    return (
        <AuthCard title={t(title)} subtitle={t(text)} icon={ICONS[kind]} wide>
            <Head title={t(title)} />
            <form onSubmit={onSubmit} className="submit-form">
                <VerificationCode value={form.data.code} onChange={(v) => form.setData('code', v)} onComplete={send} error={form.errors.code} />
                <button type="submit" className={`btn btn-login${form.processing ? ' btn-loading' : ''}`} disabled={form.processing || form.data.code.length < 6}>
                    {form.processing ? <i className="las la-spinner la-spin" /> : t('Submit')}
                </button>
            </form>

            <div className="login-footer">
                {kind === 'reset' && again && (
                    <p className="text-dark">
                        {t('Please check including your Junk/Spam Folder. if not found, you can')}{' '}
                        <Link href={again} className="signup-link">
                            {t('Try to send again')}
                        </Link>
                    </p>
                )}
                {resend && (
                    <p className="text-dark">
                        {t("If you don't get any code")},{' '}
                        {left > 0 ? (
                            <span>{t('try again after :s seconds', { s: left })}</span>
                        ) : (
                            <button
                                type="button"
                                className="btn btn-link p-0 signup-link align-baseline"
                                onClick={() => router.get(resend, {}, { preserveScroll: true, onSuccess: () => setLeft(120) })}
                            >
                                {t('Try again')}
                            </button>
                        )}
                    </p>
                )}
                {logout && (
                    <p>
                        <a href={logout} className="signup-link">
                            {t('Logout')}
                        </a>
                    </p>
                )}
            </div>
        </AuthCard>
    );
}

VerifyCode.layout = withAuthLayout;

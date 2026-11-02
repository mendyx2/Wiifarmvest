import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import VerificationCode from '@/Components/VerificationCode';
import { AdminAuthCard, withAdminAuthLayout } from '@/Layouts/AdminAuthLayout';
import { useTrans } from '@/lib/i18n';

type Props = { logo: string | null; email: string; urls: { submit: string; again: string; login: string } };

/** Password reset, step 2 of 3: the code from the email. It is sent as soon as the sixth digit is typed. */
export default function Code({ logo, email, urls }: Props) {
    const t = useTrans();
    const form = useForm({ code: '' });

    const send = (code = form.data.code) => {
        if (form.processing) return;
        form.transform(() => ({ code }));
        form.post(urls.submit, { onError: () => form.setData('code', '') });
    };
    const submit = (e: FormEvent) => {
        e.preventDefault();
        send();
    };

    return (
        <AdminAuthCard logo={logo} tag={t('Account Recovery')} title={t('Enter the code')} text={t('If :email is an admin account, a 6-digit code was sent to it. It works for 60 minutes.', { email })}>
            <Head title={t('Verify Code')} />
            <form onSubmit={submit}>
                <VerificationCode value={form.data.code} onChange={(v) => form.setData('code', v)} onComplete={send} error={form.errors.code} />
                <button type="submit" className="al-btn" disabled={form.processing || form.data.code.length < 6}>
                    {form.processing ? <i className="las la-spinner la-spin" aria-label={t('Checking')} /> : t('Submit')}
                </button>
            </form>
            <div className="al-foot">
                <Link href={urls.again} className="al-link">
                    {t('Send a new code')}
                </Link>
                <Link href={urls.login} className="al-link">
                    <i className="las la-arrow-left" aria-hidden /> {t('Back to Login')}
                </Link>
            </div>
        </AdminAuthCard>
    );
}

Code.layout = withAdminAuthLayout;

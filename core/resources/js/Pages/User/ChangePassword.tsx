import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import PasswordInput from '@/Components/PasswordInput';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type Props = PageProps<{
    securePassword: boolean;
    submitUrl: string;
}>;

const STRENGTH_COLORS = ['#DC2626', '#F59E0B', '#3B82F6', '#16A34A'];

/** Purely visual; the server applies the real password rules. */
function passwordScore(value: string): number {
    let score = 0;
    if (value.length >= 8) score++;
    if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score++;
    if (/[0-9]/.test(value)) score++;
    if (/[^A-Za-z0-9]/.test(value)) score++;
    return score;
}

export default function ChangePassword({ securePassword, submitUrl }: Props) {
    const t = useTrans();
    // Written as literal t('...') calls so the admin "fetch keywords" scan finds them.
    const strengthLabels = [t('Weak'), t('Fair'), t('Good'), t('Strong')];
    const tips = [
        { icon: 'la-ruler', title: t('Go long'), text: t('Use at least 8–12 characters — length matters more than complexity.') },
        { icon: 'la-font', title: t('Mix it up'), text: t('Combine upper and lower case letters, numbers, and a symbol.') },
        { icon: 'la-recycle', title: t('Never reuse'), text: t("Don't reuse a password from another site or an old account password.") },
        { icon: 'la-user-secret', title: t('Avoid the obvious'), text: t('Skip names, birthdays, or anything easily guessed from your profile.') },
    ];
    const form = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const score = passwordScore(form.data.password);
    const confirm = form.data.password_confirmation;
    const matches = confirm !== '' && confirm === form.data.password;

    // Requirements enforced by the server when "secure password" is switched on
    // in the admin panel (ProfileController@submitPassword).
    const requirements = [
        { label: t('At least 6 characters'), met: form.data.password.length >= 6 },
        { label: t('One uppercase and one lowercase letter'), met: /[a-z]/.test(form.data.password) && /[A-Z]/.test(form.data.password) },
        { label: t('One number'), met: /[0-9]/.test(form.data.password) },
        { label: t('One symbol'), met: /[^A-Za-z0-9]/.test(form.data.password) },
    ];

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post(submitUrl, {
            preserveScroll: true,
            // Never keep passwords in memory once the request is done.
            onFinish: () => form.reset(),
        });
    }

    return (
        <div className="nc-wrap" id="ncWrap">
            <Head title={t('Change Password')} />

            <div className="sl-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div>
                    <h4 className="sl-page-title mb-1">{t('Change Password')}</h4>
                    <p className="sl-page-subtitle mb-0">{t('Keep your account secure with a strong, unique password.')}</p>
                </div>
            </div>

            <div className="row g-4">
                <div className="col-12 col-lg-7">
                    <div className="sl-card">
                        <div className="sl-card-header">
                            <i className="las la-shield-alt me-1" /> {t('Update Your Password')}
                        </div>
                        <div className="sl-card-body">
                            <form onSubmit={submit} noValidate>
                                <PasswordInput
                                    id="current_password"
                                    label={t('Current Password')}
                                    value={form.data.current_password}
                                    onChange={(e) => form.setData('current_password', e.target.value)}
                                    error={form.errors.current_password}
                                    autoComplete="current-password"
                                    required
                                />

                                <PasswordInput
                                    id="password"
                                    label={t('New Password')}
                                    value={form.data.password}
                                    onChange={(e) => form.setData('password', e.target.value)}
                                    error={form.errors.password}
                                    autoComplete="new-password"
                                    required
                                />
                                <div className="sl-pwd-meter" style={{ marginTop: '-0.5rem' }}>
                                    {STRENGTH_COLORS.map((_, i) => (
                                        <div
                                            key={i}
                                            className="sl-pwd-meter-seg"
                                            style={{ background: i < score ? STRENGTH_COLORS[score - 1] : '#E5E9EF' }}
                                        />
                                    ))}
                                </div>
                                <p className="sl-pwd-meter-label mb-3">
                                    {form.data.password === ''
                                        ? t('Start typing a new password')
                                        : strengthLabels[Math.max(score - 1, 0)]}
                                </p>

                                {securePassword && form.data.password !== '' && (
                                    <ul className="list-unstyled small mb-3">
                                        {requirements.map((r) => (
                                            <li key={r.label} style={{ color: r.met ? 'var(--sl-green)' : '#6B7280' }}>
                                                <i className={`las ${r.met ? 'la-check-circle' : 'la-circle'} me-1`} />
                                                {r.label}
                                            </li>
                                        ))}
                                    </ul>
                                )}

                                <PasswordInput
                                    id="password_confirmation"
                                    label={t('Confirm New Password')}
                                    value={confirm}
                                    onChange={(e) => form.setData('password_confirmation', e.target.value)}
                                    autoComplete="new-password"
                                    required
                                />
                                {confirm !== '' && (
                                    <p className="sl-field-hint mb-3" style={{ color: matches ? 'var(--sl-green)' : '#DC2626', marginTop: '-0.5rem' }}>
                                        {matches ? t('Passwords match') : t('Passwords do not match')}
                                    </p>
                                )}

                                <div className="sl-form-actions mt-4">
                                    <button className="sl-btn sl-btn-primary" type="submit" disabled={form.processing}>
                                        <i className="las la-check-circle me-1" />
                                        {form.processing ? t('Updating...') : t('Update Password')}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div className="col-12 col-lg-5">
                    <div className="sl-card">
                        <div className="sl-card-header">
                            <i className="las la-lightbulb me-1" /> {t('Password Security Tips')}
                        </div>
                        <div className="sl-card-body">
                            {tips.map((tip) => (
                                <div className="sl-tip-item" key={tip.title}>
                                    <div className="sl-tip-icon">
                                        <i className={`las ${tip.icon}`} />
                                    </div>
                                    <div>
                                        <p className="fw-600 mb-0" style={{ fontSize: '.86rem' }}>{tip.title}</p>
                                        <small className="text-muted">{tip.text}</small>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import DynamicForm, { emptyValues, type FormField, type FormValues } from '@/Components/DynamicForm';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type Props = PageProps<{
    amount: string;
    charge: string;
    net: string;
    finalAmount: string;
    currency: string;
    methodName: string;
    /** Admin-written method description, sanitised on the server */
    description: string;
    fields: FormField[];
    /** User has 2FA on: the server requires authenticator_code */
    twoFactor: boolean;
    submitUrl: string;
}>;

export default function WithdrawPreview({ amount, charge, net, finalAmount, currency, methodName, description, fields, twoFactor, submitUrl }: Props) {
    const t = useTrans();
    const form = useForm<FormValues>({ ...emptyValues(fields), ...(twoFactor ? { authenticator_code: '' } : {}) });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post(submitUrl, {
            preserveScroll: true,
            // A one-time code can't be reused, so clear it after every attempt.
            onFinish: () => twoFactor && form.setData('authenticator_code', ''),
        });
    }

    return (
        <div className="wp-page">
            <Head title={t('Withdraw Preview')} />

            <div className="wp-banner">
                <div className="wp-banner-icon"><i className="las la-info-circle" /></div>
                <p className="wp-banner-text">
                    {t('You are requesting')} <strong>{amount}</strong> {t('for withdrawal.')} {t('Admin will send you')}{' '}
                    <strong className="wp-banner-net">{finalAmount}</strong> {t('to your account.')}
                </p>
            </div>

            <div className="wp-layout">
                <div className="wp-summary-card">
                    <div className="wp-sum-head">
                        <div className="wp-sum-icon"><i className="las la-arrow-circle-up" /></div>
                        <div>
                            <h6 className="wp-sum-title">{t('Withdrawal Summary')}</h6>
                            <p className="wp-sum-via">{t('via')} {methodName}</p>
                        </div>
                    </div>
                    <div className="wp-sum-rows">
                        <div className="wp-sum-row"><span>{t('Requested Amount')}</span><strong>{amount}</strong></div>
                        <div className="wp-sum-row wp-charge-row"><span>{t('Processing Charge')}</span><strong>− {charge}</strong></div>
                        <div className="wp-sum-divider" />
                        <div className="wp-sum-row wp-sum-net"><span>{t('You Receive')}</span><strong>{net}</strong></div>
                        <div className="wp-sum-row"><span>{t('In')} {currency}</span><strong>{finalAmount}</strong></div>
                    </div>
                    <div className="wp-sum-steps">
                        <div className="wp-step wp-step-done"><span className="wp-step-dot"><i className="las la-check" /></span><span>{t('Amount')}</span></div>
                        <div className="wp-step-line" />
                        <div className="wp-step wp-step-done"><span className="wp-step-dot"><i className="las la-check" /></span><span>{t('Method')}</span></div>
                        <div className="wp-step-line" />
                        <div className="wp-step wp-step-current" aria-current="step"><span className="wp-step-dot">3</span><span>{t('Confirm')}</span></div>
                    </div>
                </div>

                <div className="wp-form-card">
                    <div className="wp-form-head">
                        <h6 className="wp-form-title">{t('Provide Withdrawal Details')}</h6>
                        <p className="wp-form-sub">{t('Fill in your account information below')}</p>
                    </div>

                    {/* Sanitised with HTMLPurifier on the server before it gets here. */}
                    {description && <div className="wp-method-desc" dangerouslySetInnerHTML={{ __html: description }} />}

                    <form onSubmit={submit}>
                        <DynamicForm fields={fields} values={form.data} errors={form.errors} onChange={(key, value) => form.setData(key, value)} />

                        {twoFactor && (
                            <div className="form--group mt-3">
                                <label className="form--label" htmlFor="authenticator_code">{t('Google Authenticator Code')}</label>
                                <input
                                    id="authenticator_code"
                                    type="text"
                                    inputMode="numeric"
                                    autoComplete="one-time-code"
                                    className="form-control form--control"
                                    value={String(form.data.authenticator_code ?? '')}
                                    onChange={(e) => form.setData('authenticator_code', e.target.value)}
                                    required
                                />
                            </div>
                        )}

                        <button type="submit" className="wp-submit-btn" disabled={form.processing}>
                            <i className="las la-paper-plane" /> {form.processing ? t('Sending...') : t('Submit Withdrawal Request')}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    );
}

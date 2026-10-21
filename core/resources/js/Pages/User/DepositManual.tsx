import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import DynamicForm, { emptyValues, type FormField, type FormValues } from '@/Components/DynamicForm';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type Props = PageProps<{
    amount: string;
    /** Exact amount to pay in the gateway currency, e.g. "5,050.00 NGN" */
    payAmount: string;
    /** Admin-written instructions, sanitised on the server (FormPresenter::safeHtml) */
    instructions: string;
    fields: FormField[];
    submitUrl: string;
    historyUrl: string;
}>;

export default function DepositManual({ amount, payAmount, instructions, fields, submitUrl, historyUrl }: Props) {
    const t = useTrans();
    const form = useForm<FormValues>(emptyValues(fields));

    function submit(event: FormEvent) {
        event.preventDefault();
        // Files (payment proof) are sent as multipart/form-data automatically.
        form.post(submitUrl, { preserveScroll: true });
    }

    const steps = [
        { label: t('Amount'), done: true },
        { label: t('Gateway'), done: true },
        { label: t('Confirm'), done: false },
    ];
    const trust = [
        { cls: 't1', icon: 'la-lock', label: t('SSL Encrypted'), sub: t('256-bit security') },
        { cls: 't2', icon: 'la-user-shield', label: t('Admin Review'), sub: t('Verified manually') },
        { cls: 't3', icon: 'la-headset', label: t('24/7 Support'), sub: t('Always here for you') },
        { cls: 't4', icon: 'la-check-circle', label: t('Fast Approval'), sub: t('Usually within hours') },
    ];

    return (
        <div className="dep-page">
            <Head title={t('Confirm Deposit')} />

            <div className="dep-topbar">
                <div className="dep-topbar-left">
                    <div className="dep-topbar-icon man"><i className="las la-file-invoice-dollar" /></div>
                    <div>
                        <h6 className="dep-topbar-title">{t('Complete Your Deposit')}</h6>
                        <p className="dep-topbar-sub">{t('Manual payment confirmation')}</p>
                    </div>
                </div>
                <a href={historyUrl} className="dep-hist-btn">
                    <i className="las la-list-alt" /> {t('History')}
                </a>
            </div>

            <div className="man-banner">
                <div className="man-banner-left">
                    <div className="man-banner-label">{t('You are depositing')}</div>
                    <div className="man-banner-amount">{amount}</div>
                    <div className="man-banner-sub">
                        {t('Please pay exactly')} <strong>{payAmount}</strong> {t('for successful payment.')}
                    </div>
                </div>
                <div className="man-banner-icon"><i className="las la-paper-plane" /></div>
            </div>

            <ol className="man-steps list-unstyled" aria-label={t('Progress')}>
                {steps.map((step, i) => (
                    <li key={step.label} style={{ display: 'contents' }}>
                        {i > 0 && <div className="man-step-line active" aria-hidden="true" />}
                        <div className="man-step active" aria-current={step.done ? undefined : 'step'}>
                            <div className={`man-step-num ${step.done ? 'done' : 'current'}`}>
                                {step.done ? <i className="las la-check" /> : i + 1}
                            </div>
                            <span className="man-step-label">{step.label}</span>
                        </div>
                    </li>
                ))}
            </ol>

            <div className="man-layout">
                <div className="dep-card man-instr-card">
                    <div className="dep-card-head">
                        <div className="dep-card-head-icon gw"><i className="las la-info-circle" /></div>
                        <div>
                            <h6 className="dep-card-title">{t('Payment Instructions')}</h6>
                            <p className="dep-card-sub">{t('Follow the steps below carefully')}</p>
                        </div>
                    </div>
                    {/* Sanitised with HTMLPurifier on the server before it gets here. */}
                    <div className="man-instr-body" dangerouslySetInnerHTML={{ __html: instructions }} />
                </div>

                <div className="dep-card man-form-card">
                    <div className="dep-card-head">
                        <div className="dep-card-head-icon amt"><i className="las la-upload" /></div>
                        <div>
                            <h6 className="dep-card-title">{t('Submit Proof')}</h6>
                            <p className="dep-card-sub">{t('Fill in the required details below')}</p>
                        </div>
                    </div>

                    <form className="man-form" onSubmit={submit}>
                        <div className="man-form-body">
                            <DynamicForm fields={fields} values={form.data} errors={form.errors} onChange={(key, value) => form.setData(key, value)} />
                        </div>
                        <div className="man-form-footer">
                            <div className="man-secure-note">
                                <i className="las la-shield-alt" /> {t('Your submission is encrypted and secure')}
                            </div>
                            <button className="dep-submit-btn man-submit-btn" type="submit" disabled={form.processing}>
                                <i className="las la-paper-plane" /> {form.processing ? t('Sending...') : `${t('Pay Now')} — ${payAmount}`}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div className="dep-trust-strip">
                {trust.map((item) => (
                    <div className="dep-trust-item" key={item.cls}>
                        <div className={`dep-trust-icon ${item.cls}`}><i className={`las ${item.icon}`} /></div>
                        <div>
                            <p className="dep-trust-label">{item.label}</p>
                            <p className="dep-trust-sub">{item.sub}</p>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

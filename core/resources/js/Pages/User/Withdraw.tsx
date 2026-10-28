import { Head, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { useTrans } from '@/lib/i18n';
import { fixed, parseAmount, withdrawQuote } from '@/lib/money';
import type { PageProps } from '@/types';
import './Withdraw.css';

type Method = {
    id: number;
    name: string;
    image: string;
    currency: string;
    minLimit: number;
    maxLimit: number;
    minLabel: string;
    maxLabel: string;
    percentCharge: number;
    fixedCharge: number;
    rate: number;
};

type Props = PageProps<{
    blocked: boolean;
    blockReason: string | null;
    balance: string;
    bank: { name: string | null; accountName: string | null; accountNumber: string | null };
    methods: Method[];
    curSym: string;
    curText: string;
    submitUrl: string;
    historyUrl: string;
    profileUrl: string;
}>;

const VISIBLE_METHODS = 5;

export default function Withdraw(props: Props) {
    const t = useTrans();

    return (
        <div className="wd-page">
            <Head title={t('Withdraw Money')} />
            {props.blocked ? <Blocked reason={props.blockReason} /> : <WithdrawForm {...props} />}
        </div>
    );
}

function Blocked({ reason }: { reason: string | null }) {
    const t = useTrans();
    return (
        <div className="wd-blocked-wrap" role="alert">
            <div className="wd-blocked-icon"><i className="las la-ban" /></div>
            <h4 className="wd-blocked-title">{t('Withdrawal Suspended')}</h4>
            <p className="wd-blocked-msg">{t('Your ability to request a withdrawal has been temporarily suspended by the administrator.')}</p>
            {reason && (
                <div className="wd-blocked-reason">
                    <span className="wd-blocked-reason-label">{t('Reason:')}</span> {reason}
                </div>
            )}
            <p className="wd-blocked-support">
                {t('Please contact')} <strong>{t('support')}</strong> {t('if you believe this is an error.')}
            </p>
        </div>
    );
}

function WithdrawForm({ balance, bank, methods, curSym, curText, submitUrl, historyUrl, profileUrl }: Props) {
    const t = useTrans();
    const [showAll, setShowAll] = useState(false);
    const form = useForm({ method_code: methods[0] ? String(methods[0].id) : '', amount: '' });

    const method = methods.find((m) => String(m.id) === form.data.method_code);
    const amount = parseAmount(form.data.amount);
    const quote = method ? withdrawQuote(amount, method) : null;
    const withinLimit = method !== undefined && amount >= method.minLimit && amount <= method.maxLimit;
    const showConversion = method !== undefined && method.currency !== curText;

    function submit(event: FormEvent) {
        event.preventDefault();
        if (!withinLimit) return;
        // The server checks limits and balance again, then opens the preview step.
        form.post(submitUrl, { preserveScroll: true });
    }

    return (
        <>
            <div className="wd-hero">
                <span className="wd-hero-blob wd-blob-1" />
                <span className="wd-hero-blob wd-blob-2" />
                <div className="wd-hero-body">
                    <div className="wd-hero-icon"><i className="las la-arrow-circle-up" /></div>
                    <div>
                        <h4 className="wd-hero-title">{t('Withdraw Funds')}</h4>
                        <p className="wd-hero-sub">{t('Available balance:')} <strong>{balance}</strong></p>
                    </div>
                </div>
                <a href={historyUrl} className="wd-history-link">
                    <i className="las la-history" /> {t('History')}
                </a>
            </div>

            <form className="withdraw-form wd-form" onSubmit={submit}>
                <div className="wd-layout">
                    <div className="wd-col-left">
                        <div className="wd-section-label"><i className="las la-wallet" /> {t('Select Withdrawal Method')}</div>

                        <div className="wd-bank-card">
                            <div className="wd-bank-header">
                                <span className="wd-bank-label"><i className="las la-university" /> {t('Payout Account')}</span>
                                <a href={profileUrl} className="wd-bank-edit"><i className="las la-pen" /> {t('Edit')}</a>
                            </div>
                            <div className="wd-bank-body">
                                <div className="wd-bank-row"><span className="wd-bank-key">{t('Bank')}</span><span className="wd-bank-val">{bank.name}</span></div>
                                <div className="wd-bank-row"><span className="wd-bank-key">{t('Account Name')}</span><span className="wd-bank-val">{bank.accountName}</span></div>
                                <div className="wd-bank-row"><span className="wd-bank-key">{t('Account No.')}</span><span className="wd-bank-val wd-bank-acno">{bank.accountNumber}</span></div>
                            </div>
                        </div>

                        <div className="wd-gw-list payment-system-list is-scrollable gateway-option-list" role="radiogroup" aria-label={t('Select Withdrawal Method')}>
                            {methods.map((m, i) => (
                                <label key={m.id} className={`wd-gw-item gateway-option ${i >= VISIBLE_METHODS && !showAll ? 'd-none' : ''}`}>
                                    <div className="wd-gw-check payment-item__check" />
                                    <div className="wd-gw-thumb"><img src={m.image} alt={m.name} /></div>
                                    <span className="wd-gw-name">{m.name}</span>
                                    <input
                                        className="payment-item__radio gateway-input visually-hidden"
                                        type="radio"
                                        name="method_code"
                                        value={m.id}
                                        checked={form.data.method_code === String(m.id)}
                                        onChange={() => form.setData('method_code', String(m.id))}
                                    />
                                </label>
                            ))}
                            {methods.length > VISIBLE_METHODS && !showAll && (
                                <button type="button" className="wd-show-more more-gateway-option" onClick={() => setShowAll(true)}>
                                    <i className="las la-chevron-down" /> {t('Show All Methods')}
                                </button>
                            )}
                        </div>
                        {form.errors.method_code && <small className="d-block mt-2" style={{ color: '#DC2626' }}>{form.errors.method_code}</small>}
                    </div>

                    <div className="wd-col-right">
                        <div className="wd-section-label"><i className="las la-coins" /> {t('Enter Amount')}</div>
                        <div className="wd-amount-card">
                            <div className="wd-input-wrap">
                                <span className="wd-cur-sym">{curSym}</span>
                                <input
                                    type="text"
                                    inputMode="decimal"
                                    className="wd-amount-input amount"
                                    name="amount"
                                    aria-label={t('Amount')}
                                    placeholder="0.00"
                                    autoComplete="off"
                                    value={form.data.amount}
                                    onChange={(e) => form.setData('amount', e.target.value)}
                                />
                                <span className="wd-cur-text">{curText}</span>
                            </div>
                            {form.errors.amount && <small className="d-block mb-2" style={{ color: '#DC2626' }}>{form.errors.amount}</small>}

                            {method && quote && (
                                <div className="wd-summary" aria-live="polite">
                                    <div className="wd-sum-row">
                                        <span className="wd-sum-label"><i className="las la-arrows-alt-h" /> {t('Limit')}</span>
                                        <span className="wd-sum-val gateway-limit">{method.minLabel} – {method.maxLabel}</span>
                                    </div>
                                    <div className="wd-sum-row">
                                        <span className="wd-sum-label">
                                            <i className="las la-percentage" /> {t('Processing Fee')}
                                            <span
                                                className="proccessing-fee-info"
                                                title={`${fixed(method.percentCharge)}% ${t('with')} ${fixed(method.fixedCharge)} ${curText} ${t('charge for processing fees')}`}
                                            >
                                                {' '}<i className="las la-info-circle" />
                                            </span>
                                        </span>
                                        <span className="wd-sum-val wd-sum-charge">{curSym}<span className="processing-fee">{fixed(quote.charge)}</span> {curText}</span>
                                    </div>
                                    <div className="wd-sum-row wd-sum-total">
                                        <span className="wd-sum-label"><i className="las la-hand-holding-usd" /> {t('You Receive')}</span>
                                        <span className="wd-sum-val wd-sum-net">{curSym}<span className="final-amount">{fixed(quote.receive)}</span> {curText}</span>
                                    </div>
                                    {showConversion && (
                                        <>
                                            <div className="wd-sum-row gateway-conversion">
                                                <span className="wd-sum-label"><i className="las la-exchange-alt" /> {t('Conversion')}</span>
                                                <span className="wd-sum-val">1 {curText} = <span className="rate">{fixed(method.rate)}</span> <span className="method_currency">{method.currency}</span></span>
                                            </div>
                                            <div className="wd-sum-row conversion-currency">
                                                <span className="wd-sum-label">{t('In')} {method.currency}</span>
                                                <span className="wd-sum-val"><span className="in-currency">{fixed(quote.inCurrency)}</span></span>
                                            </div>
                                        </>
                                    )}
                                </div>
                            )}

                            <button type="submit" className="wd-submit-btn" disabled={!withinLimit || form.processing}>
                                <i className="las la-paper-plane" /> {form.processing ? t('Processing...') : t('Confirm Withdrawal')}
                            </button>
                            <p className="wd-info-text">
                                <i className="las la-shield-alt" /> {t('Safely withdraw your funds using our highly secure process.')}
                            </p>
                        </div>
                    </div>
                </div>
            </form>
        </>
    );
}

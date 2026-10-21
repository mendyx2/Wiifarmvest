import { Head, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import Modal from '@/Components/Modal';
import { useCopy } from '@/lib/clipboard';
import { useTrans } from '@/lib/i18n';
import { depositQuote, fixed, parseAmount } from '@/lib/money';
import type { PageProps } from '@/types';

type Gateway = {
    code: string;
    currency: string;
    name: string;
    image: string;
    minAmount: number;
    maxAmount: number;
    minLabel: string;
    maxLabel: string;
    percentCharge: number;
    fixedCharge: number;
    rate: number;
    crypto: boolean;
};

type Props = PageProps<{
    gateways: Gateway[];
    curSym: string;
    curText: string;
    quickAmounts: number[];
    companyBank: { bankName: string; accountName: string; accountNumber: string };
    insertUrl: string;
    historyUrl: string;
}>;

/** A gateway can be offered in several currencies, so code + currency identify it. */
const gatewayId = (g: Gateway) => `${g.code}:${g.currency}`;
const VISIBLE_GATEWAYS = 5;

export default function Deposit({ gateways, curSym, curText, quickAmounts, companyBank, insertUrl, historyUrl }: Props) {
    const t = useTrans();
    const [selectedId, setSelectedId] = useState(gateways[0] ? gatewayId(gateways[0]) : '');
    const [showAll, setShowAll] = useState(false);
    const [bankOpen, setBankOpen] = useState(false);
    const { copied, copy } = useCopy(1800);
    const [copiedField, setCopiedField] = useState('');
    const form = useForm({ amount: '', gateway: gateways[0]?.code ?? '', currency: gateways[0]?.currency ?? '' });

    const gateway = gateways.find((g) => gatewayId(g) === selectedId);
    const amount = parseAmount(form.data.amount);
    const quote = gateway ? depositQuote(amount, gateway) : null;
    const withinLimit = gateway !== undefined && amount >= gateway.minAmount && amount <= gateway.maxAmount;
    const showConversion = gateway !== undefined && gateway.currency !== curText && !gateway.crypto;

    function choose(g: Gateway) {
        setSelectedId(gatewayId(g));
        form.setData((data) => ({ ...data, gateway: g.code, currency: g.currency }));
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        if (!withinLimit) return;
        // Success leaves this page: the server sends the browser to the gateway's confirm page.
        form.post(insertUrl, { preserveScroll: true });
    }

    async function copyBankField(field: string, value: string) {
        if (await copy(value)) setCopiedField(field);
    }

    const bankRows = [
        { field: 'bank', icon: 'la-landmark', label: t('Bank Name'), value: companyBank.bankName },
        { field: 'name', icon: 'la-user-circle', label: t('Account Name'), value: companyBank.accountName },
        { field: 'number', icon: 'la-hashtag', label: t('Account Number'), value: companyBank.accountNumber },
    ];

    return (
        <div className="dep-page">
            <Head title={t('Deposit Methods')} />

            <div className="dep-topbar">
                <div className="dep-topbar-left">
                    <div className="dep-topbar-icon"><i className="las la-wallet" /></div>
                    <div>
                        <h6 className="dep-topbar-title">{t('Fund Your Wallet')}</h6>
                        <p className="dep-topbar-sub">{t('Fast, secure deposits to your account')}</p>
                    </div>
                </div>
                <a href={historyUrl} className="dep-hist-btn">
                    <i className="las la-list-alt" /> {t('History')}
                </a>
            </div>

            <div className="dep-hero">
                <div className="dep-hero-bg" />
                <div className="dep-hero-content">
                    <div className="dep-hero-badge"><i className="las la-shield-alt" /> {t('100% Secure')}</div>
                    <h3 className="dep-hero-heading">{t('Credit Money Box')}</h3>
                    <div className="dep-hero-pills">
                        <span className="dep-hero-pill"><i className="las la-bolt" /> {t('Instant')}</span>
                        <span className="dep-hero-pill"><i className="las la-lock" /> {t('Encrypted')}</span>
                        <span className="dep-hero-pill"><i className="las la-headset" /> {t('24/7 Support')}</span>
                    </div>
                </div>
            </div>

            <form className="deposit-form" onSubmit={submit}>
                <div className="dep-grid">
                    <div className="dep-card dep-gw-card">
                        <div className="dep-card-head">
                            <div className="dep-card-head-icon gw"><i className="las la-credit-card" /></div>
                            <div>
                                <h6 className="dep-card-title">{t('Payment Method')}</h6>
                                <p className="dep-card-sub">{t("Select how you'd like to pay")}</p>
                            </div>
                        </div>

                        <button type="button" className="dep-acct-btn" onClick={() => setBankOpen(true)}>
                            <span className="dep-acct-btn-inner">
                                <i className="las la-university" />
                                <span>{t('Click to View Account Details')}</span>
                            </span>
                            <i className="las la-chevron-right dep-acct-btn-arrow" />
                        </button>

                        <div className="dep-gw-list payment-system-list is-scrollable gateway-option-list" role="radiogroup" aria-label={t('Payment Method')}>
                            {gateways.map((g, i) => (
                                <label key={gatewayId(g)} className={`dep-gw-item gateway-option ${i >= VISIBLE_GATEWAYS && !showAll ? 'd-none' : ''}`}>
                                    <div className="dep-gw-item-left">
                                        <div className="dep-gw-radio-wrap"><span className="dep-gw-radio payment-item__check" /></div>
                                        <span className="dep-gw-name">{g.name}</span>
                                    </div>
                                    <div className="dep-gw-logo"><img src={g.image} alt={g.name} /></div>
                                    {/* Visually hidden but focusable, so arrow keys still switch method. */}
                                    <input
                                        className="payment-item__radio gateway-input visually-hidden"
                                        type="radio"
                                        name="gateway"
                                        value={g.code}
                                        checked={selectedId === gatewayId(g)}
                                        onChange={() => choose(g)}
                                    />
                                </label>
                            ))}
                            {/* Blade showed this button from 5 gateways, when there was nothing more to show. */}
                            {gateways.length > VISIBLE_GATEWAYS && !showAll && (
                                <button type="button" className="dep-more-btn more-gateway-option" onClick={() => setShowAll(true)}>
                                    <i className="las la-th-list" />
                                    <span className="payment-item__btn-text">{t('Show All Payment Options')}</span>
                                    <i className="las la-chevron-down" />
                                </button>
                            )}
                        </div>

                        {gateway?.crypto && (
                            <div className="dep-crypto-msg crypto-message">
                                <i className="las la-info-circle" /> {t('Conversion with')} <strong>{gateway.currency}</strong> {t('— final value shown on next step.')}
                            </div>
                        )}
                        {form.errors.gateway && <small className="d-block mt-2" style={{ color: '#DC2626' }}>{form.errors.gateway}</small>}
                    </div>

                    <div className="dep-card dep-amount-card">
                        <div className="dep-card-head">
                            <div className="dep-card-head-icon amt"><i className="las la-coins" /></div>
                            <div>
                                <h6 className="dep-card-title">{t('Enter Amount')}</h6>
                                <p className="dep-card-sub">{t('Type the amount you wish to deposit')}</p>
                            </div>
                        </div>

                        <div className="dep-amount-wrap">
                            <span className="dep-cur-sym">{curSym}</span>
                            <input
                                type="text"
                                inputMode="decimal"
                                className="dep-amount-input amount"
                                name="amount"
                                aria-label={t('Amount')}
                                placeholder="0.00"
                                autoComplete="off"
                                value={form.data.amount}
                                onChange={(e) => form.setData('amount', e.target.value)}
                            />
                            <span className="dep-cur-text">{curText}</span>
                        </div>
                        {form.errors.amount && <small className="d-block mb-2" style={{ color: '#DC2626' }}>{form.errors.amount}</small>}

                        <div className="dep-quick-amounts">
                            {quickAmounts.map((quick) => (
                                <button
                                    type="button"
                                    key={quick}
                                    className={`dep-quick-btn ${amount === quick ? 'active' : ''}`}
                                    onClick={() => form.setData('amount', String(quick))}
                                >
                                    {curSym}{quick.toLocaleString('en-US')}
                                </button>
                            ))}
                        </div>

                        {gateway && quote && (
                            <div className="dep-summary" aria-live="polite">
                                <div className="dep-summary-row">
                                    <span className="dep-summary-label"><i className="las la-arrows-alt-h" /> {t('Limit')}</span>
                                    <span className="dep-summary-val gateway-limit">{gateway.minLabel} – {gateway.maxLabel}</span>
                                </div>
                                <div className="dep-summary-row">
                                    <span className="dep-summary-label">
                                        <i className="las la-percentage" /> {t('Processing Charge')}
                                        <span
                                            className="dep-fee-info"
                                            title={`${fixed(gateway.percentCharge)}% + ${fixed(gateway.fixedCharge)} ${curText} ${t('gateway fee')}`}
                                        >
                                            {' '}<i className="las la-info-circle" />
                                        </span>
                                    </span>
                                    <span className="dep-summary-val dep-charge processing-fee">{fixed(quote.charge)}</span>
                                </div>
                                <div className="dep-summary-divider" />
                                <div className="dep-summary-row dep-summary-total total-amount">
                                    <span className="dep-summary-label">{t('Total')}</span>
                                    <span className="dep-summary-val dep-total-val">
                                        <span className="final-amount">{fixed(quote.total)}</span> <small>{curText}</small>
                                    </span>
                                </div>
                                {showConversion && (
                                    <>
                                        <div className="dep-summary-row gateway-conversion total-amount">
                                            <span className="dep-summary-label"><i className="las la-exchange-alt" /> {t('Rate')}</span>
                                            <span className="dep-summary-val">1 {curText} = <strong>{fixed(gateway.rate)} {gateway.currency}</strong></span>
                                        </div>
                                        <div className="dep-summary-row conversion-currency total-amount">
                                            <span className="dep-summary-label">{t('In')} {gateway.currency}</span>
                                            <span className="dep-summary-val dep-conv-val">{fixed(quote.inCurrency)}</span>
                                        </div>
                                    </>
                                )}
                            </div>
                        )}

                        <button type="submit" className="dep-submit-btn" disabled={!withinLimit || form.processing}>
                            <i className="las la-lock" /> {form.processing ? t('Processing...') : t('Confirm Deposit')}
                        </button>

                        <p className="dep-secure-note">
                            <i className="las la-shield-alt" />{' '}
                            {t('Ensuring your funds grow safely through our secure deposit process with world-class payment options.')}
                        </p>
                    </div>
                </div>
            </form>

            <Modal open={bankOpen} onClose={() => setBankOpen(false)} contentClassName="bdm-box" labelledBy="bdm-title">
                <div className="bdm-header">
                    <div className="bdm-header-icon"><i className="las la-university" /></div>
                    <div>
                        <h5 className="bdm-title" id="bdm-title">{t('Company Bank Details')}</h5>
                        <p className="bdm-subtitle">{t('Transfer funds to the account below')}</p>
                    </div>
                    <button type="button" className="bdm-close" aria-label={t('Close')} onClick={() => setBankOpen(false)}>
                        <i className="las la-times" />
                    </button>
                </div>
                <div className="bdm-body">
                    {bankRows.map((row) => {
                        const isCopied = copied && copiedField === row.field;
                        return (
                            <div className={`bdm-field ${row.field === 'number' ? 'bdm-field-acno' : ''}`} key={row.field}>
                                <span className="bdm-field-label"><i className={`las ${row.icon}`} /> {row.label}</span>
                                <div className="bdm-field-val-wrap">
                                    <span className={`bdm-field-val ${row.field === 'number' ? 'bdm-acno' : ''}`}>{row.value}</span>
                                    <button
                                        type="button"
                                        className={`bdm-copy-btn ${isCopied ? 'copied' : ''}`}
                                        title={t('Copy')}
                                        aria-label={`${t('Copy')} ${row.label}`}
                                        onClick={() => copyBankField(row.field, row.value)}
                                    >
                                        <i className={`las ${isCopied ? 'la-check' : 'la-copy'}`} />
                                    </button>
                                </div>
                            </div>
                        );
                    })}
                </div>
                <div className="bdm-note">
                    <i className="las la-info-circle" /> {t('After transfer, submit your deposit and upload proof of payment if required.')}
                </div>
                <button type="button" className="bdm-done-btn" onClick={() => setBankOpen(false)}>
                    <i className="las la-check-circle" /> {t('Got it')}
                </button>
            </Modal>
        </div>
    );
}

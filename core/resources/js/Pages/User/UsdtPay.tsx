import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';
import { useCopy } from '@/lib/clipboard';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';
import './UsdtPay.css';

type Status = 'waiting' | 'expired' | 'review' | 'paid' | 'rejected';

type Props = PageProps<{
    deposit: {
        trx: string;
        /** What the member gets, in the site currency */
        amount: string;
        /** The exact USDT to send, e.g. "64.37" */
        usdt: string;
        address: string;
        qr: string;
        expiresAt: string;
        canClaim: boolean;
        feedback: string | null;
    };
    status: Status;
    /** Seconds between checks for the payment while the page is open */
    checkEvery: number;
    testNetwork: boolean;
    urls: { claim: string; history: string; deposit: string };
}>;

function left(expiresAt: string, now: number): string {
    const s = Math.max(0, Math.floor((new Date(expiresAt).getTime() - now) / 1000));
    return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
}

/**
 * Pay a deposit in USDT (TRC20) to the Wiifarm address (User\UsdtDepositController).
 * The exact amount is what tells this deposit's payment apart from everyone
 * else's, so the page insists on it. While it is open the server looks for
 * the payment every few seconds; the transaction-ID box is the fallback.
 */
export default function UsdtPay({ deposit, status, checkEvery, testNetwork, urls }: Props) {
    const t = useTrans();
    const form = useForm({ txid: '' });
    const [now, setNow] = useState(() => Date.now());
    const amountCopy = useCopy(2000);
    const addressCopy = useCopy(2000);

    // The clock for the countdown
    useEffect(() => {
        const id = window.setInterval(() => setNow(Date.now()), 1000);
        return () => window.clearInterval(id);
    }, []);

    // Ask the server to look for the payment while we wait for it
    useEffect(() => {
        if (status !== 'waiting') return;
        const id = window.setInterval(() => router.reload({ only: ['status', 'deposit'] }), Math.max(5, checkEvery) * 1000);
        return () => window.clearInterval(id);
    }, [status, checkEvery]);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({ txid: d.txid.trim() }));
        form.post(urls.claim, { preserveScroll: true });
    };

    const txidOk = /^[0-9a-fA-F]{64}$/.test(form.data.txid.trim());

    return (
        <div className="dep-page usdt-page">
            <Head title={t('Pay with USDT')} />

            <div className="dep-topbar">
                <div className="dep-topbar-left">
                    <div className="dep-topbar-icon man">
                        <i className="las la-coins" />
                    </div>
                    <div>
                        <h6 className="dep-topbar-title">{t('Pay with USDT (TRC20)')}</h6>
                        <p className="dep-topbar-sub">
                            {t('Deposit')} {deposit.trx}
                        </p>
                    </div>
                </div>
                <a href={urls.history} className="dep-hist-btn">
                    <i className="las la-list-alt" /> {t('History')}
                </a>
            </div>

            {testNetwork && (
                <div className="alert alert-warning" role="note">
                    {t('Test network: send test USDT on the Tron Nile network only.')}
                </div>
            )}

            {status === 'paid' && (
                <div className="usdt-state usdt-ok" role="status">
                    <i className="las la-check-circle" aria-hidden />
                    <div>
                        <strong>{t('Payment received')}</strong>
                        <p>{t(':amount has been added to your balance.', { amount: deposit.amount })}</p>
                        <a href={urls.history}>{t('See your deposit history')}</a>
                    </div>
                </div>
            )}
            {status === 'review' && (
                <div className="usdt-state usdt-wait" role="status">
                    <i className="las la-user-clock" aria-hidden />
                    <div>
                        <strong>{t('An admin is checking your payment')}</strong>
                        <p>{t('It did not match this deposit exactly (amount or time). You will be notified once it is checked.')}</p>
                    </div>
                </div>
            )}
            {status === 'rejected' && (
                <div className="usdt-state usdt-bad" role="status">
                    <i className="las la-times-circle" aria-hidden />
                    <div>
                        <strong>{t('This deposit was rejected')}</strong>
                        {deposit.feedback && <p>{deposit.feedback}</p>}
                    </div>
                </div>
            )}

            {(status === 'waiting' || status === 'expired') && (
                <div className="usdt-layout">
                    <div className="dep-card usdt-pay-card">
                        <p className="usdt-label">{t('Send exactly')}</p>
                        <div className="usdt-amount">
                            <span>{deposit.usdt}</span> <small>USDT</small>
                            <button type="button" className="usdt-copy" onClick={() => amountCopy.copy(deposit.usdt)} aria-label={t('Copy amount')}>
                                <i className={`las ${amountCopy.copied ? 'la-check' : 'la-copy'}`} aria-hidden /> {amountCopy.copied ? t('Copied') : t('Copy')}
                            </button>
                        </div>
                        <p className="usdt-for">{t('You will receive :amount', { amount: deposit.amount })}</p>

                        <p className="usdt-label mt-3">{t('To this address (TRC20 only)')}</p>
                        <div className="usdt-address">
                            <code>{deposit.address}</code>
                            <button type="button" className="usdt-copy" onClick={() => addressCopy.copy(deposit.address)} aria-label={t('Copy address')}>
                                <i className={`las ${addressCopy.copied ? 'la-check' : 'la-copy'}`} aria-hidden /> {addressCopy.copied ? t('Copied') : t('Copy')}
                            </button>
                        </div>
                        <img className="usdt-qr" src={deposit.qr} alt={t('QR code of the address')} width={180} height={180} />

                        {status === 'waiting' ? (
                            <p className="usdt-timer" role="timer">
                                <i className="las la-hourglass-half" aria-hidden /> {t('Pay within :time', { time: left(deposit.expiresAt, now) })}
                                <span className="usdt-checking">
                                    <i className="las la-sync la-spin" aria-hidden /> {t('Waiting for your payment, we check automatically')}
                                </span>
                            </p>
                        ) : (
                            <p className="usdt-timer usdt-late" role="status">
                                {t('The time to pay has passed. If you already paid, enter your transaction ID below. Otherwise, please start a new deposit.')} <a href={urls.deposit}>{t('New deposit')}</a>
                            </p>
                        )}
                    </div>

                    <div className="dep-card usdt-rules">
                        <h6>{t('Before you send')}</h6>
                        <ul>
                            <li>{t('Send USDT on the TRON network (TRC20). Other networks (ERC20, BEP20) or other coins will be lost.')}</li>
                            <li>{t('Send the exact amount above, including the cents. It is how we know the payment is yours.')}</li>
                            <li>{t('If your exchange takes a fee, make sure the amount that arrives is exactly this amount.')}</li>
                            <li>{t('Your balance updates about a minute after the payment, once the network confirms it.')}</li>
                        </ul>

                        {deposit.canClaim && (
                            <form onSubmit={submit} className="mt-3">
                                <label htmlFor="usdt-txid" className="form-label">
                                    {t('Already paid? Enter the transaction ID (TXID)')}
                                </label>
                                <input
                                    id="usdt-txid"
                                    className={`form-control${form.errors.txid ? ' is-invalid' : ''}`}
                                    value={form.data.txid}
                                    onChange={(e) => form.setData('txid', e.target.value)}
                                    placeholder={t('64 letters and numbers')}
                                    autoComplete="off"
                                    spellCheck={false}
                                    aria-invalid={form.errors.txid ? true : undefined}
                                    aria-describedby={form.errors.txid ? 'usdt-txid-error' : 'usdt-txid-help'}
                                />
                                {form.errors.txid ? (
                                    <div className="invalid-feedback d-block" id="usdt-txid-error">
                                        {form.errors.txid}
                                    </div>
                                ) : (
                                    <small className="text-muted" id="usdt-txid-help">
                                        {t('You find it in your wallet or exchange, in the details of the withdrawal.')}
                                    </small>
                                )}
                                <button type="submit" className="dep-submit-btn man-submit-btn usdt-submit" disabled={form.processing || !txidOk}>
                                    {form.processing ? <i className="las la-spinner la-spin" aria-label={t('Checking')} /> : t('Check my payment')}
                                </button>
                            </form>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}

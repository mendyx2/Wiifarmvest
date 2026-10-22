import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type ClipboardEvent, type KeyboardEvent } from 'react';
import Modal from '@/Components/Modal';
import Pagination from '@/Components/Pagination';
import { useTrans } from '@/lib/i18n';
import type { PageProps, Paginated } from '@/types';

type Package = { code: string; amount: string; source: string; username: string; fullname: string };
type HistoryRow = { id: number; fullname: string; date: string; time: string; amount: string; source: string };

type Props = PageProps<{
    totalRedeemed: string;
    history: Paginated<HistoryRow>;
    code: string;
    package: Package | null;
    error: string | null;
    redeemed: string | null;
    urls: { index: string; redeem: string };
}>;

const LEN = 10;
const clean = (v: string) => v.replace(/[^A-Za-z0-9]/g, '').toUpperCase();

export default function WelcomePack({ totalRedeemed, history, code, package: pkg, error, redeemed, urls }: Props) {
    const t = useTrans();
    const { errors } = usePage().props as { errors?: Record<string, string> };
    const [chars, setChars] = useState<string[]>(() => Array.from({ length: LEN }, (_, i) => (pkg ? '' : code)[i] ?? ''));
    const [busy, setBusy] = useState(false);
    const [problem, setProblem] = useState<string | null>(error ?? errors?.code ?? null);
    const [success, setSuccess] = useState<string | null>(redeemed);
    const boxes = useRef<(HTMLInputElement | null)[]>([]);

    useEffect(() => setProblem(error ?? errors?.code ?? null), [error, errors?.code]);
    useEffect(() => setSuccess(redeemed), [redeemed]);
    useEffect(() => {
        if (!pkg) setChars(Array.from({ length: LEN }, () => ''));
    }, [pkg]);

    const value = chars.join('');
    const fill = (start: number, text: string) => {
        const next = [...chars];
        clean(text).split('').forEach((ch, i) => {
            if (start + i < LEN) next[start + i] = ch;
        });
        setChars(next);
        boxes.current[Math.min(start + clean(text).length, LEN - 1)]?.focus();
    };

    const onKey = (i: number) => (e: KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'Backspace') {
            e.preventDefault();
            const next = [...chars];
            if (next[i]) next[i] = '';
            else if (i > 0) {
                next[i - 1] = '';
                boxes.current[i - 1]?.focus();
            }
            setChars(next);
        }
        if (e.key === 'ArrowLeft' && i > 0) boxes.current[i - 1]?.focus();
        if (e.key === 'ArrowRight' && i < LEN - 1) boxes.current[i + 1]?.focus();
        if (e.key === 'Enter') verify();
    };
    const onPaste = (i: number) => (e: ClipboardEvent<HTMLInputElement>) => {
        e.preventDefault();
        fill(i, e.clipboardData.getData('text'));
    };

    const verify = () => {
        if (value.length !== LEN) return;
        router.get(urls.index, { code: value }, { preserveState: true, preserveScroll: true, onStart: () => setBusy(true), onFinish: () => setBusy(false) });
    };
    const reset = () => router.get(urls.index, {}, { preserveScroll: true });
    const redeem = () => {
        if (!pkg || busy) return;
        router.post(urls.redeem, { code: pkg.code }, { preserveScroll: true, onStart: () => setBusy(true), onFinish: () => setBusy(false) });
    };

    return (
        <div className="nc-wrap" id="ncWrap">
            <Head title={t('Welcome Package Redemption')} />
            <br />
            <div className="rwp-page">
                <div className="rwp-stats">
                    <div className="rwp-stat">
                        <div className="rwp-stat-icon green"><i className="las la-coins" /></div>
                        <div><p className="rwp-stat-label">{t('Total Redeemed')}</p><p className="rwp-stat-value">{totalRedeemed}</p></div>
                    </div>
                    <div className="rwp-stat">
                        <div className="rwp-stat-icon amber"><i className="las la-list-alt" /></div>
                        <div><p className="rwp-stat-label">{t('Packages Processed')}</p><p className="rwp-stat-value">{history.total}</p></div>
                    </div>
                </div>

                <div className="rwp-scanner-card">
                    <div className="rwp-scanner-bg-circle rwp-bg-c1" />
                    <div className="rwp-scanner-bg-circle rwp-bg-c2" />
                    <div className="rwp-scanner-bg-circle rwp-bg-c3" />
                    <div className="rwp-scanner-grid" />
                    <div className="rwp-scanner-header">
                        <div style={{ display: 'flex', alignItems: 'flex-start', gap: 18, flex: 1, minWidth: 0 }}>
                            <div className="rwp-scanner-icon-wrap">
                                <div className="rwp-scanner-icon-bg"><i className="las la-gift" /></div>
                                <div className="rwp-icon-ring" />
                                <div className="rwp-icon-ring-2" />
                            </div>
                            <div className="rwp-scanner-title-block">
                                <p className="rwp-scanner-label"><i className="las la-shield-alt" /> &nbsp;{t('Authorized Redemption Terminal')}</p>
                                <h3 className="rwp-scanner-title">{t('Gift Code Scanner')}</h3>
                                <p className="rwp-scanner-sub">{t("Enter the customer's 10-character welcome package code below to verify and redeem their gift in person.")}</p>
                            </div>
                        </div>
                        <div className="rwp-scanner-total-badge">
                            <p className="rwp-total-label">{t('Total Earned')}</p>
                            <p className="rwp-total-value">{totalRedeemed}</p>
                        </div>
                    </div>

                    {!pkg && (
                        <div className="rwp-code-entry">
                            <p className="rwp-code-label"><i className="las la-keyboard" style={{ fontSize: '1rem', marginRight: 3 }} /> {t('Enter Redemption Code')}</p>
                            <div className="rwp-otp-wrap">
                                {chars.map((ch, i) => (
                                    <span key={i} style={{ display: 'contents' }}>
                                        {i === 5 && <div className="rwp-otp-sep">—</div>}
                                        <input
                                            ref={(el) => {
                                                boxes.current[i] = el;
                                            }}
                                            className={`rwp-otp-box ${ch ? 'rwp-otp-filled' : ''}`}
                                            maxLength={1}
                                            autoComplete="off"
                                            inputMode="text"
                                            aria-label={t('Code character :n', { n: i + 1 })}
                                            value={ch}
                                            onChange={(e) => fill(i, e.target.value.slice(-1))}
                                            onKeyDown={onKey(i)}
                                            onPaste={onPaste(i)}
                                        />
                                    </span>
                                ))}
                            </div>
                            <div className="rwp-mobile-card">
                                <div className="rwp-mobile-card-inner">
                                    <div className="rwp-mobile-card-icon"><i className="las la-gift" /></div>
                                    <div className="rwp-mobile-card-body">
                                        <label className="rwp-mobile-card-label" htmlFor="codeSingleInput">{t('Enter Redemption Code')}</label>
                                        <input
                                            id="codeSingleInput"
                                            className="rwp-mobile-input"
                                            placeholder="XXXXXXXXXX"
                                            autoComplete="off"
                                            spellCheck={false}
                                            value={value}
                                            onChange={(e) => setChars(Array.from({ length: LEN }, (_, i) => clean(e.target.value)[i] ?? ''))}
                                            onKeyDown={(e) => e.key === 'Enter' && verify()}
                                        />
                                        <p className="rwp-mobile-card-hint">{t('10 characters • letters & numbers')}</p>
                                    </div>
                                </div>
                            </div>
                            <button type="button" className="rwp-verify-btn" disabled={value.length !== LEN || busy} onClick={verify}>
                                <span className="rwp-verify-btn-icon"><i className={`las ${busy ? 'la-spinner la-spin' : 'la-search-plus'}`} /></span>
                                <span>{busy ? t('Verifying…') : t('Verify Code')}</span>
                            </button>
                            <div className="rwp-tips-row">
                                <span className="rwp-tip"><i className="las la-keyboard" /> {t('Type or paste the code')}</span>
                                <span className="rwp-tip"><i className="las la-undo" /> {t('Backspace to correct')}</span>
                            </div>
                        </div>
                    )}
                </div>

                {pkg && (
                    <div className="rwp-reveal-outer">
                        <div className="rwp-reveal-card">
                            <div className="rwp-reveal-header">
                                <div className="rwp-reveal-check-badge"><i className="las la-check" /></div>
                                <div className="rwp-reveal-header-text">
                                    <h6>{t('Code Verified')}</h6>
                                    <p>{t('Customer identified — confirm to complete redemption')}</p>
                                </div>
                                <div className="rwp-reveal-header-code">{pkg.code}</div>
                            </div>
                            <div className="rwp-reveal-body">
                                <div className="rwp-customer-row">
                                    <div style={{ position: 'relative' }}>
                                        <div className="rwp-customer-avatar">{(pkg.fullname || pkg.username || '?').charAt(0).toUpperCase()}</div>
                                        <span className="rwp-avatar-tick"><i className="las la-check" /></span>
                                    </div>
                                    <div className="rwp-customer-info">
                                        <p className="rwp-customer-name">{pkg.fullname || '—'}</p>
                                        <p className="rwp-customer-username">@{pkg.username}</p>
                                        <span className="rwp-source-badge">{t(pkg.source.charAt(0).toUpperCase() + pkg.source.slice(1))}</span>
                                    </div>
                                </div>
                                <div className="rwp-value-box">
                                    <p className="rwp-value-label"><i className="las la-gift" /> &nbsp;{t('Welcome Package Value')}</p>
                                    <p className="rwp-value-amount">{pkg.amount}</p>
                                    <p className="rwp-value-sub">{t('Will be credited to your stockist wallet upon confirmation')}</p>
                                </div>
                                <div className="rwp-reveal-actions">
                                    <button type="button" className="rwp-cancel-btn" onClick={reset} disabled={busy}><i className="las la-times" /> {t('Check Another')}</button>
                                    <button type="button" className="rwp-confirm-btn" onClick={redeem} disabled={busy}>
                                        <i className={`las ${busy ? 'la-spinner la-spin' : 'la-check-circle'}`} /> {busy ? t('Processing...') : t('Confirm & Redeem Package')}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                <div className="rwp-history-card">
                    <div className="rwp-history-head">
                        <h6 className="rwp-history-title"><i className="las la-history" /> {t('Your Redemption History')}</h6>
                        {history.total > 0 && <span className="rwp-count-pill">{t(':count total', { count: history.total })}</span>}
                    </div>
                    {history.data.length === 0 ? (
                        <div className="sl-empty-state" style={{ padding: '52px 24px' }}>
                            <div className="sl-empty-icon" style={{ fontSize: '3rem', color: '#D1D5DB', marginBottom: 14 }}><i className="las la-gift" /></div>
                            <p className="sl-empty-title" style={{ fontWeight: 700, color: '#374151', fontSize: '.95rem', marginBottom: 6 }}>{t('No Redemptions Yet')}</p>
                            <p className="sl-empty-sub" style={{ fontSize: '.82rem', color: '#9CA3AF', maxWidth: 280, margin: '0 auto' }}>{t('Welcome packages you redeem for customers will appear here.')}</p>
                        </div>
                    ) : (
                        <>
                            <div className="rwp-history-grid">
                                {history.data.map((row) => (
                                    <div className="rwp-hcard" key={row.id}>
                                        <div className="rwp-hcard-avatar">{row.fullname.charAt(0).toUpperCase()}</div>
                                        <div className="rwp-hcard-info">
                                            <p className="rwp-hcard-name">{row.fullname}</p>
                                            <p className="rwp-hcard-meta"><i className="las la-calendar-alt" /> {row.date} &bull; {row.time}</p>
                                        </div>
                                        <div className="rwp-hcard-right">
                                            <span className="rwp-hcard-amount">{row.amount}</span>
                                            <span className={`rwp-hcard-source ${row.source}`}>{t(row.source.charAt(0).toUpperCase() + row.source.slice(1))}</span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                            <div style={{ padding: '14px 22px', borderTop: '1px solid #F3F4F6' }}><Pagination links={history.links} /></div>
                        </>
                    )}
                </div>
            </div>

            <Modal open={success !== null} onClose={() => setSuccess(null)} labelledBy="wp-success-title" contentClassName="rwp-modal-content" contentStyle={{ maxWidth: 420, margin: '0 auto' }}>
                <div className="rwp-modal-header-success">
                    <div className="rwp-modal-icon-circle"><i className="las la-check-double" /></div>
                    <div className="rwp-confetti" aria-hidden="true">🎁 🎉 ✨ 🎊 🎁</div>
                    <h5 id="wp-success-title">{t('Package Redeemed!')}</h5>
                </div>
                <div className="rwp-modal-body">
                    <p>{success}</p>
                    <button type="button" className="rwp-modal-btn" onClick={() => setSuccess(null)}>{t('Continue Scanning')}</button>
                </div>
            </Modal>

            <Modal open={problem !== null} onClose={() => setProblem(null)} labelledBy="wp-error-title" contentClassName="rwp-modal-content" contentStyle={{ maxWidth: 420, margin: '0 auto' }}>
                <div className="rwp-modal-header-error">
                    <div className="rwp-modal-icon-circle"><i className="las la-exclamation-triangle" /></div>
                    <h5 id="wp-error-title">{t('Cannot Redeem')}</h5>
                </div>
                <div className="rwp-modal-body">
                    <p>{problem}</p>
                    <button type="button" className="rwp-modal-btn" style={{ background: 'linear-gradient(135deg,#D97706,#F59E0B)' }} onClick={() => setProblem(null)}>{t('Understood')}</button>
                </div>
            </Modal>
        </div>
    );
}

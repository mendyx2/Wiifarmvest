import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState, type FormEvent, type ReactNode } from 'react';
import Pagination from '@/Components/Pagination';
import { useTrans } from '@/lib/i18n';
import type { PageProps, Paginated } from '@/types';
import './BonusTransfer.css';

type Wallet = {
    field: string;
    label: string;
    icon: string;
    kind: 'bonus' | 'autoship' | 'acb';
    balance: string;
    /** Balance as a number, for the amount box */
    amount: number;
    /** Not transferable right now (autoship before its monthly PV) */
    locked: boolean;
};

type Props = PageProps<{
    moneyBox: string;
    totalTransferable: string;
    canTransferAll: boolean;
    wallets: Wallet[];
    summary: { label: string; balance: string; skipped: boolean }[];
    autoship: { eligible: boolean; pv: number; minPv: number; month: string; balance: string; order: { invoice: string; date: string | null } | null };
    welcomePackages: { code: string; title: string; amount: string; redeemed: boolean; redeemedAt: string | null }[];
    transfers: Paginated<{ id: number; source: string; amount: string; status: string; date: string | null; time: string | null }>;
    urls: { transfer: string; transferAll: string; shop: string };
}>;

const fmt = (n: number) => n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export default function BonusTransfer({ moneyBox, totalTransferable, canTransferAll, wallets, summary, autoship, welcomePackages, transfers, urls }: Props) {
    const t = useTrans();
    const [picked, setPicked] = useState<Wallet | null>(null);
    const [allOpen, setAllOpen] = useState(false);
    const [autoshipOpen, setAutoshipOpen] = useState(false);
    const anyAutoshipSkipped = summary.some((s) => s.skipped);

    return (
        <div className="page-bonus-transfer nc-wrap">
            <Head title={t('Bonus Transfer')} />
            <br />

            <div className="bt-hero">
                <div className="bt-hero-grid">
                    <div className="bt-hero-stat">
                        <p className="bt-hero-stat-label">{t('Total Transferable')}</p>
                        <p className="bt-hero-stat-value">{totalTransferable}</p>
                    </div>
                    <div className="bt-hero-divider" />
                    <div className="bt-hero-stat">
                        <p className="bt-hero-stat-label">{t('Money Box Balance')}</p>
                        <p className="bt-hero-stat-value">{moneyBox}</p>
                    </div>
                    <div className="bt-hero-cta">
                        {canTransferAll ? (
                            <button type="button" className="bt-btn-all" onClick={() => setAllOpen(true)}>
                                {t('Transfer All')} <i className="las la-arrow-right" />
                            </button>
                        ) : (
                            <span className="bt-hero-empty">{t('No balance available')}</span>
                        )}
                    </div>
                </div>
            </div>

            <div className="bt-card">
                <div className="bt-card-head">
                    <p className="bt-card-title">
                        <i className="las la-coins" /> {t('Bonus Wallets')}
                    </p>
                    <p className="bt-card-subtitle">{t('Select a bonus to transfer it to your Money Box')}</p>
                </div>
                <div className="bt-list">
                    {wallets.length === 0 && (
                        <div className="bt-empty">
                            <i className="las la-coins" />
                            <p>{t('None of your bonus wallets has a balance right now.')}</p>
                        </div>
                    )}
                    {wallets.map((w) => (
                        <WalletRow key={w.field} wallet={w} autoship={autoship} onTransfer={() => setPicked(w)} onCheck={() => setAutoshipOpen(true)} />
                    ))}

                    {welcomePackages.length > 0 && (
                        <div className="wp-section">
                            <div className="wp-section-label">
                                <i className="las la-gift" /> {t('Welcome Packages')} <span className="wp-count-badge">{welcomePackages.length}</span>
                            </div>
                            {welcomePackages.map((wp) => (
                                <div key={wp.code} className={`wp-gift-card${wp.redeemed ? ' wp-gift-card--redeemed' : ''}`}>
                                    <div className="wp-ribbon-h" />
                                    <div className="wp-ribbon-v" />
                                    <div className="wp-gift-inner">
                                        <div className="wp-gift-top">
                                            <div className="wp-gift-icon-wrap">
                                                <i className="las la-gift" />
                                            </div>
                                            <div className="wp-gift-title-wrap">
                                                <p className="wp-gift-title">{wp.title}</p>
                                                <p className="wp-gift-amount">{wp.amount}</p>
                                            </div>
                                            <div className={`wp-status-badge ${wp.redeemed ? 'wp-status-badge--done' : 'wp-status-badge--pending'}`}>
                                                {wp.redeemed ? (
                                                    <>
                                                        <i className="las la-check-circle" /> {t('Redeemed')}
                                                    </>
                                                ) : (
                                                    <>
                                                        <i className="las la-hourglass-half" /> {t('Pending')}
                                                    </>
                                                )}
                                            </div>
                                        </div>
                                        <div className="wp-tear" />
                                        <div className="wp-code-section">
                                            <span className="wp-code-label">{t('Redemption Code')}</span>
                                            <span className="wp-code">{wp.code}</span>
                                        </div>
                                        {wp.redeemed ? (
                                            <p className="wp-gift-hint wp-gift-hint--done">
                                                <i className="las la-check" /> {t('Redeemed :date', { date: wp.redeemedAt ?? '' })}
                                            </p>
                                        ) : (
                                            <p className="wp-gift-hint">
                                                <i className="las la-map-marker-alt" /> {t('Present this code to any authorized stockist to claim your gift package.')}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
                <div className="bt-card-foot">
                    <i className="las la-info-circle" />{' '}
                    {t('All bonuses transfer directly into your Money Box (main wallet balance). Autoship unlocks once you buy :pv PV of products in the month.', { pv: autoship.minPv })}
                </div>
            </div>

            <div className="bt-card" style={{ marginTop: '1.5rem' }}>
                <div className="bt-card-head">
                    <p className="bt-card-title">
                        <i className="las la-history" /> {t('Transfer History')}
                    </p>
                </div>
                {transfers.data.length > 0 ? (
                    <>
                        <div className="bt-list bt-list--table">
                            <div className="bt-thead">
                                <span>#</span>
                                <span>{t('Source')}</span>
                                <span>{t('Amount')}</span>
                                <span>{t('Status')}</span>
                                <span>{t('Date')}</span>
                            </div>
                            {transfers.data.map((row, i) => (
                                <div className="bt-trow" key={row.id}>
                                    <span className="bt-tc bt-tc--num">{(transfers.from ?? 1) + i}</span>
                                    <span className="bt-tc">
                                        <span className="bt-source-tag">{t(row.source)}</span>
                                    </span>
                                    <span className="bt-tc bt-tc--amount">{row.amount}</span>
                                    <span className="bt-tc">
                                        <span className={`bt-status-tag bt-status-tag--${row.status.toLowerCase()}`}>{t(row.status)}</span>
                                    </span>
                                    <span className="bt-tc bt-tc--date">
                                        {row.date}
                                        <br />
                                        <small>{row.time}</small>
                                    </span>
                                </div>
                            ))}
                        </div>
                        <div style={{ padding: '.75rem 1.25rem' }}>
                            <Pagination links={transfers.links} />
                        </div>
                    </>
                ) : (
                    <div className="bt-empty">
                        <i className="las la-inbox" />
                        <p>{t('No transfers yet. Use the buttons above to move your bonuses into your Money Box.')}</p>
                    </div>
                )}
            </div>

            <TransferModal wallet={picked} url={urls.transfer} onClose={() => setPicked(null)} />
            <TransferAllModal open={allOpen} url={urls.transferAll} summary={summary} total={totalTransferable} autoship={anyAutoshipSkipped ? autoship : null} onClose={() => setAllOpen(false)} />
            <AutoshipModal open={autoshipOpen} autoship={autoship} shopUrl={urls.shop} onClose={() => setAutoshipOpen(false)} />
        </div>
    );
}

function WalletRow({ wallet: w, autoship, onTransfer, onCheck }: { wallet: Wallet; autoship: Props['autoship']; onTransfer: () => void; onCheck: () => void }) {
    const t = useTrans();
    const zero = w.amount <= 0;
    const iconStyle =
        w.kind === 'acb' ? { background: 'linear-gradient(135deg,#DC2626,#991B1B)', color: '#fff' } : w.kind === 'autoship' ? { background: 'linear-gradient(135deg,#0891B2,#0E7490)', color: '#fff' } : undefined;

    return (
        <div className={`bt-row${w.locked ? ' bt-row--locked' : ''}${w.kind === 'acb' && zero ? ' bt-row--zero' : ''}`}>
            <div className="bt-row-icon" style={iconStyle}>
                <i className={w.icon} />
            </div>
            <div className="bt-row-info">
                <span className="bt-row-name">
                    {t(w.label)}
                    {w.kind === 'autoship' &&
                        (w.locked ? (
                            <span className="bt-pill bt-pill--amber">
                                <i className="las la-lock" /> {t(':pv PV Required', { pv: autoship.minPv })}
                            </span>
                        ) : (
                            <span className="bt-pill" style={{ background: '#cffafe', color: '#0e7490', border: '1px solid #a5f3fc', fontSize: '.65rem' }}>
                                <i className="las la-check" /> {t('Unlocked')}
                            </span>
                        ))}
                    {w.kind === 'acb' && (
                        <span className="bt-pill" style={{ background: '#fef9c3', color: '#854d0e', border: '1px solid #fde68a', fontSize: '.65rem' }}>
                            <i className="las la-star" /> {t('ACB Member')}
                        </span>
                    )}
                </span>
                <span className={`bt-row-amount${zero ? ' bt-row-amount--zero' : ''}`}>{w.balance}</span>
                {w.kind === 'autoship' && (
                    <span style={{ fontSize: '.7rem', color: '#6b7280', display: 'block', marginTop: '.2rem', lineHeight: 1.4 }}>
                        {t('20% of Matching Bonus held here')} —{' '}
                        {w.locked ? (
                            <span style={{ color: '#d97706', fontWeight: 600 }}>
                                {t('buy :min PV of products this month to transfer (:pv of :min PV so far)', { min: autoship.minPv, pv: autoship.pv })}
                            </span>
                        ) : (
                            <span style={{ color: '#059669', fontWeight: 600 }}>{t('ready to transfer')}</span>
                        )}
                    </span>
                )}
            </div>
            <div className="bt-row-action">
                {w.locked ? (
                    <button type="button" className="bt-btn-check" onClick={onCheck}>
                        <i className="las la-shield-alt" /> {t('Check')}
                    </button>
                ) : zero ? (
                    <button type="button" className="bt-btn-transfer" disabled style={{ opacity: 0.4, cursor: 'not-allowed' }}>
                        {t('No Balance')}
                    </button>
                ) : (
                    <button type="button" className="bt-btn-transfer" onClick={onTransfer}>
                        {t('Transfer')}
                    </button>
                )}
            </div>
        </div>
    );
}

/** The dimmed overlay + card used by all three dialogs (classes from the Blade page). */
function Overlay({ open, onClose, labelledBy, children }: { open: boolean; onClose: () => void; labelledBy: string; children: ReactNode }) {
    useEffect(() => {
        if (!open) return;
        const onKey = (e: KeyboardEvent) => e.key === 'Escape' && onClose();
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, [open, onClose]);

    return (
        <div className={`bt-overlay${open ? ' active' : ''}`} onClick={(e) => e.target === e.currentTarget && onClose()} aria-hidden={!open}>
            <div className="bt-modal" role="dialog" aria-modal="true" aria-labelledby={labelledBy}>
                {open && children}
            </div>
        </div>
    );
}

function ModalHead({ id, icon, title, desc, amber = false, onClose }: { id: string; icon: string; title: string; desc: string; amber?: boolean; onClose: () => void }) {
    const t = useTrans();
    return (
        <div className="bt-modal-head">
            <div className={`bt-modal-lock${amber ? ' bt-modal-lock--amber' : ''}`}>
                <i className={`las ${icon}`} />
            </div>
            <div className="bt-modal-head-text">
                <h3 className="bt-modal-title" id={id}>
                    {title}
                </h3>
                <p className="bt-modal-desc">{desc}</p>
            </div>
            <button type="button" className="bt-modal-x" onClick={onClose} aria-label={t('Close')}>
                &times;
            </button>
        </div>
    );
}

function PasswordField({ value, onChange, error, inputId }: { value: string; onChange: (v: string) => void; error?: string; inputId: string }) {
    const t = useTrans();
    const [shown, setShown] = useState(false);
    const ref = useRef<HTMLInputElement>(null);
    useEffect(() => {
        const timer = setTimeout(() => ref.current?.focus(), 320);
        return () => clearTimeout(timer);
    }, []);

    return (
        <div className="bt-pw-group">
            <label className="bt-pw-label" htmlFor={inputId}>
                {t('Account Password')}
            </label>
            <div className="bt-pw-wrap">
                <input
                    ref={ref}
                    type={shown ? 'text' : 'password'}
                    id={inputId}
                    className={`bt-pw-input${error ? ' bt-pw-input--error' : ''}`}
                    placeholder={t('Enter your account password')}
                    autoComplete="current-password"
                    value={value}
                    onChange={(e) => onChange(e.target.value)}
                    required
                />
                <button type="button" className="bt-pw-eye" onClick={() => setShown((s) => !s)} tabIndex={-1} aria-label={shown ? t('Hide password') : t('Show password')}>
                    <i className={`las ${shown ? 'la-eye-slash' : 'la-eye'}`} />
                </button>
            </div>
            {error && (
                <p className="bt-pw-error">
                    <i className="las la-exclamation-circle" /> {error}
                </p>
            )}
        </div>
    );
}

function TransferModal({ wallet, url, onClose }: { wallet: Wallet | null; url: string; onClose: () => void }) {
    return (
        <Overlay open={wallet !== null} onClose={onClose} labelledBy="authModalTitle">
            {/* Keyed by wallet: each bonus starts from its full balance and an empty password. */}
            {wallet && <TransferForm key={wallet.field} wallet={wallet} url={url} onClose={onClose} />}
        </Overlay>
    );
}

function TransferForm({ wallet, url, onClose }: { wallet: Wallet; url: string; onClose: () => void }) {
    const t = useTrans();
    const form = useForm({ bonus_field: wallet.field, amount: wallet.amount.toFixed(2), password: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, {
            preserveScroll: true,
            onSuccess: () => onClose(),
            onFinish: () => form.setData('password', ''),
        });
    };

    const amount = Number(form.data.amount);
    const tooMuch = amount > wallet.amount;

    return (
        <>
            <ModalHead id="authModalTitle" icon="la-shield-alt" title={t('Authorize Transfer')} desc={t('Confirm your identity to proceed')} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="bt-modal-body">
                    <div className="bt-summary">
                        <div className="bt-summary-row">
                            <span className="bt-summary-key">{t('From')}</span>
                            <span className="bt-summary-val">{t(wallet.label)}</span>
                        </div>
                        <div className="bt-summary-row">
                            <span className="bt-summary-key">{t('Amount')}</span>
                            <div className="bt-amount-wrap">
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    max={wallet.amount.toFixed(2)}
                                    className="bt-amount-input"
                                    aria-label={t('Amount')}
                                    value={form.data.amount}
                                    onChange={(e) => form.setData('amount', e.target.value)}
                                    required
                                />
                                <span className="bt-amount-max">{t('Max: :amount', { amount: fmt(wallet.amount) })}</span>
                            </div>
                        </div>
                        <div className="bt-summary-row">
                            <span className="bt-summary-key">{t('To')}</span>
                            <span className="bt-summary-val">{t('Money Box')}</span>
                        </div>
                    </div>
                    {(form.errors.amount || tooMuch) && (
                        <p className="bt-pw-error">
                            <i className="las la-exclamation-circle" /> {form.errors.amount ?? t('That is more than this bonus holds.')}
                        </p>
                    )}
                    <PasswordField inputId="tf_password" value={form.data.password} onChange={(v) => form.setData('password', v)} error={form.errors.password} />
                </div>
                <div className="bt-modal-foot">
                    <button type="button" className="bt-btn-cancel" onClick={onClose}>
                        {t('Cancel')}
                    </button>
                    <button type="submit" className="bt-btn-authorize" disabled={form.processing || tooMuch || !(amount > 0)}>
                        <i className="las la-lock-open" /> {t('Authorize & Transfer')}
                    </button>
                </div>
            </form>
        </>
    );
}

function TransferAllModal({
    open,
    url,
    summary,
    total,
    autoship,
    onClose,
}: {
    open: boolean;
    url: string;
    summary: Props['summary'];
    total: string;
    /** Set when autoship is skipped, to say why */
    autoship: Props['autoship'] | null;
    onClose: () => void;
}) {
    return (
        <Overlay open={open} onClose={onClose} labelledBy="allModalTitle">
            <TransferAllForm url={url} summary={summary} total={total} autoship={autoship} onClose={onClose} />
        </Overlay>
    );
}

function TransferAllForm({ url, summary, total, autoship, onClose }: { url: string; summary: Props['summary']; total: string; autoship: Props['autoship'] | null; onClose: () => void }) {
    const t = useTrans();
    const form = useForm({ password: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, onSuccess: () => onClose(), onFinish: () => form.reset('password') });
    };

    return (
        <>
            <ModalHead id="allModalTitle" icon="la-paper-plane" title={t('Transfer All Bonuses')} desc={t('Confirm your identity to proceed')} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="bt-modal-body">
                    <div className="bt-summary">
                        {summary.map((row) => (
                            <div key={row.label} className={`bt-summary-row${row.skipped ? ' bt-summary-row--excluded' : ''}`}>
                                <span className="bt-summary-key">
                                    {t(row.label)}{' '}
                                    {row.skipped && (
                                        <span className="bt-pill bt-pill--grey" style={{ fontSize: '.65rem' }}>
                                            {t('Skipped')}
                                        </span>
                                    )}
                                </span>
                                <span className={`bt-summary-val${row.skipped ? ' bt-summary-val--excluded' : ''}`}>{row.balance}</span>
                            </div>
                        ))}
                        <div className="bt-summary-row bt-summary-total">
                            <span className="bt-summary-key">{t('Total')}</span>
                            <span className="bt-summary-val">{total}</span>
                        </div>
                    </div>
                    {autoship && (
                        <div className="bt-notice">
                            <i className="las la-exclamation-triangle" />{' '}
                            {t('Autoship is excluded — buy :min PV of products this month to include it (:pv PV so far).', { min: autoship.minPv, pv: autoship.pv })}
                        </div>
                    )}
                    <PasswordField inputId="all_password" value={form.data.password} onChange={(v) => form.setData('password', v)} error={form.errors.password} />
                </div>
                <div className="bt-modal-foot">
                    <button type="button" className="bt-btn-cancel" onClick={onClose}>
                        {t('Cancel')}
                    </button>
                    <button type="submit" className="bt-btn-authorize" disabled={form.processing}>
                        <i className="las la-lock-open" /> {t('Authorize & Transfer All')}
                    </button>
                </div>
            </form>
        </>
    );
}

/** Why autoship is still locked. The old dialog fetched this; it now comes with the page. */
function AutoshipModal({ open, autoship, shopUrl, onClose }: { open: boolean; autoship: Props['autoship']; shopUrl: string; onClose: () => void }) {
    const t = useTrans();

    return (
        <Overlay open={open} onClose={onClose} labelledBy="autoshipModalTitle">
            <ModalHead id="autoshipModalTitle" icon="la-sync-alt" amber title={t('Autoship')} desc={t("Checking the PV of this month's purchases")} onClose={onClose} />
            <div className="bt-modal-body">
                <div className="bt-state">
                    <div className="bt-state-icon bt-state-icon--red">
                        <i className="las la-times-circle" />
                    </div>
                    <p className="bt-state-heading bt-state-heading--red">{t('Not Eligible Yet')}</p>
                    <div className="bt-summary" style={{ marginTop: '1rem' }}>
                        <div className="bt-summary-row">
                            <span className="bt-summary-key">{t('Month')}</span>
                            <span className="bt-summary-val">{autoship.month}</span>
                        </div>
                        <div className="bt-summary-row">
                            <span className="bt-summary-key">{t('PV Bought')}</span>
                            <span className="bt-summary-val">{t(':pv of :min PV', { pv: autoship.pv, min: autoship.minPv })}</span>
                        </div>
                        {autoship.order && (
                            <div className="bt-summary-row">
                                <span className="bt-summary-key">{t('Latest Invoice')}</span>
                                <span className="bt-summary-val">
                                    {autoship.order.invoice} · {autoship.order.date}
                                </span>
                            </div>
                        )}
                        <div className="bt-summary-row">
                            <span className="bt-summary-key">{t('Autoship Balance')}</span>
                            <span className="bt-summary-val">{autoship.balance}</span>
                        </div>
                    </div>
                    <div className="bt-notice bt-notice--info">
                        <i className="las la-info-circle" />{' '}
                        {t('Autoship unlocks once the products you buy in a calendar month add up to :min PV. Anything still held at month end is forfeited.', { min: autoship.minPv })}
                    </div>
                </div>
            </div>
            <div className="bt-modal-foot">
                <button type="button" className="bt-btn-cancel" onClick={onClose}>
                    {t('Maybe Later')}
                </button>
                <Link href={shopUrl} className="bt-btn-authorize">
                    <i className="las la-shopping-cart" /> {t('Visit Shop')}
                </Link>
            </div>
        </Overlay>
    );
}

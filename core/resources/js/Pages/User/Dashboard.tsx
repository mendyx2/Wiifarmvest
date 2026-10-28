import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import Modal from '@/Components/Modal';
import { useCopy } from '@/lib/clipboard';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type Flashcard = { id: number; type: 'image' | 'video'; url: string; title: string | null; description: string | null };

type Props = PageProps<{
    card: { balance: string; masked: string; last4: string; holder: string; validThru: string; visa: string };
    /** Admin-written HTML from General Settings */
    notices: string[];
    noticeTime: string;
    stats: { totalDeposit: string; totalWithdraw: string; completeWithdraw: number; referralEarnings: string; totalRef: number; dividends: string; shares: string };
    project: { title: string; color: string; icon: string } | null;
    earnings: Record<'productWallet' | 'direct' | 'indirect' | 'upgrade' | 'unilevel' | 'matching' | 'keyIn', string>;
    adminNotice: { count: number; subject: string } | null;
    welcomePackages: { code: string; label: string }[];
    acb: string | null;
    autoship: { amount: string; minPv: number } | null;
    pv: { position: string | null; positionKey: string | null; left: string; right: string; leftPct: number; rightPct: number } | null;
    recentTransactions: { id: number; details: string; date: string; credit: boolean; amount: string }[];
    portfolio: { shares: string; shareCredits: string; dividends: string };
    status: { active: boolean; kyc: 'verified' | 'pending' | 'unverified'; kycRejection: string | null; pendingWithdraw: number; inMatrix: boolean };
    flashcards: Flashcard[];
    isStockist: boolean;
    urls: Record<
        'deposit' | 'withdraw' | 'transactions' | 'portfolio' | 'products' | 'referrals' | 'tree' | 'stages' | 'epin' | 'profile' | 'notifications' | 'acb' | 'bonusTransfer' | 'kyc' | 'flashcardDismiss',
        string
    >;
}>;

const EARNINGS: { key: keyof Props['earnings']; label: string; sub: string; icon: string; color: string; product?: boolean }[] = [
    { key: 'productWallet', label: 'Re-purchase Wallet', sub: 'Used for product purchases', icon: 'la-shopping-bag', color: '#7C3AED', product: true },
    { key: 'direct', label: 'Direct Bonus', sub: 'Lifetime earned', icon: 'la-hand-holding-usd', color: '#059669' },
    { key: 'indirect', label: 'Indirect Bonus', sub: 'Lifetime earned', icon: 'la-network-wired', color: '#0EA5E9' },
    { key: 'upgrade', label: 'Upgrade Bonus', sub: 'Lifetime earned', icon: 'la-arrow-up', color: '#D97706' },
    { key: 'unilevel', label: 'Unilevel Bonus', sub: 'Lifetime earned', icon: 'la-sitemap', color: '#EC4899' },
    { key: 'matching', label: 'Matching Bonus', sub: 'Lifetime earned', icon: 'la-balance-scale', color: '#EF4444' },
    { key: 'keyIn', label: 'Key-In Bonus', sub: 'Lifetime earned', icon: 'la-keyboard', color: '#7C3AED' },
];

export default function Dashboard(props: Props) {
    const { card, notices, noticeTime, stats, project, earnings, adminNotice, welcomePackages, acb, autoship, pv, recentTransactions, portfolio, status, flashcards, isStockist, urls } = props;
    const t = useTrans();
    const [hideBalance, setHideBalance] = useState(false);
    const [dismissed, setDismissed] = useState<number[]>([]);
    const [showRejection, setShowRejection] = useState(false);

    useTawkChat();

    return (
        <>
            <Head title={t('Dashboard')} />
            {flashcards.length > 0 && <Flashcards cards={flashcards} dismissUrl={urls.flashcardDismiss} />}

            <div className="bk-top">
                <span className="bk-blob bk-blob-1" />
                <span className="bk-blob bk-blob-2" />
                <span className="bk-blob bk-blob-3" />

                <div className="bk-card-col">
                    <div className="bk-atm-card">
                        <span className="bk-atm-glare" />
                        <span className="bk-atm-circle bk-atm-c1" />
                        <span className="bk-atm-circle bk-atm-c2" />
                        <div className="bk-atm-top">
                            <span className="bk-atm-logo">WCS</span>
                            <button type="button" className="bk-atm-eye" title={t('Show / hide balance')} aria-pressed={hideBalance} onClick={() => setHideBalance((h) => !h)}>
                                <i className={`las ${hideBalance ? 'la-eye-slash' : 'la-eye'}`} />
                            </button>
                        </div>
                        <div className="bk-atm-mid">
                            <div className="bk-chip">
                                <div className="bk-chip-h" />
                                <div className="bk-chip-v" />
                            </div>
                            <div>
                                <p className="bk-atm-bal-label">{t('Available Balance')}</p>
                                <p className="bk-atm-bal">{hideBalance ? card.masked : card.balance}</p>
                            </div>
                        </div>
                        <div className="bk-atm-num">
                            <span>••••</span>
                            <span>••••</span>
                            <span>••••</span>
                            <span>{card.last4}</span>
                        </div>
                        <div className="bk-atm-bottom">
                            <div>
                                <p className="bk-atm-meta-label">{t('Card Holder')}</p>
                                <p className="bk-atm-meta">{card.holder}</p>
                            </div>
                            <div>
                                <p className="bk-atm-meta-label">{t('Valid Thru')}</p>
                                <p className="bk-atm-meta">{card.validThru}</p>
                            </div>
                            <div className="bk-mc">
                                <span className="bk-mc-l" />
                                <span className="bk-mc-r" />
                            </div>
                        </div>
                    </div>
                    <div className="bk-card-sub-badge">
                        <span className="bk-csb-icon">
                            <i className="las la-coins" />
                        </span>
                        <span className="bk-csb-text">
                            {t('Visa wallet')} &nbsp;<strong>{card.visa}</strong>
                        </span>
                    </div>
                </div>

                <div className="bk-right-col">
                    <div className="bk-qa-grid">
                        <QuickAction href={urls.deposit} tone="green" icon="la-wallet" label={t('Deposit')} />
                        {isStockist ? (
                            <QuickAction href={urls.tree} tone="teal" icon="la-project-diagram" label={t('Genealogy')} />
                        ) : (
                            <QuickAction href={urls.withdraw} tone="rose" icon="la-arrow-circle-up" label={t('Withdraw')} />
                        )}
                        <QuickAction href={urls.transactions} tone="blue" icon="la-file-invoice-dollar" label={t('History')} />
                        <QuickAction href={urls.portfolio} tone="violet" icon="la-chart-line" label={t('Invest')} />
                        <QuickAction href={urls.products} tone="amber" icon="la-store" label={t('Shop')} />
                        <QuickAction href={urls.referrals} tone="pink" icon="la-user-friends" label={t('Referrals')} />
                    </div>
                </div>
            </div>

            {notices.map((html, i) =>
                dismissed.includes(i) ? null : (
                    <div className="nc-wrap" key={i}>
                        <div className="nc-card">
                            <div className="nc-pulse-ring" />
                            <div className="nc-icon-col">
                                <div className="nc-icon-wrap">
                                    <i className="las la-bell" />
                                    <span className="nc-dot" />
                                </div>
                            </div>
                            <div className="nc-body">
                                <div className="nc-meta">
                                    <span className="nc-badge">{t('Notice')}</span>
                                    <span className="nc-time">
                                        <i className="las la-clock" /> {noticeTime}
                                    </span>
                                </div>
                                {/* Written by the admin in General Settings; the Blade page printed it as HTML too. */}
                                <div className="nc-text" dangerouslySetInnerHTML={{ __html: html }} />
                            </div>
                            <button type="button" className="nc-close" title={t('Dismiss')} onClick={() => setDismissed((d) => [...d, i])}>
                                <i className="las la-times" />
                            </button>
                        </div>
                    </div>
                ),
            )}

            <div className="bk-stats-grid">
                <StatCard tone="emerald" icon="la-arrow-circle-down" label={t('Total Deposited')} value={stats.totalDeposit} sub={t('All time')} subIcon="la-calendar" />
                <StatCard tone="rose" icon="la-arrow-circle-up" label={t('Total Withdrawn')} value={stats.totalWithdraw} sub={t(':count completed', { count: stats.completeWithdraw })} subIcon="la-check-circle" dark />
                <StatCard tone="indigo" icon="la-users" label={t('Referral Earnings')} value={stats.referralEarnings} sub={t(':count referrals', { count: stats.totalRef })} subIcon="la-user-friends" />
                <StatCard tone="violet" icon="la-coins" label={t('Dividend Earnings')} value={stats.dividends} sub={t(':count units', { count: stats.shares })} subIcon="la-layer-group" />
            </div>

            <div className="bk-mlm-section">
                <div className="bk-mlm-header">
                    <span className="bk-section-dot bk-dot-violet" style={{ display: 'inline-block', marginRight: 6 }} />
                    <span className="bk-mlm-label">{t('MLM & Product Earnings')}</span>
                    {project && (
                        <span className="bk-mlm-plan-chip" style={{ background: `${project.color}20`, color: project.color, border: `1px solid ${project.color}40` }}>
                            <i className={project.icon} /> {project.title}
                        </span>
                    )}
                </div>
                <div className="bk-mlm-grid">
                    {EARNINGS.map((e) => (
                        <div key={e.key} className={`bk-mlm-card${e.product ? ' bk-mlm-product' : ''}`}>
                            <div className="bk-mlm-icon" style={{ background: `${e.key === 'upgrade' ? '#F59E0B' : e.color}20`, color: e.color }}>
                                <i className={`las ${e.icon}`} />
                            </div>
                            <div className="bk-mlm-info">
                                <p className="bk-mlm-val">{earnings[e.key]}</p>
                                <p className="bk-mlm-lbl">{t(e.label)}</p>
                                <p className="bk-mlm-sub">{t(e.sub)}</p>
                            </div>
                        </div>
                    ))}
                </div>

                {adminNotice && (
                    <Link href={urls.notifications} className="bk-notice-strip" style={{ textDecoration: 'none' }}>
                        <div className="bk-notice-left">
                            <span className="bk-notice-icon">
                                <i className="las la-user-shield" />
                            </span>
                            <div>
                                <p className="bk-notice-label">{t('Personal Notice from Admin')}</p>
                                <p className="bk-notice-sub">{adminNotice.subject}</p>
                            </div>
                        </div>
                        <div className="bk-notice-right">
                            <span className="bk-notice-count">{adminNotice.count > 9 ? '9+' : adminNotice.count}</span>
                            <span className="bk-notice-cta">
                                {t('View')} <i className="las la-arrow-right" />
                            </span>
                        </div>
                    </Link>
                )}

                {welcomePackages.map((wp) => (
                    <WelcomePackageStrip key={wp.code} code={wp.code} label={wp.label} />
                ))}

                {acb !== null && (
                    <Link href={urls.acb} className="bk-acb-strip" style={{ textDecoration: 'none' }}>
                        <div className="bk-acb-strip-left">
                            <span className="bk-acb-icon">
                                <i className="las la-star" />
                            </span>
                            <div>
                                <p className="bk-acb-label">{t('Upline Celebrated Bonus')}</p>
                                <p className="bk-acb-sub">{t('ACB Member · Earns 5%/2%/1% from downline awards')}</p>
                            </div>
                        </div>
                        <div className="bk-acb-balance">
                            <span className="bk-acb-val">{acb}</span>
                            <span className="bk-acb-cta">
                                {t('View History')} <i className="las la-arrow-right" />
                            </span>
                        </div>
                    </Link>
                )}

                {autoship && (
                    <Link href={urls.bonusTransfer} className="bk-autoship-strip" style={{ textDecoration: 'none' }}>
                        <div className="bk-autoship-left">
                            <span className="bk-autoship-icon">
                                <i className="las la-sync-alt" />
                            </span>
                            <div>
                                <p className="bk-autoship-label">{t('Autoship Balance')}</p>
                                <p className="bk-autoship-sub">{t('20% of Matching Bonus · Buy :pv PV of products this month to transfer', { pv: autoship.minPv })}</p>
                            </div>
                        </div>
                        <div className="bk-autoship-right">
                            <span className="bk-autoship-val">{autoship.amount}</span>
                            <span className="bk-autoship-cta">
                                {t('Transfer')} <i className="las la-arrow-right" />
                            </span>
                        </div>
                    </Link>
                )}

                {pv && (
                    <div className="bk-pv-row">
                        <div className="bk-pv-label">
                            <i className="las la-project-diagram" /> {t('Binary Tree PV')}
                            {pv.position && <span className={`bk-pv-pos-chip bk-pv-pos-${pv.positionKey}`}>{t(pv.position)}</span>}
                        </div>
                        <div className="bk-pv-bars">
                            <PvLeg label={t('Left PV')} icon="la-arrow-alt-circle-left" value={pv.left} pct={pv.leftPct} side="left" />
                            <PvLeg label={t('Right PV')} icon="la-arrow-alt-circle-right" value={pv.right} pct={pv.rightPct} side="right" />
                        </div>
                    </div>
                )}
            </div>

            <div className="bk-bottom-row">
                <div className="bk-txn-card">
                    <div className="bk-section-header">
                        <div className="bk-section-title-wrap">
                            <span className="bk-section-dot bk-dot-blue" />
                            <h6 className="bk-section-title">{t('Recent Transactions')}</h6>
                        </div>
                        <Link href={urls.transactions} className="bk-section-link">
                            {t('View all')} <i className="las la-arrow-right" />
                        </Link>
                    </div>
                    <div className="bk-txn-list">
                        {recentTransactions.length === 0 ? (
                            <div className="bk-empty-state">
                                <div className="bk-empty-icon">
                                    <i className="las la-receipt" />
                                </div>
                                <h6>{t('No Transactions Yet')}</h6>
                                <p>{t('Your history will appear here')}</p>
                                <Link href={urls.deposit} className="bk-empty-btn">
                                    {t('Make a Deposit')}
                                </Link>
                            </div>
                        ) : (
                            recentTransactions.map((trx) => {
                                const c = trx.credit ? 'emerald' : 'rose';
                                return (
                                    <div key={trx.id} className={`bk-txn-item bk-txn-${c}`}>
                                        <div className={`bk-txn-icon bk-icon-${c}`}>
                                            <i className={`las la-${trx.credit ? 'arrow-down' : 'arrow-up'}`} />
                                        </div>
                                        <div className="bk-txn-details">
                                            <p className="bk-txn-name">{trx.details}</p>
                                            <p className="bk-txn-date">{trx.date}</p>
                                        </div>
                                        <div className="bk-txn-right">
                                            <span className={`bk-txn-amount bk-amount-${c}`}>{trx.amount}</span>
                                            <span className={`bk-txn-type-badge bk-badge-${c}`}>{trx.credit ? t('Credit') : t('Debit')}</span>
                                        </div>
                                    </div>
                                );
                            })
                        )}
                    </div>
                </div>

                <div className="bk-right-panel">
                    <div className="bk-portfolio-card">
                        <div className="bk-portfolio-header">
                            <div className="bk-portfolio-header-icon">
                                <i className="las la-chart-pie" />
                            </div>
                            <div>
                                <h6 className="bk-portfolio-title">{t('Portfolio')}</h6>
                                <p className="bk-portfolio-sub">{t('Investment overview')}</p>
                            </div>
                            <Link href={urls.portfolio} className="bk-port-link">
                                {t('View')} →
                            </Link>
                        </div>
                        <div className="bk-portfolio-body">
                            <PortfolioItem tone="teal" icon="la-layer-group" label={t('Share Units')} value={portfolio.shares} />
                            <PortfolioItem tone="green" icon="la-coins" label={t('Share Credits')} value={portfolio.shareCredits} />
                            <PortfolioItem tone="violet" icon="la-hand-holding-usd" label={t('Dividends')} value={portfolio.dividends} />
                        </div>
                    </div>

                    <div className="bk-status-card">
                        <div className="bk-section-header">
                            <div className="bk-section-title-wrap">
                                <span className="bk-section-dot bk-dot-green" />
                                <h6 className="bk-section-title">{t('Account Status')}</h6>
                            </div>
                        </div>
                        <div className="bk-status-list">
                            <StatusRow icon="la-shield-alt" label={t('Account')} tone={status.active ? 'green' : 'red'} value={status.active ? t('Active') : t('Inactive')} />
                            <div className="bk-status-row">
                                <span className="bk-status-label">
                                    <i className="las la-id-card" /> {t('KYC')}
                                </span>
                                <span>
                                    {status.kycRejection && (
                                        <button type="button" className="bk-kyc-reason-btn" onClick={() => setShowRejection(true)}>
                                            {t('Why?')}
                                        </button>
                                    )}
                                    <span className={`bk-pill-badge ${status.kyc === 'verified' ? 'bk-pill-green' : status.kyc === 'pending' ? 'bk-pill-amber' : 'bk-pill-red'}`}>
                                        {status.kyc === 'verified' ? t('Verified') : status.kyc === 'pending' ? t('Pending') : t('Unverified')}
                                    </span>
                                </span>
                            </div>
                            <StatusRow icon="la-clock" label={t('Pending W/D')} tone={status.pendingWithdraw > 0 ? 'amber' : 'green'} value={String(status.pendingWithdraw)} />
                            <StatusRow icon="la-check-circle" label={t('Matrix')} tone={status.inMatrix ? 'green' : 'red'} value={status.inMatrix ? t('Active') : t('Inactive')} />
                        </div>
                    </div>

                    <div className="bk-links-card">
                        <div className="bk-section-header">
                            <div className="bk-section-title-wrap">
                                <span className="bk-section-dot bk-dot-violet" />
                                <h6 className="bk-section-title">{t('Quick Links')}</h6>
                            </div>
                        </div>
                        <div className="bk-grid-links">
                            <Link href={urls.referrals} className="bk-grid-link bk-gl-blue">
                                <i className="las la-users" />
                                <span>{t('Referrals')}</span>
                                <em className="bk-gl-badge">{stats.totalRef}</em>
                            </Link>
                            <Link href={urls.tree} className="bk-grid-link bk-gl-teal">
                                <i className="las la-sitemap" />
                                <span>{t('Genealogy')}</span>
                            </Link>
                            <Link href={urls.stages} className="bk-grid-link bk-gl-amber">
                                <i className="las la-medal" />
                                <span>{t('Stages')}</span>
                            </Link>
                            <Link href={urls.epin} className="bk-grid-link bk-gl-pink">
                                <i className="las la-qrcode" />
                                <span>{t('E-Pin')}</span>
                            </Link>
                            <Link href={urls.products} className="bk-grid-link bk-gl-green">
                                <i className="las la-shopping-bag" />
                                <span>{t('Shop')}</span>
                            </Link>
                            <Link href={urls.profile} className="bk-grid-link bk-gl-violet">
                                <i className="las la-user-edit" />
                                <span>{t('Profile')}</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            {status.kycRejection && (
                <Modal open={showRejection} onClose={() => setShowRejection(false)} labelledBy="kyc-reason-title">
                    <div className="modal-header">
                        <h5 className="modal-title" id="kyc-reason-title">
                            {t('KYC Rejection Reason')}
                        </h5>
                        <button type="button" className="btn-close" aria-label={t('Close')} onClick={() => setShowRejection(false)} />
                    </div>
                    <div className="modal-body">
                        <p>{status.kycRejection}</p>
                        <Link href={urls.kyc} className="btn btn--base btn-sm">
                            {t('Submit KYC again')}
                        </Link>
                    </div>
                </Modal>
            )}
        </>
    );
}

function QuickAction({ href, tone, icon, label }: { href: string; tone: string; icon: string; label: string }) {
    return (
        <Link href={href} className={`bk-qa bk-qa-${tone}`}>
            <i className={`bk-qa-icon las ${icon}`} />
            <span>{label}</span>
        </Link>
    );
}

function StatCard({ tone, icon, label, value, sub, subIcon, dark = false }: { tone: string; icon: string; label: string; value: string; sub: string; subIcon: string; dark?: boolean }) {
    return (
        <div className={`bk-stat-card bk-stat-${tone}`}>
            <div className="bk-stat-bg-icon">
                <i className={`las ${icon}`} />
            </div>
            <p className={`bk-stat-label${dark ? ' text-dark' : ''}`}>{label}</p>
            <h4 className="bk-stat-value">{value}</h4>
            <p className={`bk-stat-sub${dark ? ' text-dark' : ''}`}>
                <i className={`las ${subIcon}`} /> {sub}
            </p>
        </div>
    );
}

function PvLeg({ label, icon, value, pct, side }: { label: string; icon: string; value: string; pct: number; side: 'left' | 'right' }) {
    return (
        <div className="bk-pv-leg">
            <div className="bk-pv-leg-top">
                <span className="bk-pv-leg-lbl">
                    <i className={`las ${icon}`} /> {label}
                </span>
                <span className="bk-pv-leg-val">{value}</span>
            </div>
            <div className="bk-pv-bar-track">
                <div className={`bk-pv-bar-fill bk-pv-${side}`} style={{ width: `${pct}%` }} />
            </div>
        </div>
    );
}

function PortfolioItem({ tone, icon, label, value }: { tone: string; icon: string; label: string; value: string }) {
    return (
        <div className="bk-port-item">
            <div className={`bk-port-pill bk-pill-${tone}`}>
                <i className={`las ${icon}`} />
            </div>
            <div className="bk-port-info">
                <span className="bk-port-label">{label}</span>
                <span className="bk-port-val">{value}</span>
            </div>
        </div>
    );
}

function StatusRow({ icon, label, tone, value }: { icon: string; label: string; tone: 'green' | 'amber' | 'red'; value: string }) {
    return (
        <div className="bk-status-row">
            <span className="bk-status-label">
                <i className={`las ${icon}`} /> {label}
            </span>
            <span className={`bk-pill-badge bk-pill-${tone}`}>{value}</span>
        </div>
    );
}

function WelcomePackageStrip({ code, label }: { code: string; label: string }) {
    const t = useTrans();
    const { copied, copy } = useCopy(2000);

    return (
        <div className="bk-wp-strip">
            <div className="bk-wp-strip-left">
                <span className="bk-wp-icon">
                    <i className="las la-gift" />
                </span>
                <div>
                    <p className="bk-wp-label">{label}</p>
                    <p className="bk-wp-sub">{t('Present code to a stockist to redeem your gift')}</p>
                </div>
            </div>
            <div className="bk-wp-right">
                <span className="bk-wp-code">{code}</span>
                <button
                    type="button"
                    className="bk-wp-copy"
                    title={t('Copy code')}
                    style={copied ? { background: '#059669' } : undefined}
                    onClick={async () => {
                        if (!(await copy(code))) window.notify?.('error', t('Failed to copy code.'));
                    }}
                >
                    <i className={`las ${copied ? 'la-check' : 'la-copy'}`} />
                </button>
            </div>
        </div>
    );
}

/**
 * Admin flashcards (Admin → Flashcards) the member has not seen yet, one at a
 * time. "Got it" records the card as seen; the × records every remaining card,
 * so the overlay does not come back on the next visit.
 */
function Flashcards({ cards, dismissUrl }: { cards: Flashcard[]; dismissUrl: string }) {
    const t = useTrans();
    const { csrfToken } = usePage().props;
    const [index, setIndex] = useState(0);
    const [visible, setVisible] = useState(false);
    const [closed, setClosed] = useState(false);
    const [leaving, setLeaving] = useState(false);

    useEffect(() => {
        const frame = requestAnimationFrame(() => setVisible(true));
        return () => cancelAnimationFrame(frame);
    }, []);

    const record = (id: number) =>
        fetch(dismissUrl.replace('__ID__', String(id)), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        }).catch(() => undefined); // not worth bothering the member about

    const close = () => {
        setVisible(false);
        setTimeout(() => setClosed(true), 400);
    };

    const next = (closeAll: boolean) => {
        const remaining = closeAll ? cards.slice(index) : [cards[index]];
        remaining.forEach((card) => record(card.id));
        setLeaving(true);
        setTimeout(() => {
            setLeaving(false);
            if (!closeAll && index + 1 < cards.length) setIndex(index + 1);
            else close();
        }, 350);
    };

    if (closed) return null;

    return (
        <div className={`fc-overlay${visible ? ' fc-visible' : ''}`} role="dialog" aria-modal="true">
            <div className="fc-backdrop" />
            <div className="fc-modal">
                {cards.length > 1 && (
                    <div className="fc-dots">
                        {cards.map((card, i) => (
                            <span key={card.id} className={`fc-dot${i === index ? ' fc-dot-active' : ''}`} />
                        ))}
                    </div>
                )}
                <button type="button" className="fc-close" title={t('Close')} onClick={() => next(true)}>
                    <i className="las la-times" />
                </button>
                <div className="fc-slides">
                    {cards.map((card, i) =>
                        i !== index ? null : (
                            <div key={card.id} className={`fc-slide fc-slide-active${leaving ? ' fc-slide-out' : ''}`}>
                                <div className="fc-media">
                                    {card.type === 'image' ? (
                                        <img src={card.url} alt={card.title ?? ''} className="fc-img" />
                                    ) : (
                                        <video className="fc-video" controls playsInline>
                                            <source src={card.url} type="video/mp4" />
                                        </video>
                                    )}
                                </div>
                                {(card.title || card.description) && (
                                    <div className="fc-content">
                                        {card.title && <h5 className="fc-title">{card.title}</h5>}
                                        {card.description && <p className="fc-desc">{card.description}</p>}
                                    </div>
                                )}
                                <div className="fc-footer">
                                    <span className="fc-counter">{t(':current of :total', { current: i + 1, total: cards.length })}</span>
                                    <button type="button" className="fc-done-btn" onClick={() => next(false)}>
                                        <i className="las la-check" /> {i + 1 < cards.length ? t('Got it, Next') : t('Got it, Close')}
                                    </button>
                                </div>
                            </div>
                        ),
                    )}
                </div>
            </div>
        </div>
    );
}

type TawkApi = { showWidget?: () => void; hideWidget?: () => void; onLoad?: () => void };
declare global {
    interface Window {
        Tawk_API?: TawkApi;
        Tawk_LoadStart?: Date;
    }
}

/**
 * Tawk.to live chat, on the dashboard only (as the Blade layout intended).
 * The script loads once; leaving the dashboard hides the widget and coming
 * back shows it again, without reloading anything.
 */
function useTawkChat() {
    useEffect(() => {
        if (!document.getElementById('tawk-script')) {
            window.Tawk_API = window.Tawk_API || {};
            window.Tawk_LoadStart = new Date();
            const script = document.createElement('script');
            script.id = 'tawk-script';
            script.async = true;
            script.src = 'https://embed.tawk.to/68b56669cbdd78615202ce7b/1j429g3gd';
            script.charset = 'UTF-8';
            script.setAttribute('crossorigin', '*');
            document.body.appendChild(script);
        } else {
            window.Tawk_API?.showWidget?.();
        }

        return () => window.Tawk_API?.hideWidget?.();
    }, []);
}

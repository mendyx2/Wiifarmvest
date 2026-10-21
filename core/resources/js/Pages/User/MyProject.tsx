import { Head, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import Modal from '@/Components/Modal';
import PageTitleBar from '@/Components/PageTitleBar';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type Project = {
    id: number;
    title: string;
    icon: string;
    color: string;
    amount: string;
    amountValue: number;
    pv: number;
    pvLabel: string;
    direct: string;
    indirect: string;
    pairing: string | null;
    cashBack: string | null;
    upgradeBonus: number;
    upgradeBonusLabel: string | null;
    monthlyFee: string | null;
    description: string | null;
    isCurrent: boolean;
    /** A higher tier (or any tier when the user has no project yet) */
    upgradable: boolean;
    available: boolean;
    cost: number;
    costLabel: string;
    shortfall: string | null;
};

type Props = PageProps<{
    visa: string;
    current: { id: number; title: string; amount: string; pv: number } | null;
    projects: Project[];
    curSym: string;
    upgradeUrl: string;
    cartUrl: string;
    cartCount: number;
}>;

/** Darken a #rrggbb colour (same helper as the Blade page, for the pop-up icon gradient). */
function shade(hex: string, pct: number): string {
    const num = parseInt(hex.replace('#', ''), 16);
    if (Number.isNaN(num)) return hex;
    const clamp = (v: number) => Math.min(255, Math.max(0, v));
    const r = clamp((num >> 16) + pct), g = clamp(((num >> 8) & 0xff) + pct), b = clamp((num & 0xff) + pct);
    return `#${((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1)}`;
}

export default function MyProject({ visa, current, projects, curSym, upgradeUrl, cartUrl, cartCount }: Props) {
    const t = useTrans();
    const [chosen, setChosen] = useState<Project | null>(null);
    const form = useForm({ project_id: '' });
    const money = (n: number) => curSym + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function open(project: Project) {
        form.setData('project_id', String(project.id));
        setChosen(project);
    }

    function confirm(event: FormEvent) {
        event.preventDefault();
        form.post(upgradeUrl, { preserveScroll: true, onFinish: () => setChosen(null) });
    }

    return (
        <>
            <Head title={t('My Project')} />
            <PageTitleBar title={t('My Project')} cartUrl={cartUrl} cartCount={cartCount} />

            <div className="mp-header">
                <div className="mp-header-icon"><i className="las la-project-diagram" /></div>
                <div className="mp-header-meta">
                    <h1 className="mp-header-title">{t('My Project')}</h1>
                    <p className="mp-header-sub">
                        {current ? <>{t('Currently on')} <strong>{current.title}</strong> &mdash; {t('upgrade to unlock higher tiers')}</> : t('Subscribe to a project to activate your network membership')}
                    </p>
                </div>
                <div className="mp-header-wallet">
                    <div className="mp-header-wallet-label">{t('VISA Wallet')}</div>
                    <div className="mp-header-wallet-amount">{visa}</div>
                </div>
            </div>

            {projects.length === 0 ? (
                <div className="text-center py-5">
                    <i className="las la-project-diagram" style={{ fontSize: '3rem', color: '#D1D5DB' }} />
                    <p className="mt-3 text-muted">{t('No projects available at this time.')}</p>
                </div>
            ) : (
                <>
                    <p className="mp-section-label">{current ? t('Available Tiers') : t('All Projects')}</p>
                    <div className="mp-grid">
                        {projects.map((p) => <ProjectCard key={p.id} project={p} isFirst={!current} onUpgrade={open} />)}
                    </div>
                </>
            )}

            <Modal open={chosen !== null} onClose={() => setChosen(null)} contentClassName="mp-modal border-0 bg-transparent" labelledBy="mpModalTitle">
                {chosen && (
                    <div className="mp-modal" style={{ transform: 'none', margin: 0, maxWidth: '100%' }}>
                        <div className="mp-modal-head" style={{ background: `linear-gradient(135deg,${chosen.color}, ${shade(chosen.color, -20)})` }}>
                            <div className="mp-modal-head-icon"><i className={chosen.icon} /></div>
                            <div>
                                <p className="mp-modal-head-title" id="mpModalTitle">{t('Upgrade to :title', { title: chosen.title })}</p>
                                <p className="mp-modal-head-sub">
                                    {chosen.shortfall ? t('You do not have enough VISA balance for this upgrade.') : t('Review the cost breakdown below and confirm.')}
                                </p>
                            </div>
                            <button className="mp-modal-close" type="button" onClick={() => setChosen(null)} aria-label={t('Close')}><i className="las la-times" /></button>
                        </div>
                        <div className="mp-modal-body">
                            <div className="mp-visa-row">
                                <span className="mp-visa-row-label"><i className="las la-wallet" /> {t('VISA Wallet Balance')}</span>
                                <span className="mp-visa-row-val">{visa}</span>
                            </div>
                            <div className="mp-breakdown">
                                <Row label={t('New project price')} value={chosen.amount} />
                                {current && <Row label={t('Your current plan (:title)', { title: current.title })} value={<>&minus; {current.amount}</>} />}
                                <Row label={t('PV gain')} value={`+${Math.max(0, chosen.pv - (current?.pv ?? 0)).toLocaleString('en-US', { minimumFractionDigits: 2 })} PV`} />
                                {/* Same estimate the Blade pop-up showed; the server works out the real bonus. */}
                                <Row
                                    label={t('Sponsor upgrade bonus')}
                                    value={chosen.upgradeBonus > 0 ? `${money((chosen.upgradeBonus / 100) * chosen.cost)} (${chosen.upgradeBonusLabel}) ${t('to sponsor')}` : t('None')}
                                />
                                <Row label={t('You pay')} value={chosen.costLabel} total />
                            </div>
                            {chosen.shortfall && (
                                <div className="mp-modal-warn" style={{ display: 'flex' }} role="alert">
                                    <i className="las la-exclamation-triangle" style={{ fontSize: '1.1rem', flexShrink: 0, marginTop: 1 }} />
                                    <span>{t('You need :amount more in your VISA wallet to complete this upgrade.', { amount: chosen.shortfall })}</span>
                                </div>
                            )}
                            <div className="mp-modal-actions">
                                <button className="mp-modal-cancel" type="button" onClick={() => setChosen(null)}>{t('Cancel')}</button>
                                <form onSubmit={confirm} style={{ flex: 2, display: 'flex' }}>
                                    <button className="mp-modal-confirm" type="submit" disabled={chosen.shortfall !== null || form.processing}>
                                        {form.processing ? <><i className="las la-circle-notch la-spin" /> {t('Processing…')}</> : <><i className="las la-arrow-circle-up" /> {t('Confirm Upgrade')}</>}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                )}
            </Modal>
        </>
    );
}

function Row({ label, value, total = false }: { label: string; value: React.ReactNode; total?: boolean }) {
    return (
        <div className={`mp-breakdown-row ${total ? 'mp-breakdown-total' : ''}`}>
            <span className="mp-breakdown-lbl">{label}</span>
            <span className="mp-breakdown-val">{value}</span>
        </div>
    );
}

function ProjectCard({ project: p, isFirst, onUpgrade }: { project: Project; isFirst: boolean; onUpgrade: (p: Project) => void }) {
    const t = useTrans();
    return (
        <div className={`mp-card ${p.isCurrent ? 'mp-card--current' : ''}`}>
            <div className="mp-card-band" style={{ background: p.color }} />
            <div className="mp-card-head">
                <div className="mp-card-icon" style={{ background: p.color }}><i className={p.icon} /></div>
                <div style={{ flex: 1, minWidth: 0 }}>
                    <p className="mp-card-title">{p.title}</p>
                    <p className="mp-card-amount">{p.amount}</p>
                    <div className="mp-card-badges">
                        {p.isCurrent && <span className="mp-badge mp-badge--current"><i className="las la-check-circle" /> {t('Current Plan')}</span>}
                        {p.upgradable && (p.available ? <span className="mp-badge mp-badge--upgrade">{t('Upgrade Available')}</span> : <span className="mp-badge mp-badge--locked">{t('Not Available')}</span>)}
                    </div>
                </div>
            </div>
            <div className="mp-stats">
                <div className="mp-stat"><p className="mp-stat-val">{p.pvLabel}</p><p className="mp-stat-lbl">PV</p></div>
                <div className="mp-stat"><p className="mp-stat-val">{p.direct}</p><p className="mp-stat-lbl">{t('Direct bonus.')}</p></div>
                <div className="mp-stat"><p className="mp-stat-val">{p.indirect}</p><p className="mp-stat-lbl">{t('Indirect bonus.')}</p></div>
            </div>
            <div className="mp-details">
                {p.pairing && <Detail icon="la-code-branch" label={t('Pairing/Day')} value={p.pairing} green />}
                {p.cashBack && <Detail icon="la-percentage" label={t('Cash Back')} value={p.cashBack} />}
                {p.upgradeBonusLabel && <Detail icon="la-arrow-circle-up" label={t('Upgrade Bonus')} value={p.upgradeBonusLabel} />}
                {p.monthlyFee && <Detail icon="la-calendar-check" label={t('Monthly Fee')} value={p.monthlyFee} />}
                {p.description && (
                    <div className="mp-detail-row" style={{ alignItems: 'flex-start' }}>
                        <span className="mp-detail-lbl" style={{ minWidth: 0, flex: 1, lineHeight: 1.5 }}><i className="las la-info-circle" /> {p.description}</span>
                    </div>
                )}
            </div>
            <div className="mp-card-footer">
                {p.isCurrent ? (
                    <div className="mp-current-tag"><i className="las la-check-double" /> {t('Active Plan')}</div>
                ) : p.available ? (
                    <>
                        <button className={`mp-btn-upgrade ${p.shortfall ? 'mp-btn-upgrade--low' : ''}`} type="button" onClick={() => onUpgrade(p)}>
                            <i className="las la-arrow-circle-up" /> {isFirst ? t('Subscribe') : t('Upgrade')} &mdash; {p.costLabel}
                        </button>
                        {p.shortfall ? (
                            <div className="mp-insufficient"><i className="las la-exclamation-triangle" /> {t('Need :amount more in VISA wallet', { amount: p.shortfall })}</div>
                        ) : (
                            <div className="mp-cost-diff">{t('You pay only the')} <strong>{t('difference')}</strong> &mdash; {p.costLabel}</div>
                        )}
                    </>
                ) : (
                    <button className="mp-btn-upgrade" disabled type="button"><i className="las la-lock" /> {t('Not Available')}</button>
                )}
            </div>
        </div>
    );
}

function Detail({ icon, label, value, green = false }: { icon: string; label: string; value: string; green?: boolean }) {
    return (
        <div className="mp-detail-row">
            <span className="mp-detail-lbl"><i className={`las ${icon}`} /> {label}</span>
            <span className={`mp-detail-val ${green ? 'mp-detail-val--green' : ''}`}>{value}</span>
        </div>
    );
}

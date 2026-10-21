import { Head } from '@inertiajs/react';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';
import './Awards.css';

type Leg = { text: string; pct: number };
type Award = {
    id: number;
    order: number;
    name: string;
    description: string;
    image: string | null;
    state: 'locked' | 'earned' | 'paid';
    total: Leg;
    left: Leg;
    right: Leg;
    reward: string;
    paidAmount: string | null;
    prerequisite: string | null;
    date: string | null;
};

type Props = PageProps<{ stats: { total: number; earned: number; paid: number }; awards: Award[] }>;

const shorten = (text: string, max: number) => (text.length > max ? `${text.slice(0, max)}...` : text);

export default function Awards({ stats, awards }: Props) {
    const t = useTrans();

    const pills = [
        { icon: 'la-trophy', value: stats.total, label: t('Total Awards') },
        { icon: 'la-user-check', value: stats.earned, label: t('Earned') },
        { icon: 'la-coins', value: stats.paid, label: t('Paid Out') },
    ];

    return (
        <div className="page-awards">
            <Head title={t('My Awards')} />
            <div className="ua-page">
                <div className="ua-hero">
                    <i className="las la-trophy ua-hero-icon" />
                    <div className="ua-hero-eyebrow">{t('Achievement Centre')}</div>
                    <div className="ua-hero-title">{t('My Awards')}</div>
                    <div className="ua-hero-sub">{t('Track your achievements and unlock new milestones')}</div>
                    <div className="ua-hero-stats">
                        {pills.map((p) => (
                            <div className="ua-stat-pill" key={p.label}><i className={`las ${p.icon}`} /><b>{p.value}</b><span>{p.label}</span></div>
                        ))}
                    </div>
                </div>

                {awards.length === 0 ? (
                    <div className="ua-empty">
                        <div className="ua-empty-icon"><i className="las la-trophy" /></div>
                        <h5 style={{ fontSize: '1.1rem', fontWeight: 800, color: '#374151', marginBottom: 8 }}>{t('No Awards Available')}</h5>
                        <p style={{ fontSize: '.86rem', color: '#9ca3af', maxWidth: 260, margin: '0 auto' }}>{t('Awards have not been configured yet. Keep building your network!')}</p>
                    </div>
                ) : (
                    <div className="ua-grid">
                        {awards.map((award) => <AwardCard award={award} key={award.id} />)}
                    </div>
                )}
            </div>
        </div>
    );
}

function AwardCard({ award }: { award: Award }) {
    const t = useTrans();
    const locked = award.state === 'locked';
    const legs = [
        { key: 'total', dot: 'dot-total', fill: 'fill-total', label: t('Total PV'), leg: award.total },
        { key: 'left', dot: 'dot-left', fill: 'fill-left', label: t('Left Leg'), leg: award.left },
        { key: 'right', dot: 'dot-right', fill: 'fill-right', label: t('Right Leg'), leg: award.right },
    ];

    return (
        <div className={`ua-card ${award.state}`}>
            <div className="ua-card-head">
                <span className="ua-order-badge"># {award.order}</span>
                {award.state === 'paid' && <span className="ua-status-badge ua-status-paid"><i className="las la-check-circle" /> {t('Paid')}</span>}
                {award.state === 'earned' && <span className="ua-status-badge ua-status-earned"><i className="las la-star" /> {t('Earned')}</span>}
                {locked && <span className="ua-status-badge ua-status-locked"><i className="las la-lock" /> {t('Locked')}</span>}
                <div className="ua-avatar-ring">
                    {award.image ? (
                        <img src={award.image} alt={award.name} />
                    ) : (
                        <div className="ua-avatar-inner"><i className={`las ${locked ? 'la-lock' : 'la-trophy'}`} /></div>
                    )}
                </div>
                <div className="ua-card-name">{award.name}</div>
                <div className="ua-card-desc">{award.description}</div>
            </div>

            <div className="ua-pv-block">
                {legs.map(({ key, dot, fill, label, leg }) => (
                    <div key={key}>
                        <div className="ua-pv-row">
                            <span className="ua-pv-label"><span className={`ua-pv-dot ${dot}`} /> {label}</span>
                            <span className="ua-pv-val">{leg.text}</span>
                        </div>
                        <div className="ua-pv-progress-track" role="progressbar" aria-label={label} aria-valuenow={leg.pct} aria-valuemin={0} aria-valuemax={100}>
                            <div className={`ua-pv-progress-fill ${fill}`} style={{ width: `${leg.pct}%` }} />
                        </div>
                    </div>
                ))}
            </div>

            <div className="ua-meta-strip">
                <span className="ua-chip chip-reward"><i className="las la-coins" /> {award.reward}</span>
                {award.prerequisite && (
                    <span className="ua-chip chip-prereq" title={t('Requires: :name', { name: award.prerequisite })}>
                        <i className="las la-link" /> {shorten(award.prerequisite, 14)}
                    </span>
                )}
                {award.state === 'paid' && (
                    <span className="ua-chip chip-earned" style={{ background: 'linear-gradient(135deg,#fef3c7,#fde68a)', color: '#92400e', marginLeft: 'auto' }}>
                        <i className="las la-coins" /> {award.paidAmount}
                    </span>
                )}
                {award.state === 'earned' && <span className="ua-chip chip-pending"><i className="las la-clock" /> {t('Awaiting Payment')}</span>}
                {locked && <span className="ua-chip chip-locked"><i className="las la-lock" /> {t('Not Qualified')}</span>}
            </div>

            {!locked && (
                <div className={`ua-earned-date ${award.state === 'paid' ? 'paid-date' : ''}`}>
                    {award.state === 'paid' ? (
                        <><i className="las la-check-circle" style={{ color: '#d97706' }} /> {t('Paid on :date', { date: award.date ?? '—' })}</>
                    ) : (
                        <><i className="las la-calendar-check" style={{ color: '#16a34a' }} /> {t('Qualified on :date', { date: award.date ?? '—' })}</>
                    )}
                </div>
            )}
        </div>
    );
}

import { Head } from '@inertiajs/react';
import Pagination from '@/Components/Pagination';
import { useTrans } from '@/lib/i18n';
import type { PageProps, Paginated } from '@/types';
import './Acb.css';

type Payment = { id: number; trx: string; gen: number | null; member: string | null; details: string | null; amount: string; balance: string; date: string; ago: string };

type Props = PageProps<{ balance: string; history: Paginated<Payment> }>;

const RATES = [
    { gen: 1, pct: '5%', bg: '#eff6ff', color: '#1d4ed8' },
    { gen: 2, pct: '2%', bg: '#faf5ff', color: '#7c3aed' },
    { gen: 3, pct: '1%', bg: '#fdf4ff', color: '#a21caf' },
];

export default function Acb({ balance, history }: Props) {
    const t = useTrans();
    const rateLabel = [t('Generation 1 · Direct Upline'), t('Generation 2 · Grandupline'), t('Generation 3 · Great-Grandupline')];
    const pills = [
        { icon: 'la-wallet', value: balance, label: t('Current Balance') },
        { icon: 'la-exchange-alt', value: String(history.total), label: t('Total Payments') },
        { icon: 'la-layer-group', value: t('3 Levels'), label: t('Upline Depth') },
    ];

    return (
        <div className="page-acb">
            <Head title={t('My ACB')} />
            <div className="acb-page">
                <div className="acb-hero">
                    <div className="acb-hero-watermark"><i className="las la-star" /></div>
                    <div className="acb-hero-eyebrow">{t('Achievements')}</div>
                    <h1 className="acb-hero-title">{t('Achievers Celebrated Bonus')}</h1>
                    <p className="acb-hero-sub">{t('You earn when your team wins. Every award your downlines qualify for puts money in your pocket — up to 3 generations deep.')}</p>
                    <div className="acb-hero-pills">
                        {pills.map((p) => (
                            <div className="acb-pill" key={p.label}>
                                <div className="acb-pill-icon"><i className={`las ${p.icon}`} /></div>
                                <div className="acb-pill-body"><b>{p.value}</b><span>{p.label}</span></div>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="acb-rates">
                    {RATES.map((r, i) => (
                        <div className="acb-rate" key={r.gen}>
                            <div className="acb-rate-dot" style={{ background: r.bg, color: r.color }}>{r.gen}</div>
                            <div>
                                <div className="acb-rate-pct">{r.pct}</div>
                                <div className="acb-rate-label">{rateLabel[i]}</div>
                            </div>
                        </div>
                    ))}
                </div>

                <div className="acb-hist-card">
                    <div className="acb-hist-head"><h5><i className="las la-history" /> {t('Payment History')}</h5></div>
                    {history.data.length > 0 ? (
                        <>
                            <div className="table-responsive">
                                <table className="acb-table">
                                    <thead>
                                        <tr>
                                            <th>{t('TRX')}</th>
                                            <th>{t('Details')}</th>
                                            <th>{t('Amount')}</th>
                                            <th>{t('Balance After')}</th>
                                            <th>{t('Date')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {history.data.map((txn) => (
                                            <tr key={txn.id}>
                                                <td><span className="acb-trx">{txn.trx}</span></td>
                                                <td>
                                                    <div className="acb-detail">
                                                        {txn.gen !== null && (
                                                            <>
                                                                <span className={`acb-gen acb-gen-${txn.gen <= 3 ? txn.gen : 'other'}`}><i className="las la-layer-group" /> {t('Gen :n', { n: txn.gen })}</span>
                                                                <br />
                                                            </>
                                                        )}
                                                        {txn.member ? (
                                                            <span style={{ fontSize: '.73rem', color: '#64748b', marginTop: 4, display: 'block' }}>
                                                                {t('from')} <span className="from-user">@{txn.member}</span>
                                                            </span>
                                                        ) : (
                                                            <span style={{ fontSize: '.73rem', color: '#64748b' }}>{txn.details}</span>
                                                        )}
                                                    </div>
                                                </td>
                                                <td><span className="acb-amount">{txn.amount}</span></td>
                                                <td><span className="acb-balance"><i className="las la-wallet" /> {txn.balance}</span></td>
                                                <td><div className="acb-date">{txn.date}</div><div className="acb-date-rel">{txn.ago}</div></td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                            {history.last_page > 1 && <div style={{ padding: '16px 20px', borderTop: '1px solid #f1f5f9' }}><Pagination links={history.links} /></div>}
                        </>
                    ) : (
                        <div className="acb-empty">
                            <div className="acb-empty-icon"><i className="las la-star" /></div>
                            <h6>{t('No ACB payments yet')}</h6>
                            <p>{t('When members in your downline qualify for awards, your bonus will appear here automatically.')}</p>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

import { Head } from '@inertiajs/react';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type Tier = { id: number; title: string; required: string; qualified: boolean; pct: number; remaining: string; reward: string };

type Props = PageProps<{ totalPv: string; walletBalance: string; hasBalance: boolean; awards: Tier[]; transferUrl: string }>;

export default function RepurchaseAward({ totalPv, walletBalance, hasBalance, awards, transferUrl }: Props) {
    const t = useTrans();
    const steps = [
        t('Purchase products through your stockist. Every product carries a Point Value (PV).'),
        t('Your PV accumulates automatically each time your stockist redeems your invoice.'),
        null, // step 3 has bold text, rendered below
        t('Admin reviews qualified members and credits the reward directly to your Repurchase Award Wallet.'),
        null, // step 5 has a link, rendered below
    ];

    return (
        <div className="rpu-page">
            <Head title={t('Repurchase (Unilevel) Award')} />
            <div className="rpu-header">
                <div className="rpu-header-left">
                    <div className="rpu-header-icon"><i className="las la-medal" /></div>
                    <div>
                        <h1 className="rpu-header-title">{t('Repurchase (Unilevel) Award')}</h1>
                        <p className="rpu-header-sub">{t('Track your product PV and unlock reward milestones')}</p>
                    </div>
                </div>
                <div className="rpu-total-pv-box">
                    <span className="rpu-total-pv-label">{t('Your Total PV')}</span>
                    <span className="rpu-total-pv-val">{totalPv}</span>
                </div>
            </div>

            <div className="rpu-wallet-card">
                <div className="rpu-wallet-left">
                    <div className="rpu-wallet-icon"><i className="las la-wallet" /></div>
                    <div>
                        <p className="rpu-wallet-label">{t('Repurchase Award Wallet')}</p>
                        <p className="rpu-wallet-hint">{t('Credited by admin when you qualify — transferable to your Money Box')}</p>
                    </div>
                </div>
                <div className="rpu-wallet-right">
                    <span className="rpu-wallet-amount">{walletBalance}</span>
                    {hasBalance && <a href={transferUrl} className="rpu-wallet-transfer-btn"><i className="las la-exchange-alt" /> {t('Transfer to Money Box')}</a>}
                </div>
            </div>

            {awards.length > 0 ? (
                <div className="rpu-tiers">
                    {awards.map((tier) => (
                        <div className={`rpu-tier ${tier.qualified ? 'rpu-tier--qualified' : 'rpu-tier--pending'}`} key={tier.id}>
                            <div className="rpu-tier-left">
                                <div className={`rpu-tier-medal ${tier.qualified ? 'rpu-medal--gold' : 'rpu-medal--grey'}`}><i className="las la-medal" /></div>
                                <div className="rpu-tier-info">
                                    <div className="rpu-tier-name">{tier.title}</div>
                                    <div className="rpu-tier-pv-req">{t(':pv PV required', { pv: tier.required })}</div>
                                </div>
                            </div>
                            <div className="rpu-tier-center">
                                <div className="rpu-bar-wrap">
                                    <div className="rpu-bar" role="progressbar" aria-valuenow={tier.pct} aria-valuemin={0} aria-valuemax={100}>
                                        <div className={`rpu-bar-fill ${tier.qualified ? 'rpu-bar-fill--done' : ''}`} style={{ width: `${tier.pct}%` }} />
                                    </div>
                                    <span className="rpu-bar-pct">{tier.pct}%</span>
                                </div>
                                {tier.qualified ? (
                                    <p className="rpu-tier-done-note"><i className="las la-check-circle" /> {t('Target reached — await admin credit')}</p>
                                ) : (
                                    <p className="rpu-tier-remaining">{t(':pv PV remaining', { pv: tier.remaining })}</p>
                                )}
                            </div>
                            <div className="rpu-tier-right">
                                <div className="rpu-tier-reward">
                                    <span className="rpu-reward-label">{t('Reward')}</span>
                                    <span className="rpu-reward-val">{tier.reward}</span>
                                </div>
                                {tier.qualified ? <span className="rpu-qualified-tag"><i className="las la-check" /> {t('Qualified')}</span> : <span className="rpu-pending-tag">{t('In Progress')}</span>}
                            </div>
                        </div>
                    ))}
                </div>
            ) : (
                <div className="rpu-empty">
                    <i className="las la-medal" />
                    <h3>{t('No awards configured')}</h3>
                    <p>{t('Award milestones will appear here once the admin sets them up.')}</p>
                </div>
            )}

            <div className="rpu-how-card">
                <div className="rpu-how-head"><i className="las la-info-circle" /> {t('How It Works')}</div>
                <div className="rpu-how-body">
                    {steps.map((text, i) => (
                        <div className="rpu-how-step" key={i}>
                            <div className="rpu-how-num">{i + 1}</div>
                            <div>
                                {i === 2 && <>{t('Once your total PV crosses an award threshold, you are marked as')} <strong>{t('Qualified')}</strong>.</>}
                                {i === 4 && <>{t('Visit')} <a href={transferUrl} className="rpu-inline-link">{t('Bonus Transfer')}</a> {t('to move your wallet balance into your Money Box.')}</>}
                                {text}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}

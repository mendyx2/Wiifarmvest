import { Head, Link } from '@inertiajs/react';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';
import './BinarySummary.css';

type Leg = {
    label: 'Left' | 'Right';
    members: number;
    deepest: number;
    direct: string | null;
    pairingPv: string;
    rankingPv: string;
    /** Register link for an empty first position under you */
    emptySlot: string | null;
};

type Props = PageProps<{
    inTree: boolean;
    legs: { left: Leg; right: Leg };
    totalMembers: number;
    binaryListUrl: string;
    treeUrl: string;
}>;

export default function BinarySummary({ inTree, legs, totalMembers, binaryListUrl, treeUrl }: Props) {
    const t = useTrans();

    return (
        <div className="page-binary-summary bx-page">
            <Head title={t('Binary Summary')} />
            <div className="bx-hero">
                <div className="bx-hero-glow1" />
                <div className="bx-hero-glow2" />
                <div className="bx-hero-body">
                    <div className="bx-hero-left">
                        <div className="bx-hero-eyebrow"><span className="bx-live-dot" /> {t('Genealogy Network')}</div>
                        <h2 className="bx-hero-title">{t('Binary Summary')}</h2>
                    </div>
                    <div className="bx-hero-bubble">
                        <span className="bx-bubble-num">{totalMembers.toLocaleString('en-US')}</span>
                        <span className="bx-bubble-lbl">{t('Members')}</span>
                    </div>
                </div>
            </div>

            {!inTree ? (
                <div className="bx-state bx-state--empty">
                    <div className="bx-state-orb" />
                    <div className="bx-state-ico"><i className="las la-sitemap" /></div>
                    <h5 className="bx-state-title">{t('You are not placed in the tree yet')}</h5>
                    <p className="bx-state-msg">{t('Your summary appears once you have a position in the stage 1 tree.')}</p>
                </div>
            ) : (
                <div className="bsm-legs">
                    {[legs.left, legs.right].map((leg) => (
                        <section className={`bsm-leg bsm-leg--${leg.label.toLowerCase()}`} key={leg.label} aria-label={t(':side leg', { side: t(leg.label) })}>
                            <div className="bsm-leg-head">
                                <span><i className={`las la-arrow-${leg.label === 'Left' ? 'left' : 'right'}`} /> {t(':side leg', { side: t(leg.label) })}</span>
                                {leg.direct && <span className="bsm-leg-direct"><i className="las la-user" /> {leg.direct}</span>}
                            </div>
                            <ul className="bsm-rows">
                                <li className="bsm-row"><span>{t('Members')}</span><strong>{leg.members.toLocaleString('en-US')}</strong></li>
                                <li className="bsm-row"><span>{t('Deepest level')}</span><strong>{leg.deepest}</strong></li>
                                <li className="bsm-row"><span>{t('Pairing PV')}</span><strong>{leg.pairingPv}</strong></li>
                                <li className="bsm-row"><span>{t('Ranking PV')}</span><strong>{leg.rankingPv}</strong></li>
                            </ul>
                            {leg.emptySlot && (
                                <Link href={leg.emptySlot} className="bsm-slot"><i className="las la-plus" /> {t('Register into this slot')}</Link>
                            )}
                        </section>
                    ))}
                </div>
            )}

            <p className="bsm-hint mt-3 px-0">
                {t('Pairing PV is what is still waiting to be matched for the matching bonus. Ranking PV is everything your leg has ever earned.')}
            </p>

            <div className="bsm-links">
                <Link href={binaryListUrl} className="bsm-link"><i className="las la-list" /> {t('Binary List')}</Link>
                <Link href={treeUrl} className="bsm-link"><i className="las la-sitemap" /> {t('My Tree')}</Link>
            </div>
        </div>
    );
}

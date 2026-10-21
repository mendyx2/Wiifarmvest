import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import Lightbox from '@/Components/Lightbox';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type Attachment = { view: string; download: string; isImage: boolean };
type State = 'verified' | 'pending' | 'rejected' | 'empty';

type Props = PageProps<{
    state: State;
    rejectionReason: string | null;
    submitted: boolean;
    kycRows: { label: string; value: string | null; file: Attachment | null }[];
    nin: string | null;
    idCardType: string | null;
    idCard: Attachment | null;
    guarantor: { name: string; username: string; status: 'pending' | 'accepted' | 'declined'; respondedAt: string | null } | null;
    formUrl: string;
}>;

const HERO_ICON: Record<State, [string, string]> = {
    verified: ['fas fa-shield-alt', 'fas fa-check-circle'],
    pending: ['fas fa-hourglass-half', 'fas fa-clock'],
    rejected: ['fas fa-times-circle', 'fas fa-exclamation-circle'],
    empty: ['fas fa-id-card', 'fas fa-exclamation-circle'],
};

export default function KycInfo({ state, rejectionReason, submitted, kycRows, nin, idCardType, idCard, guarantor, formUrl }: Props) {
    const t = useTrans();
    const [lightbox, setLightbox] = useState<string | null>(null);

    const hero: Record<State, { title: string; sub: string; badge: string }> = {
        verified: { title: t('Identity Verified'), sub: t('Your KYC has been approved. You are eligible to apply for loans.'), badge: t('Verified') },
        pending: { title: t('KYC Under Review'), sub: t('Your documents are being reviewed by our team. We will notify you once complete.'), badge: t('Pending Review') },
        rejected: { title: t('KYC Rejected'), sub: t('Your submission was not accepted. Please review the reason and resubmit.'), badge: t('Rejected') },
        empty: { title: t('KYC Not Submitted'), sub: t('Complete your identity verification to unlock loan applications.'), badge: t('Action Required') },
    };
    const cardTypes: Record<string, string> = {
        driving_license: t("Driver's License"),
        international_passport: t('International Passport'),
        nin: t('NIN Card'),
    };
    const pill = {
        accepted: <><i className="fas fa-check-circle" /> {t('Accepted')}</>,
        pending: <><i className="fas fa-hourglass-half" /> {t('Awaiting Response')}</>,
        declined: <><i className="fas fa-times-circle" /> {t('Declined')}</>,
    };

    return (
        <div className="kyc-info-page">
            <Head title={t('KYC Status')} />
            <div className="container">
                <div className={`ki-hero ${state}`}>
                    <div className="ki-hero-top">
                        <div className="ki-hero-icon"><i className={HERO_ICON[state][0]} /></div>
                        <div>
                            <p className="ki-hero-title">{hero[state].title}</p>
                            <p className="ki-hero-sub">{hero[state].sub}</p>
                        </div>
                    </div>
                    <div className="ki-hero-badge"><i className={HERO_ICON[state][1]} /> {hero[state].badge}</div>
                    {rejectionReason && (
                        <div className="ki-rejection mt-3">
                            <p className="ki-rejection-label">{t('Rejection Reason')}</p>
                            <p className="ki-rejection-text">{rejectionReason}</p>
                        </div>
                    )}
                </div>

                {!submitted ? (
                    <div className="ki-section">
                        <div className="ki-empty">
                            <div className="ki-empty-icon"><i className="fas fa-id-card-alt" /></div>
                            <p className="ki-empty-title">{t('No KYC Submission Found')}</p>
                            <p className="ki-empty-sub">{t('Fill out your identity details, upload your ID card, and nominate a guarantor to get started.')}</p>
                            <Link href={formUrl} className="ki-cta"><i className="fas fa-paper-plane" /> {t('Start KYC Verification')}</Link>
                        </div>
                    </div>
                ) : (
                    <>
                        {kycRows.length > 0 && (
                            <Section icon="fas fa-user" title={t('Basic Information')}>
                                {kycRows.map((row, i) => (
                                    <Row key={i} label={row.label}>
                                        {row.file ? <FileLinks file={row.file} onView={setLightbox} /> : row.value}
                                    </Row>
                                ))}
                            </Section>
                        )}

                        {(nin || idCardType || idCard) && (
                            <Section icon="fas fa-id-card" title={t('Identity Verification')}>
                                {nin && <Row label={t('NIN')}>{nin}</Row>}
                                {idCardType && <Row label={t('ID Card Type')}>{cardTypes[idCardType] ?? idCardType.replace(/_/g, ' ')}</Row>}
                                {idCard && <Row label={t('ID Card Image')}><FileLinks file={idCard} onView={setLightbox} large /></Row>}
                            </Section>
                        )}

                        <Section icon="fas fa-handshake" title={t('Guarantor')}>
                            {guarantor ? (
                                <>
                                    <Row label={t('Nominated Guarantor')}>
                                        {guarantor.name} <span style={{ color: '#6B7280', fontWeight: 400 }}>(@ {guarantor.username})</span>
                                    </Row>
                                    <Row label={t('Guarantor Status')}><span className={`ki-g-pill ${guarantor.status}`}>{pill[guarantor.status]}</span></Row>
                                    {guarantor.respondedAt && <Row label={t('Responded')}>{guarantor.respondedAt}</Row>}
                                </>
                            ) : (
                                <Row label={t('Guarantor')}><span style={{ color: '#9CA3AF' }}>{t('Not nominated')}</span></Row>
                            )}
                        </Section>

                        {state !== 'verified' && (
                            <div className="ki-section">
                                <div className="ki-action-row">
                                    {state === 'pending' ? (
                                        <span style={{ fontSize: '.85rem', color: '#6B7280', alignSelf: 'center' }}>
                                            <i className="fas fa-info-circle" style={{ color: '#D97706' }} /> {t('Your submission is under review. No action needed right now.')}
                                        </span>
                                    ) : (
                                        <Link href={formUrl} className="ki-cta">
                                            <i className="fas fa-redo-alt" /> {rejectionReason ? t('Resubmit KYC') : t('Edit & Resubmit')}
                                        </Link>
                                    )}
                                </div>
                            </div>
                        )}
                    </>
                )}
            </div>
            <Lightbox src={lightbox} onClose={() => setLightbox(null)} />
        </div>
    );
}

function Section({ icon, title, children }: { icon: string; title: string; children: React.ReactNode }) {
    return (
        <div className="ki-section">
            <div className="ki-section-header">
                <div className="ki-section-icon"><i className={icon} /></div>
                <p className="ki-section-title">{title}</p>
            </div>
            {children}
        </div>
    );
}

function Row({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="ki-row">
            <span className="ki-row-label">{label}</span>
            <span className="ki-row-value">{children}</span>
        </div>
    );
}

function FileLinks({ file, onView, large = false }: { file: Attachment; onView: (src: string) => void; large?: boolean }) {
    const t = useTrans();
    const link = { fontSize: '.8rem', fontWeight: 600, textDecoration: 'none' } as const;
    return (
        <span style={{ display: 'flex', gap: 8, alignItems: 'center', justifyContent: 'flex-end', flexWrap: 'wrap' }}>
            {file.isImage && (
                <>
                    <button type="button" className="p-0 border-0 bg-transparent" onClick={() => onView(file.view)} aria-label={t('View')}>
                        <img src={file.view} alt="" style={{ height: large ? 44 : 40, width: large ? 66 : 60, objectFit: 'cover', borderRadius: large ? 6 : 5, cursor: 'zoom-in', border: '1px solid #E5E7EB' }} />
                    </button>
                    <button type="button" className="p-0 border-0 bg-transparent" style={{ ...link, color: '#059669' }} onClick={() => onView(file.view)}>
                        <i className="fas fa-eye" /> {t('View')}
                    </button>
                </>
            )}
            <a href={file.download} style={{ ...link, color: '#6B7280' }}>
                <i className="fas fa-download" /> {t('Download')}
            </a>
        </span>
    );
}

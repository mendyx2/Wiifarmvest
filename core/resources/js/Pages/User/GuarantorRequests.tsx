import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import Pagination from '@/Components/Pagination';
import { useTrans } from '@/lib/i18n';
import { initials } from '@/lib/initials';
import type { PageProps, Paginated } from '@/types';

type Request = {
    id: number;
    name: string;
    username: string | null;
    status: 'pending' | 'accepted' | 'declined';
    requested: string;
    responded: string | null;
    respondedAt: string | null;
    acceptUrl: string;
    declineUrl: string;
};

type Props = PageProps<{ requests: Paginated<Request> }>;

const BADGE_ICON = { pending: 'fas fa-hourglass-half', accepted: 'fas fa-check-circle', declined: 'fas fa-times-circle' };

export default function GuarantorRequests({ requests }: Props) {
    const t = useTrans();
    const [busy, setBusy] = useState<number | null>(null);
    const label = { pending: t('Pending'), accepted: t('Accepted'), declined: t('Declined') };

    function respond(req: Request, url: string) {
        router.post(url, {}, { preserveScroll: true, onStart: () => setBusy(req.id), onFinish: () => setBusy(null) });
    }

    return (
        <div className="gr-page">
            <Head title={t('Guarantor Requests')} />
            <div className="container">
                <div className="gr-hero">
                    <div className="gr-hero-icon"><i className="fas fa-handshake" /></div>
                    <h2>{t('Guarantor Requests')}</h2>
                    <p>{t('Users who have nominated you as their KYC guarantor. Accept to support their verification.')}</p>
                </div>
                <div className="row g-3">
                    {requests.data.length === 0 ? (
                        <div className="gr-empty">
                            <div className="gr-empty-icon"><i className="fas fa-handshake-slash" /></div>
                            <p className="gr-empty-title">{t('No Guarantor Requests')}</p>
                            <p className="gr-empty-sub">{t('You have not been nominated as a guarantor by anyone yet.')}</p>
                        </div>
                    ) : (
                        requests.data.map((req) => (
                            <div className="gr-card col-sm-6 col-md-6 col-lg-6 col-xl-4 col-12" key={req.id}>
                                <div className="gr-card-header">
                                    <div className="gr-avatar">{initials(req.name)}</div>
                                    <div className="gr-user-info-block">
                                        <p className="gr-user-name">{req.name}</p>
                                        {req.username && <p className="gr-user-handle">@{req.username}</p>}
                                    </div>
                                    <span className={`gr-badge gr-badge-${req.status}`}>
                                        <i className={BADGE_ICON[req.status]} /> {label[req.status]}
                                    </span>
                                </div>
                                <div className="gr-card-body">
                                    <div className="gr-meta">
                                        <div className="gr-meta-item">
                                            <i className="fas fa-calendar-alt gr-meta-icon" />
                                            <span className="gr-meta-text">{t('Requested')} {req.requested}</span>
                                        </div>
                                        {req.responded && (
                                            <div className="gr-meta-item">
                                                <i className="fas fa-reply gr-meta-icon" />
                                                <span className="gr-meta-text">{t('Responded')} {req.responded}</span>
                                            </div>
                                        )}
                                    </div>
                                    {req.status === 'pending' ? (
                                        <div className="gr-actions">
                                            <button type="button" className="gr-btn gr-btn-accept" disabled={busy !== null} onClick={() => respond(req, req.acceptUrl)}>
                                                <i className="fas fa-check-circle" /> {t('Accept')}
                                            </button>
                                            <button type="button" className="gr-btn gr-btn-decline" disabled={busy !== null} onClick={() => respond(req, req.declineUrl)}>
                                                <i className="fas fa-times-circle" /> {t('Decline')}
                                            </button>
                                        </div>
                                    ) : (
                                        <div className={`gr-responded gr-responded-${req.status}`}>
                                            <i className={BADGE_ICON[req.status]} />
                                            <span>{req.status === 'accepted' ? t('You accepted this request.') : t('You declined this request.')}</span>
                                            <span className="gr-responded-date">{req.respondedAt}</span>
                                        </div>
                                    )}
                                </div>
                            </div>
                        ))
                    )}
                </div>
                <Pagination links={requests.links} className="mt-3" />
            </div>
        </div>
    );
}

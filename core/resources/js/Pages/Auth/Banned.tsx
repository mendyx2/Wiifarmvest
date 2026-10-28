import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';
import './Banned.css';

type Props = PageProps<{ reason: string | null; id: number; date: string; urls: { ticket: string; home: string; logout: string } }>;

export default function Banned({ reason, id, date, urls }: Props) {
    const t = useTrans();

    return (
        <div className="page-banned">
            <Head title={t('Banned')} />
            <div className="ban-screen">
                <div className="ban-card">
                    <div className="ban-card__visual">
                        <div className="ban-card__icon-ring">
                            <svg className="ban-card__icon-svg" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <circle cx="32" cy="32" r="27" stroke="currentColor" strokeWidth="2.5" />
                                <path d="M12.5 12.5L51.5 51.5" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" />
                            </svg>
                        </div>
                        <span className="ban-card__visual-label">{t('Suspended')}</span>
                    </div>
                    <div className="ban-card__content">
                        <div className="ban-card__badge">
                            <svg width="10" height="10" viewBox="0 0 10 10" fill="currentColor" aria-hidden="true">
                                <circle cx="5" cy="5" r="5" />
                            </svg>
                            {t('Administrator Action')}
                        </div>
                        <h2 className="ban-card__title">{t('Your account access has been restricted.')}</h2>
                        <p className="ban-card__desc">
                            {t('An administrator has suspended your account. Review the stated reason below. If you believe this is in error, open a support ticket and our team will investigate.')}
                        </p>
                        {reason && (
                            <div className="ban-card__reason">
                                <span className="ban-card__reason-tag">{t('Reason for suspension')}</span>
                                <p className="ban-card__reason-body">{reason}</p>
                            </div>
                        )}
                        <div className="ban-card__actions">
                            <a href={urls.ticket} className="ban-btn ban-btn--primary">
                                {t('Open a Support Ticket')}
                            </a>
                            <a href={urls.home} className="ban-btn ban-btn--ghost">
                                {t('Back to Home')}
                            </a>
                        </div>
                        <p className="ban-card__meta">
                            {t('Account')} <strong>#{id}</strong> &nbsp;&middot;&nbsp; {date} &nbsp;&middot;&nbsp;{' '}
                            <a href={urls.logout}>{t('Sign Out')}</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}

// Full screen, without the dashboard around it.
Banned.layout = (page: ReactNode) => page;

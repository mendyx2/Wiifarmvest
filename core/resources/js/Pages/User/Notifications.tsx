import { Head } from '@inertiajs/react';
import Pagination from '@/Components/Pagination';
import { useTrans } from '@/lib/i18n';
import type { PageProps, Paginated } from '@/types';

type Notification = {
    id: number;
    subject: string;
    message: string;
    type: string;
    unread: boolean;
    adminNote: boolean;
    from: string | null;
    time: string;
};

type Props = PageProps<{ notifications: Paginated<Notification> }>;

const TYPE_ICONS: Record<string, string> = { email: 'la-envelope', sms: 'la-sms', push: 'la-bell' };

export default function Notifications({ notifications }: Props) {
    const t = useTrans();
    // Literal t('...') calls so the admin "fetch keywords" scan finds them.
    const typeLabels: Record<string, string> = { email: t('Email'), sms: t('SMS'), push: t('Push') };
    const typeLabel = (type: string) => typeLabels[type] ?? type.charAt(0).toUpperCase() + type.slice(1);

    return (
        <div className="notif-page">
            <Head title={t('Notifications')} />

            <div className="notif-header">
                <h5 className="notif-title">
                    <i className="las la-bell" /> {t('Notifications')}
                </h5>
                <span className="notif-count">{t(':count total', { count: notifications.total })}</span>
            </div>

            <div className="notif-list-card">
                {notifications.data.length === 0 ? (
                    // The Blade markup for this empty state differs from the shared
                    // EmptyState component (an <i> instead of a wrapper div), so keep it.
                    <div className="notif-empty">
                        <i className="las la-bell-slash notif-empty-icon" />
                        <h6>{t('No Notifications Yet')}</h6>
                        <p>{t("You'll see system alerts and updates here.")}</p>
                    </div>
                ) : (
                    notifications.data.map((notif) => (
                        <div key={notif.id} className={`notif-item ${notif.unread ? 'notif-unread' : ''}`}>
                            {notif.unread && <span className="notif-unread-dot" />}
                            <div className={`notif-icon-wrap ${notif.adminNote ? 'notif-icon-admin' : `notif-icon-${notif.type}`}`}>
                                <i className={`las ${notif.adminNote ? 'la-user-shield' : (TYPE_ICONS[notif.type] ?? 'la-bell')}`} />
                            </div>
                            <div className="notif-body">
                                <div className="notif-subject">{t(notif.subject)}</div>
                                <div className="notif-message">{notif.message}</div>
                                <div className="notif-meta">
                                    {notif.adminNote ? (
                                        <>
                                            <span className="notif-admin-badge">
                                                <i className="las la-user-shield" /> {t('Personal Notice')}
                                            </span>
                                            {notif.from && <span className="notif-from">{t('From :name', { name: notif.from })}</span>}
                                        </>
                                    ) : (
                                        <span className="notif-type-badge">{typeLabel(notif.type)}</span>
                                    )}
                                    <span className="notif-time">{notif.time}</span>
                                </div>
                            </div>
                        </div>
                    ))
                )}
            </div>

            <Pagination links={notifications.links} className="notif-pagination" />
        </div>
    );
}

import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import PageHeader from '@/Components/Admin/PageHeader';
import ConfirmDialog from '@/Components/ConfirmDialog';
import Pagination from '@/Components/Pagination';
import { useTrans } from '@/lib/i18n';
import type { Paginated } from '@/types';

type Notification = { id: number; title: string; time: string; unread: boolean; url: string; deleteUrl: string };

type Props = {
    notifications: Paginated<Notification>;
    hasUnread: boolean;
    urls: { readAll: string; deleteAll: string };
};

/** All admin notifications (/admin/notifications): open, delete one, mark all read, delete all. */
export default function AdminNotifications({ notifications, hasUnread, urls }: Props) {
    const t = useTrans();
    const [confirm, setConfirm] = useState<{ question: string; url: string } | null>(null);
    const [busy, setBusy] = useState(false);
    const hasAny = notifications.total > 0;

    const run = () => {
        if (!confirm) return;
        router.post(confirm.url, {}, {
            preserveScroll: true,
            onStart: () => setBusy(true),
            onFinish: () => {
                setBusy(false);
                setConfirm(null);
            },
        });
    };

    return (
        <>
            <PageHeader title={t('Notifications')}>
                {hasUnread && (
                    <Link href={urls.readAll} className="btn btn-sm btn-outline--primary" preserveScroll>
                        <i className="las la-check" />
                        {t('Mark All as Read')}
                    </Link>
                )}
                {hasAny && (
                    <button type="button" className="btn btn-sm btn-outline--danger" onClick={() => setConfirm({ question: t('Are you sure to delete all notifications?'), url: urls.deleteAll })}>
                        <i className="las la-trash" />
                        {t('Delete all Notification')}
                    </button>
                )}
            </PageHeader>

            <div className="notify__area">
                {notifications.data.length ? (
                    notifications.data.map((n) => (
                        <div className="notify-item-wrapper" key={n.id}>
                            {/* Marks it read, then goes to its page (often Blade): a full load. */}
                            <a className={`notify__item ${n.unread ? 'unread--notification' : ''}`} href={n.url}>
                                <div className="notify__content d-flex justify-content-between">
                                    <div>
                                        <h6 className="title">{n.title}</h6>
                                        <span className="date">
                                            <i className="las la-clock" /> {n.time}
                                        </span>
                                    </div>
                                </div>
                            </a>
                            <button
                                type="button"
                                className="btn btn-sm btn-outline--danger notify-delete-btn"
                                aria-label={t('Delete')}
                                onClick={() => setConfirm({ question: t('Are you sure to delete the notification?'), url: n.deleteUrl })}
                            >
                                <i className="las la-trash me-0" />
                            </button>
                        </div>
                    ))
                ) : (
                    <div className="card">
                        <div className="card-body">
                            <div className="empty-notification-list text-center">
                                <h5 className="text-muted">{t('No notification found.')}</h5>
                            </div>
                        </div>
                    </div>
                )}
                <Pagination links={notifications.links} className="mt-3" />
            </div>

            <ConfirmDialog open={confirm !== null} question={confirm?.question ?? ''} busy={busy} onConfirm={run} onCancel={() => setConfirm(null)} />
        </>
    );
}

import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import { UserCell, WhenCell, type AdminUser, type When } from '@/Components/Admin/Cells';
import DataTable from '@/Components/Admin/DataTable';
import DateRangeField, { dateQuery, type DateRange } from '@/Components/Admin/DateRangeField';
import FilterCard from '@/Components/Admin/FilterCard';
import PageHeader from '@/Components/Admin/PageHeader';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';
import type { Paginated } from '@/types';

type Row = {
    id: number;
    user: AdminUser;
    when: When;
    type: string;
    typeLabel: string;
    sender: string;
    subject: string | null;
    sentTo: string;
    detailUrl: string | null;
    message: string | null;
    image: string | null;
};

type Props = {
    title: string;
    logs: Paginated<Row>;
    /** null on one member's list, which has no filters */
    filters: { search: string; date: DateRange } | null;
};

/** Report → Notification History: every email, SMS and push sent to members (or to one member). */
export default function NotificationHistory({ title, logs, filters }: Props) {
    const t = useTrans();
    const url = usePage().url;
    const [search, setSearch] = useState(filters?.search ?? '');
    const [date, setDate] = useState(filters?.date ?? { from: '', to: '' });
    const [detail, setDetail] = useState<Row | null>(null);

    return (
        <>
            <PageHeader title={t(title)} />
            {filters && (
                <FilterCard onSubmit={() => visitWithQuery(url, { search: search.trim(), date: dateQuery(date) })}>
                    <div className="flex-grow-1">
                        <label htmlFor="notif-search">{t('Username')}</label>
                        <input id="notif-search" className="form-control" type="search" value={search} onChange={(e) => setSearch(e.target.value)} placeholder={t('Search')} />
                    </div>
                    <div className="flex-grow-1">
                        <DateRangeField value={date} onChange={setDate} />
                    </div>
                </FilterCard>
            )}
            <DataTable
                responsive="sm"
                rows={logs.data}
                rowKey={(r) => r.id}
                links={logs.links}
                columns={[
                    { label: t('User'), cell: (r) => (r.user ? <UserCell user={r.user} /> : <span className="fw-bold">{t('System')}</span>) },
                    { label: t('Sent'), cell: (r) => <WhenCell when={r.when} /> },
                    {
                        label: t('Sender'),
                        cell: (r) => (
                            <>
                                <span className="fw-bold">{r.typeLabel}</span>
                                <br />
                                {t('via')} {r.sender}
                            </>
                        ),
                    },
                    { label: t('Subject'), cell: (r) => r.subject ?? t('N/A') },
                    {
                        label: t('Action'),
                        cell: (r) => (
                            <button type="button" className="btn btn-sm btn-outline--primary" onClick={() => setDetail(r)}>
                                <i className="las la-desktop" /> {t('Detail')}
                            </button>
                        ),
                    },
                ]}
            />

            <Modal open={detail !== null} onClose={() => setDetail(null)} labelledBy="notify-detail-title" size="lg">
                <div className="modal-header">
                    <h5 className="modal-title" id="notify-detail-title">
                        {t('Notification Details')}
                    </h5>
                    <button type="button" className="close" aria-label={t('Close')} onClick={() => setDetail(null)}>
                        <i className="las la-times" />
                    </button>
                </div>
                {detail && (
                    <div className="modal-body">
                        <h3 className="text-center mb-3">
                            {t('To')}: <span className="sent_to">{detail.sentTo}</span>
                        </h3>
                        {detail.detailUrl ? (
                            // sandbox: the email's HTML may contain text a member typed; it must not run scripts as the admin.
                            <iframe src={detail.detailUrl} sandbox="" height={500} width="100%" title={t('Email message')} className="border-0" />
                        ) : (
                            <div className="detail">
                                {detail.image && <img src={detail.image} className="w-100 mb-2" alt="" />}
                                {/* SMS and push text: shown as text, not HTML (the Blade page inserted it as HTML) */}
                                <p className="admin-notify-text">{detail.message}</p>
                            </div>
                        )}
                    </div>
                )}
            </Modal>
        </>
    );
}

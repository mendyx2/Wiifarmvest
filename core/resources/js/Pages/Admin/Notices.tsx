import { Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import type { Channel } from '@/Components/Admin/ChannelPicker';
import DataTable from '@/Components/Admin/DataTable';
import { DialogHeader, FieldError } from '@/Components/Admin/Form';
import NotificationFields, { type MessageData } from '@/Components/Admin/NotificationFields';
import PageHeader from '@/Components/Admin/PageHeader';
import SearchForm from '@/Components/Admin/SearchForm';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';
import type { Paginated } from '@/types';

type Notice = { id: number; member: { name: string; username: string; url: string } | null; subject: string; message: string; via: string; by: string; date: string };

type Props = {
    stats: { total: number; today: number; members: number };
    search: string;
    notices: Paginated<Notice>;
    channels: Channel[];
    urls: { store: string; index: string };
};

const VIA_TONE: Record<string, string> = { email: 'primary', sms: 'success', push: 'warning' };

/** Direct Notices: messages sent by an admin to one member, and the form to send one. */
export default function Notices({ stats, search, notices, channels, urls }: Props) {
    const t = useTrans();
    const [sending, setSending] = useState(false);

    return (
        <>
            <PageHeader title={t('Direct Notices')}>
                <SearchForm url={urls.index} value={search} placeholder="Username or email" />
                {channels.length > 0 && (
                    <button type="button" className="btn btn-sm btn-outline--primary" onClick={() => setSending(true)}>
                        <i className="las la-plus" /> {t('New Notice')}
                    </button>
                )}
            </PageHeader>
            {channels.length === 0 && (
                <div className="card bl--5 border--warning mb-4">
                    <div className="card-body text--warning">{t('Email, SMS and push notifications are all switched off in Notification Setting, so no notice can be sent.')}</div>
                </div>
            )}
            <div className="row gy-3 mb-4">
                {(
                    [
                        ['Notices sent', stats.total, 'las la-paper-plane'],
                        ['Sent today', stats.today, 'las la-calendar-day'],
                        ['Members reached', stats.members, 'las la-users'],
                    ] as const
                ).map(([label, value, icon]) => (
                    <div className="col-sm-4" key={label}>
                        <div className="card">
                            <div className="card-body d-flex align-items-center gap-3">
                                <i className={`${icon} fs-2 text--primary`} aria-hidden />
                                <div>
                                    <h4 className="mb-0">{value}</h4>
                                    <small className="text-muted">{t(label)}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
            <DataTable
                rows={notices.data}
                rowKey={(n) => n.id}
                links={notices.links}
                empty={search ? t('No notice matches this search.') : t('No notice sent yet.')}
                columns={[
                    {
                        label: t('Recipient'),
                        cell: (n) =>
                            n.member ? (
                                <Link href={n.member.url}>
                                    {n.member.name}
                                    <small className="text-muted d-block">@{n.member.username}</small>
                                </Link>
                            ) : (
                                '—'
                            ),
                    },
                    {
                        label: t('Notice'),
                        cell: (n) => (
                            <div style={{ maxWidth: 380 }} className="ms-auto ms-lg-0">
                                <span className="fw-bold d-block">{n.subject || '—'}</span>
                                <small className="text-muted d-block text-truncate" title={n.message}>
                                    {n.message}
                                </small>
                            </div>
                        ),
                    },
                    { label: t('Via'), cell: (n) => <span className={`badge badge--${VIA_TONE[n.via] ?? 'dark'}`}>{n.via}</span> },
                    { label: t('Sent By'), cell: (n) => n.by },
                    { label: t('Date'), cell: (n) => n.date },
                ]}
            />
            {sending && <SendDialog channels={channels} url={urls.store} onClose={() => setSending(false)} />}
        </>
    );
}

function SendDialog({ channels, url, onClose }: { channels: Channel[]; url: string; onClose: () => void }) {
    const t = useTrans();
    const form = useForm<MessageData & { username: string }>({ username: '', via: channels[0], subject: '', message: '', image: null });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { forceFormData: true, preserveScroll: true, onSuccess: onClose });
    };
    return (
        <Modal open onClose={onClose} labelledBy="notice-title" size="lg">
            <DialogHeader id="notice-title" title={t('Send Direct Notice')} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    <div className="form-group">
                        <label htmlFor="notice-username">{t('Username')}</label>
                        <input id="notice-username" className="form-control" required maxLength={40} placeholder={t('Recipient username')} value={form.data.username} onChange={(e) => form.setData('username', e.target.value.trim())} />
                        <FieldError message={form.errors.username} />
                    </div>
                    <NotificationFields channels={channels} data={form.data} errors={form.errors} onChange={(next) => form.setData({ ...form.data, ...next })} />
                </div>
                <div className="modal-footer">
                    <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing}>
                        {t('Send')}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

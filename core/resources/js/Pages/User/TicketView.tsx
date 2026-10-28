import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import AttachmentPicker, { chosenFiles, attachmentError } from '@/Components/AttachmentPicker';
import ConfirmDialog from '@/Components/ConfirmDialog';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type Message = { id: number; fromStaff: boolean; author: string; postedOn: string; message: string; attachments: string[] };

type Props = PageProps<{
    ticket: { id: number; number: string; subject: string; status: { label: string; tone: string }; closed: boolean };
    messages: Message[];
    maxUpload: string;
    replyUrl: string;
    closeUrl: string;
    listUrl: string;
    emptyImage: string;
}>;

export default function TicketView({ ticket, messages, maxUpload, replyUrl, closeUrl, listUrl, emptyImage }: Props) {
    const t = useTrans();
    const [confirmClose, setConfirmClose] = useState(false);
    const [closing, setClosing] = useState(false);
    const form = useForm({ message: '', attachments: [] as (File | null)[] });

    function reply(event: FormEvent) {
        event.preventDefault();
        form.transform((data) => ({ ...data, attachments: chosenFiles(data.attachments) }));
        form.post(replyUrl, { preserveScroll: true, onSuccess: () => form.reset() });
    }

    function close() {
        router.post(closeUrl, {}, {
            preserveScroll: true,
            onStart: () => setClosing(true),
            onFinish: () => {
                setClosing(false);
                setConfirmClose(false);
            },
        });
    }

    return (
        <div className="container">
            <Head title={t('View Ticket')} />
            <div className="text-end mb-3">
                <Link href={listUrl} className="btn btn--base">
                    <i className="las la-list" /> {t('My Support Ticket')}
                </Link>
            </div>
            <div className="card custom--card">
                <div className="card-header card-header-bg d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <h5 className="mt-0">
                        <span className={`badge badge--${ticket.status.tone}`}>{t(ticket.status.label)}</span> [{t('Ticket')}#{ticket.number}] {ticket.subject}
                    </h5>
                    {!ticket.closed && (
                        <button className="btn btn-danger close-button btn-sm" type="button" aria-label={t('Close ticket')} onClick={() => setConfirmClose(true)}>
                            <i className="fas fa-lg fa-times-circle" />
                        </button>
                    )}
                </div>
                <div className="card-body">
                    <form onSubmit={reply}>
                        <div className="row justify-content-between">
                            <div className="col-md-12">
                                <div className="form-group">
                                    <textarea
                                        className="form-control form--control"
                                        rows={4}
                                        aria-label={t('Your reply')}
                                        value={form.data.message}
                                        onChange={(e) => form.setData('message', e.target.value)}
                                        required
                                    />
                                    {form.errors.message && <small style={{ color: '#DC2626' }}>{form.errors.message}</small>}
                                </div>
                            </div>
                            <div className="col-md-9">
                                <AttachmentPicker files={form.data.attachments} onChange={(files) => form.setData('attachments', files)} maxUpload={maxUpload} error={attachmentError(form.errors)} />
                            </div>
                            <div className="col-md-3">
                                <button className="btn btn--base w-100 my-2" type="submit" disabled={form.processing}>
                                    <i className="la la-fw la-lg la-reply" /> {t('Reply')}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div className="card mt-4 custom--card">
                <div className="card-body">
                    {messages.length === 0 ? (
                        <div className="empty-message text-center">
                            <img src={emptyImage} alt="" style={{ width: 120, marginBottom: 15 }} />
                            <h5 className="text-muted">{t('No replies found here!')}</h5>
                        </div>
                    ) : (
                        messages.map((m) => (
                            <div
                                key={m.id}
                                className={`${m.fromStaff ? 'border-warning' : 'border-primary'} support-message border-radius-3 border py-3`}
                                style={m.fromStaff ? { backgroundColor: '#ffd96729' } : undefined}
                            >
                                <div className="message-box border-end text-end">
                                    <h5 className="my-3">{m.author}</h5>
                                    {m.fromStaff && <p className="lead text-muted">{t('Staff')}</p>}
                                </div>
                                <div className="message-box">
                                    <p className="text-muted fw-bold my-3">{t('Posted on')} {m.postedOn}</p>
                                    {/* white-space keeps the line breaks people type, still as plain text */}
                                    <p style={{ whiteSpace: 'pre-line' }}>{m.message}</p>
                                    {m.attachments.length > 0 && (
                                        <div className="mt-2">
                                            {m.attachments.map((url, i) => (
                                                <a className="me-3" href={url} key={url}>
                                                    <i className="fa-regular fa-file" /> {t('Attachment')} {i + 1}
                                                </a>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            </div>
                        ))
                    )}
                </div>
            </div>

            <ConfirmDialog
                open={confirmClose}
                question={t('Are you sure to close this ticket?')}
                onConfirm={close}
                onCancel={() => setConfirmClose(false)}
                busy={closing}
            />
        </div>
    );
}

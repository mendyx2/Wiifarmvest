import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AttachmentPicker, { attachmentError, chosenFiles } from '@/Components/AttachmentPicker';
import { ConfirmButton, FieldError, withId } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';
import type { Tone } from './Tickets';

type Message = { id: number; staff: boolean; author: string; message: string; date: string; attachments: { name: string; url: string }[] };

type Props = {
    ticket: { id: number; number: string; subject: string; name: string; username: string; email: string; userUrl: string | null; status: Tone; priority: Tone; closed: boolean; opened: string };
    messages: Message[];
    maxUpload: string;
    urls: { reply: string; close: string; delete: string; back: string };
};

/** One support ticket: the conversation, newest first, with the reply form. */
export default function Ticket({ ticket, messages, maxUpload, urls }: Props) {
    const t = useTrans();
    const form = useForm({ message: '', attachments: [] as (File | null)[] });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({ ...d, attachments: chosenFiles(d.attachments) }));
        form.post(urls.reply, { preserveScroll: true, forceFormData: true, onSuccess: () => form.reset() });
    };

    return (
        <>
            <PageHeader title={t('Reply Ticket')}>
                {!ticket.closed && (
                    <ConfirmButton url={urls.close} question={t('Are you sure to close this ticket?')} className="btn btn-sm btn--danger">
                        <i className="las la-times-circle" /> {t('Close Ticket')}
                    </ConfirmButton>
                )}
                <Link href={urls.back} className="btn btn-sm btn-outline--primary">
                    <i className="la la-undo" /> {t('Back')}
                </Link>
            </PageHeader>
            <div className="card mb-4">
                <div className="card-body">
                    <h5 className="mb-2 d-flex flex-wrap align-items-center gap-2">
                        <span className={`badge badge--${ticket.status[1]}`}>{t(ticket.status[0])}</span>
                        <span>
                            [{t('Ticket')}#{ticket.number}] {ticket.subject}
                        </span>
                    </h5>
                    <p className="text-muted mb-0 small">
                        {ticket.userUrl ? <Link href={ticket.userUrl}>{ticket.name}</Link> : ticket.name}
                        {ticket.username && ` (@${ticket.username})`}
                        {ticket.email && ` · ${ticket.email}`} · {t('Opened')} {ticket.opened} · {t('Priority')}: <span className={`badge badge--${ticket.priority[1]}`}>{t(ticket.priority[0])}</span>
                    </p>
                </div>
                {ticket.closed ? (
                    <div className="card-footer text-muted">{t('This ticket is closed. A reply from the member opens it again.')}</div>
                ) : (
                    <form onSubmit={submit} className="card-footer">
                        <label htmlFor="reply-message" className="form-label">
                            {t('Reply')}
                        </label>
                        <textarea id="reply-message" className="form-control" rows={5} required placeholder={t('Enter reply here')} value={form.data.message} onChange={(e) => form.setData('message', e.target.value)} />
                        <FieldError message={form.errors.message} />
                        <AttachmentPicker files={form.data.attachments} onChange={(files) => form.setData('attachments', files)} maxUpload={maxUpload} error={attachmentError(form.errors)} />
                        <button type="submit" className="btn btn--primary w-100 mt-2" disabled={form.processing}>
                            <i className="la la-reply" /> {t('Reply')}
                        </button>
                    </form>
                )}
            </div>
            {messages.map((m) => (
                <div key={m.id} className={`card mb-3 ${m.staff ? 'border--primary' : ''}`} style={{ borderLeft: `4px solid ${m.staff ? '#4634ff' : '#ced4da'}` }}>
                    <div className="card-body">
                        <div className="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <h6 className="mb-0">
                                    {m.author} {m.staff && <span className="badge badge--primary ms-1">{t('Staff')}</span>}
                                </h6>
                                <small className="text-muted">
                                    {t('Posted on')} {m.date}
                                </small>
                            </div>
                            <ConfirmButton url={withId(urls.delete, m.id)} question={t('Are you sure to delete this message?')} className="btn btn-sm btn-outline--danger">
                                <i className="la la-trash" /> {t('Delete')}
                            </ConfirmButton>
                        </div>
                        <p className="mb-2" style={{ whiteSpace: 'pre-wrap', wordBreak: 'break-word' }}>
                            {m.message}
                        </p>
                        {m.attachments.length > 0 && (
                            <div className="d-flex flex-wrap gap-3">
                                {m.attachments.map((a) => (
                                    <a key={a.url} href={a.url}>
                                        <i className="las la-file" /> {t(a.name)}
                                    </a>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            ))}
        </>
    );
}

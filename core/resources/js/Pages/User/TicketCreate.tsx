import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AttachmentPicker, { chosenFiles, attachmentError } from '@/Components/AttachmentPicker';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type Props = PageProps<{ maxUpload: string; storeUrl: string; listUrl: string }>;

export default function TicketCreate({ maxUpload, storeUrl, listUrl }: Props) {
    const t = useTrans();
    const form = useForm({ subject: '', priority: '3', message: '', attachments: [] as (File | null)[] });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.transform((data) => ({ ...data, attachments: chosenFiles(data.attachments) }));
        form.post(storeUrl, { preserveScroll: true });
    }

    return (
        <div className="row">
            <Head title={t('Open Ticket')} />
            <div className="col-md-12">
                <div className="text-end mb-3">
                    <Link href={listUrl} className="btn btn--base">
                        <i className="las la-list" /> {t('My Support Ticket')}
                    </Link>
                </div>
                <div className="card custom--card">
                    <div className="card-body">
                        <form onSubmit={submit}>
                            <div className="row">
                                <div className="form--group col-md-6">
                                    <label className="form--label" htmlFor="subject">{t('Subject')}</label>
                                    <input id="subject" type="text" className="form-control form--control" value={form.data.subject} onChange={(e) => form.setData('subject', e.target.value)} required />
                                    {form.errors.subject && <small style={{ color: '#DC2626' }}>{form.errors.subject}</small>}
                                </div>
                                <div className="form--group col-md-6">
                                    <label className="form--label" htmlFor="priority">{t('Priority')}</label>
                                    <select id="priority" className="form-select form--control" value={form.data.priority} onChange={(e) => form.setData('priority', e.target.value)} required>
                                        <option value="3">{t('High')}</option>
                                        <option value="2">{t('Medium')}</option>
                                        <option value="1">{t('Low')}</option>
                                    </select>
                                </div>
                                <div className="col-12 form--group">
                                    <label className="form--label" htmlFor="inputMessage">{t('Message')}</label>
                                    <textarea id="inputMessage" rows={6} className="form-control form--control" value={form.data.message} onChange={(e) => form.setData('message', e.target.value)} required />
                                    {form.errors.message && <small style={{ color: '#DC2626' }}>{form.errors.message}</small>}
                                </div>
                                <div className="col-md-9">
                                    <AttachmentPicker files={form.data.attachments} onChange={(files) => form.setData('attachments', files)} maxUpload={maxUpload} error={attachmentError(form.errors)} />
                                </div>
                                <div className="col-md-3">
                                    <button className="btn btn--base w-100 my-2" type="submit" disabled={form.processing}>
                                        <i className="las la-paper-plane" /> {t('Submit')}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
}

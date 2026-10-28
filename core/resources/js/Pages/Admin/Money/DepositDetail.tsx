import { useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { BadgeCell, type AdminUser, type Badge } from '@/Components/Admin/Cells';
import { ConfirmButton, DialogHeader, FieldError } from '@/Components/Admin/Form';
import FormAnswers, { type FormAnswer } from '@/Components/Admin/FormAnswers';
import PageHeader from '@/Components/Admin/PageHeader';
import Modal from '@/Components/Modal';
import { Link } from '@inertiajs/react';
import { useTrans } from '@/lib/i18n';

type Props = {
    title: string;
    deposit: {
        id: number;
        method: string;
        date: string;
        trx: string;
        user: AdminUser;
        amount: string;
        charge: string;
        total: string;
        rate: string;
        final: string;
        status: Badge;
        feedback: string | null;
        pending: boolean;
    };
    fields: FormAnswer[];
    gateway: { label: string; value: string | null; depth: number }[];
    urls: { approve: string; reject: string };
};

/** One deposit: the amounts, what the member sent, and Approve / Reject while it is pending. */
export default function DepositDetail({ title, deposit: d, fields, gateway, urls }: Props) {
    const t = useTrans();
    const [rejecting, setRejecting] = useState(false);
    const rows: [string, React.ReactNode][] = [
        [t('Date'), d.date],
        [t('Transaction Number'), d.trx],
        [t('Username'), d.user ? <Link href={d.user.url}>@{d.user.username}</Link> : '—'],
        [t('Method'), d.method],
        [t('Amount'), d.amount],
        [t('Charge'), d.charge],
        [t('After Charge'), d.total],
        [t('Rate'), d.rate],
        [t('After Rate Conversion'), d.final],
        [t('Status'), <BadgeCell badge={d.status} />],
    ];
    const hasInfo = fields.length > 0 || gateway.length > 0;

    return (
        <>
            <PageHeader title={title} />
            <div className="row gy-4 justify-content-center">
                <div className="col-xl-4 col-md-6">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="mb-3 text-muted">
                                {t('Deposit Via')} {d.method}
                            </h5>
                            <ul className="list-group">
                                {rows.map(([k, v]) => (
                                    <li key={k} className="list-group-item d-flex justify-content-between align-items-center gap-2">
                                        {k}
                                        <span className="fw-bold text-end">{v}</span>
                                    </li>
                                ))}
                                {d.feedback && (
                                    <li className="list-group-item">
                                        <strong>{t('Admin Response')}</strong>
                                        <p className="mb-0">{d.feedback}</p>
                                    </li>
                                )}
                            </ul>
                        </div>
                    </div>
                </div>
                {(hasInfo || d.pending) && (
                    <div className="col-xl-8 col-md-6">
                        <div className="card">
                            <div className="card-body">
                                <h5 className="card-title border-bottom pb-2">{t('User Deposit Information')}</h5>
                                <FormAnswers fields={fields} />
                                {gateway.length > 0 && (
                                    <dl className="mb-0">
                                        {gateway.map((g, i) => (
                                            <div key={i} className="d-flex gap-2 flex-wrap" style={{ paddingLeft: g.depth * 16 }}>
                                                <dt className={g.value === null ? 'mt-2' : ''}>{g.label}</dt>
                                                {g.value !== null && <dd className="mb-1 text-break">{g.value}</dd>}
                                            </div>
                                        ))}
                                    </dl>
                                )}
                                {!hasInfo && <p className="text-muted mb-0">{t('The member sent no extra information.')}</p>}
                                {d.pending && (
                                    <div className="d-flex gap-2 mt-4">
                                        <ConfirmButton url={urls.approve} className="btn btn-outline--success btn-sm" question={t('Are you sure to approve this transaction? :amount is credited to the member.', { amount: d.amount })}>
                                            <i className="las la-check" /> {t('Approve')}
                                        </ConfirmButton>
                                        <button type="button" className="btn btn-outline--danger btn-sm" onClick={() => setRejecting(true)}>
                                            <i className="las la-ban" /> {t('Reject')}
                                        </button>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                )}
            </div>
            {rejecting && <RejectDialog id={d.id} amount={d.amount} username={d.user?.username ?? ''} url={urls.reject} onClose={() => setRejecting(false)} />}
        </>
    );
}

function RejectDialog({ id, amount, username, url, onClose }: { id: number; amount: string; username: string; url: string; onClose: () => void }) {
    const t = useTrans();
    const form = useForm({ id, message: '' });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { onSuccess: onClose });
    };
    return (
        <Modal open onClose={onClose} labelledBy="reject-title">
            <DialogHeader id="reject-title" title={t('Reject Deposit Confirmation')} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    <p>{t('Reject the :amount deposit of :user?', { amount, user: username })}</p>
                    <label htmlFor="reject-message" className="mt-2">
                        {t('Reason for Rejection')}
                    </label>
                    <textarea id="reject-message" className="form-control" rows={5} maxLength={255} required value={form.data.message} onChange={(e) => form.setData('message', e.target.value)} />
                    <FieldError message={form.errors.message} />
                </div>
                <div className="modal-footer">
                    <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing || !form.data.message.trim()}>
                        {t('Submit')}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

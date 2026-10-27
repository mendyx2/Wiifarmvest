import { Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { BadgeCell, type AdminUser, type Badge } from '@/Components/Admin/Cells';
import { DialogHeader, FieldError } from '@/Components/Admin/Form';
import FormAnswers, { type FormAnswer } from '@/Components/Admin/FormAnswers';
import PageHeader from '@/Components/Admin/PageHeader';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';

type Props = {
    title: string;
    withdrawal: {
        id: number;
        method: string;
        date: string;
        trx: string;
        user: AdminUser;
        amount: string;
        charge: string;
        net: string;
        rate: string;
        payable: string;
        status: Badge;
        feedback: string | null;
        pending: boolean;
    };
    bank: { bank: string; name: string; number: string; balance: string } | null;
    fields: FormAnswer[];
    urls: { approve: string; reject: string };
};

/** One withdrawal request: amounts, where to pay, and Approve / Reject while it is pending. */
export default function WithdrawalDetail({ title, withdrawal: w, bank, fields, urls }: Props) {
    const t = useTrans();
    const [dialog, setDialog] = useState<'approve' | 'reject' | null>(null);
    const rows: [string, React.ReactNode][] = [
        [t('Date'), w.date],
        [t('Trx Number'), w.trx],
        [t('Username'), w.user ? <Link href={w.user.url}>@{w.user.username}</Link> : '—'],
        [t('Method'), w.method],
        [t('Amount'), w.amount],
        [t('Charge'), w.charge],
        [t('After Charge'), w.net],
        [t('Rate'), w.rate],
        [t('Payable'), w.payable],
        [t('Status'), <BadgeCell badge={w.status} />],
    ];

    return (
        <>
            <PageHeader title={title} />
            <div className="row gy-4 justify-content-center">
                <div className="col-xl-4 col-md-6">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="mb-3 text-muted">
                                {t('Withdraw Via')} {w.method}
                            </h5>
                            <ul className="list-group">
                                {rows.map(([k, v]) => (
                                    <li key={k} className="list-group-item d-flex justify-content-between align-items-center gap-2">
                                        {k}
                                        <span className="fw-bold text-end">{v}</span>
                                    </li>
                                ))}
                                {w.feedback && (
                                    <li className="list-group-item">
                                        <strong>{t('Admin Response')}</strong>
                                        <p className="mb-0">{w.feedback}</p>
                                    </li>
                                )}
                            </ul>
                        </div>
                    </div>
                </div>
                <div className="col-xl-8 col-md-6">
                    <div className="card">
                        <div className="card-body">
                            {bank && (
                                <>
                                    <h5 className="card-title border-bottom pb-2">{t('Bank Details on the Profile')}</h5>
                                    <ul className="list-group mb-4">
                                        {(
                                            [
                                                [t('Bank Name'), bank.bank],
                                                [t('Account Name'), bank.name],
                                                [t('Account Number'), bank.number],
                                                [t('Money Box now'), bank.balance],
                                            ] as const
                                        ).map(([k, v]) => (
                                            <li key={k} className="list-group-item d-flex justify-content-between align-items-center">
                                                {k}
                                                <span className="fw-bold">{v || '—'}</span>
                                            </li>
                                        ))}
                                    </ul>
                                </>
                            )}
                            <h5 className="card-title border-bottom pb-2">{t('User Withdraw Information')}</h5>
                            {fields.length ? <FormAnswers fields={fields} /> : <p className="text-muted">{t('The member sent no extra information.')}</p>}
                            {w.pending && (
                                <div className="d-flex gap-2 mt-4">
                                    <button type="button" className="btn btn-outline--success btn-sm" onClick={() => setDialog('approve')}>
                                        <i className="las la-check" /> {t('Approve')}
                                    </button>
                                    <button type="button" className="btn btn-outline--danger btn-sm" onClick={() => setDialog('reject')}>
                                        <i className="las la-ban" /> {t('Reject')}
                                    </button>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
            {dialog && <DecisionDialog kind={dialog} id={w.id} payable={w.payable} amount={w.amount} url={dialog === 'approve' ? urls.approve : urls.reject} onClose={() => setDialog(null)} />}
        </>
    );
}

function DecisionDialog({ kind, id, payable, amount, url, onClose }: { kind: 'approve' | 'reject'; id: number; payable: string; amount: string; url: string; onClose: () => void }) {
    const t = useTrans();
    const form = useForm({ id, details: '' });
    const approve = kind === 'approve';
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { onSuccess: onClose });
    };
    return (
        <Modal open onClose={onClose} labelledBy="decision-title">
            <DialogHeader id="decision-title" title={approve ? t('Approve Withdrawal Confirmation') : t('Reject Withdrawal Confirmation')} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    <p>{approve ? t('Have you sent :amount?', { amount: payable }) : t(':amount goes back to the member’s Money Box.', { amount })}</p>
                    <label htmlFor="decision-details">{approve ? t('Payment details (e.g. transaction number)') : t('Reason of Rejection')}</label>
                    <textarea id="decision-details" className="form-control" rows={3} maxLength={255} required value={form.data.details} onChange={(e) => form.setData('details', e.target.value)} />
                    <FieldError message={form.errors.details} />
                </div>
                <div className="modal-footer">
                    <button type="submit" className={`btn ${approve ? 'btn--primary' : 'btn--danger'} w-100 h-45`} disabled={form.processing || !form.data.details.trim()}>
                        {approve ? t('Approve') : t('Reject')}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { DialogHeader, FieldError } from '@/Components/Admin/Form';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';

/** "Reject Loan Application" with the required reason, from the loan list and the loan page. */
export default function LoanReject({ url, reference, onClose }: { url: string; reference: string; onClose: () => void }) {
    const t = useTrans();
    const form = useForm({ rejection_reason: '' });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, onSuccess: onClose });
    };
    return (
        <Modal open onClose={() => !form.processing && onClose()} labelledBy="loan-reject-title">
            <DialogHeader id="loan-reject-title" title={`${t('Reject Loan Application')} ${reference}`} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    <label className="form-label fw-bold" htmlFor="loan-reject-reason">
                        {t('Reason for Rejection')} <span className="text--danger">*</span>
                    </label>
                    <textarea
                        id="loan-reject-reason"
                        className="form-control"
                        rows={4}
                        required
                        maxLength={500}
                        placeholder={t('This reason will be stored for audit purposes…')}
                        value={form.data.rejection_reason}
                        onChange={(e) => form.setData('rejection_reason', e.target.value)}
                    />
                    <FieldError message={form.errors.rejection_reason} />
                </div>
                <div className="modal-footer">
                    <button type="button" className="btn btn-outline--dark" onClick={onClose}>
                        {t('Cancel')}
                    </button>
                    <button type="submit" className="btn btn--danger" disabled={form.processing || !form.data.rejection_reason.trim()}>
                        <i className="las la-times-circle" /> {t('Reject Application')}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

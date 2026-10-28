import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { DialogHeader, FieldError } from '@/Components/Admin/Form';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';

const naira = (n: number) => `₦${n.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

/** "Top up wallet": add money to a stockist wallet (was its own page, stockist-wallet-topup.blade.php). */
export default function StockistTopUp({ name, balance, url, onClose }: { name: string; balance: number; url: string; onClose: () => void }) {
    const t = useTrans();
    const form = useForm({ amount: '', notes: '' });
    const amount = parseFloat(form.data.amount) || 0;
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, onSuccess: onClose });
    };
    return (
        <Modal open onClose={onClose} labelledBy="topup-title" staticBackdrop={form.processing}>
            <DialogHeader id="topup-title" title={t("Top up :name's wallet", { name })} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    <p className="mb-3">
                        {t('Current balance')}: <strong>{naira(balance)}</strong>
                    </p>
                    <div className="form-group">
                        <label htmlFor="topup-amount">{t('Top-up Amount')}</label>
                        <div className="input-group">
                            <span className="input-group-text">₦</span>
                            <input id="topup-amount" className="form-control" type="number" step="0.01" min="0.01" required value={form.data.amount} onChange={(e) => form.setData('amount', e.target.value)} />
                        </div>
                        <FieldError message={form.errors.amount} />
                    </div>
                    <div className="form-group">
                        <label htmlFor="topup-notes">{t('Notes (optional)')}</label>
                        <textarea id="topup-notes" className="form-control" rows={3} maxLength={500} value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)} />
                        <FieldError message={form.errors.notes} />
                    </div>
                    {amount > 0 && (
                        <div className="alert alert-info py-2 small mb-0" role="status">
                            {t('New balance')}: <strong>{naira(balance + amount)}</strong>
                        </div>
                    )}
                </div>
                <div className="modal-footer">
                    <button className="btn btn--primary w-100 h-45" type="submit" disabled={form.processing || amount <= 0}>
                        <i className="las la-wallet" /> {amount > 0 ? t('Add :amount', { amount: naira(amount) }) : t('Confirm Top-up')}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

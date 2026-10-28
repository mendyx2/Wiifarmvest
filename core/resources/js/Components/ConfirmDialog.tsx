import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';

type Props = {
    open: boolean;
    question: string;
    onConfirm: () => void;
    onCancel: () => void;
    busy?: boolean;
};

/** "Are you sure?" dialog: the React version of <x-confirmation-modal>. */
export default function ConfirmDialog({ open, question, onConfirm, onCancel, busy = false }: Props) {
    const t = useTrans();
    return (
        <Modal open={open} onClose={onCancel} labelledBy="confirm-title">
            <div className="modal-header">
                <h5 className="modal-title" id="confirm-title">{t('Confirmation Alert!')}</h5>
                <button type="button" className="close" aria-label={t('Close')} onClick={onCancel}>
                    <i className="las la-times" />
                </button>
            </div>
            <div className="modal-body">
                <p className="question">{question}</p>
            </div>
            <div className="modal-footer">
                <button type="button" className="btn btn--dark" onClick={onCancel}>{t('No')}</button>
                <button type="button" className="btn btn--base btn--primary" onClick={onConfirm} disabled={busy}>{t('Yes')}</button>
            </div>
        </Modal>
    );
}

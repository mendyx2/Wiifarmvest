import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';

type Props = {
    /** What was copied, or null when the dialog is closed */
    copied: { code: string; kind: 'invoice' | 'package' } | null;
    onClose: () => void;
};

/** "Code copied" dialog shown after copying an invoice or welcome-package code. */
export default function CopyNotice({ copied, onClose }: Props) {
    const t = useTrans();
    return (
        <Modal open={copied !== null} onClose={onClose} labelledBy="copied-title" contentClassName="pd-modal-content">
            <div className="pd-modal-header pd-modal-header--success">
                <div className="pd-modal-icon"><i className="las la-check-circle" /></div>
                <h6 className="pd-modal-title" id="copied-title">{t('Invoice Code Copied!')}</h6>
                <button className="pd-modal-close" type="button" onClick={onClose} aria-label={t('Close')}>
                    <i className="las la-times" />
                </button>
            </div>
            <div className="pd-modal-body">
                {copied && (
                    <p>
                        {copied.kind === 'invoice'
                            ? t('Invoice code :code copied. Present it to the stockist to redeem your products.', { code: copied.code })
                            : t('Welcome package code :code copied. Present it to any authorized stockist to redeem your gift.', { code: copied.code })}
                    </p>
                )}
            </div>
            <div className="pd-modal-footer">
                <button className="pd-modal-btn-confirm" type="button" onClick={onClose}>{t('Got It')}</button>
            </div>
        </Modal>
    );
}

import { router } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import ConfirmDialog from '@/Components/ConfirmDialog';
import { useTrans } from '@/lib/i18n';

/** A server validation message under a field. */
export function FieldError({ message }: { message?: string }) {
    return message ? <small className="text--danger d-block mt-1">{message}</small> : null;
}

/** The title row of an admin dialog (Bootstrap .modal-header). */
export function DialogHeader({ id, title, onClose }: { id: string; title: ReactNode; onClose: () => void }) {
    const t = useTrans();
    return (
        <div className="modal-header">
            <h5 className="modal-title" id={id}>
                {title}
            </h5>
            <button type="button" className="close" aria-label={t('Close')} onClick={onClose}>
                <i className="las la-times" />
            </button>
        </div>
    );
}

type ConfirmButtonProps = {
    /** POST (default) or DELETE to this URL once confirmed */
    url: string;
    question: string;
    data?: Record<string, string | number>;
    method?: 'post' | 'delete';
    className?: string;
    title?: string;
    children: ReactNode;
};

/**
 * A button that asks "Are you sure?" and then sends the request: the
 * Blade "confirmationBtn" + <x-confirmation-modal> pair, and the inline
 * onsubmit="return confirm(...)" forms.
 */
export function ConfirmButton({ url, question, data = {}, method = 'post', className = 'btn btn-sm btn-outline--primary', title, children }: ConfirmButtonProps) {
    const [open, setOpen] = useState(false);
    const [busy, setBusy] = useState(false);
    const send = () => {
        setBusy(true);
        const options = { preserveScroll: true, onFinish: () => (setBusy(false), setOpen(false)) };
        if (method === 'delete') router.delete(url, options);
        else router.post(url, data, options);
    };
    return (
        <>
            <button type="button" className={className} title={title} aria-label={title} onClick={() => setOpen(true)}>
                {children}
            </button>
            <ConfirmDialog open={open} question={question} busy={busy} onConfirm={send} onCancel={() => setOpen(false)} />
        </>
    );
}

/** Put a record id into a route URL built with an ":id" placeholder. */
export const withId = (url: string, id: number | string) => url.replace(':id', String(id));

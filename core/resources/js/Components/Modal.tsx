import { useEffect, useId, useRef, type CSSProperties, type ReactNode } from 'react';

type Props = {
    open: boolean;
    onClose: () => void;
    /** Everything inside .modal-content (header, body, footer) */
    children: ReactNode;
    contentClassName?: string;
    contentStyle?: CSSProperties;
    /** Static: clicking the backdrop or pressing Escape does not close it. */
    staticBackdrop?: boolean;
    labelledBy?: string;
};

/**
 * Bootstrap-styled modal controlled by React state (no jQuery / bootstrap.js).
 * Closes on Escape and backdrop click, locks page scrolling, moves focus into
 * the dialog and gives it back to the button that opened it.
 */
export default function Modal({ open, onClose, children, contentClassName = '', contentStyle, staticBackdrop = false, labelledBy }: Props) {
    const dialog = useRef<HTMLDivElement>(null);
    const fallbackId = useId();

    useEffect(() => {
        if (!open) return;
        const previouslyFocused = document.activeElement as HTMLElement | null;
        document.body.classList.add('modal-open');
        dialog.current?.focus();

        const onKey = (e: KeyboardEvent) => {
            if (e.key === 'Escape' && !staticBackdrop) onClose();
        };
        document.addEventListener('keydown', onKey);

        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.classList.remove('modal-open');
            previouslyFocused?.focus();
        };
    }, [open, onClose, staticBackdrop]);

    if (!open) return null;

    return (
        <>
            <div
                ref={dialog}
                className="modal fade show d-block"
                tabIndex={-1}
                role="dialog"
                aria-modal="true"
                aria-labelledby={labelledBy ?? fallbackId}
                onMouseDown={(e) => {
                    if (e.target === e.currentTarget && !staticBackdrop) onClose();
                }}
            >
                <div className="modal-dialog modal-dialog-centered">
                    <div className={`modal-content ${contentClassName}`} style={contentStyle}>
                        {children}
                    </div>
                </div>
            </div>
            <div className="modal-backdrop fade show" />
        </>
    );
}

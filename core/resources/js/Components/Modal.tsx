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
    size?: 'sm' | 'lg' | 'xl';
};

/**
 * Bootstrap-styled modal controlled by React state (no jQuery / bootstrap.js).
 * Closes on Escape and backdrop click, locks page scrolling, moves focus into
 * the dialog and gives it back to the button that opened it.
 */
export default function Modal({ open, onClose, children, contentClassName = '', contentStyle, staticBackdrop = false, labelledBy, size }: Props) {
    const dialog = useRef<HTMLDivElement>(null);
    const fallbackId = useId();

    // Parents usually pass a new onClose function on every render. Keep the
    // latest one in a ref so the effect below runs only when the dialog opens
    // or closes; otherwise each keystroke in an input inside the dialog would
    // re-run it and pull focus back to the dialog.
    const closeRef = useRef(onClose);
    closeRef.current = onClose;

    useEffect(() => {
        if (!open) return;
        const previouslyFocused = document.activeElement as HTMLElement | null;
        document.body.classList.add('modal-open');
        dialog.current?.focus();

        const onKey = (e: KeyboardEvent) => {
            if (e.key === 'Escape' && !staticBackdrop) closeRef.current();
        };
        document.addEventListener('keydown', onKey);

        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.classList.remove('modal-open');
            previouslyFocused?.focus();
        };
    }, [open, staticBackdrop]);

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
                    if (e.target === e.currentTarget && !staticBackdrop) closeRef.current();
                }}
            >
                <div className={`modal-dialog modal-dialog-centered ${size ? `modal-${size}` : ''}`}>
                    <div className={`modal-content ${contentClassName}`} style={contentStyle}>
                        {children}
                    </div>
                </div>
            </div>
            <div className="modal-backdrop fade show" />
        </>
    );
}

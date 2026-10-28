import { useEffect, useRef, type ReactNode } from 'react';
import { useTrans } from '@/lib/i18n';

type Props = {
    open: boolean;
    title: string;
    onClose: () => void;
    /** Class prefix of the page's drawer styles in admin app.css: "state" (.state-drawer…) or "rpa" (.rpa-drawer…) */
    prefix: 'state' | 'rpa';
    children: ReactNode;
};

/**
 * The slide-in form panel of the States and Repurchase Awards pages. The
 * Blade pages opened it with jQuery; here it also closes on Escape, moves
 * focus into the panel and gives it back to the button that opened it.
 */
export default function Drawer({ open, title, onClose, prefix, children }: Props) {
    const t = useTrans();
    const panel = useRef<HTMLDivElement>(null);
    const closeRef = useRef(onClose);
    closeRef.current = onClose;

    useEffect(() => {
        if (!open) return;
        const before = document.activeElement as HTMLElement | null;
        // After the slide-in starts, so the browser does not scroll to it.
        const focus = window.setTimeout(() => panel.current?.querySelector<HTMLElement>('input, select, textarea')?.focus(), 50);
        const onKey = (e: KeyboardEvent) => e.key === 'Escape' && closeRef.current();
        document.addEventListener('keydown', onKey);
        return () => {
            window.clearTimeout(focus);
            document.removeEventListener('keydown', onKey);
            before?.focus?.();
        };
    }, [open]);

    return (
        <>
            <div className={`${prefix}-overlay ${open ? 'active' : ''}`} onClick={onClose} aria-hidden="true" />
            <div ref={panel} className={`${prefix}-drawer ${open ? 'active' : ''}`} role="dialog" aria-modal="true" aria-label={title} aria-hidden={!open} inert={!open}>
                <div className={`${prefix}-drawer-head`}>
                    <span>{title}</span>
                    <button type="button" className={`${prefix}-drawer-close`} aria-label={t('Close')} onClick={onClose}>
                        <i className="las la-times" />
                    </button>
                </div>
                <div className={`${prefix}-drawer-body`}>{children}</div>
            </div>
        </>
    );
}

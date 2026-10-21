import { useEffect } from 'react';
import { useTrans } from '@/lib/i18n';

/** Full-screen image viewer: click outside, × or Escape closes it. */
export default function Lightbox({ src, onClose }: { src: string | null; onClose: () => void }) {
    const t = useTrans();

    useEffect(() => {
        if (!src) return;
        const onKey = (e: KeyboardEvent) => e.key === 'Escape' && onClose();
        document.addEventListener('keydown', onKey);
        document.body.style.overflow = 'hidden';
        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = '';
        };
    }, [src, onClose]);

    if (!src) return null;

    return (
        <div
            role="dialog"
            aria-modal="true"
            aria-label={t('Attachment preview')}
            onClick={onClose}
            style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,.88)', zIndex: 99999, display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'zoom-out', padding: 20 }}
        >
            <img
                src={src}
                alt={t('Attachment preview')}
                onClick={(e) => e.stopPropagation()}
                style={{ maxWidth: '92vw', maxHeight: '88vh', borderRadius: 10, boxShadow: '0 12px 48px rgba(0,0,0,.6)', objectFit: 'contain', cursor: 'default' }}
            />
            <button
                type="button"
                onClick={onClose}
                aria-label={t('Close')}
                style={{ position: 'fixed', top: 18, right: 22, background: 'rgba(255,255,255,.15)', border: 'none', color: '#fff', fontSize: '1.6rem', lineHeight: 1, width: 40, height: 40, borderRadius: '50%', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}
            >
                &times;
            </button>
        </div>
    );
}

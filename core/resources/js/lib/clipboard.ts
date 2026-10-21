import { useCallback, useRef, useState } from 'react';

/** Old-browser / plain-HTTP fallback: copy through a temporary, off-screen textarea. */
function copyWithTextarea(text: string): boolean {
    const area = document.createElement('textarea');
    area.value = text;
    area.setAttribute('readonly', '');
    area.style.position = 'fixed';
    area.style.opacity = '0';
    document.body.appendChild(area);
    area.select();
    const ok = document.execCommand('copy');
    area.remove();
    return ok;
}

/**
 * Copy text to the clipboard. `copied` is true for a moment afterwards so
 * the button can show a tick. Uses the Clipboard API (HTTPS only) and falls
 * back to execCommand('copy') like the old jQuery code did.
 */
export function useCopy(resetAfterMs = 1500) {
    const [copied, setCopied] = useState(false);
    const timer = useRef<ReturnType<typeof setTimeout>>(undefined);

    const copy = useCallback(
        async (text: string): Promise<boolean> => {
            try {
                await navigator.clipboard.writeText(text);
            } catch {
                if (!copyWithTextarea(text)) return false;
            }
            setCopied(true);
            clearTimeout(timer.current);
            timer.current = setTimeout(() => setCopied(false), resetAfterMs);
            return true;
        },
        [resetAfterMs],
    );

    return { copied, copy };
}

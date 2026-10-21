import { useCallback, useRef, useState } from 'react';

/**
 * Copy text to the clipboard. `copied` is true for a moment afterwards so
 * the button can show a tick. Uses the Clipboard API (HTTPS only) and falls
 * back to selecting `fallbackInput` and execCommand('copy') like the old
 * jQuery code did.
 */
export function useCopy(resetAfterMs = 1500) {
    const [copied, setCopied] = useState(false);
    const timer = useRef<ReturnType<typeof setTimeout>>(undefined);

    const copy = useCallback(
        async (text: string, fallbackInput?: HTMLInputElement | null): Promise<boolean> => {
            try {
                await navigator.clipboard.writeText(text);
            } catch {
                fallbackInput?.select();
                if (!document.execCommand('copy')) return false;
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

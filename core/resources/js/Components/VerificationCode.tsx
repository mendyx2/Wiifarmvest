import { useEffect, useRef, useState } from 'react';
import { useTrans } from '@/lib/i18n';
import './VerificationCode.css';

/**
 * The six-box code input (partials/verification_code.blade.php): digits only,
 * and `onComplete` fires when the sixth digit is typed, as the old page
 * submitted the form by itself.
 *
 * One real input lies over the boxes (so typing, pasting and one-time-code
 * autofill work as usual); its own text is invisible and each box shows its
 * digit. The old version spread the input's text with letter-spacing, which
 * only lined up with the boxes at one width.
 */
export default function VerificationCode({ value, onChange, onComplete, error }: { value: string; onChange: (v: string) => void; onComplete: (v: string) => void; error?: string }) {
    const t = useTrans();
    const input = useRef<HTMLInputElement>(null);
    const [focused, setFocused] = useState(false);
    useEffect(() => input.current?.focus(), []);

    return (
        <div className="mb-3">
            <label className="form-label" htmlFor="verification-code">
                {t('Verification Code')}
            </label>
            <div className={`verification-code${error ? ' has-error' : ''}`}>
                <input
                    ref={input}
                    id="verification-code"
                    type="text"
                    inputMode="numeric"
                    autoComplete="one-time-code"
                    maxLength={6}
                    value={value}
                    onFocus={() => setFocused(true)}
                    onBlur={() => setFocused(false)}
                    onChange={(e) => {
                        const code = e.target.value.replace(/\D/g, '').slice(0, 6);
                        onChange(code);
                        if (code.length === 6) onComplete(code);
                    }}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={error ? 'verification-code-error' : undefined}
                    required
                />
                <div className="boxes" aria-hidden>
                    {Array.from({ length: 6 }, (_, i) => (
                        <span key={i} className={[value[i] === undefined && 'empty', focused && i === Math.min(value.length, 5) && 'active'].filter(Boolean).join(' ') || undefined}>
                            {value[i] ?? '-'}
                        </span>
                    ))}
                </div>
            </div>
            {error && (
                <div className="invalid-feedback d-block mt-2" id="verification-code-error">
                    {error}
                </div>
            )}
        </div>
    );
}

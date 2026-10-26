import { useEffect, useRef } from 'react';
import { useTrans } from '@/lib/i18n';
import './VerificationCode.css';

/**
 * The six-box code input (partials/verification_code.blade.php): digits only,
 * and `onComplete` fires when the sixth digit is typed, as the old page
 * submitted the form by itself.
 */
export default function VerificationCode({ value, onChange, onComplete, error }: { value: string; onChange: (v: string) => void; onComplete: (v: string) => void; error?: string }) {
    const t = useTrans();
    const input = useRef<HTMLInputElement>(null);
    useEffect(() => input.current?.focus(), []);

    return (
        <div className="mb-3">
            <label className="form-label" htmlFor="verification-code">
                {t('Verification Code')}
            </label>
            <div className="verification-code">
                <input
                    ref={input}
                    id="verification-code"
                    type="text"
                    inputMode="numeric"
                    autoComplete="one-time-code"
                    className="form-control overflow-hidden"
                    value={value}
                    onChange={(e) => {
                        const code = e.target.value.replace(/\D/g, '').slice(0, 6);
                        onChange(code);
                        if (code.length === 6) onComplete(code);
                    }}
                    required
                />
                <div className="boxes">
                    {Array.from({ length: 6 }, (_, i) => (
                        <span key={i}>{i < value.length ? '' : '-'}</span>
                    ))}
                </div>
            </div>
            {error && <div className="invalid-feedback d-block mt-2">{error}</div>}
        </div>
    );
}

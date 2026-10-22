import type { InputHTMLAttributes, ReactNode } from 'react';

type Props = InputHTMLAttributes<HTMLInputElement> & {
    label: string;
    error?: string;
    required?: boolean;
    hint?: ReactNode;
    /** Render a <textarea> with this many rows instead of an <input> */
    rows?: number;
    onValue: (value: string) => void;
};

/** Labelled input in the stockist pages' "sl-" style, with its validation error. */
export default function SlField({ label, error, hint, rows, onValue, id, name, value, required, ...input }: Props) {
    const fieldId = id ?? name;
    const errorId = error ? `${fieldId}-error` : undefined;
    const common = {
        id: fieldId,
        name,
        className: 'sl-input',
        value: value ?? '',
        required,
        'aria-invalid': error ? true : undefined,
        'aria-describedby': errorId,
    };

    return (
        <>
            <label className="sl-label" htmlFor={fieldId}>
                {label} {required && <span className="sl-required">*</span>}
            </label>
            {rows ? (
                <textarea {...common} rows={rows} placeholder={input.placeholder} onChange={(e) => onValue(e.target.value)} />
            ) : (
                <input {...input} {...common} onChange={(e) => onValue(e.target.value)} />
            )}
            {hint && <p className="sl-field-hint">{hint}</p>}
            {error && <small id={errorId} className="d-block mt-1" style={{ color: '#DC2626' }}>{error}</small>}
        </>
    );
}

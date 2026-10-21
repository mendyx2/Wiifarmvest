import type { CSSProperties, InputHTMLAttributes } from 'react';

type Props = InputHTMLAttributes<HTMLInputElement> & {
    label: string;
    error?: string;
    /** Line Awesome icon shown inside the input, e.g. "la-phone" */
    icon?: string;
    wrapperStyle?: CSSProperties;
};

/** Labelled text input with an inline validation error (profile "prof-field" styling). */
export default function TextField({ label, error, icon, wrapperStyle, id, name, ...input }: Props) {
    const fieldId = id ?? name;
    const errorId = error ? `${fieldId}-error` : undefined;

    const field = (
        <input
            {...input}
            id={fieldId}
            name={name}
            aria-invalid={error ? true : undefined}
            aria-describedby={errorId}
        />
    );

    return (
        <div className="prof-field" style={wrapperStyle}>
            <label htmlFor={fieldId}>{label}</label>
            {icon ? (
                <div className="prof-input-icon-wrap">
                    <i className={`las ${icon} prof-input-icon`} />
                    {field}
                </div>
            ) : (
                field
            )}
            {error && (
                <small id={errorId} className="d-block mt-1" style={{ color: '#DC2626' }}>
                    {error}
                </small>
            )}
        </div>
    );
}

import { useState, type InputHTMLAttributes } from 'react';

type Props = Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> & {
    id: string;
    label: string;
    error?: string;
};

/** Password field with a show/hide toggle, styled with the dashboard's sl-* classes. */
export default function PasswordInput({ id, label, error, className = '', ...input }: Props) {
    const [visible, setVisible] = useState(false);

    return (
        <div className="mb-3">
            <label className="sl-label" htmlFor={id}>
                {label} <span className="sl-required">*</span>
            </label>
            <div className="sl-pwd-wrap">
                <input
                    {...input}
                    id={id}
                    name={id}
                    type={visible ? 'text' : 'password'}
                    className={`sl-input pwd-field ${className}`}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={error ? `${id}-error` : undefined}
                />
                <button
                    type="button"
                    className="sl-pwd-toggle"
                    tabIndex={-1}
                    aria-label={visible ? 'Hide password' : 'Show password'}
                    onClick={() => setVisible(!visible)}
                >
                    <i className={`las ${visible ? 'la-eye-slash' : 'la-eye'}`} />
                </button>
            </div>
            {error && (
                <p id={`${id}-error`} className="sl-field-hint mb-0" style={{ color: '#DC2626' }}>
                    {error}
                </p>
            )}
        </div>
    );
}

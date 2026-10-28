import { useTrans } from '@/lib/i18n';

/** One field of an admin-built form (App\Support\FormPresenter::fields). */
export type FormField = {
    /** Request key the server reads */
    key: string;
    label: string;
    type: 'text' | 'url' | 'email' | 'datetime' | 'date' | 'time' | 'number' | 'textarea' | 'select' | 'checkbox' | 'radio' | 'file' | string;
    required: boolean;
    instruction: string | null;
    options: string[];
    extensions: string | null;
    width: string;
};

export type FormValue = string | string[] | File | null;
export type FormValues = Record<string, FormValue>;

/** Empty starting values: [] for checkboxes, null for files, '' otherwise. */
export function emptyValues(fields: FormField[]): FormValues {
    return Object.fromEntries(fields.map((f) => [f.key, f.type === 'checkbox' ? [] : f.type === 'file' ? null : '']));
}

const INPUT_TYPES: Record<string, string> = {
    text: 'text', url: 'url', email: 'email', datetime: 'datetime-local', date: 'date', time: 'time', number: 'number',
};

type Props = {
    fields: FormField[];
    values: FormValues;
    errors: Partial<Record<string, string>>;
    onChange: (key: string, value: FormValue) => void;
};

/**
 * React version of <x-viser-form>: renders the form an admin designed for a
 * gateway / withdraw method. Same markup and classes as the Blade component.
 */
export default function DynamicForm({ fields, values, errors, onChange }: Props) {
    const t = useTrans();

    // Laravel reports errors for checkbox arrays as "key" or "key.0".
    const errorFor = (key: string) => errors[key] ?? Object.entries(errors).find(([k]) => k.startsWith(`${key}.`))?.[1];

    return (
        <div className="row">
            {fields.map((field) => {
                const id = `field_${field.key}`;
                const error = errorFor(field.key);
                const value = values[field.key];
                const text = typeof value === 'string' ? value : '';
                const common = {
                    id,
                    name: field.key,
                    required: field.required,
                    'aria-invalid': error ? true : undefined,
                    'aria-describedby': error ? `${id}_error` : undefined,
                };
                const groupLabel = field.type === 'checkbox' || field.type === 'radio';

                return (
                    <div className={`col-md-${field.width}`} key={field.key}>
                        <div className="form-group form--group" role={groupLabel ? 'group' : undefined} aria-labelledby={groupLabel ? `${id}_label` : undefined}>
                            <label className="form-label" id={`${id}_label`} htmlFor={groupLabel ? undefined : id}>
                                {t(field.label)}
                                {field.instruction && (
                                    <span title={t(field.instruction)} aria-label={t(field.instruction)}>
                                        {' '}<i className="fas fa-info-circle" />
                                    </span>
                                )}
                                {field.required && groupLabel && <span className="text--danger"> *</span>}
                            </label>

                            {INPUT_TYPES[field.type] && (
                                <input
                                    {...common}
                                    type={INPUT_TYPES[field.type]}
                                    step={field.type === 'number' ? 'any' : undefined}
                                    className="form-control form--control"
                                    value={text}
                                    onChange={(e) => onChange(field.key, e.target.value)}
                                />
                            )}

                            {field.type === 'textarea' && (
                                <textarea {...common} className="form-control form--control" value={text} onChange={(e) => onChange(field.key, e.target.value)} />
                            )}

                            {field.type === 'select' && (
                                <select {...common} className="form-select form--control" value={text} onChange={(e) => onChange(field.key, e.target.value)}>
                                    <option value="">{t('Select One')}</option>
                                    {field.options.map((option) => (
                                        <option key={option} value={option}>{t(option)}</option>
                                    ))}
                                </select>
                            )}

                            {(field.type === 'checkbox' || field.type === 'radio') && (
                                <div className="d-flex gap-3 flex-wrap">
                                    {field.options.map((option, i) => {
                                        const optionId = `${id}_${i}`;
                                        const checked = field.type === 'checkbox' ? Array.isArray(value) && value.includes(option) : value === option;
                                        return (
                                            <div className="form-check" key={option}>
                                                <input
                                                    className="form-check-input"
                                                    type={field.type}
                                                    id={optionId}
                                                    name={field.type === 'checkbox' ? `${field.key}[]` : field.key}
                                                    value={option}
                                                    checked={checked}
                                                    onChange={(e) => {
                                                        if (field.type === 'radio') return onChange(field.key, option);
                                                        const current = Array.isArray(value) ? value : [];
                                                        onChange(field.key, e.target.checked ? [...current, option] : current.filter((o) => o !== option));
                                                    }}
                                                />
                                                <label className="form-check-label" htmlFor={optionId}>{option}</label>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}

                            {field.type === 'file' && (
                                <>
                                    <input
                                        {...common}
                                        type="file"
                                        className="form-control form--control"
                                        accept={field.extensions?.split(',').map((ext) => `.${ext.trim()}`).join(',')}
                                        onChange={(e) => onChange(field.key, e.target.files?.[0] ?? null)}
                                    />
                                    {field.extensions && <pre className="text--base mt-1">{t('Supported mimes')}: {field.extensions}</pre>}
                                </>
                            )}

                            {error && (
                                <small id={`${id}_error`} className="d-block mt-1 text--danger" style={{ color: '#DC2626' }}>
                                    {error}
                                </small>
                            )}
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

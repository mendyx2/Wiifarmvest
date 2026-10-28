import { useState, type KeyboardEvent } from 'react';
import { DialogHeader } from '@/Components/Admin/Form';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';

export type BuilderField = {
    label: string;
    type: string;
    is_required: 'required' | 'optional';
    width: string;
    /** Comma-separated choices (select, radio, checkbox) */
    options: string;
    /** Comma-separated file types (file) */
    extensions: string;
    instruction: string;
};

const TYPES: [string, string][] = [
    ['text', 'Text'],
    ['textarea', 'Textarea'],
    ['select', 'Select'],
    ['radio', 'Radio'],
    ['checkbox', 'Checkbox'],
    ['file', 'File'],
    ['email', 'Email'],
    ['url', 'URL'],
    ['number', 'Number'],
    ['date', 'Date'],
    ['time', 'Time'],
    ['datetime', 'Date & Time'],
];
const WIDTHS: [string, string][] = [
    ['12', '100%'],
    ['6', '50%'],
    ['4', '33%'],
    ['3', '25%'],
];
const hasOptions = (type: string) => ['select', 'radio', 'checkbox'].includes(type);

/**
 * The form builder: the fields a member fills in (manual deposit, withdrawal,
 * KYC). Replaces <x-generated-form> + <x-form-generator-modal> (jQuery).
 * Fields can be added, edited, removed and moved up or down.
 */
export default function FormBuilder({ fields, onChange, extensions, errors = {} }: { fields: BuilderField[]; onChange: (f: BuilderField[]) => void; extensions: string[]; errors?: Record<string, string> }) {
    const t = useTrans();
    // null: closed; -1: adding; otherwise the index being edited
    const [editing, setEditing] = useState<number | null>(null);
    const move = (i: number, by: number) => {
        const next = [...fields];
        [next[i], next[i + by]] = [next[i + by], next[i]];
        onChange(next);
    };
    const builderErrors = Object.entries(errors).filter(([k]) => k.startsWith('form_generator'));

    return (
        <div className="card border--primary">
            <div className="card-header bg--primary d-flex justify-content-between align-items-center">
                <h5 className="text-white m-0">{t('User Data')}</h5>
                <button type="button" className="btn btn-sm btn-outline-light" onClick={() => setEditing(-1)}>
                    <i className="la la-plus" /> {t('Add New')}
                </button>
            </div>
            <div className="card-body">
                {builderErrors.map(([k, m]) => (
                    <p key={k} className="text--danger small mb-2" role="alert">
                        {m}
                    </p>
                ))}
                {fields.length === 0 && <p className="text-muted mb-0">{t('No fields yet. Members will only see the instruction.')}</p>}
                <ul className="list-group">
                    {fields.map((f, i) => (
                        <li key={`${f.label}-${i}`} className="list-group-item d-flex flex-wrap gap-2 justify-content-between align-items-center">
                            <div>
                                <strong>{f.label}</strong>{' '}
                                <span className={`badge ${f.is_required === 'required' ? 'badge--success' : 'badge--dark'}`}>{f.is_required === 'required' ? t('Required') : t('Optional')}</span>
                                <br />
                                <small className="text-muted">
                                    {t(TYPES.find(([v]) => v === f.type)?.[1] ?? f.type)} · {WIDTHS.find(([v]) => v === f.width)?.[1]}
                                    {hasOptions(f.type) && f.options && ` · ${f.options}`}
                                    {f.type === 'file' && f.extensions && ` · ${f.extensions}`}
                                </small>
                            </div>
                            <div className="d-flex gap-1">
                                <button type="button" className="btn btn-sm btn-outline--dark" aria-label={t('Move :name up', { name: f.label })} disabled={i === 0} onClick={() => move(i, -1)}>
                                    <i className="las la-arrow-up" />
                                </button>
                                <button type="button" className="btn btn-sm btn-outline--dark" aria-label={t('Move :name down', { name: f.label })} disabled={i === fields.length - 1} onClick={() => move(i, 1)}>
                                    <i className="las la-arrow-down" />
                                </button>
                                <button type="button" className="btn btn-sm btn-outline--primary" aria-label={t('Edit :name', { name: f.label })} onClick={() => setEditing(i)}>
                                    <i className="las la-pen" />
                                </button>
                                <button type="button" className="btn btn-sm btn-outline--danger" aria-label={t('Remove :name', { name: f.label })} onClick={() => onChange(fields.filter((_, j) => j !== i))}>
                                    <i className="las la-times" />
                                </button>
                            </div>
                        </li>
                    ))}
                </ul>
            </div>
            {editing !== null && (
                <FieldDialog
                    field={editing >= 0 ? fields[editing] : null}
                    taken={fields.filter((_, j) => j !== editing).map((f) => f.label.trim().toLowerCase())}
                    extensions={extensions}
                    onClose={() => setEditing(null)}
                    onSave={(f) => {
                        onChange(editing >= 0 ? fields.map((x, j) => (j === editing ? f : x)) : [...fields, f]);
                        setEditing(null);
                    }}
                />
            )}
        </div>
    );
}

function FieldDialog({ field, taken, extensions, onClose, onSave }: { field: BuilderField | null; taken: string[]; extensions: string[]; onClose: () => void; onSave: (f: BuilderField) => void }) {
    const t = useTrans();
    const [f, setF] = useState<BuilderField>(field ?? { label: '', type: 'text', is_required: 'required', width: '12', options: '', extensions: '', instruction: '' });
    const set = <K extends keyof BuilderField>(k: K, v: BuilderField[K]) => setF((x) => ({ ...x, [k]: v }));
    const duplicate = taken.includes(f.label.trim().toLowerCase());
    const chosen = f.extensions.split(',').map((e) => e.trim()).filter(Boolean);
    const invalid = duplicate || !f.label.trim() || (f.type === 'file' && chosen.length === 0) || (hasOptions(f.type) && !f.options.trim());
    const save = () => {
        if (invalid) return;
        onSave({ ...f, label: f.label.trim(), options: hasOptions(f.type) ? f.options : '', extensions: f.type === 'file' ? f.extensions : '' });
    };
    // Not a <form>: the builder sits inside the page's own form, and a nested
    // form's button (or Enter) would submit the whole page. Enter saves the field.
    const onKey = (e: KeyboardEvent) => {
        if (e.key === 'Enter' && (e.target as HTMLElement).tagName === 'INPUT') {
            e.preventDefault();
            save();
        }
    };

    return (
        <Modal open onClose={onClose} labelledBy="fb-field-title">
            <DialogHeader id="fb-field-title" title={field ? t('Edit Field') : t('Generate Form')} onClose={onClose} />
            <div onKeyDown={onKey}>
                <div className="modal-body">
                    <div className="form-group">
                        <label htmlFor="fb-type">{t('Type')}</label>
                        <select id="fb-type" className="form-control" value={f.type} onChange={(e) => set('type', e.target.value)}>
                            {TYPES.map(([v, l]) => (
                                <option key={v} value={v}>
                                    {t(l)}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="form-group">
                        <label htmlFor="fb-label">{t('Label')}</label>
                        <input id="fb-label" className="form-control" maxLength={255} value={f.label} onChange={(e) => set('label', e.target.value)} />
                        {duplicate && <small className="text--danger">{t('Another field already has this label.')}</small>}
                    </div>
                    {hasOptions(f.type) && (
                        <div className="form-group">
                            <label htmlFor="fb-options">{t('Options')}</label>
                            <input id="fb-options" className="form-control" placeholder={t('Comma separated, e.g. Savings,Current')} value={f.options} onChange={(e) => set('options', e.target.value)} />
                        </div>
                    )}
                    {f.type === 'file' && (
                        <fieldset className="form-group">
                            <legend className="fs-6">{t('Allowed file types')}</legend>
                            <div className="d-flex flex-wrap gap-2">
                                {extensions.map((ext) => (
                                    <label key={ext} className="form-check-label d-inline-flex align-items-center gap-1 border rounded px-2 py-1">
                                        <input
                                            type="checkbox"
                                            className="form-check-input m-0"
                                            checked={chosen.includes(ext)}
                                            onChange={(e) => set('extensions', (e.target.checked ? [...chosen, ext] : chosen.filter((x) => x !== ext)).join(','))}
                                        />
                                        {ext}
                                    </label>
                                ))}
                            </div>
                            {chosen.length === 0 && <small className="text--danger">{t('Choose at least one file type.')}</small>}
                        </fieldset>
                    )}
                    <div className="row">
                        <div className="col-6 form-group">
                            <label htmlFor="fb-required">{t('Is Required')}</label>
                            <select id="fb-required" className="form-control" value={f.is_required} onChange={(e) => set('is_required', e.target.value as BuilderField['is_required'])}>
                                <option value="required">{t('Required')}</option>
                                <option value="optional">{t('Optional')}</option>
                            </select>
                        </div>
                        <div className="col-6 form-group">
                            <label htmlFor="fb-width">{t('Width')}</label>
                            <select id="fb-width" className="form-control" value={f.width} onChange={(e) => set('width', e.target.value)}>
                                {WIDTHS.map(([v, l]) => (
                                    <option key={v} value={v}>
                                        {l}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>
                    <div className="form-group">
                        <label htmlFor="fb-instruction">
                            {t('Instruction')} <small className="text-muted">({t('if any')})</small>
                        </label>
                        <input id="fb-instruction" className="form-control" maxLength={500} value={f.instruction} onChange={(e) => set('instruction', e.target.value)} />
                    </div>
                </div>
                <div className="modal-footer">
                    <button type="button" className="btn btn--primary w-100 h-45" disabled={invalid} onClick={save}>
                        {field ? t('Update') : t('Add')}
                    </button>
                </div>
            </div>
        </Modal>
    );
}

/** The fields as the server's form_generator arrays (one array per property, index = field). */
export function toGenerator(fields: BuilderField[]) {
    return {
        form_label: fields.map((f) => f.label),
        form_type: fields.map((f) => f.type),
        is_required: fields.map((f) => f.is_required),
        form_width: fields.map((f) => f.width),
        options: fields.map((f) => f.options),
        extensions: fields.map((f) => f.extensions || 'null'),
        instruction: fields.map((f) => f.instruction),
    };
}

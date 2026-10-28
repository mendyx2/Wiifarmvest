import { Head, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState, type DragEvent, type FormEvent } from 'react';
import DynamicForm, { emptyValues, type FormField, type FormValue } from '@/Components/DynamicForm';
import { useTrans } from '@/lib/i18n';
import { initials } from '@/lib/initials';
import type { PageProps } from '@/types';

type Person = { id: number; name: string; username: string };
type GuarantorStatus = 'pending' | 'accepted' | 'declined';

type Props = PageProps<{
    rejectionReason: string | null;
    fields: FormField[];
    nin: string;
    idCardType: string;
    hasIdCard: boolean;
    guarantor: Person | null;
    guarantorStatus: GuarantorStatus | null;
    submitUrl: string;
    searchUrl: string;
}>;

const CARD_TYPES = [
    { value: 'driving_license', icon: '🪪' },
    { value: 'international_passport', icon: '🛂' },
    { value: 'nin', icon: '🆔' },
];

const STATUS_STYLE: Record<GuarantorStatus, { cls: string; icon: string }> = {
    pending: { cls: 'g-pending', icon: 'fas fa-hourglass-half' },
    accepted: { cls: 'g-accepted', icon: 'fas fa-check-circle' },
    declined: { cls: 'g-declined', icon: 'fas fa-times-circle' },
};

type FormShape = Record<string, FormValue> & { nin: string; id_card_type: string; id_card_image: File | null; guarantor_id: string };

export default function KycForm(props: Props) {
    const { rejectionReason, fields, hasIdCard, guarantorStatus, submitUrl, searchUrl } = props;
    const t = useTrans();
    const form = useForm<FormShape>({
        ...emptyValues(fields),
        nin: props.nin,
        id_card_type: props.idCardType,
        id_card_image: null,
        guarantor_id: props.guarantor ? String(props.guarantor.id) : '',
    });

    const cardLabels: Record<string, string> = {
        driving_license: t("Driver's License"),
        international_passport: t('International Passport'),
        nin: t('NIN Card'),
    };

    // ── ID card upload preview ──
    const [preview, setPreview] = useState<string | null>(null);
    const [dragOver, setDragOver] = useState(false);
    useEffect(() => {
        const file = form.data.id_card_image;
        if (!file || !file.type.startsWith('image/')) return setPreview(null);
        const url = URL.createObjectURL(file);
        setPreview(url);
        return () => URL.revokeObjectURL(url);
    }, [form.data.id_card_image]);

    function onDrop(event: DragEvent) {
        event.preventDefault();
        setDragOver(false);
        const file = event.dataTransfer.files[0];
        if (file?.type.startsWith('image/')) form.setData('id_card_image', file);
    }

    // ── Guarantor search (same JSON endpoint and 280 ms debounce as before) ──
    const [selected, setSelected] = useState<Person | null>(props.guarantor);
    const [query, setQuery] = useState(props.guarantor?.name ?? '');
    const [results, setResults] = useState<Person[] | null>(null);
    const searchBox = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (selected || query.trim().length < 2) return setResults(null);
        const controller = new AbortController();
        const timer = setTimeout(async () => {
            try {
                const res = await fetch(`${searchUrl}?q=${encodeURIComponent(query.trim())}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: controller.signal,
                });
                setResults(res.ok ? await res.json() : []);
            } catch {
                /* aborted: a newer search replaced this one */
            }
        }, 280);
        return () => {
            clearTimeout(timer);
            controller.abort();
        };
    }, [query, selected, searchUrl]);

    // Close the result list when clicking elsewhere.
    useEffect(() => {
        const onClick = (e: MouseEvent) => !searchBox.current?.contains(e.target as Node) && setResults(null);
        document.addEventListener('click', onClick);
        return () => document.removeEventListener('click', onClick);
    }, []);

    function choose(person: Person) {
        setSelected(person);
        setQuery(person.name);
        setResults(null);
        form.setData('guarantor_id', String(person.id));
    }

    function change() {
        setSelected(null);
        setQuery('');
        form.setData('guarantor_id', '');
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post(submitUrl, { preserveScroll: true });
    }

    const section = fields.length > 0 ? 1 : 0;
    const err = (key: string) => form.errors[key as keyof FormShape];
    const nominee = props.guarantor?.name ?? t('Guarantor');
    const statusText: Record<GuarantorStatus, { title: string; sub: string }> = {
        pending: { title: t('Awaiting Response'), sub: t(':name has been notified and has not yet responded.', { name: nominee }) },
        accepted: { title: t('Guarantor Accepted'), sub: t(':name has accepted your guarantor request.', { name: nominee }) },
        declined: { title: t('Guarantor Declined'), sub: t(':name declined. You may select a different guarantor.', { name: nominee }) },
    };

    return (
        <div className="kyc-page">
            <Head title={t('KYC Verification')} />
            <div className="container">
                <div className="kyc-hero">
                    <div className="kyc-hero-icon"><i className="fas fa-shield-alt" /></div>
                    <h2 className="text-white">{t('KYC Verification')}</h2>
                    <p>{t('Verify your identity to unlock full platform features including loan applications.')}</p>
                    <div className="kyc-steps">
                        {[t('Basic Info'), t('ID Verification'), t('Guarantor')].map((label, i) => (
                            <div className="kyc-step" key={label}>
                                <div className="kyc-step-num">{i + 1}</div>
                                <div className="kyc-step-label">{label}</div>
                            </div>
                        ))}
                    </div>
                </div>

                {rejectionReason && (
                    <div className="kyc-rejection" role="alert">
                        <div className="kyc-rejection-icon"><i className="fas fa-times-circle" /></div>
                        <div>
                            <p className="kyc-rejection-title">{t('Previous Submission Rejected')}</p>
                            <p className="kyc-rejection-text">{rejectionReason}</p>
                        </div>
                    </div>
                )}

                <form onSubmit={submit}>
                    {fields.length > 0 && (
                        <div className="kyc-section">
                            <SectionHeader num={1} title={t('Basic Information')} sub={t('Personal details as they appear on your documents')} />
                            <div className="kyc-section-body">
                                <DynamicForm fields={fields} values={form.data} errors={form.errors as Record<string, string>} onChange={(key, value) => form.setData(key, value)} />
                            </div>
                        </div>
                    )}

                    <div className="kyc-section">
                        <SectionHeader num={section + 1} title={t('Identity Verification')} sub={t('Your NIN and a valid government-issued ID')} />
                        <div className="kyc-section-body">
                            <div className="mb-4">
                                <label className="kyc-label" htmlFor="nin">{t('National Identification Number (NIN)')} <span>*</span></label>
                                <input
                                    type="text"
                                    id="nin"
                                    className={`kyc-input ${err('nin') ? 'border-danger' : ''}`}
                                    placeholder="e.g. 12345678901"
                                    maxLength={20}
                                    autoComplete="off"
                                    value={form.data.nin}
                                    onChange={(e) => form.setData('nin', e.target.value)}
                                    aria-invalid={err('nin') ? true : undefined}
                                />
                                {err('nin') && <span className="kyc-error-msg">{err('nin')}</span>}
                                <p className="kyc-hint">{t('Enter your 11-digit National Identification Number.')}</p>
                            </div>

                            <div className="mb-4" role="radiogroup" aria-labelledby="card-type-label">
                                <label className="kyc-label" id="card-type-label">{t('ID Card Type')} <span>*</span></label>
                                {err('id_card_type') && <span className="kyc-error-msg mb-2 d-block">{err('id_card_type')}</span>}
                                <div className="card-type-grid">
                                    {CARD_TYPES.map((card) => (
                                        <label key={card.value} className={`card-type-pill ${form.data.id_card_type === card.value ? 'active' : ''}`}>
                                            <input
                                                type="radio"
                                                name="id_card_type"
                                                value={card.value}
                                                checked={form.data.id_card_type === card.value}
                                                onChange={() => form.setData('id_card_type', card.value)}
                                            />
                                            <span className="pill-icon" aria-hidden="true">{card.icon}</span>
                                            <span className="pill-text">{cardLabels[card.value]}</span>
                                        </label>
                                    ))}
                                </div>
                            </div>

                            <div>
                                <label className="kyc-label" htmlFor="idCardFile">{t('ID Card Image')} {!hasIdCard && <span>*</span>}</label>
                                {err('id_card_image') && <span className="kyc-error-msg mb-2 d-block">{err('id_card_image')}</span>}
                                {hasIdCard && (
                                    <div className="kyc-existing-file mb-2">
                                        <i className="fas fa-check-circle kyc-existing-icon" />
                                        <div>
                                            <p className="kyc-existing-text">{t('ID card already on file')}</p>
                                            <p className="kyc-existing-sub">{t('Upload a new image only if you want to replace it')}</p>
                                        </div>
                                    </div>
                                )}
                                <div
                                    className={`kyc-file-zone ${dragOver ? 'drag-over' : ''}`}
                                    onDragOver={(e) => { e.preventDefault(); setDragOver(true); }}
                                    onDragLeave={() => setDragOver(false)}
                                    onDrop={onDrop}
                                >
                                    <input
                                        type="file"
                                        id="idCardFile"
                                        accept="image/jpg,image/jpeg,image/png"
                                        onChange={(e) => form.setData('id_card_image', e.target.files?.[0] ?? null)}
                                    />
                                    <div className="kyc-upload-icon"><i className="fas fa-cloud-upload-alt" /></div>
                                    <p className="kyc-upload-title">{t('Click to upload or drag & drop')}</p>
                                    <p className="kyc-upload-hint">{t('JPG, JPEG, PNG — max 2 MB')}</p>
                                </div>
                                {form.data.id_card_image && (
                                    <div className="kyc-file-preview" style={{ display: 'flex' }}>
                                        {preview && <img src={preview} alt="" />}
                                        <div>
                                            <p className="kyc-file-preview-name">{form.data.id_card_image.name}</p>
                                            <p className="kyc-file-preview-size">{(form.data.id_card_image.size / 1024).toFixed(1)} KB</p>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="kyc-section">
                        <SectionHeader num={section + 2} title={t('Guarantor Nomination')} sub={t('Nominate another registered user to vouch for you')} />
                        <div className="kyc-section-body">
                            {err('guarantor_id') && <span className="kyc-error-msg mb-2 d-block">{err('guarantor_id')}</span>}
                            <div className="guarantor-search-wrap mb-2" ref={searchBox}>
                                <label className="kyc-label" htmlFor="guarantorSearch">{t('Search by name or username')} <span>*</span></label>
                                <div className="kyc-input-icon">
                                    <i className="fas fa-search" />
                                    <input
                                        type="text"
                                        id="guarantorSearch"
                                        className="kyc-input"
                                        role="combobox"
                                        aria-expanded={results !== null}
                                        aria-controls="guarantorDropdown"
                                        placeholder={t('Type at least 2 characters…')}
                                        autoComplete="off"
                                        value={query}
                                        readOnly={selected !== null}
                                        onChange={(e) => setQuery(e.target.value)}
                                    />
                                </div>
                                {results !== null && (
                                    <div className="guarantor-dropdown" id="guarantorDropdown" role="listbox" style={{ display: 'block' }}>
                                        {results.length === 0 ? (
                                            <div className="g-no-results">{t('No users found')}</div>
                                        ) : (
                                            results.map((person) => (
                                                <div
                                                    key={person.id}
                                                    className="guarantor-option"
                                                    role="option"
                                                    aria-selected={false}
                                                    tabIndex={0}
                                                    onClick={() => choose(person)}
                                                    onKeyDown={(e) => (e.key === 'Enter' || e.key === ' ') && (e.preventDefault(), choose(person))}
                                                >
                                                    <div className="g-option-avatar">{initials(person.name)}</div>
                                                    <div>
                                                        <div className="g-option-name">{person.name}</div>
                                                        <div className="g-option-user">@{person.username}</div>
                                                    </div>
                                                </div>
                                            ))
                                        )}
                                    </div>
                                )}
                            </div>

                            {selected && (
                                <div className="guarantor-selected" style={{ display: 'flex' }}>
                                    <div className="g-sel-avatar">{initials(selected.name)}</div>
                                    <div>
                                        <p className="g-sel-name">{selected.name}</p>
                                        <p className="g-sel-user">@{selected.username}</p>
                                    </div>
                                    <button type="button" className="g-sel-change btn btn-link p-0" onClick={change}>{t('Change')}</button>
                                </div>
                            )}

                            {/* Status of the request already sent. Blade hid this as soon as the page loaded,
                                so users never saw that their guarantor had declined. */}
                            {guarantorStatus && (!selected || selected.id === props.guarantor?.id) && (
                                <div className={`g-status-card mt-2 ${STATUS_STYLE[guarantorStatus].cls}`}>
                                    <i className={`${STATUS_STYLE[guarantorStatus].icon} g-status-icon`} />
                                    <div>
                                        <p className="g-status-title">{statusText[guarantorStatus].title}</p>
                                        <p className="g-status-sub">{statusText[guarantorStatus].sub}</p>
                                    </div>
                                </div>
                            )}

                            <p className="kyc-hint mt-3">{t('Your nominated guarantor will receive a request on their dashboard and must accept it before your KYC can be fully approved.')}</p>
                        </div>
                    </div>

                    <button type="submit" className={`kyc-submit-btn ${form.processing ? 'submitting' : ''}`} disabled={form.processing}>
                        <span className="btn-idle"><i className="fas fa-paper-plane" /> {t('Submit KYC Application')}</span>
                        <span className="btn-spinner"><i className="fas fa-spinner fa-spin" /> {t('Submitting…')}</span>
                    </button>
                </form>
            </div>
        </div>
    );
}

function SectionHeader({ num, title, sub }: { num: number; title: string; sub: string }) {
    return (
        <div className="kyc-section-header">
            <div className="kyc-section-num">{num}</div>
            <div>
                <p className="kyc-section-title">{title}</p>
                <p className="kyc-section-sub">{sub}</p>
            </div>
        </div>
    );
}

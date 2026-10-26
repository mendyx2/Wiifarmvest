import { Head, useForm } from '@inertiajs/react';
import { Fragment, useEffect, useRef, useState, type FormEvent, type InputHTMLAttributes, type ReactNode } from 'react';
import { useGeoLookup, type Geo } from '@/lib/geo';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type Country = { code: string; name: string; dial: string };
type Project = { id: number; title: string; amount: string; affordable: boolean };

type Props = PageProps<{
    sponsorUsername: string;
    visa: string;
    projects: Project[];
    countries: Country[];
    defaultCountry: string;
    prefill: { parent: string; position: string };
    urls: { store: string; check: string; geo: string };
}>;

type Check = { usernameTaken?: boolean; emailUsed?: boolean; mobileUsed?: boolean; parent?: { found: boolean; left: boolean; right: boolean } };

const STEPS = [
    { n: 1, icon: 'las la-user-friends', name: 'Referral & Project', short: 'Referral' },
    { n: 2, icon: 'las la-key', name: 'Credentials', short: 'Credentials' },
    { n: 3, icon: 'las la-address-card', name: 'Personal Details', short: 'Personal' },
    { n: 4, icon: 'las la-clipboard-check', name: 'Review & Submit', short: 'Finish' },
] as const;

/** Which step holds each server-side field, to jump back to the first error. */
const FIELD_STEP: Record<string, number> = {
    referBy: 1, project_id: 1, parent: 1, position: 1,
    username: 2, email: 2, password: 2,
    firstname: 3, lastname: 3, country: 3, country_code: 3, mobile_code: 3, mobile: 3, address: 3, state: 3, city: 3,
};

export default function Distributor({ sponsorUsername, visa, projects, countries, defaultCountry, prefill, urls }: Props) {
    const t = useTrans();
    const startCountry = countries.find((c) => c.code === defaultCountry) ?? countries[0];
    const blank = {
        referBy: sponsorUsername,
        parent: prefill.parent,
        position: prefill.position,
        project_id: '',
        username: '',
        email: '',
        password: '',
        password_confirmation: '',
        firstname: '',
        lastname: '',
        country: startCountry?.name ?? '',
        country_code: startCountry?.code ?? '',
        mobile_code: startCountry?.dial ?? '',
        mobile: '',
        state: '',
        city: '',
        address: '',
    };
    const form = useForm(blank);
    const [step, setStep] = useState(1);
    const [placement, setPlacement] = useState(prefill.parent !== '' || prefill.position !== '');
    const [check, setCheck] = useState<Check>({});
    const [shown, setShown] = useState({ password: false, password_confirmation: false });
    const { states, cities } = useGeoLookup(urls.geo, form.data.country, form.data.state);
    const paneRef = useRef<HTMLDivElement>(null);
    const project = projects.find((p) => String(p.id) === form.data.project_id);
    const country = countries.find((c) => c.name === form.data.country);
    const set = (key: keyof typeof blank) => (e: { target: { value: string } }) => form.setData(key, e.target.value);

    const runCheck = async (query: Record<string, string>) => {
        try {
            const res = await fetch(`${urls.check}?${new URLSearchParams(query)}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const data: Check = await res.json();
            setCheck((current) => ({ ...current, ...data }));
        } catch {
            // A failed live check is not worth an error: the server checks again on submit.
        }
    };

    useEffect(() => {
        if (placement && prefill.parent) runCheck({ parent: prefill.parent });
    }, []); // eslint-disable-line react-hooks/exhaustive-deps

    const goTo = (n: number) => {
        setStep(n);
        paneRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    /** The same checks the old page ran before each "Continue". */
    const problem = (target: number): string | null => {
        if (target >= 2) {
            if (!project) return t('Please select a project package');
            if (!project.affordable) return t('Insufficient VISA wallet balance for the selected project');
            if (placement && form.data.parent && !form.data.position) return t('Please choose the left or right position');
            if (placement && check.parent && !check.parent.found) return t('Parent not found or account is inactive');
        }
        if (target >= 3) {
            if (!form.data.username) return t('Please enter a username');
            if (check.usernameTaken) return t('Username already taken');
            if (!form.data.email) return t('Please enter an email');
            if (!form.data.password) return t('Please enter a password');
            if (form.data.password !== form.data.password_confirmation) return t('Passwords do not match');
        }
        if (target >= 4) {
            const required: [keyof typeof blank, string][] = [
                ['firstname', t('First name')],
                ['lastname', t('Last name')],
                ['mobile', t('Mobile number')],
                ['state', t('State')],
                ['city', t('City')],
                ['address', t('Address')],
            ];
            const missing = required.find(([key]) => !form.data[key]);
            if (missing) return t(':field is required', { field: missing[1] });
        }
        return null;
    };

    const next = (target: number) => {
        const message = problem(target);
        if (message) window.notify?.('error', message);
        else goTo(target);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (step !== 4) return;
        form.transform((data) => (placement ? data : { ...data, parent: '', position: '' }));
        form.post(urls.store, {
            preserveScroll: true,
            onSuccess: (page) => {
                // A refusal comes back as an error toast with no field errors: keep the form.
                const failed = (page.props.notify ?? []).some(([status]) => status === 'error');
                if (!failed) {
                    form.setData(blank);
                    setCheck({});
                    setPlacement(false);
                    goTo(1);
                }
            },
            onError: (errors) => {
                const first = Math.min(...Object.keys(errors).map((key) => FIELD_STEP[key] ?? 4));
                goTo(Number.isFinite(first) ? first : 4);
            },
            onFinish: () => form.setData((data) => ({ ...data, password: '', password_confirmation: '' })),
        });
    };

    const pwMatch = form.data.password && form.data.password_confirmation ? form.data.password === form.data.password_confirmation : null;
    const err = (key: string) => (form.errors as Record<string, string | undefined>)[key];

    return (
        <div className="dist-page">
            <Head title={t('Add Distributor')} />

            <div className="dist-page-header">
                <div className="dist-page-header-inner">
                    <div className="dist-page-header-icon">
                        <i className="las la-user-plus" />
                    </div>
                    <div>
                        <h4 className="dist-page-title">{t('Add Distributor')}</h4>
                        <p className="dist-page-sub">{t('Register a new distributor under your network. Registration cost will be deducted from your VISA wallet.')}</p>
                    </div>
                </div>
                <div className="dist-visa-pill">
                    <i className="las la-wallet" />
                    <span className="dist-visa-label">{t('VISA Wallet')}</span>
                    <strong className="dist-visa-amount">{visa}</strong>
                </div>
            </div>

            <div className="dist-body">
                <div className="dist-left-panel">
                    <div className="dist-step-guide">
                        {STEPS.map((s, i) => (
                            <div key={s.n}>
                                <div className={`dist-guide-item${s.n === step ? ' active' : ''}${s.n < step ? ' completed' : ''}`}>
                                    <div className="dist-guide-dot">
                                        <i className={s.icon} />
                                    </div>
                                    <div className="dist-guide-text">
                                        <span className="dist-guide-label">{t('Step :n', { n: s.n })}</span>
                                        <span className="dist-guide-name">{t(s.name)}</span>
                                    </div>
                                </div>
                                {i < STEPS.length - 1 && <div className="dist-guide-connector" />}
                            </div>
                        ))}
                    </div>

                    {project && (
                        <div className="dist-info-box" style={{ borderColor: project.affordable ? '#BBF7D0' : '#FECACA' }}>
                            <div className="dist-info-box-title">
                                <i className="las la-info-circle" /> {t('Project Cost')}
                            </div>
                            <div className="dist-info-box-row">
                                <span>{t('Project')}</span>
                                <strong>{project.title}</strong>
                            </div>
                            <div className="dist-info-box-row">
                                <span>{t('Cost')}</span>
                                <strong>{project.amount}</strong>
                            </div>
                            <div className="dist-info-box-row">
                                <span>{t('Your VISA')}</span>
                                <strong>{visa}</strong>
                            </div>
                            <div className={`dist-info-box-status ${project.affordable ? 'ok' : 'err'}`}>
                                {project.affordable ? `✓ ${t('Sufficient balance')}` : `✗ ${t('Insufficient balance')}`}
                            </div>
                        </div>
                    )}
                </div>

                <div className="dist-right-panel" ref={paneRef}>
                    <div className="dist-progress-wrap">
                        <div className="dist-progress-track">
                            <div className="dist-progress-fill" style={{ width: `${step * 25}%` }} />
                        </div>
                        <div className="dist-progress-steps">
                            {STEPS.map((s, i) => (
                                <Fragment key={s.n}>
                                    <div className={`dist-progress-step${s.n === step ? ' active' : ''}${s.n < step ? ' completed' : ''}`}>
                                        <div className="dist-step-circle">
                                            <span className="dist-step-num">{s.n}</span>
                                            <i className="las la-check dist-step-check" />
                                        </div>
                                        <span className="dist-step-label">{t(s.short)}</span>
                                    </div>
                                    {i < STEPS.length - 1 && <div className={`dist-step-line${i < step - 1 ? ' completed' : ''}`} />}
                                </Fragment>
                            ))}
                        </div>
                    </div>

                    <div className="dist-form-card">
                        <form onSubmit={submit} noValidate>
                            {/* ── Step 1 ── */}
                            <Pane active={step === 1} icon="las la-user-friends" title={t('Referral & Project')} sub={t('Enter the sponsor and select the project package.')}>
                                <Field label={t('Sponsor Username')} error={err('referBy')} hint={t('Leave as your own username to be the direct sponsor')}>
                                    <Input icon="la-at" value={form.data.referBy} onChange={set('referBy')} placeholder={t('Sponsor username')} name="referBy" />
                                </Field>

                                <div className="dist-toggle-row">
                                    <label className="dist-toggle-label">
                                        <div className="dist-toggle-switch">
                                            <input type="checkbox" checked={placement} onChange={(e) => setPlacement(e.target.checked)} />
                                            <span className="dist-toggle-slider" />
                                        </div>
                                        {t('Specify Placement Position')}
                                    </label>
                                </div>

                                {placement && (
                                    <div className="dist-placement-box" style={{ display: 'block' }}>
                                        {(prefill.parent || prefill.position) && (
                                            <div className="dist-pre-notice">
                                                <i className="las la-info-circle" /> {t('Placement pre-filled from your binary tree. You may adjust if needed.')}
                                            </div>
                                        )}
                                        <Field label={t('Parent Username')} error={err('parent')}>
                                            <Input
                                                icon="la-sitemap"
                                                name="parent"
                                                value={form.data.parent}
                                                onChange={(e) => {
                                                    form.setData('parent', e.target.value);
                                                    setCheck((c) => ({ ...c, parent: undefined }));
                                                }}
                                                onBlur={() => form.data.parent && runCheck({ parent: form.data.parent })}
                                                placeholder={t('Parent username')}
                                            />
                                            {check.parent && (
                                                <small className={check.parent.found ? 'text--success' : 'text--danger'}>
                                                    {!check.parent.found
                                                        ? t('Parent not found or account is inactive')
                                                        : check.parent.left || check.parent.right
                                                          ? t('Parent matched')
                                                          : t('Both positions under this parent are taken')}
                                                </small>
                                            )}
                                        </Field>
                                        <Field label={t('Position')} error={err('position')}>
                                            <div className="dist-select-wrap">
                                                <select className="dist-select" name="position" value={form.data.position} onChange={set('position')}>
                                                    <option value="">{t('Select position')}</option>
                                                    <option value="left" disabled={check.parent?.found && !check.parent.left}>
                                                        {t('Left Child')}
                                                        {check.parent?.found && !check.parent.left ? ` (${t('taken')})` : ''}
                                                    </option>
                                                    <option value="right" disabled={check.parent?.found && !check.parent.right}>
                                                        {t('Right Child')}
                                                        {check.parent?.found && !check.parent.right ? ` (${t('taken')})` : ''}
                                                    </option>
                                                </select>
                                                <i className="las la-chevron-down dist-select-arrow" />
                                            </div>
                                        </Field>
                                    </div>
                                )}

                                <Field label={<>{t('Select Project')} <span className="text--danger">*</span></>} error={err('project_id')}>
                                    <div className="dist-select-wrap">
                                        <select className="dist-select" name="project_id" value={form.data.project_id} onChange={set('project_id')}>
                                            <option value="">{t('Choose a project package')}</option>
                                            {projects.map((p) => (
                                                <option key={p.id} value={p.id}>
                                                    {p.title} — {p.amount}
                                                </option>
                                            ))}
                                        </select>
                                        <i className="las la-chevron-down dist-select-arrow" />
                                    </div>
                                    {project && (
                                        <div className={`dist-visa-status ${project.affordable ? 'ok' : 'err'}`} style={{ display: 'block' }}>
                                            {project.affordable ? (
                                                <>
                                                    <i className="las la-check-circle" /> {t('Sufficient balance — :visa available', { visa })}
                                                </>
                                            ) : (
                                                <>
                                                    <i className="las la-times-circle" /> {t('Insufficient balance. You have :visa but need :cost', { visa, cost: project.amount })}
                                                </>
                                            )}
                                        </div>
                                    )}
                                </Field>
                                <Nav onNext={() => next(2)} />
                            </Pane>

                            {/* ── Step 2 ── */}
                            <Pane active={step === 2} icon="las la-key" title={t('Account Credentials')} sub={t('Set up login credentials for the new distributor.')}>
                                <Field label={t('Username')} error={err('username') ?? (check.usernameTaken ? t('Username already taken') : undefined)}>
                                    <Input
                                        icon="la-user"
                                        name="username"
                                        value={form.data.username}
                                        onChange={(e) => {
                                            form.setData('username', e.target.value);
                                            setCheck((c) => ({ ...c, usernameTaken: undefined }));
                                        }}
                                        onBlur={() => form.data.username && runCheck({ username: form.data.username })}
                                        placeholder={t('Choose a username')}
                                    />
                                </Field>
                                <Field label={t('Email Address')} error={err('email')}>
                                    <Input icon="la-envelope" type="email" name="email" value={form.data.email} onChange={set('email')} onBlur={() => form.data.email && runCheck({ email: form.data.email })} placeholder="distributor@email.com" />
                                    {check.emailUsed && (
                                        <small className="dist-field-warn">
                                            <i className="las la-exclamation-triangle" /> {t('Email already registered — you may still proceed')}
                                        </small>
                                    )}
                                </Field>
                                <div className="dist-field-row">
                                    {(['password', 'password_confirmation'] as const).map((key) => (
                                        <Field key={key} label={key === 'password' ? t('Password') : t('Confirm Password')} error={key === 'password' ? err('password') : undefined}>
                                            <div className="dist-input-wrap">
                                                <span className="dist-input-icon">
                                                    <i className="las la-lock" />
                                                </span>
                                                <input
                                                    type={shown[key] ? 'text' : 'password'}
                                                    className="dist-input"
                                                    name={key}
                                                    autoComplete="new-password"
                                                    value={form.data[key]}
                                                    onChange={set(key)}
                                                    placeholder={key === 'password' ? t('Create password') : t('Repeat password')}
                                                />
                                                <button type="button" className="dist-eye-btn" aria-label={t('Show password')} onClick={() => setShown((s) => ({ ...s, [key]: !s[key] }))}>
                                                    <i className={`las ${shown[key] ? 'la-eye-slash' : 'la-eye'}`} />
                                                </button>
                                            </div>
                                            {key === 'password_confirmation' && pwMatch !== null && (
                                                <small className={pwMatch ? 'text--success' : 'text--danger'}>
                                                    <i className={`las ${pwMatch ? 'la-check-circle' : 'la-times-circle'}`} /> {pwMatch ? t('Passwords match') : t('Passwords do not match')}
                                                </small>
                                            )}
                                        </Field>
                                    ))}
                                </div>
                                <Nav onBack={() => goTo(1)} onNext={() => next(3)} />
                            </Pane>

                            {/* ── Step 3 ── */}
                            <Pane active={step === 3} icon="las la-address-card" title={t('Personal Details')} sub={t("Fill in the distributor's personal and contact information.")}>
                                <div className="dist-field-row">
                                    <Field label={t('First Name')} error={err('firstname')}>
                                        <Input icon="la-user-circle" name="firstname" value={form.data.firstname} onChange={set('firstname')} placeholder={t('First name')} />
                                    </Field>
                                    <Field label={t('Last Name')} error={err('lastname')}>
                                        <Input icon="la-user-circle" name="lastname" value={form.data.lastname} onChange={set('lastname')} placeholder={t('Last name')} />
                                    </Field>
                                </div>
                                <div className="dist-field-row">
                                    <Field label={<>{t('Country')} <span className="text--danger">*</span></>} error={err('country')}>
                                        <div className="dist-select-wrap">
                                            <select
                                                name="country"
                                                className="dist-select"
                                                value={form.data.country}
                                                onChange={(e) => {
                                                    const c = countries.find((x) => x.name === e.target.value);
                                                    form.setData((d) => ({ ...d, country: e.target.value, country_code: c?.code ?? '', mobile_code: c?.dial ?? '', state: '', city: '' }));
                                                }}
                                            >
                                                {countries.map((c, i) => (
                                                    <CountryOption key={c.code} country={c} separator={i === 1 && countries[0].name === 'Nigeria'} />
                                                ))}
                                            </select>
                                            <i className="las la-chevron-down dist-select-arrow" />
                                        </div>
                                    </Field>
                                    <Field label={t('Mobile Number')} error={err('mobile')}>
                                        <div className="dist-input-wrap dist-phone-wrap">
                                            <span className="dist-phone-code dist-mobile-code">+{country?.dial ?? ''}</span>
                                            <input
                                                type="tel"
                                                inputMode="numeric"
                                                name="mobile"
                                                className="dist-input dist-input-phone"
                                                value={form.data.mobile}
                                                onChange={(e) => form.setData('mobile', e.target.value.replace(/\D/g, ''))}
                                                onBlur={() => form.data.mobile && runCheck({ mobile: form.data.mobile, dial: form.data.mobile_code })}
                                                placeholder={t('Phone number')}
                                            />
                                        </div>
                                        {check.mobileUsed && (
                                            <small className="dist-field-warn">
                                                <i className="las la-exclamation-triangle" /> {t('Mobile already registered — you may still proceed')}
                                            </small>
                                        )}
                                    </Field>
                                </div>
                                <div className="dist-field-row">
                                    <Field label={<>{t('State / Province')} <span className="text--danger">*</span></>} error={err('state')}>
                                        <GeoSelect
                                            name="state"
                                            geo={states}
                                            value={form.data.state}
                                            placeholder={t('— Select state —')}
                                            onChange={(v) => form.setData((d) => ({ ...d, state: v, city: '' }))}
                                            icon="la-map"
                                        />
                                    </Field>
                                    <Field label={<>{t('City / LGA')} <span className="text--danger">*</span></>} error={err('city')}>
                                        <GeoSelect
                                            name="city"
                                            geo={cities}
                                            value={form.data.city}
                                            placeholder={form.data.state ? t('— Select city —') : t('— Select a state first —')}
                                            onChange={(v) => form.setData('city', v)}
                                            icon="la-city"
                                        />
                                    </Field>
                                </div>
                                <Field label={<>{t('Address')} <span className="text--danger">*</span></>} error={err('address')}>
                                    <Input icon="la-map-marker-alt" name="address" value={form.data.address} onChange={set('address')} placeholder={t('Street address')} />
                                </Field>
                                <Nav onBack={() => goTo(2)} onNext={() => next(4)} />
                            </Pane>

                            {/* ── Step 4 ── */}
                            <Pane active={step === 4} icon="las la-clipboard-check" title={t('Review & Submit')} sub={t('Confirm all details before registering the distributor.')}>
                                <div className="dist-review-card">
                                    <Review title={t('Referral & Project')} icon="la-user-friends" rows={[
                                        [t('Sponsor'), form.data.referBy || '—'],
                                        [t('Project'), project ? `${project.title} — ${project.amount}` : '—'],
                                        [t('Parent'), (placement && form.data.parent) || t('Automatic')],
                                        [t('Position'), placement && form.data.position ? t(form.data.position === 'left' ? 'Left' : 'Right') : t('Automatic')],
                                    ]} />
                                    <Review title={t('Credentials')} icon="la-key" rows={[
                                        [t('Username'), form.data.username || '—'],
                                        [t('Email'), form.data.email || '—'],
                                        [t('Password'), form.data.password ? '●'.repeat(Math.min(form.data.password.length, 10)) : '—'],
                                    ]} />
                                    <Review title={t('Personal')} icon="la-address-card" rows={[
                                        [t('Full Name'), `${form.data.firstname} ${form.data.lastname}`.trim() || '—'],
                                        [t('Country'), form.data.country || '—'],
                                        [t('Mobile'), form.data.mobile ? `+${form.data.mobile_code} ${form.data.mobile}` : '—'],
                                        [t('State'), form.data.state || '—'],
                                        [t('City / LGA'), form.data.city || '—'],
                                        [t('Address'), form.data.address || '—'],
                                    ]} />
                                </div>
                                <div className="dist-cost-notice">
                                    <i className="las la-exclamation-circle" />{' '}
                                    <span>
                                        {project
                                            ? t(':cost for ":project" will be deducted from your VISA wallet.', { cost: project.amount, project: project.title })
                                            : t('The project cost will be deducted from your VISA wallet upon submission.')}
                                    </span>
                                </div>
                                <div className="dist-edit-links">
                                    {[1, 2, 3].map((n) => (
                                        <button key={n} type="button" className="dist-edit-link" onClick={() => goTo(n)}>
                                            <i className="las la-pencil-alt" /> {t(['Edit Referral', 'Edit Credentials', 'Edit Personal'][n - 1])}
                                        </button>
                                    ))}
                                </div>
                                <div className="dist-submit-row">
                                    <button type="button" className="dist-btn-back" onClick={() => goTo(3)}>
                                        <i className="las la-arrow-left" /> {t('Back')}
                                    </button>
                                    <button type="submit" className="dist-btn-submit" disabled={form.processing}>
                                        <i className="las la-user-plus" /> {form.processing ? t('Registering…') : t('Register Distributor')}
                                    </button>
                                </div>
                            </Pane>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
}

/** States / cities as a list when the lookup works, a text box when it does not. */
function GeoSelect({ name, geo, value, placeholder, onChange, icon }: { name: string; geo: Geo; value: string; placeholder: string; onChange: (v: string) => void; icon: string }) {
    const t = useTrans();

    if (geo.status === 'failed') {
        return (
            <>
                <Input icon={icon} name={name} value={value} onChange={(e) => onChange(e.target.value)} placeholder={name === 'state' ? t('Type the state') : t('Type the city / LGA')} />
                <small className="dist-hint">{t('The list could not be loaded, so type it in.')}</small>
            </>
        );
    }

    return (
        <div className="dist-select-wrap dist-geo-wrap">
            <select name={name} className="dist-select" value={value} onChange={(e) => onChange(e.target.value)} disabled={geo.status !== 'ok'}>
                <option value="">{geo.status === 'loading' ? t('Loading…') : placeholder}</option>
                {geo.options.map((option) => (
                    <option key={option} value={option}>
                        {option}
                    </option>
                ))}
            </select>
            <i className={`las ${icon} dist-select-arrow`} />
            {geo.status === 'loading' && (
                <span className="dist-geo-loader">
                    <i className="las la-spinner la-spin" />
                </span>
            )}
        </div>
    );
}

function CountryOption({ country, separator }: { country: Country; separator: boolean }) {
    return (
        <>
            {separator && (
                <option value="" disabled>
                    ── Other countries ──
                </option>
            )}
            <option value={country.name}>{country.name}</option>
        </>
    );
}

function Pane({ active, icon, title, sub, children }: { active: boolean; icon: string; title: string; sub: string; children: ReactNode }) {
    return (
        <div className={`dist-pane${active ? ' active' : ''}`} hidden={!active}>
            <div className="dist-pane-header">
                <div className="dist-pane-icon">
                    <i className={icon} />
                </div>
                <div>
                    <h5 className="dist-pane-title">{title}</h5>
                    <p className="dist-pane-sub">{sub}</p>
                </div>
            </div>
            {children}
        </div>
    );
}

function Field({ label, error, hint, children }: { label: ReactNode; error?: string; hint?: string; children: ReactNode }) {
    return (
        <div className="dist-field-group">
            <label className="dist-label">{label}</label>
            {children}
            {hint && !error && <small className="dist-hint">{hint}</small>}
            {error && <small className="text--danger d-block">{error}</small>}
        </div>
    );
}

function Input({ icon, ...rest }: { icon: string } & InputHTMLAttributes<HTMLInputElement>) {
    return (
        <div className="dist-input-wrap">
            <span className="dist-input-icon">
                <i className={`las ${icon}`} />
            </span>
            <input type="text" className="dist-input" {...rest} />
        </div>
    );
}

function Nav({ onBack, onNext }: { onBack?: () => void; onNext: () => void }) {
    const t = useTrans();
    return (
        <div className="dist-nav-row">
            {onBack ? (
                <button type="button" className="dist-btn-back" onClick={onBack}>
                    <i className="las la-arrow-left" /> {t('Back')}
                </button>
            ) : (
                <div />
            )}
            <button type="button" className="dist-btn-next" onClick={onNext}>
                {t('Continue')} <i className="las la-arrow-right" />
            </button>
        </div>
    );
}

function Review({ title, icon, rows }: { title: string; icon: string; rows: [string, string][] }) {
    return (
        <div className="dist-review-section">
            <div className="dist-review-section-title">
                <i className={`las ${icon}`} /> {title}
            </div>
            {rows.map(([label, value]) => (
                <div className="dist-review-row" key={label}>
                    <span>{label}</span>
                    <strong>{value}</strong>
                </div>
            ))}
        </div>
    );
}

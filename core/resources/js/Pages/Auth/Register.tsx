import { Head, useForm } from '@inertiajs/react';
import { Fragment, useEffect, useRef, useState, type FormEvent, type InputHTMLAttributes, type ReactNode } from 'react';
import Captcha, { type CaptchaConfig, type CaptchaFields } from '@/Components/Captcha';
import Modal from '@/Components/Modal';
import { useGeoLookup, type Geo } from '@/lib/geo';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';
import './Register.css';

type Country = { code: string; name: string; dial: string };

type Props = PageProps<{
    logo: string;
    heading: { title: string; sub: string };
    /** From a ?ref= link: the sponsor is fixed */
    referrer: string | null;
    projects: { id: number; title: string; amount: string }[];
    countries: Country[];
    defaultCountry: string;
    open: boolean;
    mustAgree: boolean;
    securePassword: boolean;
    captcha: CaptchaConfig;
    urls: { submit: string; check: string; login: string; agreement: string; home: string; geo: string };
}>;

type Check = { usernameTaken?: boolean; emailUsed?: boolean; mobileUsed?: boolean; referralFound?: boolean; parent?: { found: boolean; left: boolean; right: boolean } };

const STEPS = [
    { n: 1, icon: 'las la-user-friends', name: 'Referral & Sponsor', short: 'Referral' },
    { n: 2, icon: 'las la-key', name: 'Account Credentials', short: 'Account' },
    { n: 3, icon: 'las la-address-card', name: 'Personal Details', short: 'Personal' },
    { n: 4, icon: 'las la-clipboard-check', name: 'Review & Submit', short: 'Finish' },
] as const;

const FIELD_STEP: Record<string, number> = {
    referBy: 1, parent: 1, position: 1, project_id: 1,
    username: 2, email: 2, password: 2,
    firstname: 3, lastname: 3, country: 3, country_code: 3, mobile_code: 3, mobile: 3, address: 3, state: 3, city: 3,
};

/** Same scoring and colours as the old page. */
const STRENGTH = [
    { w: '15%', color: '#EF4444', label: 'Weak' },
    { w: '40%', color: '#F97316', label: 'Fair' },
    { w: '70%', color: '#EAB308', label: 'Good' },
    { w: '100%', color: '#16A34A', label: 'Strong' },
];
const strength = (p: string) => STRENGTH[Math.max(0, [p.length >= 8, /[A-Z]/.test(p), /\d/.test(p), /[^A-Za-z0-9]/.test(p)].filter(Boolean).length - 1)];

export default function Register({ logo, heading, referrer, projects, countries, defaultCountry, open, mustAgree, securePassword, captcha, urls }: Props) {
    const t = useTrans();
    const start = countries.find((c) => c.code === defaultCountry) ?? countries[0];
    const form = useForm({
        referBy: referrer ?? '',
        parent: '',
        position: '',
        project_id: '',
        username: '',
        email: '',
        password: '',
        password_confirmation: '',
        firstname: '',
        lastname: '',
        country: start?.name ?? '',
        country_code: start?.code ?? '',
        mobile_code: start?.dial ?? '',
        mobile: '',
        address: '',
        state: '',
        city: '',
        agree: false,
    });
    const [step, setStep] = useState(1);
    const [placement, setPlacement] = useState(false);
    const [check, setCheck] = useState<Check>({});
    const [shown, setShown] = useState({ password: false, password_confirmation: false });
    const [captchaFields, setCaptchaFields] = useState<CaptchaFields>({});
    const [captchaKey, setCaptchaKey] = useState(0);
    const [existsOpen, setExistsOpen] = useState(false);
    const top = useRef<HTMLDivElement>(null);
    const { states, cities } = useGeoLookup(urls.geo, form.data.country, form.data.state);
    const project = projects.find((p) => String(p.id) === form.data.project_id);
    const country = countries.find((c) => c.name === form.data.country);
    const err = (key: string) => (form.errors as Record<string, string | undefined>)[key];
    type Field = Exclude<keyof typeof form.data, 'agree'>;
    const set = (key: Field) => (e: { target: { value: string } }) => form.setData(key, e.target.value);

    const runCheck = async (query: Record<string, string>) => {
        try {
            const res = await fetch(`${urls.check}?${new URLSearchParams(query)}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const data: Check = await res.json();
            setCheck((c) => ({ ...c, ...data }));
            if (data.emailUsed) setExistsOpen(true);
        } catch {
            // The server checks everything again on submit.
        }
    };

    // Referral typed by hand: look it up shortly after typing stops.
    useEffect(() => {
        if (referrer || !form.data.referBy) return setCheck((c) => ({ ...c, referralFound: undefined }));
        const timer = setTimeout(() => runCheck({ referral: form.data.referBy }), 400);
        return () => clearTimeout(timer);
    }, [form.data.referBy]); // eslint-disable-line react-hooks/exhaustive-deps

    const goTo = (n: number) => {
        setStep(n);
        top.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    const problem = (target: number): string | null => {
        if (target >= 2) {
            if (form.data.referBy && check.referralFound === false) return t('Referrer not found or account is inactive');
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
            const required: [Field, string][] = [
                ['firstname', t('First name')],
                ['lastname', t('Last name')],
                ['mobile', t('Mobile number')],
                ['address', t('Address')],
                ['state', t('State')],
                ['city', t('City')],
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
        if (step !== 4 || !open) return;
        form.transform((data) => ({ ...data, ...(placement ? {} : { parent: '', position: '' }), agree: data.agree ? 1 : '', ...captchaFields }));
        form.post(urls.submit, {
            onError: (errors) => {
                const first = Math.min(...Object.keys(errors).map((key) => FIELD_STEP[key] ?? 4));
                goTo(Number.isFinite(first) ? first : 4);
            },
            onFinish: () => {
                form.setData((d) => ({ ...d, password: '', password_confirmation: '' }));
                setCaptchaKey((k) => k + 1);
            },
        });
    };

    const pwMatch = form.data.password && form.data.password_confirmation ? form.data.password === form.data.password_confirmation : null;
    const meter = form.data.password ? strength(form.data.password) : null;

    return (
        <div className="page-register">
            <Head title={t('Register')} />
            <div className="bank-register-wrap">
                <div className="bank-left-panel">
                    <svg className="bank-geo-bg" viewBox="0 0 600 800" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" aria-hidden="true">
                        <circle cx="500" cy="-50" r="260" fill="rgba(255,255,255,.04)" />
                        <circle cx="-80" cy="700" r="300" fill="rgba(255,255,255,.04)" />
                        <circle cx="300" cy="400" r="180" fill="rgba(255,255,255,.03)" />
                        <polygon points="0,600 200,450 400,600" fill="rgba(255,255,255,.03)" />
                    </svg>
                    <div className="bank-left-inner">
                        <div className="bank-logo-wrap">
                            <a href={urls.home}>
                                <img src={logo} alt="logo" className="bank-logo" />
                            </a>
                        </div>
                        <div className="bank-left-hero">
                            <div className="bank-shield-icon">
                                <i className="las la-shield-alt" />
                            </div>
                            <h2 className="bank-hero-title">{t(heading.title)}</h2>
                            <p className="bank-hero-sub">{t(heading.sub)}</p>
                        </div>
                        <div className="bank-step-guide">
                            {STEPS.map((s, i) => (
                                <Fragment key={s.n}>
                                    <div className={`step-guide-item${s.n === step ? ' active-guide' : ''}${s.n < step ? ' completed-guide' : ''}`}>
                                        <div className="step-guide-dot">
                                            <i className={s.icon} />
                                        </div>
                                        <div>
                                            <div className="step-guide-label">{t('Step :n', { n: s.n })}</div>
                                            <div className="step-guide-name">{t(s.name)}</div>
                                        </div>
                                    </div>
                                    {i < STEPS.length - 1 && <div className="step-guide-connector" />}
                                </Fragment>
                            ))}
                        </div>
                    </div>
                </div>

                <div className="bank-right-panel" ref={top}>
                    {!open && (
                        <div className="bank-disabled-notice">
                            <i className="las la-exclamation-triangle" /> <strong>{t('Registration is currently disabled.')}</strong> {t('Please contact support for assistance.')}
                        </div>
                    )}

                    <div className="bank-progress-bar-wrap">
                        <div className="bank-progress-track">
                            <div className="bank-progress-fill" style={{ width: `${step * 25}%` }} />
                        </div>
                        <div className="bank-progress-steps">
                            {STEPS.map((s, i) => (
                                <Fragment key={s.n}>
                                    <div className={`bank-progress-step${s.n === step ? ' active' : ''}${s.n < step ? ' completed' : ''}`}>
                                        <div className="bps-circle">
                                            <span className="bps-num">{s.n}</span>
                                            <i className="las la-check bps-check" />
                                        </div>
                                        <span className="bps-label">{t(s.short)}</span>
                                    </div>
                                    {i < STEPS.length - 1 && <div className={`bps-line${i < step - 1 ? ' completed' : ''}`} />}
                                </Fragment>
                            ))}
                        </div>
                    </div>

                    <div className={`bank-form-card${open ? '' : ' form-disabled'}`}>
                        <form onSubmit={submit} noValidate>
                            {/* ── Step 1 ── */}
                            <Pane active={step === 1} icon="las la-user-friends" title={t('Referral & Sponsor Info')} sub={t("Enter your sponsor's details or leave blank if you don't have one.")}>
                                <Field label={t('Referral Username')} error={err('referBy')}>
                                    <Input icon="la-at" name="referBy" value={form.data.referBy} onChange={set('referBy')} readOnly={!!referrer} placeholder={t('Enter referral username')} />
                                    {referrer ? (
                                        <small className="bank-hint">
                                            <i className="las la-check-circle text--success" /> {t('Sponsor confirmed')}
                                        </small>
                                    ) : check.referralFound === undefined ? (
                                        <small className="bank-hint">{t("Leave blank if you don't have a sponsor")}</small>
                                    ) : (
                                        <small className={check.referralFound ? 'text--success' : 'text--danger'}>
                                            {check.referralFound ? t('Referrer matched') : t('Referrer not found or account is inactive')}
                                        </small>
                                    )}
                                </Field>

                                <div className="bank-toggle-row">
                                    <label className="bank-toggle-label">
                                        <div className="bank-toggle-switch">
                                            <input type="checkbox" checked={placement} onChange={(e) => setPlacement(e.target.checked)} />
                                            <span className="bank-toggle-slider" />
                                        </div>
                                        {t('Add Placement Details')}
                                    </label>
                                </div>

                                {placement && (
                                    <div className="bank-placement-box" style={{ display: 'block' }}>
                                        <Field label={t('Parent Placement Username')} error={err('parent')}>
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
                                                <small className={check.parent.found && (check.parent.left || check.parent.right) ? 'text--success' : 'text--danger'}>
                                                    {!check.parent.found ? t('Parent not found or account is inactive') : check.parent.left || check.parent.right ? t('Parent matched') : t('Both positions under this parent are taken')}
                                                </small>
                                            )}
                                        </Field>
                                        <Field label={t('Position')} error={err('position')}>
                                            <div className="bank-select-wrap">
                                                <select className="bank-select" name="position" value={form.data.position} onChange={set('position')}>
                                                    <option value="">{t('Select position')}</option>
                                                    {(['left', 'right'] as const).map((side) => (
                                                        <option key={side} value={side} disabled={check.parent?.found && !check.parent[side]}>
                                                            {side === 'left' ? t('Left Child') : t('Right Child')}
                                                            {check.parent?.found && !check.parent[side] ? ` (${t('taken')})` : ''}
                                                        </option>
                                                    ))}
                                                </select>
                                                <i className="las la-chevron-down bank-select-arrow" />
                                            </div>
                                        </Field>
                                    </div>
                                )}

                                {projects.length > 0 && (
                                    <Field label={t('Select Project')} error={err('project_id')}>
                                        <div className="bank-select-wrap">
                                            <select className="bank-select" name="project_id" value={form.data.project_id} onChange={set('project_id')}>
                                                <option value="">{t('Choose a project')}</option>
                                                {projects.map((p) => (
                                                    <option key={p.id} value={p.id}>
                                                        {p.title} — {p.amount}
                                                    </option>
                                                ))}
                                            </select>
                                            <i className="las la-chevron-down bank-select-arrow" />
                                        </div>
                                    </Field>
                                )}
                                <Nav onNext={() => next(2)} />
                            </Pane>

                            {/* ── Step 2 ── */}
                            <Pane active={step === 2} icon="las la-key" title={t('Account Credentials')} sub={t('Choose a unique username, enter your email and set a strong password.')}>
                                <Field label={t('Username')} error={err('username') ?? (check.usernameTaken ? t('Username already exist') : undefined)}>
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
                                    <Input icon="la-envelope" type="email" name="email" value={form.data.email} onChange={set('email')} onBlur={() => form.data.email && runCheck({ email: form.data.email })} placeholder="your@email.com" />
                                </Field>
                                <div className="bank-field-row">
                                    {(['password', 'password_confirmation'] as const).map((key) => (
                                        <Field key={key} label={key === 'password' ? t('Password') : t('Confirm Password')} error={key === 'password' ? err('password') : undefined}>
                                            <div className="bank-input-wrap">
                                                <span className="bank-input-icon">
                                                    <i className="las la-lock" />
                                                </span>
                                                <input
                                                    className="bank-input"
                                                    name={key}
                                                    type={shown[key] ? 'text' : 'password'}
                                                    autoComplete="new-password"
                                                    value={form.data[key]}
                                                    onChange={set(key)}
                                                    placeholder={key === 'password' ? t('Create password') : t('Repeat password')}
                                                />
                                                <button type="button" className="bank-eye-btn" aria-label={t('Show password')} onClick={() => setShown((s) => ({ ...s, [key]: !s[key] }))}>
                                                    <i className={`las ${shown[key] ? 'la-eye-slash' : 'la-eye'}`} />
                                                </button>
                                            </div>
                                            {key === 'password' && meter && (
                                                <div className="pw-strength-wrap">
                                                    <div className="pw-strength-bar">
                                                        <div className="pw-strength-fill" style={{ width: meter.w, background: meter.color }} />
                                                    </div>
                                                    <span className="pw-strength-label" style={{ color: meter.color }}>
                                                        {t(meter.label)}
                                                    </span>
                                                </div>
                                            )}
                                            {key === 'password' && securePassword && form.data.password && meter?.label !== 'Strong' && (
                                                <small className="bank-hint">{t('Use upper and lower case letters, a number and a symbol.')}</small>
                                            )}
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
                            <Pane active={step === 3} icon="las la-address-card" title={t('Personal & Contact Details')} sub={t('Tell us a bit about yourself to complete your profile.')}>
                                <div className="bank-field-row">
                                    <Field label={t('First Name')} error={err('firstname')}>
                                        <Input icon="la-user-circle" name="firstname" value={form.data.firstname} onChange={set('firstname')} placeholder={t('First name')} />
                                    </Field>
                                    <Field label={t('Last Name')} error={err('lastname')}>
                                        <Input icon="la-user-circle" name="lastname" value={form.data.lastname} onChange={set('lastname')} placeholder={t('Last name')} />
                                    </Field>
                                </div>
                                <div className="bank-field-row">
                                    <Field label={t('Country')} error={err('country')}>
                                        <div className="bank-select-wrap">
                                            <select
                                                name="country"
                                                className="bank-select"
                                                value={form.data.country}
                                                onChange={(e) => {
                                                    const c = countries.find((x) => x.name === e.target.value);
                                                    form.setData((d) => ({ ...d, country: e.target.value, country_code: c?.code ?? '', mobile_code: c?.dial ?? '', state: '', city: '' }));
                                                }}
                                            >
                                                {countries.map((c, i) => (
                                                    <Fragment key={c.code}>
                                                        {i === 1 && countries[0].name === 'Nigeria' && (
                                                            <option value="" disabled>
                                                                ── {t('Other countries')} ──
                                                            </option>
                                                        )}
                                                        <option value={c.name}>{c.name}</option>
                                                    </Fragment>
                                                ))}
                                            </select>
                                            <i className="las la-chevron-down bank-select-arrow" />
                                        </div>
                                    </Field>
                                    <Field label={t('Mobile Number')} error={err('mobile') ?? (check.mobileUsed ? t('Mobile already exist') : undefined)}>
                                        <div className="bank-input-wrap bank-phone-wrap">
                                            <span className="bank-phone-code mobile-code">+{country?.dial ?? ''}</span>
                                            <input
                                                type="tel"
                                                inputMode="numeric"
                                                name="mobile"
                                                className="bank-input bank-input-phone"
                                                value={form.data.mobile}
                                                onChange={(e) => form.setData('mobile', e.target.value.replace(/\D/g, ''))}
                                                onBlur={() => form.data.mobile && runCheck({ mobile: form.data.mobile, dial: form.data.mobile_code })}
                                                placeholder={t('Phone number')}
                                            />
                                        </div>
                                    </Field>
                                </div>
                                <Field label={t('Address')} error={err('address')}>
                                    <Input icon="la-map-marker-alt" name="address" value={form.data.address} onChange={set('address')} placeholder={t('Street address')} />
                                </Field>
                                <div className="bank-field-row">
                                    <Field label={t('State / Province')} error={err('state')}>
                                        <GeoField name="state" geo={states} value={form.data.state} placeholder={t('— Select state —')} onChange={(v) => form.setData((d) => ({ ...d, state: v, city: '' }))} />
                                    </Field>
                                    <Field label={t('City')} error={err('city')}>
                                        <GeoField name="city" geo={cities} value={form.data.city} placeholder={form.data.state ? t('— Select city —') : t('— Select a state first —')} onChange={(v) => form.setData('city', v)} />
                                    </Field>
                                </div>
                                <Nav onBack={() => goTo(2)} onNext={() => next(4)} />
                            </Pane>

                            {/* ── Step 4 ── */}
                            <Pane active={step === 4} icon="las la-clipboard-check" title={t('Review & Submit')} sub={t('Please verify your information before submitting your application.')}>
                                <div className="bank-review-card">
                                    <Review title={t('Referral & Project')} icon="la-user-friends" rows={[
                                        [t('Referral'), form.data.referBy || '—'],
                                        [t('Parent'), (placement && form.data.parent) || '—'],
                                        [t('Position'), placement && form.data.position ? t(form.data.position === 'left' ? 'Left' : 'Right') : '—'],
                                        [t('Project'), project ? `${project.title} — ${project.amount}` : '—'],
                                    ]} />
                                    <Review title={t('Account')} icon="la-key" rows={[
                                        [t('Username'), form.data.username || '—'],
                                        [t('Email'), form.data.email || '—'],
                                    ]} />
                                    <Review title={t('Personal')} icon="la-address-card" rows={[
                                        [t('Full Name'), `${form.data.firstname} ${form.data.lastname}`.trim() || '—'],
                                        [t('Country'), form.data.country || '—'],
                                        [t('Mobile'), form.data.mobile ? `+${form.data.mobile_code} ${form.data.mobile}` : '—'],
                                        [t('Address'), form.data.address || '—'],
                                        [t('State'), form.data.state || '—'],
                                        [t('City'), form.data.city || '—'],
                                    ]} />
                                </div>

                                <div className="bank-edit-links">
                                    {[1, 2, 3].map((n) => (
                                        <button key={n} type="button" className="bank-edit-link" onClick={() => goTo(n)}>
                                            <i className="las la-pencil-alt" /> {t(['Edit Referral', 'Edit Account', 'Edit Personal'][n - 1])}
                                        </button>
                                    ))}
                                </div>

                                <div className="mt-3">
                                    <Captcha key={captchaKey} config={captcha} onChange={setCaptchaFields} />
                                </div>

                                {mustAgree && (
                                    <div className="bank-agree-row">
                                        <label className="bank-agree-label" htmlFor="agree">
                                            <input id="agree" type="checkbox" className="bank-checkbox" checked={form.data.agree} onChange={(e) => form.setData('agree', e.target.checked)} />
                                            <span>
                                                {t('I agree with the')}{' '}
                                                <a className="bank-link" href={urls.agreement} target="_blank" rel="noopener noreferrer">
                                                    {t('Terms of Use and Member Investment Policy')}
                                                </a>
                                            </span>
                                        </label>
                                        {err('agree') && <small className="text--danger d-block">{err('agree')}</small>}
                                    </div>
                                )}

                                <div className="bank-submit-row">
                                    <button type="button" className="bank-btn-back" onClick={() => goTo(3)}>
                                        <i className="las la-arrow-left" /> {t('Back')}
                                    </button>
                                    <button className="bank-btn-submit" type="submit" disabled={!open || form.processing || (mustAgree && !form.data.agree)}>
                                        <i className="las la-paper-plane" /> {form.processing ? t('Submitting…') : t('Submit Application')}
                                    </button>
                                </div>
                            </Pane>

                            <div className="bank-login-row">
                                {t('Already have an account?')}{' '}
                                <a href={urls.login} className="bank-link">
                                    {t('Sign In')}
                                </a>
                            </div>
                        </form>
                    </div>

                    <div className="bank-footer-note">
                        <i className="las la-lock" /> {t('Click continue to advance')}
                    </div>
                </div>
            </div>

            <Modal open={existsOpen} onClose={() => setExistsOpen(false)} contentClassName="bank-modal" labelledBy="exists-title">
                <div className="modal-header bank-modal-header">
                    <h5 className="modal-title" id="exists-title">
                        {t('Account Already Exists')}
                    </h5>
                    <button type="button" className="btn-close btn-close-white" aria-label={t('Close')} onClick={() => setExistsOpen(false)} />
                </div>
                <div className="modal-body text-center py-4">
                    <div className="bank-modal-icon">
                        <i className="las la-user-check" />
                    </div>
                    <p className="mt-2">{t('An account with this information already exists. Please sign in instead.')}</p>
                </div>
                <div className="modal-footer">
                    <button className="bank-btn-back" type="button" onClick={() => setExistsOpen(false)}>
                        {t('Close')}
                    </button>
                    <a className="bank-btn-next" href={urls.login}>
                        {t('Go to Login')}
                    </a>
                </div>
            </Modal>
        </div>
    );
}

// Full screen two-panel page: neither the dashboard nor the login card.
Register.layout = (page: ReactNode) => page;

function GeoField({ name, geo, value, placeholder, onChange }: { name: string; geo: Geo; value: string; placeholder: string; onChange: (v: string) => void }) {
    const t = useTrans();
    if (geo.status === 'failed') {
        return (
            <>
                <Input icon={name === 'state' ? 'la-map' : 'la-city'} name={name} value={value} onChange={(e) => onChange(e.target.value)} placeholder={name === 'state' ? t('Type the state') : t('Type the city')} />
                <small className="bank-hint">{t('The list could not be loaded, so type it in.')}</small>
            </>
        );
    }
    return (
        <>
            <div className="bank-select-wrap">
                <select name={name} className="bank-select" value={value} onChange={(e) => onChange(e.target.value)} disabled={geo.status !== 'ok'}>
                    <option value="">{geo.status === 'loading' ? t('Loading…') : placeholder}</option>
                    {geo.options.map((option) => (
                        <option key={option} value={option}>
                            {option}
                        </option>
                    ))}
                </select>
                <i className="las la-chevron-down bank-select-arrow" />
            </div>
            {geo.status === 'loading' && (
                <small className="reg-geo-loader">
                    <i className="las la-spinner la-spin" /> {name === 'state' ? t('Loading states…') : t('Loading cities…')}
                </small>
            )}
        </>
    );
}

function Pane({ active, icon, title, sub, children }: { active: boolean; icon: string; title: string; sub: string; children: ReactNode }) {
    return (
        <div className={`bank-pane${active ? ' active' : ''}`} hidden={!active}>
            <div className="bank-pane-header">
                <div className="bank-pane-icon">
                    <i className={icon} />
                </div>
                <div>
                    <h5 className="bank-pane-title">{title}</h5>
                    <p className="bank-pane-sub">{sub}</p>
                </div>
            </div>
            {children}
        </div>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) {
    return (
        <div className="bank-field-group">
            <label className="bank-label">{label}</label>
            {children}
            {error && <small className="text--danger d-block">{error}</small>}
        </div>
    );
}

function Input({ icon, ...rest }: { icon: string } & InputHTMLAttributes<HTMLInputElement>) {
    return (
        <div className="bank-input-wrap">
            <span className="bank-input-icon">
                <i className={`las ${icon}`} />
            </span>
            <input type="text" className="bank-input" {...rest} />
        </div>
    );
}

function Nav({ onBack, onNext }: { onBack?: () => void; onNext: () => void }) {
    const t = useTrans();
    return (
        <div className="bank-nav-row">
            {onBack ? (
                <button type="button" className="bank-btn-back" onClick={onBack}>
                    <i className="las la-arrow-left" /> {t('Back')}
                </button>
            ) : (
                <div />
            )}
            <button type="button" className="bank-btn-next" onClick={onNext}>
                {t('Continue')} <i className="las la-arrow-right" />
            </button>
        </div>
    );
}

function Review({ title, icon, rows }: { title: string; icon: string; rows: [string, string][] }) {
    return (
        <div className="bank-review-section">
            <div className="bank-review-section-title">
                <i className={`las ${icon}`} /> {title}
            </div>
            {rows.map(([label, value]) => (
                <div className="bank-review-row" key={label}>
                    <span className="bank-review-key">{label}</span>
                    <span className="bank-review-val">{value}</span>
                </div>
            ))}
        </div>
    );
}

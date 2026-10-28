import { Head, Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import SlField from '@/Components/Stockist/SlField';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type Basic = {
    business_name: string;
    business_registration_number: string;
    business_email: string;
    business_phone: string;
    website: string;
    business_description: string;
};
type Location = { address_line_1: string; address_line_2: string; city: string; state: string; postal_code: string; country: string; phone: string; email: string };
type Day = { day: string; open: string | null; close: string | null };
type Service = { name: string; description: string };

type Props = PageProps<{
    active: boolean;
    basic: Basic;
    location: Location | null;
    hours: Day[];
    services: Service[];
    urls: { basic: string; location: string; hours: string; services: string; dashboard: string };
}>;

const TABS = [
    { key: 'basic', icon: 'la-building', label: 'Basic Info' },
    { key: 'location', icon: 'la-map-marker-alt', label: 'Location' },
    { key: 'hours', icon: 'la-clock', label: 'Opening Hours' },
    { key: 'services', icon: 'la-concierge-bell', label: 'Services' },
    { key: 'preview', icon: 'la-eye', label: 'Preview' },
] as const;
type Tab = (typeof TABS)[number]['key'];

const COUNTRIES = ['Nigeria', 'Ghana', 'Kenya', 'South Africa'];
const EMPTY_LOCATION: Location = { address_line_1: '', address_line_2: '', city: '', state: '', postal_code: '', country: 'Nigeria', phone: '', email: '' };

export default function StockistProfile({ active, basic, location, hours, services, urls }: Props) {
    const t = useTrans();
    const [tab, setTab] = useState<Tab>('basic');

    const basicForm = useForm<Basic>(basic);
    const locationForm = useForm<Location>(location ?? EMPTY_LOCATION);
    const hoursForm = useForm({
        opening_hours: Object.fromEntries(hours.map((d) => [d.day, { closed: !d.open || !d.close, open: d.open ?? '08:00', close: d.close ?? '17:00' }])) as Record<string, { closed: boolean; open: string; close: string }>,
    });
    const servicesForm = useForm({ services });

    const save = (form: { post: (url: string, options: object) => void }, url: string) => (event: FormEvent) => {
        event.preventDefault();
        form.post(url, { preserveScroll: true });
    };

    const setDay = (day: string, change: Partial<{ closed: boolean; open: string; close: string }>) =>
        hoursForm.setData('opening_hours', { ...hoursForm.data.opening_hours, [day]: { ...hoursForm.data.opening_hours[day], ...change } });

    const errors = (form: { errors: Record<string, string> }, key: string) => form.errors[key];
    const saveButton = (busy: boolean, label: string) => (
        <div className="sl-form-actions mt-4" style={{ justifyContent: 'flex-end' }}>
            <button type="submit" className="sl-btn sl-btn-primary" disabled={busy}>
                <i className={`las ${busy ? 'la-spinner la-spin' : 'la-save'} me-1`} /> {label}
            </button>
        </div>
    );

    return (
        <div className="nc-wrap" id="ncWrap">
            <Head title={t('Stockist Profile')} />
            <br />
            <div className="sl-page-header d-flex align-items-center gap-3 mb-4">
                <Link href={urls.dashboard} className="sl-back-btn" aria-label={t('Back')}><i className="las la-arrow-left" /></Link>
                <div>
                    <h4 className="sl-page-title mb-0">{t('Stockist Profile')}</h4>
                    <p className="sl-page-subtitle mb-0">{t('Manage your business information and locations.')}</p>
                </div>
            </div>

            <div className="row g-4">
                <div className="col-12 col-lg-3">
                    <div className="sl-card mb-3 sticky-lg-top" style={{ top: 100 }}>
                        <ul className="sl-profile-nav" role="tablist" aria-label={t('Profile sections')}>
                            {TABS.map((item) => (
                                <li key={item.key} role="presentation">
                                    <button
                                        type="button"
                                        role="tab"
                                        id={`tab-${item.key}`}
                                        aria-selected={tab === item.key}
                                        aria-controls={`panel-${item.key}`}
                                        className={`sl-profile-nav-item w-100 text-start border-0 bg-transparent ${tab === item.key ? 'active' : ''}`}
                                        onClick={() => setTab(item.key)}
                                    >
                                        <i className={`las ${item.icon}`} /> {t(item.label)}
                                    </button>
                                </li>
                            ))}
                        </ul>
                    </div>
                    <div className="sl-card">
                        <div className="sl-card-body text-center">
                            {active ? (
                                <span className="sl-status sl-status-active" style={{ fontSize: '.8rem', padding: '.4rem .9rem' }}><i className="las la-check-circle me-1" /> {t('Verified Stockist')}</span>
                            ) : (
                                <span className="sl-status sl-status-pending" style={{ fontSize: '.8rem', padding: '.4rem .9rem' }}><i className="las la-hourglass-half me-1" /> {t('Pending Verification')}</span>
                            )}
                            <p className="text-muted mt-3 mb-0" style={{ fontSize: '.8rem' }}>
                                {active ? t('Your account is verified and active') : t('Your account is pending admin verification')}
                            </p>
                        </div>
                    </div>
                </div>

                <div className="col-12 col-lg-9" role="tabpanel" id={`panel-${tab}`} aria-labelledby={`tab-${tab}`}>
                    {tab === 'basic' && (
                        <div className="sl-card">
                            <div className="sl-card-header"><i className="las la-building me-1" /> {t('Basic Business Information')}</div>
                            <div className="sl-card-body">
                                <form onSubmit={save(basicForm, urls.basic)}>
                                    <div className="row g-3">
                                        <div className="col-12 col-md-6">
                                            <SlField label={t('Business Name')} name="business_name" required value={basicForm.data.business_name} onValue={(v) => basicForm.setData('business_name', v)} error={errors(basicForm, 'business_name')} />
                                        </div>
                                        <div className="col-12 col-md-6">
                                            <SlField label={t('Registration Number')} name="business_registration_number" placeholder={t('Optional')} value={basicForm.data.business_registration_number} onValue={(v) => basicForm.setData('business_registration_number', v)} error={errors(basicForm, 'business_registration_number')} />
                                        </div>
                                        <div className="col-12 col-md-6">
                                            <SlField label={t('Business Email')} name="business_email" type="email" required value={basicForm.data.business_email} onValue={(v) => basicForm.setData('business_email', v)} error={errors(basicForm, 'business_email')} />
                                        </div>
                                        <div className="col-12 col-md-6">
                                            <SlField label={t('Business Phone')} name="business_phone" required value={basicForm.data.business_phone} onValue={(v) => basicForm.setData('business_phone', v)} error={errors(basicForm, 'business_phone')} />
                                        </div>
                                        <div className="col-12">
                                            <SlField label={t('Website')} name="website" type="url" placeholder="https://example.com" value={basicForm.data.website} onValue={(v) => basicForm.setData('website', v)} error={errors(basicForm, 'website')} />
                                        </div>
                                        <div className="col-12">
                                            <SlField
                                                label={t('Business Description')}
                                                name="business_description"
                                                rows={4}
                                                maxLength={1000}
                                                placeholder={t('Describe your business…')}
                                                hint={t('Tell customers about your business (max 1000 characters)')}
                                                value={basicForm.data.business_description}
                                                onValue={(v) => basicForm.setData('business_description', v)}
                                                error={errors(basicForm, 'business_description')}
                                            />
                                        </div>
                                    </div>
                                    {saveButton(basicForm.processing, t('Save Changes'))}
                                </form>
                            </div>
                        </div>
                    )}

                    {tab === 'location' && (
                        <div className="sl-card">
                            <div className="sl-card-header"><i className="las la-map-marker-alt me-1" /> {t('Business Location')}</div>
                            <div className="sl-card-body">
                                <form onSubmit={save(locationForm, urls.location)}>
                                    <div className="row g-3">
                                        <div className="col-12">
                                            <SlField label={t('Address Line 1')} name="address_line_1" required placeholder={t('Street address, P.O. box')} value={locationForm.data.address_line_1} onValue={(v) => locationForm.setData('address_line_1', v)} error={errors(locationForm, 'address_line_1')} />
                                        </div>
                                        <div className="col-12">
                                            <SlField label={t('Address Line 2')} name="address_line_2" placeholder={t('Apartment, suite, unit, building, floor, etc.')} value={locationForm.data.address_line_2} onValue={(v) => locationForm.setData('address_line_2', v)} error={errors(locationForm, 'address_line_2')} />
                                        </div>
                                        <div className="col-12 col-md-4">
                                            <SlField label={t('City')} name="city" required value={locationForm.data.city} onValue={(v) => locationForm.setData('city', v)} error={errors(locationForm, 'city')} />
                                        </div>
                                        <div className="col-12 col-md-4">
                                            <SlField label={t('State')} name="state" required value={locationForm.data.state} onValue={(v) => locationForm.setData('state', v)} error={errors(locationForm, 'state')} />
                                        </div>
                                        <div className="col-12 col-md-4">
                                            <SlField label={t('Postal Code')} name="postal_code" value={locationForm.data.postal_code} onValue={(v) => locationForm.setData('postal_code', v)} error={errors(locationForm, 'postal_code')} />
                                        </div>
                                        <div className="col-12 col-md-6">
                                            <label className="sl-label" htmlFor="country">{t('Country')} <span className="sl-required">*</span></label>
                                            <select className="sl-select" id="country" value={locationForm.data.country} onChange={(e) => locationForm.setData('country', e.target.value)} required>
                                                {(COUNTRIES.includes(locationForm.data.country) ? COUNTRIES : [locationForm.data.country, ...COUNTRIES]).map((c) => <option key={c} value={c}>{c}</option>)}
                                            </select>
                                        </div>
                                        <div className="col-12 col-md-6">
                                            <SlField label={t('Location Phone')} id="location_phone" name="phone" value={locationForm.data.phone} onValue={(v) => locationForm.setData('phone', v)} error={errors(locationForm, 'phone')} />
                                        </div>
                                        <div className="col-12">
                                            <SlField label={t('Location Email')} id="location_email" name="email" type="email" value={locationForm.data.email} onValue={(v) => locationForm.setData('email', v)} error={errors(locationForm, 'email')} />
                                        </div>
                                    </div>
                                    {saveButton(locationForm.processing, t('Save Location'))}
                                </form>
                            </div>
                        </div>
                    )}

                    {tab === 'hours' && (
                        <div className="sl-card">
                            <div className="sl-card-header"><i className="las la-clock me-1" /> {t('Opening Hours')}</div>
                            <div className="sl-card-body">
                                {!location && <div className="alert sl-alert-warning" style={{ fontSize: '.85rem' }}>{t('Save your business location first; opening hours belong to it.')}</div>}
                                <form onSubmit={save(hoursForm, urls.hours)}>
                                    <div className="table-responsive">
                                        <table className="sl-table" aria-label={t('Opening hours')}>
                                            <thead>
                                                <tr>
                                                    <th>{t('Day')}</th>
                                                    <th>{t('Open')}</th>
                                                    <th>{t('Close')}</th>
                                                    <th>{t('Status')}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {hours.map(({ day }) => {
                                                    const row = hoursForm.data.opening_hours[day];
                                                    const label = t(day.charAt(0).toUpperCase() + day.slice(1));
                                                    return (
                                                        <tr key={day}>
                                                            <td data-label={t('Day')} className="fw-600">{label}</td>
                                                            <td data-label={t('Open')}>
                                                                <input type="time" className="sl-input" style={{ padding: '.4rem .6rem' }} aria-label={t(':day opens', { day: label })} value={row.open} disabled={row.closed} required={!row.closed} onChange={(e) => setDay(day, { open: e.target.value })} />
                                                            </td>
                                                            <td data-label={t('Close')}>
                                                                <input type="time" className="sl-input" style={{ padding: '.4rem .6rem' }} aria-label={t(':day closes', { day: label })} value={row.close} disabled={row.closed} required={!row.closed} onChange={(e) => setDay(day, { close: e.target.value })} />
                                                            </td>
                                                            <td data-label={t('Status')}>
                                                                <label className="sl-switch">
                                                                    <input type="checkbox" aria-label={t('Open on :day', { day: label })} checked={!row.closed} onChange={(e) => setDay(day, { closed: !e.target.checked })} />
                                                                    <span className="sl-switch-track" />
                                                                </label>
                                                                <span className="ms-2" style={{ fontSize: '.78rem' }}>{row.closed ? t('Closed') : t('Open')}</span>
                                                            </td>
                                                        </tr>
                                                    );
                                                })}
                                            </tbody>
                                        </table>
                                    </div>
                                    {Object.keys(hoursForm.errors).length > 0 && <small className="d-block mt-2" style={{ color: '#DC2626' }}>{Object.values(hoursForm.errors)[0]}</small>}
                                    {saveButton(hoursForm.processing, t('Save Hours'))}
                                </form>
                            </div>
                        </div>
                    )}

                    {tab === 'services' && (
                        <div className="sl-card">
                            <div className="sl-card-header"><i className="las la-concierge-bell me-1" /> {t('Services Offered')}</div>
                            <div className="sl-card-body">
                                <form onSubmit={save(servicesForm, urls.services)}>
                                    {servicesForm.data.services.map((service, i) => {
                                        const update = (change: Partial<Service>) =>
                                            servicesForm.setData('services', servicesForm.data.services.map((s, j) => (j === i ? { ...s, ...change } : s)));
                                        return (
                                            <div className="sl-card mb-3" style={{ boxShadow: 'none' }} key={i}>
                                                <div className="sl-card-body">
                                                    <div className="row g-2">
                                                        <div className="col-12 col-md-5">
                                                            <SlField label={t('Service Name')} id={`service-name-${i}`} required placeholder={t('e.g., Product Redemption')} value={service.name} onValue={(v) => update({ name: v })} error={servicesForm.errors[`services.${i}.name` as keyof typeof servicesForm.errors]} />
                                                        </div>
                                                        <div className="col-12 col-md-6">
                                                            <SlField label={t('Description')} id={`service-description-${i}`} rows={1} value={service.description} onValue={(v) => update({ description: v })} error={servicesForm.errors[`services.${i}.description` as keyof typeof servicesForm.errors]} />
                                                        </div>
                                                        <div className="col-12 col-md-1 d-flex align-items-end">
                                                            <button
                                                                type="button"
                                                                className="sl-btn sl-btn-outline w-100"
                                                                title={t('Remove')}
                                                                aria-label={t('Remove service :n', { n: i + 1 })}
                                                                disabled={servicesForm.data.services.length === 1}
                                                                onClick={() => servicesForm.setData('services', servicesForm.data.services.filter((_, j) => j !== i))}
                                                            >
                                                                <i className="las la-times" />
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })}
                                    <button type="button" className="sl-btn sl-btn-outline mt-2" onClick={() => servicesForm.setData('services', [...servicesForm.data.services, { name: '', description: '' }])}>
                                        <i className="las la-plus me-1" /> {t('Add Another Service')}
                                    </button>
                                    {saveButton(servicesForm.processing, t('Save Services'))}
                                </form>
                            </div>
                        </div>
                    )}

                    {tab === 'preview' && (
                        <div className="sl-card">
                            <div className="sl-card-header"><i className="las la-eye me-1" /> {t('Profile Preview')}</div>
                            <div className="sl-card-body">
                                <div className="row">
                                    <div className="col-12 col-md-6">
                                        <h6 className="fw-600 mb-3" style={{ color: 'var(--sl-green)' }}>{t('Business Information')}</h6>
                                        <div className="mb-3">
                                            <strong>{t('Business Name:')}</strong> {basic.business_name}<br />
                                            <strong>{t('Email:')}</strong> {basic.business_email}<br />
                                            <strong>{t('Phone:')}</strong> {basic.business_phone}<br />
                                            {/^https?:\/\//i.test(basic.website) && (
                                                <><strong>{t('Website:')}</strong> <a href={basic.website} target="_blank" rel="noopener noreferrer">{basic.website}</a><br /></>
                                            )}
                                            {basic.business_registration_number && <><strong>{t('Registration:')}</strong> {basic.business_registration_number}<br /></>}
                                        </div>
                                        {basic.business_description && (
                                            <div className="mb-3">
                                                <strong>{t('Description:')}</strong>
                                                <p className="text-muted mt-1" style={{ whiteSpace: 'pre-line' }}>{basic.business_description}</p>
                                            </div>
                                        )}
                                    </div>
                                    <div className="col-12 col-md-6">
                                        <h6 className="fw-600 mb-3" style={{ color: 'var(--sl-green)' }}>{t('Location')}</h6>
                                        {location ? (
                                            <>
                                                <div className="mb-3">
                                                    <strong>{t('Address:')}</strong>
                                                    <br />
                                                    {location.address_line_1}
                                                    <br />
                                                    {location.address_line_2 && <>{location.address_line_2}<br /></>}
                                                    {location.city}, {location.state}
                                                    <br />
                                                    {location.country}{location.postal_code && ` - ${location.postal_code}`}
                                                </div>
                                                {location.phone && <div><strong>{t('Phone:')}</strong> {location.phone}</div>}
                                                {location.email && <div><strong>{t('Email:')}</strong> {location.email}</div>}
                                            </>
                                        ) : (
                                            <div className="text-muted">{t('No location information added yet.')}</div>
                                        )}
                                    </div>
                                </div>
                                {services.length > 0 && (
                                    <div className="row mt-4">
                                        <div className="col-12">
                                            <h6 className="fw-600 mb-3" style={{ color: 'var(--sl-green)' }}>{t('Services Offered')}</h6>
                                            <div className="d-flex flex-wrap gap-2">
                                                {services.map((s, i) => <span key={i} className="sl-type-badge" style={{ background: 'var(--sl-green-lt)', color: 'var(--sl-green)' }}>{s.name}</span>)}
                                            </div>
                                        </div>
                                    </div>
                                )}
                                <p className="text-muted mt-3 mb-0" style={{ fontSize: '.78rem' }}>{t('The preview shows what is saved. Save a section to update it.')}</p>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

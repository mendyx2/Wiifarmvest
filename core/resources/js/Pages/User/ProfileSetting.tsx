import { Head, useForm } from '@inertiajs/react';
import { useEffect, useState, type ChangeEvent, type FormEvent } from 'react';
import TextField from '@/Components/TextField';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type Profile = {
    firstname: string;
    lastname: string;
    username: string;
    fullname: string;
    email: string;
    mobile: string;
    bname: string;
    aname: string;
    ano: string;
    address: string;
    state: string;
    city: string;
    zip: string;
    country: string;
    image: string | null;
};

type BankHistory = { id: number; bname: string; aname: string; ano: string; date: string };

type Props = PageProps<{
    profile: Profile;
    bankHistories: BankHistory[];
    submitUrl: string;
}>;

export default function ProfileSetting({ profile, bankHistories, submitUrl }: Props) {
    const t = useTrans();
    const form = useForm({
        firstname: profile.firstname,
        lastname: profile.lastname,
        // Not editable, but ProfileController@submitProfile validates that it is present.
        email: profile.email,
        mobile: profile.mobile,
        bname: profile.bname,
        aname: profile.aname,
        ano: profile.ano,
        address: profile.address,
        state: profile.state,
        city: profile.city,
        zip: profile.zip,
        image: null as File | null,
    });

    // Local preview of a newly chosen photo, freed again when it changes.
    const [preview, setPreview] = useState<string | null>(null);
    useEffect(() => {
        if (!form.data.image) {
            setPreview(null);
            return;
        }
        const url = URL.createObjectURL(form.data.image);
        setPreview(url);
        return () => URL.revokeObjectURL(url);
    }, [form.data.image]);

    const avatar = preview ?? profile.image;
    const initials = `${profile.firstname.charAt(0)}${profile.lastname.charAt(0)}`.toUpperCase();

    function field(name: Exclude<keyof typeof form.data, 'image'>) {
        return {
            name,
            value: form.data[name],
            error: form.errors[name],
            onChange: (e: ChangeEvent<HTMLInputElement>) => form.setData(name, e.target.value),
        };
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        // A File in the data makes Inertia send multipart/form-data automatically.
        form.post(submitUrl, {
            preserveScroll: true,
            onSuccess: () => form.setData('image', null),
        });
    }

    return (
        <div className="nc-wrap" id="ncWrap">
            <Head title={t('Profile Setting')} />
            <br />
            <form onSubmit={submit} className="prof-page" noValidate>
                <div className="prof-header-card">
                    <div className="prof-photo-zone">
                        <label htmlFor="imageInput" className="prof-photo-label">
                            <div className="prof-photo-preview">
                                {avatar ? <img src={avatar} alt={t('Profile photo')} /> : <div className="prof-photo-initials">{initials}</div>}
                                <div className="prof-photo-overlay">
                                    <i className="las la-camera" />
                                    <span>{t('Change')}</span>
                                </div>
                            </div>
                        </label>
                        <input
                            type="file"
                            id="imageInput"
                            accept=".png,.jpg,.jpeg"
                            style={{ display: 'none' }}
                            onChange={(e) => form.setData('image', e.target.files?.[0] ?? null)}
                        />
                        <p className="prof-photo-hint">
                            <i className="las la-image" /> {t('JPG or PNG · 350×300px')}
                        </p>
                        {form.errors.image && <small style={{ color: '#DC2626' }}>{form.errors.image}</small>}
                    </div>

                    <div className="prof-header-info">
                        <h5 className="prof-header-name">{profile.fullname}</h5>
                        <p className="prof-header-username">@ {profile.username}</p>
                        <div className="prof-header-badges">
                            <span className="prof-badge green">
                                <i className="las la-check-circle" /> {t('Active')}
                            </span>
                            <span className="prof-badge slate">
                                <i className="las la-envelope" /> {profile.email}
                            </span>
                        </div>
                    </div>
                </div>

                <div className="prof-two-col">
                    <div className="prof-section-card">
                        <SectionHead icon="la-user" color="green" title={t('Personal Info')} sub={t('Your name and contact details')} />
                        <div className="prof-section-body">
                            <div className="prof-field-grid">
                                <TextField label={t('First Name')} {...field('firstname')} placeholder={t('First name')} required />
                                <TextField label={t('Last Name')} {...field('lastname')} placeholder={t('Last name')} required />
                                <TextField
                                    label={t('Email Address')}
                                    name="email"
                                    type="email"
                                    icon="la-lock-open"
                                    value={profile.email}
                                    readOnly
                                    className="prof-readonly"
                                    error={form.errors.email}
                                />
                                <TextField label={t('Mobile Number')} {...field('mobile')} icon="la-phone" placeholder={t('e.g. 08012345678')} required />
                            </div>
                        </div>
                    </div>

                    <div className="prof-section-card">
                        <SectionHead icon="la-university" color="violet" title={t('Bank Details')} sub={t('Where your withdrawals are sent')} />
                        <div className="prof-section-body">
                            <div className="prof-field-grid">
                                <TextField
                                    label={t('Bank Name')}
                                    {...field('bname')}
                                    icon="la-building"
                                    placeholder={t('e.g. First Bank')}
                                    wrapperStyle={{ gridColumn: '1 / -1' }}
                                    required
                                />
                                <TextField label={t('Account Name')} {...field('aname')} placeholder={t('As on bank card')} required />
                                <TextField
                                    label={t('Account Number')}
                                    {...field('ano')}
                                    inputMode="numeric"
                                    maxLength={10}
                                    placeholder={t('10-digit number')}
                                    required
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div className="prof-section-card">
                    <SectionHead icon="la-map-marker-alt" color="amber" title={t('Address')} sub={t('Your residential information')} />
                    <div className="prof-section-body">
                        <div className="prof-field-grid prof-field-grid--4">
                            <TextField
                                label={t('Street Address')}
                                {...field('address')}
                                placeholder={t('House / Street')}
                                wrapperStyle={{ gridColumn: 'span 2' }}
                            />
                            <TextField label={t('State')} {...field('state')} placeholder={t('State')} />
                            <TextField label={t('City')} {...field('city')} placeholder={t('City')} />
                            <TextField label={t('Zip Code')} {...field('zip')} placeholder={t('ZIP')} />
                            <TextField label={t('Country')} id="country" icon="la-globe" value={profile.country} disabled className="prof-readonly" />
                        </div>
                    </div>
                </div>

                <button type="submit" className="prof-save-btn" disabled={form.processing}>
                    <i className="las la-save" /> {form.processing ? t('Saving...') : t('Save Changes')}
                </button>
            </form>

            {bankHistories.length > 0 && (
                <div className="prof-section-card" style={{ marginTop: '1.5rem' }}>
                    <SectionHead
                        icon="la-history"
                        color="amber"
                        title={t('Bank Details History')}
                        sub={t('Previous bank accounts linked to your profile')}
                    />
                    <div className="prof-section-body">
                        <div className="table-responsive">
                            <table className="table table-bordered mb-0" style={{ fontSize: '0.875rem' }}>
                                <thead style={{ background: 'var(--prof-bg, #f8f9ff)' }}>
                                    <tr>
                                        <th>#</th>
                                        <th>{t('Bank Name')}</th>
                                        <th>{t('Account Name')}</th>
                                        <th>{t('Account Number')}</th>
                                        <th>{t('Changed On')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {bankHistories.map((history, i) => (
                                        <tr key={history.id}>
                                            <td>{i + 1}</td>
                                            <td>{history.bname}</td>
                                            <td>{history.aname}</td>
                                            <td>{history.ano}</td>
                                            <td>{history.date}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

function SectionHead({ icon, color, title, sub }: { icon: string; color: string; title: string; sub: string }) {
    return (
        <div className="prof-section-head">
            <div className={`prof-section-icon ${color}`}>
                <i className={`las ${icon}`} />
            </div>
            <div>
                <p className="prof-section-title">{title}</p>
                <p className="prof-section-sub">{sub}</p>
            </div>
        </div>
    );
}

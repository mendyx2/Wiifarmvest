import { useForm } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import ImageUploader from '@/Components/Admin/ImageUploader';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';

type Props = {
    admin: { name: string; username: string; email: string; image: string };
    imageSize: string;
    urls: { profile: string; password: string };
};

/**
 * The admin's own account: profile and password. They were two pages
 * (admin/profile.blade.php, admin/password.blade.php) with the same profile
 * card and one form each; /admin/password now opens this page.
 */
export default function AdminProfile({ admin, imageSize, urls }: Props) {
    const t = useTrans();
    const profile = useForm<{ name: string; email: string; image: File | null }>({ name: admin.name, email: admin.email, image: null });
    const password = useForm({ old_password: '', password: '', password_confirmation: '' });

    const saveProfile = (e: FormEvent) => {
        e.preventDefault();
        profile.post(urls.profile, { forceFormData: true, preserveScroll: true });
    };

    const savePassword = (e: FormEvent) => {
        e.preventDefault();
        password.post(urls.password, {
            preserveScroll: true,
            // Never keep typed passwords in the page, whatever the answer.
            onFinish: () => password.reset(),
        });
    };

    return (
        <>
            <PageHeader title={t('Profile')} />
            <div className="row mb-none-30">
                <div className="col-xl-3 col-lg-4 mb-30">
                    <div className="card b-radius--5 overflow-hidden">
                        <div className="card-body p-0">
                            <div className="d-flex bg--primary align-items-center p-3">
                                <div className="avatar avatar--lg">
                                    <img src={admin.image} alt="" />
                                </div>
                                <div className="ps-3">
                                    <h4 className="text--white">{admin.name}</h4>
                                </div>
                            </div>
                            <ul className="list-group admin-profile-list">
                                <Info label={t('Name')}>{admin.name}</Info>
                                <Info label={t('Username')}>{admin.username}</Info>
                                <Info label={t('Email')}>{admin.email}</Info>
                            </ul>
                        </div>
                    </div>
                </div>

                <div className="col-xl-9 col-lg-8">
                    <div className="card mb-30">
                        <div className="card-body">
                            <h5 className="card-title border-bottom mb-4 pb-2">{t('Profile Information')}</h5>
                            <form onSubmit={saveProfile} noValidate>
                                <div className="row">
                                    <div className="col-xxl-4 col-lg-6">
                                        <div className="form-group">
                                            <label>{t('Image')}</label>
                                            <ImageUploader className="w-100" image={admin.image} size={imageSize} onChange={(file) => profile.setData('image', file)} />
                                            <FieldError message={profile.errors.image} />
                                        </div>
                                    </div>
                                    <div className="col-xxl-8 col-lg-6">
                                        <div className="form-group">
                                            <label htmlFor="admin-name">{t('Name')}</label>
                                            <input id="admin-name" className="form-control" type="text" value={profile.data.name} onChange={(e) => profile.setData('name', e.target.value)} required />
                                            <FieldError message={profile.errors.name} />
                                        </div>
                                        <div className="form-group">
                                            <label htmlFor="admin-email">{t('Email')}</label>
                                            <input id="admin-email" className="form-control" type="email" value={profile.data.email} onChange={(e) => profile.setData('email', e.target.value)} required />
                                            <FieldError message={profile.errors.email} />
                                        </div>
                                    </div>
                                </div>
                                <button className="btn btn--primary h-45 w-100" type="submit" disabled={profile.processing}>
                                    {t('Submit')}
                                </button>
                            </form>
                        </div>
                    </div>

                    <div className="card mb-30" id="password">
                        <div className="card-body">
                            <h5 className="card-title border-bottom mb-4 pb-2">{t('Change Password')}</h5>
                            <form onSubmit={savePassword} noValidate>
                                <div className="form-group">
                                    <label htmlFor="old-password">{t('Current Password')}</label>
                                    <input
                                        id="old-password"
                                        className="form-control"
                                        type="password"
                                        autoComplete="current-password"
                                        value={password.data.old_password}
                                        onChange={(e) => password.setData('old_password', e.target.value)}
                                        required
                                    />
                                    <FieldError message={password.errors.old_password} />
                                </div>
                                <div className="form-group">
                                    <label htmlFor="new-password">{t('New Password')}</label>
                                    <input
                                        id="new-password"
                                        className="form-control"
                                        type="password"
                                        autoComplete="new-password"
                                        value={password.data.password}
                                        onChange={(e) => password.setData('password', e.target.value)}
                                        required
                                    />
                                    <small className="text-muted">{t('At least 8 characters.')}</small>
                                    <FieldError message={password.errors.password} />
                                </div>
                                <div className="form-group">
                                    <label htmlFor="confirm-password">{t('Confirm Password')}</label>
                                    <input
                                        id="confirm-password"
                                        className="form-control"
                                        type="password"
                                        autoComplete="new-password"
                                        value={password.data.password_confirmation}
                                        onChange={(e) => password.setData('password_confirmation', e.target.value)}
                                        required
                                    />
                                </div>
                                <button type="submit" className="btn btn--primary w-100 btn-lg h-45" disabled={password.processing}>
                                    {t('Submit')}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

function Info({ label, children }: { label: string; children: ReactNode }) {
    return (
        <li className="list-group-item d-flex justify-content-between align-items-center">
            {label}
            <span className="fw-bold">{children}</span>
        </li>
    );
}

function FieldError({ message }: { message?: string }) {
    return message ? <small className="text--danger d-block mt-1">{message}</small> : null;
}

import { Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import DataTable from '@/Components/Admin/DataTable';
import { ConfirmButton, DialogHeader, FieldError, withId } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';

type Admin = {
    id: number;
    name: string;
    username: string;
    email: string;
    active: boolean;
    roles: number[];
    roleNames: string[];
    permissions: string[];
    me: boolean;
    owner: boolean;
};

type Props = {
    admins: Admin[];
    roles: { id: number; name: string }[];
    urls: { store: string; update: string; destroy: string; status: string; profile: string; roles: string };
};

/** Staff → Admins: who can sign in to the admin panel, with their roles. */
export default function Admins({ admins, roles, urls }: Props) {
    const t = useTrans();
    // null: closed; 0: adding; otherwise the admin being edited
    const [editing, setEditing] = useState<Admin | 0 | null>(null);
    const [viewing, setViewing] = useState<Admin | null>(null);

    return (
        <>
            <PageHeader title={t('Manage Admins')}>
                <button type="button" className="btn btn-sm btn-outline--primary" onClick={() => setEditing(0)}>
                    <i className="las la-plus" /> {t('Add New')}
                </button>
            </PageHeader>
            <DataTable
                rows={admins}
                rowKey={(a) => a.id}
                empty={t('Data not found')}
                columns={[
                    {
                        label: t('Admin'),
                        cell: (a) => (
                            <>
                                <span className="fw-bold d-block">{a.name}</span>
                                <small className="text-muted">@{a.username}</small>
                            </>
                        ),
                    },
                    { label: t('Email'), cell: (a) => a.email || <span className="text-muted">—</span> },
                    {
                        label: t('Roles'),
                        cell: (a) => (
                            <div className="d-flex flex-wrap gap-1 justify-content-end justify-content-lg-start">
                                {a.roleNames.map((r) => (
                                    <span key={r} className="badge badge--primary">
                                        {r}
                                    </span>
                                ))}
                            </div>
                        ),
                    },
                    {
                        label: t('Status'),
                        cell: (a) => (
                            <>
                                {a.active ? <span className="badge badge--success">{t('Active')}</span> : <span className="badge badge--danger">{t('Inactive')}</span>}
                                {a.me && <span className="badge badge--dark ms-1">{t('You')}</span>}
                                {a.owner && <span className="badge badge--warning ms-1">{t('Owner')}</span>}
                            </>
                        ),
                    },
                    {
                        label: t('Action'),
                        cell: (a) => (
                            <div className="button--group">
                                <button type="button" className="btn btn-sm btn-outline--info" onClick={() => setViewing(a)}>
                                    <i className="las la-key" /> {t('Access')}
                                </button>
                                {a.me ? (
                                    <Link href={urls.profile} className="btn btn-sm btn-outline--primary">
                                        <i className="las la-user" /> {t('Profile')}
                                    </Link>
                                ) : (
                                    !a.owner && (
                                        <>
                                            <button type="button" className="btn btn-sm btn-outline--primary" onClick={() => setEditing(a)}>
                                                <i className="la la-pen" /> {t('Edit')}
                                            </button>
                                            {a.active ? (
                                                <ConfirmButton
                                                    url={withId(urls.status, a.id)}
                                                    data={{ state: 'disable' }}
                                                    question={t('Deactivate :name? They are signed out on their next page.', { name: a.name })}
                                                    className="btn btn-sm btn-outline--warning"
                                                >
                                                    <i className="las la-ban" /> {t('Deactivate')}
                                                </ConfirmButton>
                                            ) : (
                                                <ConfirmButton url={withId(urls.status, a.id)} data={{ state: 'enable' }} question={t('Activate :name?', { name: a.name })} className="btn btn-sm btn-outline--success">
                                                    <i className="las la-check" /> {t('Activate')}
                                                </ConfirmButton>
                                            )}
                                            <ConfirmButton url={withId(urls.destroy, a.id)} method="delete" question={t('Permanently delete :name?', { name: a.name })} className="btn btn-sm btn-outline--danger">
                                                <i className="la la-trash" /> {t('Delete')}
                                            </ConfirmButton>
                                        </>
                                    )
                                )}
                            </div>
                        ),
                    },
                ]}
            />
            {editing !== null && <AdminDialog admin={editing || null} roles={roles} urls={urls} onClose={() => setEditing(null)} />}
            {viewing && (
                <Modal open onClose={() => setViewing(null)} labelledBy="access-title">
                    <DialogHeader id="access-title" title={t(':name: access', { name: viewing.name })} onClose={() => setViewing(null)} />
                    <div className="modal-body">
                        <p className="mb-2">
                            {t('Roles')}: <b>{viewing.roleNames.join(', ') || '—'}</b>
                        </p>
                        {viewing.roleNames.includes('super-admin') ? (
                            <p className="text--primary mb-0">{t('super-admin opens every page.')}</p>
                        ) : viewing.permissions.length ? (
                            <div className="d-flex flex-wrap gap-1">
                                {viewing.permissions.map((p) => (
                                    <span key={p} className="badge badge--info">
                                        {p}
                                    </span>
                                ))}
                            </div>
                        ) : (
                            <p className="text-muted mb-0">{t('No permissions.')}</p>
                        )}
                        <p className="mt-3 mb-0">
                            <Link href={urls.roles}>{t('Change what roles allow')}</Link>
                        </p>
                    </div>
                </Modal>
            )}
        </>
    );
}

type Form = { name: string; username: string; email: string; password: string; password_confirmation: string; roles: number[] };

function AdminDialog({ admin, roles, urls, onClose }: { admin: Admin | null; roles: Props['roles']; urls: Props['urls']; onClose: () => void }) {
    const t = useTrans();
    const form = useForm<Form>({ name: admin?.name ?? '', username: admin?.username ?? '', email: admin?.email ?? '', password: '', password_confirmation: '', roles: admin?.roles ?? [] });
    const errors = form.errors as Record<string, string | undefined>;
    const submit = (e: FormEvent) => {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess: onClose };
        if (admin) form.put(withId(urls.update, admin.id), options);
        else form.post(urls.store, options);
    };
    const toggleRole = (id: number) => form.setData('roles', form.data.roles.includes(id) ? form.data.roles.filter((r) => r !== id) : [...form.data.roles, id]);

    return (
        <Modal open onClose={onClose} labelledBy="admin-title">
            <DialogHeader id="admin-title" title={admin ? t('Edit Admin') : t('Add Admin')} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    <div className="row">
                        <div className="col-sm-6 form-group">
                            <label htmlFor="adm-name">{t('Name')}</label>
                            <input id="adm-name" className="form-control" required maxLength={255} value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                            <FieldError message={form.errors.name} />
                        </div>
                        <div className="col-sm-6 form-group">
                            <label htmlFor="adm-username">{t('Username')}</label>
                            <input id="adm-username" className="form-control" required minLength={3} maxLength={40} pattern="[A-Za-z0-9_.\-]+" autoComplete="off" value={form.data.username} onChange={(e) => form.setData('username', e.target.value)} />
                            <FieldError message={form.errors.username} />
                        </div>
                    </div>
                    <div className="form-group">
                        <label htmlFor="adm-email">
                            {t('Email')} <small className="text-muted">({t('optional')})</small>
                        </label>
                        <input id="adm-email" type="email" className="form-control" maxLength={255} value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} />
                        <FieldError message={form.errors.email} />
                    </div>
                    <div className="row">
                        <div className="col-sm-6 form-group">
                            <label htmlFor="adm-password">{t('Password')}</label>
                            <input id="adm-password" type="password" className="form-control" required={!admin} minLength={8} autoComplete="new-password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} />
                            {admin && <small className="text-muted">{t('Leave empty to keep the current password.')}</small>}
                            <FieldError message={form.errors.password} />
                        </div>
                        <div className="col-sm-6 form-group">
                            <label htmlFor="adm-password2">{t('Confirm Password')}</label>
                            <input
                                id="adm-password2"
                                type="password"
                                className="form-control"
                                required={!admin || !!form.data.password}
                                autoComplete="new-password"
                                value={form.data.password_confirmation}
                                onChange={(e) => form.setData('password_confirmation', e.target.value)}
                            />
                        </div>
                    </div>
                    <fieldset className="form-group">
                        <legend className="fs-6 fw-normal mb-1">{t('Roles')}</legend>
                        <div className="d-flex flex-wrap gap-3">
                            {roles.map((r) => (
                                <div className="form-check" key={r.id}>
                                    <input id={`adm-role-${r.id}`} type="checkbox" className="form-check-input" checked={form.data.roles.includes(r.id)} onChange={() => toggleRole(r.id)} />
                                    <label className="form-check-label" htmlFor={`adm-role-${r.id}`}>
                                        {r.name}
                                    </label>
                                </div>
                            ))}
                        </div>
                        <FieldError message={errors.roles ?? Object.entries(errors).find(([k]) => k.startsWith('roles.'))?.[1]} />
                    </fieldset>
                </div>
                <div className="modal-footer">
                    <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing || form.data.roles.length === 0}>
                        {t('Submit')}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

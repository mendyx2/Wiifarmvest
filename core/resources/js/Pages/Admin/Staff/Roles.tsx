import { Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import DataTable from '@/Components/Admin/DataTable';
import { ConfirmButton, DialogHeader, FieldError, withId } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import PermissionOpens, { type Opens } from '@/Components/Admin/PermissionOpens';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';

type Role = { id: number; name: string; permissions: number[]; admins: number; super: boolean; system: boolean };
export type PermissionGroup = { name: string; permissions: { id: number; name: string; opens: Opens[] }[] };

type Props = {
    roles: Role[];
    groups: PermissionGroup[];
    urls: { store: string; update: string; destroy: string; permissions: string };
};

/** Staff → Roles: named sets of permissions given to admins. */
export default function Roles({ roles, groups, urls }: Props) {
    const t = useTrans();
    const [editing, setEditing] = useState<Role | 0 | null>(null);

    return (
        <>
            <PageHeader title={t('Admin Roles')}>
                <Link href={urls.permissions} className="btn btn-sm btn-outline--info">
                    <i className="las la-key" /> {t('Permissions')}
                </Link>
                <button type="button" className="btn btn-sm btn-outline--primary" onClick={() => setEditing(0)}>
                    <i className="las la-plus" /> {t('Add New')}
                </button>
            </PageHeader>
            <DataTable
                rows={roles}
                rowKey={(r) => r.id}
                empty={t('Data not found')}
                columns={[
                    { label: t('Role'), cell: (r) => <span className="badge badge--primary">{r.name}</span> },
                    { label: t('Permissions'), cell: (r) => (r.super ? t('All pages') : r.permissions.length) },
                    { label: t('Admins'), cell: (r) => r.admins },
                    {
                        label: t('Action'),
                        cell: (r) =>
                            r.super ? (
                                <small className="text-muted">{t('Built in: opens every page')}</small>
                            ) : (
                                <div className="button--group">
                                    <button type="button" className="btn btn-sm btn-outline--primary" onClick={() => setEditing(r)}>
                                        <i className="la la-pen" /> {t('Edit')}
                                    </button>
                                    {!r.system && r.admins === 0 && (
                                        <ConfirmButton url={withId(urls.destroy, r.id)} method="delete" question={t('Delete the role :name?', { name: r.name })} className="btn btn-sm btn-outline--danger">
                                            <i className="la la-trash" /> {t('Delete')}
                                        </ConfirmButton>
                                    )}
                                </div>
                            ),
                    },
                ]}
            />
            {editing !== null && <RoleDialog role={editing || null} groups={groups} urls={urls} onClose={() => setEditing(null)} />}
        </>
    );
}

function RoleDialog({ role, groups, urls, onClose }: { role: Role | null; groups: PermissionGroup[]; urls: Props['urls']; onClose: () => void }) {
    const t = useTrans();
    const form = useForm<{ name: string; permissions: number[] }>({ name: role?.name ?? '', permissions: role?.permissions ?? [] });
    const [useful, setUseful] = useState(false);
    const chosen = new Set(form.data.permissions);
    const set = (ids: number[], on: boolean) => {
        const next = new Set(chosen);
        ids.forEach((id) => (on ? next.add(id) : next.delete(id)));
        form.setData('permissions', [...next]);
    };
    const submit = (e: FormEvent) => {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess: onClose };
        if (role) form.put(withId(urls.update, role.id), options);
        else form.post(urls.store, options);
    };
    const shown = groups.map((g) => ({ ...g, permissions: useful ? g.permissions.filter((p) => p.opens.length || chosen.has(p.id)) : g.permissions })).filter((g) => g.permissions.length);

    return (
        <Modal open onClose={onClose} labelledBy="role-title" size="lg">
            <DialogHeader id="role-title" title={role ? t('Edit Role') : t('Add Role')} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    <div className="form-group">
                        <label htmlFor="role-name">{t('Name')}</label>
                        <input id="role-name" className="form-control" required maxLength={60} value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                        <FieldError message={form.errors.name} />
                    </div>
                    <div className="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <span>
                            {t('Permissions')} <span className="badge badge--dark">{chosen.size}</span>
                        </span>
                        <div className="form-check form-switch mb-0">
                            <input id="role-useful" type="checkbox" role="switch" className="form-check-input" checked={useful} onChange={(e) => setUseful(e.target.checked)} />
                            <label className="form-check-label" htmlFor="role-useful">
                                {t('Only permissions that open pages')}
                            </label>
                        </div>
                    </div>
                    <div style={{ maxHeight: '55vh', overflowY: 'auto' }}>
                        {shown.map((g) => {
                            const ids = g.permissions.map((p) => p.id);
                            const all = ids.every((id) => chosen.has(id));
                            return (
                                <fieldset key={g.name} className="border rounded p-2 mb-2">
                                    <legend className="float-none w-auto fs-6 fw-bold px-1 mb-0 d-flex align-items-center gap-2">
                                        {g.name}
                                        <button type="button" className="btn btn-link btn-sm p-0" onClick={() => set(ids, !all)}>
                                            {all ? t('none') : t('all')}
                                        </button>
                                    </legend>
                                    {g.permissions.map((p) => (
                                        <div className="form-check d-flex flex-wrap align-items-center gap-2" key={p.id}>
                                            <input id={`perm-${p.id}`} type="checkbox" className="form-check-input" checked={chosen.has(p.id)} onChange={(e) => set([p.id], e.target.checked)} />
                                            <label className="form-check-label" htmlFor={`perm-${p.id}`}>
                                                {p.name}
                                            </label>
                                            <PermissionOpens opens={p.opens} />
                                        </div>
                                    ))}
                                </fieldset>
                            );
                        })}
                    </div>
                </div>
                <div className="modal-footer">
                    <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing}>
                        {t('Submit')}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

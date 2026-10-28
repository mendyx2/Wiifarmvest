import { Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { ConfirmButton, DialogHeader, FieldError, withId } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import PermissionOpens, { type Opens } from '@/Components/Admin/PermissionOpens';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';

type Permission = { id: number; name: string; opens: Opens[]; roles: number; locked: boolean };
type Group = { name: string; permissions: Permission[] };

type Props = {
    groups: Group[];
    urls: { store: string; update: string; destroy: string; roles: string };
};

/**
 * Staff → Permissions. A permission only opens pages when the access map
 * (config/admin_access.php) names it; each row says what it opens.
 */
export default function Permissions({ groups, urls }: Props) {
    const t = useTrans();
    const [adding, setAdding] = useState(false);
    const [editing, setEditing] = useState<(Permission & { group: string }) | null>(null);
    const [query, setQuery] = useState('');
    const q = query.trim().toLowerCase();
    const shown = groups.map((g) => ({ ...g, permissions: g.permissions.filter((p) => !q || p.name.toLowerCase().includes(q) || g.name.toLowerCase().includes(q)) })).filter((g) => g.permissions.length);
    const total = groups.reduce((n, g) => n + g.permissions.length, 0);
    const working = groups.reduce((n, g) => n + g.permissions.filter((p) => p.opens.length).length, 0);

    return (
        <>
            <PageHeader title={t('Admin Permissions')}>
                <Link href={urls.roles} className="btn btn-sm btn-outline--info">
                    <i className="las la-user-tag" /> {t('Roles')}
                </Link>
                <button type="button" className="btn btn-sm btn-outline--primary" onClick={() => setAdding(true)}>
                    <i className="las la-plus" /> {t('Add New')}
                </button>
            </PageHeader>
            <div className="card bl--5 border--primary mb-4">
                <div className="card-body">
                    <p className="text--primary mb-0">
                        {t(
                            ':working of :total permissions open pages. Which page needs which permission is set in the code (config/admin_access.php); a new permission opens nothing until the code names it. Permissions the code uses cannot be renamed or deleted here.',
                            { working, total },
                        )}
                    </p>
                </div>
            </div>
            <div className="mb-3">
                <input type="search" className="form-control" placeholder={t('Search') + '...'} aria-label={t('Search permissions')} value={query} onChange={(e) => setQuery(e.target.value)} />
            </div>
            {shown.map((g) => (
                <div className="card mb-3" key={g.name}>
                    <div className="card-header">
                        <h6 className="mb-0">
                            {g.name} <span className="badge badge--dark">{g.permissions.length}</span>
                        </h6>
                    </div>
                    <ul className="list-group list-group-flush">
                        {g.permissions.map((p) => (
                            <li key={p.id} className="list-group-item d-flex flex-wrap align-items-center gap-2">
                                <span className="fw-bold">{p.name}</span>
                                <PermissionOpens opens={p.opens} />
                                <small className="text-muted ms-auto me-3">{p.roles === 1 ? t('in 1 role') : t('in :n roles', { n: p.roles })}</small>
                                <div className="button--group">
                                    <button type="button" className="btn btn-sm btn-outline--primary" onClick={() => setEditing({ ...p, group: g.name })}>
                                        <i className="la la-pen" /> {t('Edit')}
                                    </button>
                                    {!p.locked && p.roles === 0 && (
                                        <ConfirmButton url={withId(urls.destroy, p.id)} method="delete" question={t('Delete the permission :name?', { name: p.name })} className="btn btn-sm btn-outline--danger">
                                            <i className="la la-trash" /> {t('Delete')}
                                        </ConfirmButton>
                                    )}
                                </div>
                            </li>
                        ))}
                    </ul>
                </div>
            ))}
            {shown.length === 0 && <p className="text-muted text-center py-4">{t('No search result found.')}</p>}
            <datalist id="perm-groups">
                {groups.map((g) => (
                    <option key={g.name} value={g.name} />
                ))}
            </datalist>
            {adding && <AddDialog url={urls.store} onClose={() => setAdding(false)} />}
            {editing && <EditDialog permission={editing} url={withId(urls.update, editing.id)} onClose={() => setEditing(null)} />}
        </>
    );
}

/** Add one or several permissions (the "bulk create" page was a separate form). */
function AddDialog({ url, onClose }: { url: string; onClose: () => void }) {
    const t = useTrans();
    const form = useForm<{ permissions: { name: string; group: string }[] }>({ permissions: [{ name: '', group: '' }] });
    const errors = form.errors as Record<string, string | undefined>;
    const rows = form.data.permissions;
    const setRow = (i: number, key: 'name' | 'group', value: string) =>
        form.setData(
            'permissions',
            rows.map((r, j) => (j === i ? { ...r, [key]: value } : r)),
        );
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, onSuccess: onClose });
    };
    return (
        <Modal open onClose={onClose} labelledBy="perm-add-title" size="lg">
            <DialogHeader id="perm-add-title" title={t('Add Permissions')} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    {rows.map((r, i) => (
                        <div className="row g-2 align-items-start mb-2" key={i}>
                            <div className="col-sm-6">
                                <input
                                    className="form-control"
                                    required
                                    maxLength={100}
                                    placeholder={t('Name, e.g. manage tickets')}
                                    aria-label={t('Name of permission :n', { n: i + 1 })}
                                    value={r.name}
                                    onChange={(e) => setRow(i, 'name', e.target.value)}
                                />
                                <FieldError message={errors[`permissions.${i}.name`]} />
                            </div>
                            <div className="col-sm-5 col-10">
                                <input
                                    className="form-control"
                                    required
                                    maxLength={60}
                                    list="perm-groups"
                                    placeholder={t('Group')}
                                    aria-label={t('Group of permission :n', { n: i + 1 })}
                                    value={r.group}
                                    onChange={(e) => setRow(i, 'group', e.target.value)}
                                />
                                <FieldError message={errors[`permissions.${i}.group`]} />
                            </div>
                            <div className="col-sm-1 col-2">
                                <button
                                    type="button"
                                    className="btn btn--danger w-100"
                                    aria-label={t('Remove row :n', { n: i + 1 })}
                                    disabled={rows.length === 1}
                                    onClick={() =>
                                        form.setData(
                                            'permissions',
                                            rows.filter((_, j) => j !== i),
                                        )
                                    }
                                >
                                    <i className="las la-times me-0" />
                                </button>
                            </div>
                        </div>
                    ))}
                    <button type="button" className="btn btn-sm btn-outline--primary" onClick={() => form.setData('permissions', [...rows, { name: '', group: rows.at(-1)?.group ?? '' }])}>
                        <i className="las la-plus" /> {t('Add another')}
                    </button>
                    <FieldError message={errors.permissions} />
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

function EditDialog({ permission, url, onClose }: { permission: Permission & { group: string }; url: string; onClose: () => void }) {
    const t = useTrans();
    const form = useForm({ name: permission.name, group: permission.group });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.put(url, { preserveScroll: true, onSuccess: onClose });
    };
    return (
        <Modal open onClose={onClose} labelledBy="perm-edit-title">
            <DialogHeader id="perm-edit-title" title={t('Edit Permission')} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    <div className="form-group">
                        <label htmlFor="perm-name">{t('Name')}</label>
                        <input id="perm-name" className="form-control" required maxLength={100} readOnly={permission.locked} value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                        {permission.locked && <small className="text-muted">{t('The code uses this name, so it cannot be changed.')}</small>}
                        <FieldError message={form.errors.name} />
                    </div>
                    <div className="form-group">
                        <label htmlFor="perm-group">{t('Group')}</label>
                        <input id="perm-group" className="form-control" required maxLength={60} list="perm-groups" value={form.data.group} onChange={(e) => form.setData('group', e.target.value)} />
                        <FieldError message={form.errors.group} />
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

import { useForm, usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { UserCell, WhenCell, type AdminUser, type When } from '@/Components/Admin/Cells';
import DataTable from '@/Components/Admin/DataTable';
import { DialogHeader, FieldError } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import SearchForm from '@/Components/Admin/SearchForm';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';
import type { Paginated } from '@/types';

type Row = {
    id: number;
    details: string;
    creator: AdminUser;
    usedBy: AdminUser;
    amount: string;
    pin: string;
    used: boolean;
    usedAgo: string | null;
    when: When;
};

type Props = {
    title: string;
    pins: Paginated<Row>;
    filters: { search: string; status: string; source: string };
    /** A member's own pin page: fixed to that member, no filters */
    member: boolean;
    urls: { store: string | null };
    currency: string;
};

/**
 * E-pins: All, Used, Unused, Admin and User pins (one page, preset by the
 * sidebar link), and a member's created / used pins. Owners can create a
 * batch of admin pins.
 */
export default function PinsIndex({ title, pins, filters, member, urls, currency }: Props) {
    const t = useTrans();
    const url = usePage().url;
    const [creating, setCreating] = useState(false);
    const keep = { status: filters.status, source: filters.source };
    const pick = (key: 'status' | 'source', value: string) => visitWithQuery(url, { ...keep, search: filters.search, [key]: value });

    return (
        <>
            <PageHeader title={t(title)}>
                <SearchForm url={url} value={filters.search} placeholder="Pin" extra={keep} />
                {urls.store && (
                    <button type="button" className="btn btn-outline--primary" onClick={() => setCreating(true)}>
                        <i className="las la-plus" /> {t('Create Pins')}
                    </button>
                )}
            </PageHeader>

            {!member && (
                <div className="d-flex flex-wrap gap-3 mb-3" role="group" aria-label={t('Filters')}>
                    <select className="form-control w-auto" aria-label={t('Status')} value={filters.status} onChange={(e) => pick('status', e.target.value)}>
                        <option value="">{t('Used and unused')}</option>
                        <option value="unused">{t('Unused')}</option>
                        <option value="used">{t('Used')}</option>
                    </select>
                    <select className="form-control w-auto" aria-label={t('Created by')} value={filters.source} onChange={(e) => pick('source', e.target.value)}>
                        <option value="">{t('Created by anyone')}</option>
                        <option value="admin">{t('Created by admin')}</option>
                        <option value="user">{t('Created by members')}</option>
                    </select>
                </div>
            )}

            <DataTable
                rows={pins.data}
                rowKey={(r) => r.id}
                links={pins.links}
                empty={t('No data found')}
                columns={[
                    {
                        label: t('User|Admin'),
                        cell: (r) => (
                            <>
                                {r.details}
                                <br />
                                {r.creator ? <UserCell user={r.creator} /> : <span className="small">{t('admin')}</span>}
                            </>
                        ),
                    },
                    { label: t('Used By'), cell: (r) => (r.usedBy ? <UserCell user={r.usedBy} /> : <span className="text-muted">—</span>) },
                    { label: t('Amount'), cell: (r) => r.amount },
                    { label: t('Pin'), cell: (r) => <code className="text-break">{r.pin}</code> },
                    {
                        label: t('Status'),
                        cell: (r) =>
                            r.used ? (
                                <>
                                    <span className="badge badge--success">{t('Used')}</span>
                                    <br />
                                    <small>{r.usedAgo}</small>
                                </>
                            ) : (
                                <span className="badge badge--danger">{t('Unused')}</span>
                            ),
                    },
                    { label: t('Created'), cell: (r) => <WhenCell when={r.when} /> },
                ]}
            />

            {urls.store && <CreatePins open={creating} url={urls.store} currency={currency} onClose={() => setCreating(false)} />}
        </>
    );
}

function CreatePins({ open, url, currency, onClose }: { open: boolean; url: string; currency: string; onClose: () => void }) {
    const t = useTrans();
    const form = useForm({ amount: '', number: '1' });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, onSuccess: () => (form.reset(), onClose()) });
    };

    return (
        <Modal open={open} onClose={onClose} labelledBy="create-pins-title">
            <DialogHeader id="create-pins-title" title={t('Create Pins')} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    <div className="form-group">
                        <label htmlFor="pin-amount">{t('Amount')}</label>
                        <div className="input-group">
                            <input id="pin-amount" type="number" step="any" min="0" className="form-control" required value={form.data.amount} onChange={(e) => form.setData('amount', e.target.value)} />
                            <span className="input-group-text">{currency}</span>
                        </div>
                        <FieldError message={form.errors.amount} />
                    </div>
                    <div className="form-group">
                        <label htmlFor="pin-number">{t('Total Number of Pin')}</label>
                        <input id="pin-number" type="number" min="1" max="500" className="form-control" required value={form.data.number} onChange={(e) => form.setData('number', e.target.value)} />
                        <small className="text-muted">{t('Up to 500 at a time.')}</small>
                        <FieldError message={form.errors.number} />
                    </div>
                </div>
                <div className="modal-footer">
                    <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing}>
                        {t('Create')}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

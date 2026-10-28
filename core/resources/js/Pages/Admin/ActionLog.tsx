import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import DataTable from '@/Components/Admin/DataTable';
import DateRangeField, { dateQuery, type DateRange } from '@/Components/Admin/DateRangeField';
import FilterCard from '@/Components/Admin/FilterCard';
import PageHeader from '@/Components/Admin/PageHeader';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';
import type { Paginated } from '@/types';

type Row = {
    id: number;
    date: string;
    admin: string;
    role: string;
    method: string;
    action: string;
    resource: string;
    summary: string;
    description: string;
    uri: string;
    subject: string | null;
    meta: string | null;
    ip: string;
    userAgent: string;
};

type Filters = { search: string; admin: string; method: string; date: DateRange };

type Props = { logs: Paginated<Row>; admins: { id: number; label: string }[]; filters: Filters };

/** System Setting → Admin Action Log (super-admins): who changed what, and when. */
export default function ActionLog({ logs, admins, filters }: Props) {
    const t = useTrans();
    const url = usePage().url;
    const [form, setForm] = useState(filters);
    const [detail, setDetail] = useState<Row | null>(null);

    return (
        <>
            <PageHeader title={t('Admin Action Log')} />
            <FilterCard onSubmit={() => visitWithQuery(url, { search: form.search.trim(), admin: form.admin, method: form.method, date: dateQuery(form.date) })}>
                <div className="flex-grow-1">
                    <label htmlFor="log-search">{t('Page / description')}</label>
                    <input id="log-search" className="form-control" type="search" value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder={t('e.g. users.update')} />
                </div>
                <div className="flex-grow-1">
                    <label htmlFor="log-admin">{t('Admin')}</label>
                    <select id="log-admin" className="form-control" value={form.admin} onChange={(e) => setForm({ ...form, admin: e.target.value })}>
                        <option value="">{t('All')}</option>
                        {admins.map((a) => (
                            <option key={a.id} value={a.id}>
                                {a.label}
                            </option>
                        ))}
                    </select>
                </div>
                <div className="flex-grow-1">
                    <label htmlFor="log-method">{t('Method')}</label>
                    <select id="log-method" className="form-control" value={form.method} onChange={(e) => setForm({ ...form, method: e.target.value })}>
                        <option value="">{t('All')}</option>
                        {['POST', 'PUT', 'PATCH', 'DELETE'].map((m) => (
                            <option key={m} value={m}>
                                {m}
                            </option>
                        ))}
                    </select>
                </div>
                <div className="flex-grow-1">
                    <DateRangeField value={form.date} onChange={(date) => setForm({ ...form, date })} />
                </div>
            </FilterCard>

            <DataTable
                responsive="md"
                rows={logs.data}
                rowKey={(r) => r.id}
                links={logs.links}
                empty={t('No admin action logs found')}
                columns={[
                    { label: t('Date'), cell: (r) => r.date },
                    { label: t('Admin'), cell: (r) => r.admin },
                    { label: t('Action'), cell: (r) => r.action },
                    { label: t('Resource'), cell: (r) => r.resource },
                    { label: t('Details'), cell: (r) => r.summary },
                    { label: t('IP'), cell: (r) => r.ip },
                    {
                        label: t('More'),
                        cell: (r) => (
                            <button type="button" className="btn btn-sm btn-outline--primary" onClick={() => setDetail(r)}>
                                <i className="las la-desktop" /> {t('Detail')}
                            </button>
                        ),
                    },
                ]}
            />

            <Modal open={detail !== null} onClose={() => setDetail(null)} labelledBy="action-detail-title" size="lg">
                <div className="modal-header">
                    <h5 className="modal-title" id="action-detail-title">
                        {t('Action Details')}
                    </h5>
                    <button type="button" className="close" aria-label={t('Close')} onClick={() => setDetail(null)}>
                        <i className="las la-times" />
                    </button>
                </div>
                {detail && (
                    <div className="modal-body">
                        <ul className="list-group list-group-flush admin-action-detail">
                            <Item label={t('Date')}>{detail.date}</Item>
                            <Item label={t('Admin')}>
                                {detail.admin}
                                {detail.role && <small className="text-muted"> ({detail.role})</small>}
                            </Item>
                            <Item label={t('Request')}>
                                <code>
                                    {detail.method} {detail.uri}
                                </code>
                            </Item>
                            <Item label={t('Route')}>{detail.resource}</Item>
                            {detail.subject && <Item label={t('Subject')}>{detail.subject}</Item>}
                            <Item label={t('Details')}>{detail.description || '—'}</Item>
                            <Item label={t('IP')}>{detail.ip}</Item>
                            <Item label={t('Browser')}>{detail.userAgent || '—'}</Item>
                        </ul>
                        {detail.meta && (
                            <>
                                <h6 className="mt-3">{t('Data sent')}</h6>
                                <pre className="admin-action-meta">{detail.meta}</pre>
                            </>
                        )}
                    </div>
                )}
            </Modal>
        </>
    );
}

function Item({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <li className="list-group-item d-flex justify-content-between gap-3 px-0">
            <span className="text-muted">{label}</span>
            <span className="text-end">{children}</span>
        </li>
    );
}

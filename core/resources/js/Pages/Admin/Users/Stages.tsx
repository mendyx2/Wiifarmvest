import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';
import DataTable from '@/Components/Admin/DataTable';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';
import type { Paginated } from '@/types';
import './Stages.css';

type Row = { id: number; number: number; fullname: string; username: string; mobile: string; email: string; joined: string; url: string };

type Props = {
    stages: { id: number; name: string; count: number }[];
    active: { id: number; name: string } | null;
    users: Paginated<Row>;
    search: string;
};

/** Manage Users → User Stages (super-admins): members by matrix stage, one tab per stage. */
export default function Stages({ stages, active, users, search }: Props) {
    const t = useTrans();
    const path = usePage().url.split('?')[0];
    const [term, setTerm] = useState(search);
    useEffect(() => setTerm(search), [search]);

    const visit = (stage: number | undefined, q: string) => {
        const params: Record<string, string> = {};
        if (stage) params.stage = String(stage);
        if (q) params.search = q;
        router.get(path, params, { preserveState: true, preserveScroll: true });
    };
    const submit = (e: FormEvent) => {
        e.preventDefault();
        visit(active?.id, term.trim());
    };
    const tabQuery = (id: number) => (search ? `?stage=${id}&search=${encodeURIComponent(search)}` : `?stage=${id}`);

    return (
        <div className="page-user-stages">
            <PageHeader title={t('User Stages')} />
            <div className="st-tabs" role="tablist">
                {stages.map((stage) => (
                    <Link
                        key={stage.id}
                        href={`${path}${tabQuery(stage.id)}`}
                        preserveScroll
                        role="tab"
                        aria-selected={stage.id === active?.id}
                        className={`st-tab ${stage.id === active?.id ? 'is-active' : ''}`}
                    >
                        {stage.name}
                        <span className="st-tab-badge">{stage.count}</span>
                    </Link>
                ))}
            </div>

            <form onSubmit={submit} className="st-search-row" role="search">
                <div className="st-search-wrap">
                    <i className="las la-search" />
                    <input type="text" className="st-search-input" aria-label={t('Search')} value={term} onChange={(e) => setTerm(e.target.value)} placeholder={t('Search username, email or phone…')} />
                </div>
                <button type="submit" className="st-search-btn">
                    <i className="las la-search me-1" />
                    {t('Search')}
                </button>
                {search && (
                    <button type="button" className="st-clear-btn" onClick={() => visit(active?.id, '')}>
                        <i className="las la-times" />
                        {t('Clear')}
                    </button>
                )}
            </form>

            <div className="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <h5 className="card-title mb-0">{t(':stage Members', { stage: active?.name ?? t('Stage') })}</h5>
                <span className="badge badge--primary">{t(users.total === 1 ? ':count member' : ':count members', { count: users.total })}</span>
            </div>
            <DataTable
                rows={users.data}
                rowKey={(r) => r.id}
                links={users.links}
                empty={search ? t('No results for “:q”', { q: search }) : t('No members in :stage yet.', { stage: active?.name ?? t('this stage') })}
                columns={[
                    { label: '#', cell: (r) => r.number },
                    { label: t('Full Name'), cell: (r) => <strong>{r.fullname}</strong> },
                    { label: t('Username'), cell: (r) => `@ ${r.username}` },
                    { label: t('Phone'), cell: (r) => r.mobile },
                    { label: t('Email'), cell: (r) => r.email },
                    { label: t('Stage Level'), cell: () => <span className="st-level-badge">{active?.name ?? '—'}</span> },
                    { label: t('Date Joined'), cell: (r) => r.joined },
                    {
                        label: t('Action'),
                        cell: (r) => (
                            <a href={r.url} className="btn btn-sm btn-outline--primary">
                                <i className="las la-eye" /> {t('Details')}
                            </a>
                        ),
                    },
                ]}
            />
        </div>
    );
}

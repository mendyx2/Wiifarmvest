import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import BoxWidget from '@/Components/Admin/BoxWidget';
import { BadgeCell, UserCell, type AdminUser, type Badge } from '@/Components/Admin/Cells';
import DataTable, { type Column } from '@/Components/Admin/DataTable';
import FilterCard from '@/Components/Admin/FilterCard';
import { ConfirmButton, withId } from '@/Components/Admin/Form';
import LoanReject from '@/Components/Admin/LoanReject';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';
import type { Paginated } from '@/types';

type Row = {
    id: number;
    user: AdminUser;
    email: string;
    reference: string;
    product: string;
    amount: string;
    outstanding: string;
    owing: boolean;
    repayable: string;
    savings: string;
    purpose: string;
    rate: string;
    tenure: string;
    applied: string;
    nextDue: string | null;
    status: Badge;
    pending: boolean;
    url: string;
};

type Props = {
    title: string;
    loans: Paginated<Row>;
    summary: {
        total: number;
        pending: number;
        disbursed: string;
        outstanding: string;
    };
    filters: { search: string; status: string };
    /** The pending page: its status cannot be changed */
    fixed: boolean;
    urls: {
        index: string;
        pending: string;
        products: string;
        approve: string;
        reject: string;
    };
};

const STATUSES = ['pending', 'active', 'at_risk', 'defaulted', 'cleared', 'rejected'];

/**
 * Savings & Loans → All Loans, and Pending Applications (the same list with
 * the status set to pending, showing what a decision needs: repayable,
 * savings at application and purpose).
 */
export default function LoansIndex({ title, loans, summary, filters, fixed, urls }: Props) {
    const t = useTrans();
    const url = usePage().url;
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);
    const [rejecting, setRejecting] = useState<Row | null>(null);
    const reviewing = filters.status === 'pending';

    const actions = (r: Row) => (
        <div className="button--group">
            <Link href={r.url} className="btn btn-sm btn-outline--primary">
                <i className="las la-eye" /> {t('View')}
            </Link>
            {r.pending && (
                <>
                    <ConfirmButton
                        url={withId(urls.approve, r.id)}
                        question={t('Approve and disburse :amount to :user?', {
                            amount: r.amount,
                            user: r.user?.username ?? t('this member'),
                        })}
                        className="btn btn-sm btn-outline--success"
                    >
                        <i className="las la-check" /> {t('Approve')}
                    </ConfirmButton>
                    <button type="button" className="btn btn-sm btn-outline--danger" onClick={() => setRejecting(r)}>
                        <i className="las la-times" /> {t('Reject')}
                    </button>
                </>
            )}
        </div>
    );
    const user: Column<Row> = {
        label: t('User'),
        cell: (r) => (
            <>
                <UserCell user={r.user} />
                {r.email && (
                    <>
                        <br />
                        <small className="text-muted">{r.email}</small>
                    </>
                )}
            </>
        ),
    };
    const columns: Column<Row>[] = reviewing
        ? [
              { label: t('Applied'), cell: (r) => r.applied },
              user,
              { label: t('Product'), cell: (r) => r.product },
              { label: t('Amount'), cell: (r) => <strong>{r.amount}</strong> },
              { label: t('Tenure'), cell: (r) => r.tenure },
              { label: t('Rate'), cell: (r) => `${r.rate} p.a.` },
              { label: t('Total Repayable'), cell: (r) => r.repayable },
              { label: t('Savings at Application'), cell: (r) => r.savings },
              {
                  label: t('Purpose'),
                  cell: (r) => <small className="text-muted">{r.purpose || '—'}</small>,
              },
              { label: t('Action'), cell: actions },
          ]
        : [
              user,
              {
                  label: t('Reference'),
                  cell: (r) => <code>{r.reference}</code>,
              },
              { label: t('Product'), cell: (r) => r.product },
              { label: t('Amount'), cell: (r) => <strong>{r.amount}</strong> },
              {
                  label: t('Outstanding'),
                  cell: (r) => <span className={r.owing ? 'text--danger fw-bold' : 'text-muted'}>{r.outstanding}</span>,
              },
              { label: t('Rate'), cell: (r) => r.rate },
              { label: t('Tenure'), cell: (r) => r.tenure },
              { label: t('Next Due'), cell: (r) => r.nextDue ?? '—' },
              {
                  label: t('Status'),
                  cell: (r) => <BadgeCell badge={r.status} />,
              },
              { label: t('Action'), cell: actions },
          ];

    return (
        <>
            <PageHeader title={t(title)}>
                {!reviewing && summary.pending > 0 && (
                    <Link href={urls.pending} className="btn btn-outline--warning">
                        <i className="las la-clock" /> {t('Pending')} <span className="badge bg--warning ms-1">{summary.pending}</span>
                    </Link>
                )}
                {reviewing && (
                    <Link href={urls.index} className="btn btn-outline--dark">
                        <i className="las la-list" /> {t('All Loans')}
                    </Link>
                )}
                <Link href={urls.products} className="btn btn-outline--primary">
                    <i className="las la-cog" /> {t('Loan Products')}
                </Link>
            </PageHeader>
            {!reviewing && (
                <div className="row gy-4 mb-4">
                    <div className="col-sm-6 col-xl-3">
                        <BoxWidget outline bg="primary" icon="las la-file-invoice-dollar" title={t('Total Loans')} value={String(summary.total)} />
                    </div>
                    <div className="col-sm-6 col-xl-3">
                        <BoxWidget outline inertia bg="warning" icon="las la-clock" title={t('Pending')} value={String(summary.pending)} link={urls.pending} />
                    </div>
                    <div className="col-sm-6 col-xl-3">
                        <BoxWidget outline bg="success" icon="las la-hand-holding-usd" title={t('Total Disbursed')} value={summary.disbursed} />
                    </div>
                    <div className="col-sm-6 col-xl-3">
                        <BoxWidget outline bg="danger" icon="las la-exclamation-circle" title={t('Outstanding')} value={summary.outstanding} />
                    </div>
                </div>
            )}
            <FilterCard onSubmit={() => visitWithQuery(url, fixed ? { search: search.trim() } : { search: search.trim(), status })}>
                <div className="flex-grow-1">
                    <label htmlFor="loan-search">{t('Search')}</label>
                    <input id="loan-search" type="search" className="form-control" placeholder={t('Username, email or reference')} value={search} onChange={(e) => setSearch(e.target.value)} />
                </div>
                {!fixed && (
                    <div className="flex-grow-1">
                        <label htmlFor="loan-status">{t('Status')}</label>
                        <select id="loan-status" className="form-control" value={status} onChange={(e) => setStatus(e.target.value)}>
                            <option value="">{t('All Status')}</option>
                            {STATUSES.map((s) => (
                                <option key={s} value={s}>
                                    {t(s.charAt(0).toUpperCase() + s.slice(1).replace('_', ' '))}
                                </option>
                            ))}
                        </select>
                    </div>
                )}
            </FilterCard>
            <DataTable rows={loans.data} rowKey={(r) => r.id} links={loans.links} empty={reviewing ? t('No pending applications. All caught up!') : t('No loans found.')} columns={columns} />
            {rejecting && <LoanReject url={withId(urls.reject, rejecting.id)} reference={rejecting.reference} onClose={() => setRejecting(null)} />}
        </>
    );
}

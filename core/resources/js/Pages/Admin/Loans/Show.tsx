import { Link } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { BadgeCell, UserCell, type AdminUser, type Badge } from '@/Components/Admin/Cells';
import DataTable from '@/Components/Admin/DataTable';
import { ConfirmButton } from '@/Components/Admin/Form';
import LoanReject from '@/Components/Admin/LoanReject';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';

type Loan = {
    id: number;
    reference: string;
    status: Badge;
    pending: boolean;
    user: AdminUser;
    product: string;
    amount: string;
    rate: string;
    interest: string;
    repayable: string;
    outstanding: string;
    owing: boolean;
    repaid: string;
    progress: number;
    tenure: string;
    installmentLabel: string;
    installment: string;
    nextDue: string | null;
    applied: string;
    approved: string | null;
    cleared: string | null;
    savings: string;
    purpose: string;
    rejection: string;
};

type Installment = { id: number; number: number; due: string | null; principal: string; interest: string; total: string; paid: string; anyPaid: boolean; status: Badge };
type Repayment = { id: number; date: string; reference: string; amount: string; source: Badge; after: string };

type Props = {
    loan: Loan;
    schedule: Installment[];
    repayments: Repayment[];
    urls: { index: string; approve: string; reject: string };
};

/** One loan: its terms, repayment progress, schedule and repayments; Approve & Disburse / Reject while pending. */
export default function LoanShow({ loan, schedule, repayments, urls }: Props) {
    const t = useTrans();
    const [rejecting, setRejecting] = useState(false);
    const rows: [string, ReactNode][] = [
        [t('Member'), <UserCell key="u" user={loan.user} />],
        [t('Product'), loan.product],
        [t('Loan Amount'), <strong key="a">{loan.amount}</strong>],
        [t('Interest Rate'), <span key="r" className="text--success fw-bold">{loan.rate}</span>],
        [t('Total Interest'), loan.interest],
        [t('Total Repayable'), <strong key="tr">{loan.repayable}</strong>],
        [t('Outstanding'), <span key="o" className={loan.owing ? 'text--danger fw-bold' : 'text-muted'}>{loan.outstanding}</span>],
        [t('Total Repaid'), <span key="rp" className="text--success fw-bold">{loan.repaid}</span>],
        [t('Tenure'), loan.tenure],
        [t(loan.installmentLabel), loan.installment],
        [t('Next Due'), loan.nextDue ?? '—'],
        [t('Applied On'), loan.applied],
        ...(loan.approved ? ([[t('Approved'), loan.approved]] as [string, string][]) : []),
        ...(loan.cleared ? ([[t('Cleared'), loan.cleared]] as [string, string][]) : []),
        [t('Savings at Application'), loan.savings],
        [t('Purpose'), <span key="p" className="text-muted">{loan.purpose || '—'}</span>],
        ...(loan.rejection ? ([[t('Rejection Reason'), <small key="x" className="text--danger">{loan.rejection}</small>]] as [string, ReactNode][]) : []),
    ];

    return (
        <>
            <PageHeader title={t('Loan Detail')}>
                <Link href={urls.index} className="btn btn-outline--dark">
                    <i className="las la-arrow-left" /> {t('Back to Loans')}
                </Link>
                {loan.pending && (
                    <>
                        <ConfirmButton url={urls.approve} question={t('Approve and disburse :amount?', { amount: loan.amount })} className="btn btn-outline--success">
                            <i className="las la-check" /> {t('Approve & Disburse')}
                        </ConfirmButton>
                        <button type="button" className="btn btn-outline--danger" onClick={() => setRejecting(true)}>
                            <i className="las la-times" /> {t('Reject')}
                        </button>
                    </>
                )}
            </PageHeader>
            <div className="row g-4">
                <div className="col-lg-5">
                    <div className="card b-radius--10 mb-4">
                        <div className="card-header d-flex align-items-center justify-content-between">
                            <h5 className="card-title mb-0">{loan.reference}</h5>
                            <BadgeCell badge={loan.status} />
                        </div>
                        <div className="card-body">
                            <table className="table table-sm table-borderless mb-0">
                                <tbody>
                                    {rows.map(([label, value]) => (
                                        <tr key={label}>
                                            <td className="text-muted">{label}</td>
                                            <td>{value}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div className="card b-radius--10">
                        <div className="card-body">
                            <div className="d-flex justify-content-between mb-2">
                                <span className="fw-bold">{t('Repayment Progress')}</span>
                                <span className="fw-bold text--success">{loan.progress}%</span>
                            </div>
                            <div className="progress" style={{ height: 12, borderRadius: 999 }} role="progressbar" aria-valuenow={loan.progress} aria-valuemin={0} aria-valuemax={100} aria-label={t('Repayment Progress')}>
                                <div className="progress-bar bg-success" style={{ width: `${loan.progress}%`, borderRadius: 999 }} />
                            </div>
                            <div className="d-flex justify-content-between mt-2 text-muted small">
                                <span>{t(':amount repaid', { amount: loan.repaid })}</span>
                                <span>{t(':amount remaining', { amount: loan.outstanding })}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="col-lg-7">
                    <h5 className="mb-3">{t('Repayment Schedule')}</h5>
                    <div className="mb-4">
                        <DataTable
                            rows={schedule}
                            rowKey={(i) => i.id}
                            empty={t('No schedule generated.')}
                            columns={[
                                { label: '#', cell: (i) => i.number },
                                { label: t('Due Date'), cell: (i) => i.due ?? '—' },
                                { label: t('Principal'), cell: (i) => i.principal },
                                { label: t('Interest'), cell: (i) => i.interest },
                                { label: t('Total'), cell: (i) => <strong>{i.total}</strong> },
                                { label: t('Paid'), cell: (i) => <span className={i.anyPaid ? 'text--success' : ''}>{i.paid}</span> },
                                { label: t('Status'), cell: (i) => <BadgeCell badge={i.status} /> },
                            ]}
                        />
                    </div>
                    <h5 className="mb-3">{t('Repayment History')}</h5>
                    <DataTable
                        rows={repayments}
                        rowKey={(r) => r.id}
                        empty={t('No repayments recorded yet.')}
                        columns={[
                            { label: t('Date'), cell: (r) => r.date },
                            { label: t('Reference'), cell: (r) => <code className="small">{r.reference}</code> },
                            { label: t('Amount'), cell: (r) => <span className="text--success fw-bold">{r.amount}</span> },
                            { label: t('Source'), cell: (r) => <BadgeCell badge={r.source} /> },
                            { label: t('Outstanding After'), cell: (r) => r.after },
                        ]}
                    />
                </div>
            </div>
            {rejecting && <LoanReject url={urls.reject} reference={loan.reference} onClose={() => setRejecting(false)} />}
        </>
    );
}

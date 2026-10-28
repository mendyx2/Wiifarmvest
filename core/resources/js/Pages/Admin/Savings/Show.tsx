import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { BadgeCell, UserCell, type AdminUser, type Badge } from '@/Components/Admin/Cells';
import DataTable from '@/Components/Admin/DataTable';
import { DialogHeader } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';

type Saving = {
    id: number;
    reference: string;
    user: AdminUser;
    name: string;
    type: Badge;
    status: Badge;
    principal: string;
    balance: string;
    target: string | null;
    goal: string;
    progress: number;
    rate: string;
    interest: string;
    start: string | null;
    maturity: string | null;
    closed: string | null;
    payout: string;
    canTransfer: boolean;
    maturesLater: boolean;
};

type Txn = { id: number; date: string; trx: string; amount: string; credit: boolean; details: string };

type Props = {
    saving: Saving;
    transactions: Txn[];
    urls: { transfer: string; index: string };
};

/** One member's savings product, with "Transfer to Money Box" once it is due. */
export default function SavingsShow({ saving, transactions, urls }: Props) {
    const t = useTrans();
    const [asking, setAsking] = useState(false);
    const [busy, setBusy] = useState(false);
    const transfer = () => {
        setBusy(true);
        router.post(urls.transfer, {}, { preserveScroll: true, onFinish: () => (setBusy(false), setAsking(false)) });
    };
    const rows: [string, React.ReactNode][] = [
        [t('Reference'), <code key="r">{saving.reference}</code>],
        [t('Owner'), <UserCell key="u" user={saving.user} />],
        [t('Name'), saving.name],
        [t('Type'), <BadgeCell key="t" badge={saving.type} />],
        [t('Principal'), <strong key="p">{saving.principal}</strong>],
        [t('Current Balance'), <strong key="b">{saving.balance}</strong>],
        [t('Target Amount'), saving.target ?? '—'],
        [t('Interest Rate'), <span key="ra" className="text--success fw-bold">{saving.rate}</span>],
        [t('Interest Earned'), <span key="i" className="text--success">{saving.interest}</span>],
        [t('Start Date'), saving.start ?? '—'],
        [t('Maturity Date'), saving.maturity ?? '—'],
        ...(saving.closed ? ([[t('Closed At'), saving.closed]] as [string, string][]) : []),
    ];

    return (
        <>
            <PageHeader title={t('Savings Detail')}>
                <Link href={urls.index} className="btn btn-outline--dark">
                    <i className="las la-arrow-left" /> {t('Back to Savings')}
                </Link>
                {saving.canTransfer && (
                    <button type="button" className="btn btn--success" onClick={() => setAsking(true)}>
                        <i className="las la-exchange-alt" /> {t('Transfer to Money Box')}
                    </button>
                )}
                {saving.maturesLater && (
                    <span className="badge badge--warning">
                        <i className="las la-clock" /> {t('Matures :date', { date: saving.maturity ?? '' })}
                    </span>
                )}
            </PageHeader>
            <div className="row g-4">
                <div className="col-lg-5">
                    <div className="card b-radius--10 mb-4">
                        <div className="card-header d-flex align-items-center justify-content-between">
                            <h5 className="card-title mb-0">{t('Savings Product')}</h5>
                            <BadgeCell badge={saving.status} />
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
                                <span className="fw-bold">{t('Goal Progress')}</span>
                                <span className="fw-bold text--success">{saving.progress}%</span>
                            </div>
                            <div className="progress" style={{ height: 12, borderRadius: 999 }} role="progressbar" aria-valuenow={saving.progress} aria-valuemin={0} aria-valuemax={100} aria-label={t('Goal Progress')}>
                                <div className="progress-bar bg-success" style={{ width: `${saving.progress}%`, borderRadius: 999 }} />
                            </div>
                            <div className="d-flex justify-content-between mt-2 text-muted small">
                                <span>{t(':amount saved', { amount: saving.balance })}</span>
                                <span>{t(':amount goal', { amount: saving.goal })}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="col-lg-7">
                    <h5 className="mb-3">{t('Transaction History')}</h5>
                    <DataTable
                        rows={transactions}
                        rowKey={(x) => x.id}
                        empty={t('No transactions found.')}
                        columns={[
                            { label: t('Date'), cell: (x) => x.date },
                            { label: t('TRX'), cell: (x) => <code className="small">{x.trx}</code> },
                            { label: t('Amount'), cell: (x) => <span className={`fw-bold ${x.credit ? 'text--success' : 'text--danger'}`}>{x.amount}</span> },
                            { label: t('Type'), cell: (x) => <span className={`badge ${x.credit ? 'badge--success' : 'badge--danger'}`}>{x.credit ? t('Credit') : t('Debit')}</span> },
                            { label: t('Details'), cell: (x) => <small className="text-muted">{x.details}</small> },
                        ]}
                    />
                </div>
            </div>

            <Modal open={asking} onClose={() => !busy && setAsking(false)} labelledBy="transfer-title">
                <DialogHeader id="transfer-title" title={t('Transfer Savings to Money Box')} onClose={() => setAsking(false)} />
                <div className="modal-body">
                    <p className="mb-3">{t("You are about to transfer the savings balance and interest to the user's Money Box.")}</p>
                    <table className="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td className="text-muted">{t('User')}</td>
                                <td className="fw-bold">{saving.user?.username}</td>
                            </tr>
                            <tr>
                                <td className="text-muted">{t('Total Transfer')}</td>
                                <td className="fw-bold text--success">{saving.payout}</td>
                            </tr>
                            <tr>
                                <td className="text-muted">{t('Reference')}</td>
                                <td>
                                    <code>{saving.reference}</code>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div className="alert alert-warning mt-3 mb-0 p-2 small">
                        <i className="las la-exclamation-triangle me-1" />
                        {t('This action is irreversible. The savings product will be closed after transfer.')}
                    </div>
                </div>
                <div className="modal-footer">
                    <button type="button" className="btn btn-outline--dark" onClick={() => setAsking(false)} disabled={busy}>
                        {t('Cancel')}
                    </button>
                    <button type="button" className="btn btn--success" onClick={transfer} disabled={busy}>
                        {busy ? <i className="las la-spinner la-spin" /> : <i className="las la-check" />} {t('Confirm Transfer')}
                    </button>
                </div>
            </Modal>
        </>
    );
}

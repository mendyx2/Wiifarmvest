import { Link } from '@inertiajs/react';
import { BadgeCell, UserCell, type AdminUser, type Badge } from '@/Components/Admin/Cells';
import DataTable from '@/Components/Admin/DataTable';
import PageHeader from '@/Components/Admin/PageHeader';
import SearchForm from '@/Components/Admin/SearchForm';
import { useTrans } from '@/lib/i18n';
import type { Paginated } from '@/types';

type Payment = {
    id: number;
    txid: string;
    explorer: string;
    from: string;
    amount: string;
    sentAt: string;
    source: string;
    status: Badge;
    note: string;
    deposit: { trx: string; url: string; user: AdminUser } | null;
};

type Props = {
    tab: string;
    tabs: { key: string; label: string; count: number; url: string }[];
    search: string;
    payments: Paginated<Payment>;
    settings: { address: string | null; network: string; edit: string };
};

/**
 * Every USDT (TRC20) payment seen at the Wiifarm address
 * (DepositController::usdtPayments). "Needs checking" ones are approved or
 * rejected on their deposit's page; "No matching deposit" ones can still be
 * claimed by the member who sent them, with the transaction ID.
 */
export default function UsdtPayments({ tab, tabs, search, payments, settings }: Props) {
    const t = useTrans();
    const current = tabs.find((x) => x.key === tab) ?? tabs[0];

    return (
        <>
            <PageHeader title={t('USDT Payments')}>
                <SearchForm url={current.url} value={search} placeholder="Transaction ID or sender address" />
            </PageHeader>

            <div className="card mb-3">
                <div className="card-body d-flex flex-wrap gap-3 align-items-center justify-content-between">
                    <div>
                        <small className="text-muted d-block">{t('Wiifarm USDT (TRC20) address')}</small>
                        {settings.address ? <code>{settings.address}</code> : <span className="text--danger">{t('Not set: members cannot pay in USDT yet.')}</span>}
                        {settings.network !== 'mainnet' && <span className="badge badge--warning ms-2">{t('Test network')}</span>}
                    </div>
                    <Link href={settings.edit} className="btn btn-sm btn-outline--primary">
                        <i className="las la-cog" /> {t('Gateway settings')}
                    </Link>
                </div>
            </div>

            <ul className="nav nav-tabs mb-3">
                {tabs.map((x) => (
                    <li className="nav-item" key={x.key}>
                        <Link className={`nav-link ${x.key === tab ? 'active' : ''}`} href={x.url} preserveScroll aria-current={x.key === tab ? 'page' : undefined}>
                            {t(x.label)} <span className="badge bg-secondary">{x.count}</span>
                        </Link>
                    </li>
                ))}
            </ul>

            <DataTable
                rows={payments.data}
                rowKey={(r) => r.id}
                links={payments.links}
                empty={t('No USDT payments')}
                columns={[
                    {
                        label: t('Payment'),
                        cell: (r) => (
                            <>
                                <strong>{r.amount}</strong>
                                <br />
                                <a href={r.explorer} target="_blank" rel="noopener noreferrer" title={r.txid}>
                                    <small>
                                        {r.txid.slice(0, 10)}…{r.txid.slice(-6)} <i className="las la-external-link-alt" aria-hidden />
                                    </small>
                                </a>
                            </>
                        ),
                    },
                    {
                        label: t('Sent'),
                        cell: (r) => (
                            <>
                                {r.sentAt}
                                <br />
                                <small className="text-muted">{t(r.source)}</small>
                            </>
                        ),
                    },
                    { label: t('From'), cell: (r) => <small className="text-break">{r.from || '—'}</small> },
                    {
                        label: t('Deposit'),
                        cell: (r) =>
                            r.deposit ? (
                                <>
                                    <UserCell user={r.deposit.user} />
                                    <Link href={r.deposit.url}>
                                        <small>{r.deposit.trx}</small>
                                    </Link>
                                </>
                            ) : (
                                '—'
                            ),
                    },
                    {
                        label: t('Status'),
                        cell: (r) => (
                            <>
                                <BadgeCell badge={r.status} />
                                {r.note && <small className="d-block text-muted mt-1">{r.note}</small>}
                            </>
                        ),
                    },
                ]}
            />
        </>
    );
}

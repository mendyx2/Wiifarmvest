import { Link } from '@inertiajs/react';
import DataTable from '@/Components/Admin/DataTable';
import { ConfirmButton } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import SearchForm from '@/Components/Admin/SearchForm';
import { useTrans } from '@/lib/i18n';
import type { Paginated } from '@/types';
import type { Batch } from './Batches';

type Payment = { id: number; reference: string; member: { name: string; username: string; url: string } | null; plan: string; units: string; perUnit: string; amount: string; status: [string, string]; date: string };

type Props = {
    batch: Batch & { notes: string; reversedAt: string | null };
    search: string;
    transactions: Paginated<Payment>;
    urls: { self: string; cancel: string; reverse: string; back: string };
};

/** One dividend batch: what it pays and each member's payment. */
export default function Detail({ batch, search, transactions, urls }: Props) {
    const t = useTrans();
    const facts: [string, string | number][] = [
        ['Plan', batch.plan],
        ['Holdings bought', batch.scope],
        ['Per unit', batch.perUnit],
        ['Members', batch.users],
        ['Units', batch.units],
        ['Total', batch.amount],
        ['Paid', batch.processed],
        ['Failed', batch.failed],
        ['Created', `${batch.date} · ${batch.by}`],
    ];
    if (batch.reversedAt) facts.push(['Reversed', batch.reversedAt]);

    return (
        <>
            <PageHeader title={t('Dividend Batch #:id', { id: batch.id })}>
                {batch.canCancel && (
                    <ConfirmButton url={urls.cancel} question={t('Cancel batch #:id? Nobody is paid.', { id: batch.id })} className="btn btn-sm btn--danger">
                        <i className="las la-times" /> {t('Cancel')}
                    </ConfirmButton>
                )}
                {batch.canReverse && (
                    <ConfirmButton
                        url={urls.reverse}
                        question={t("Reverse batch #:id? :amount is taken back from :n members' balances, even if that takes a balance below zero.", { id: batch.id, amount: batch.amount, n: batch.users })}
                        className="btn btn-sm btn--warning"
                    >
                        <i className="las la-undo" /> {t('Reverse')}
                    </ConfirmButton>
                )}
                <Link href={urls.back} className="btn btn-sm btn-outline--primary">
                    <i className="la la-undo" /> {t('Back')}
                </Link>
            </PageHeader>
            <div className="card mb-4">
                <div className="card-body">
                    <div className="d-flex align-items-center gap-2 mb-3">
                        <span className={`badge badge--${batch.status[1]}`}>{t(batch.status[0])}</span>
                        {batch.error && <span className="text--danger small">{batch.error}</span>}
                    </div>
                    <div className="row gy-3">
                        {facts.map(([label, value]) => (
                            <div className="col-6 col-md-4 col-xl-3" key={label}>
                                <small className="text-muted d-block">{t(label)}</small>
                                <span className="fw-bold">{value}</span>
                            </div>
                        ))}
                    </div>
                    {batch.notes && (
                        <p className="mt-3 mb-0 text-muted" style={{ whiteSpace: 'pre-wrap' }}>
                            {batch.notes}
                        </p>
                    )}
                </div>
            </div>
            <div className="d-flex justify-content-end mb-3">
                <SearchForm url={urls.self} value={search} placeholder="Username" />
            </div>
            <DataTable
                rows={transactions.data}
                rowKey={(p) => p.id}
                links={transactions.links}
                empty={batch.status[0] === 'Pending' ? t('Not paid yet.') : t('No payments.')}
                columns={[
                    { label: t('Reference'), cell: (p) => <small>{p.reference}</small> },
                    {
                        label: t('Member'),
                        cell: (p) =>
                            p.member ? (
                                <Link href={p.member.url}>
                                    {p.member.name}
                                    <small className="text-muted d-block">@{p.member.username}</small>
                                </Link>
                            ) : (
                                '—'
                            ),
                    },
                    { label: t('Plan'), cell: (p) => p.plan },
                    { label: t('Units'), cell: (p) => p.units },
                    { label: t('Rate/Unit'), cell: (p) => p.perUnit },
                    { label: t('Amount'), cell: (p) => <strong>{p.amount}</strong> },
                    { label: t('Status'), cell: (p) => <span className={`badge badge--${p.status[1]}`}>{t(p.status[0])}</span> },
                    { label: t('Date'), cell: (p) => p.date },
                ]}
            />
        </>
    );
}

import { Link } from '@inertiajs/react';
import DataTable from '@/Components/Admin/DataTable';
import { ConfirmButton, withId } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';
import type { Paginated } from '@/types';

export type Batch = {
    id: number;
    by: string;
    scope: string;
    plan: string;
    perUnit: string;
    users: number;
    units: string;
    amount: string;
    processed: number;
    failed: number;
    status: [string, string];
    canCancel: boolean;
    canReverse: boolean;
    error: string;
    date: string;
    url: string;
};

type Props = {
    title: string;
    tab: 'open' | 'history';
    stats: { shareholders: number; units: string; distributed: string };
    stalled: number;
    batches: Paginated<Batch>;
    urls: { open: string; history: string; create: string; investors: string; cancel: string; reverse: string };
};

/** Manage FFO → Dividends: batches waiting or being paid, and the finished ones (one page, two tabs). */
export default function Batches({ title, tab, stats, stalled, batches, urls }: Props) {
    const t = useTrans();
    return (
        <>
            <PageHeader title={t(title)}>
                <Link href={urls.investors} className="btn btn-sm btn-outline--info">
                    <i className="las la-users" /> {t('Investors')}
                </Link>
                <Link href={urls.create} className="btn btn-sm btn-outline--primary">
                    <i className="las la-plus" /> {t('New Dividend Payment')}
                </Link>
            </PageHeader>
            {stalled > 0 && (
                <div className="card bl--5 border--danger mb-4" role="alert">
                    <div className="card-body text--danger">
                        {t(':n batch(es) have been pending for more than 10 minutes. Payments run on the "dividends" queue: check that a queue worker runs it (php artisan queue:work --queue=dividends,default).', { n: stalled })}
                    </div>
                </div>
            )}
            <div className="row gy-3 mb-4">
                {(
                    [
                        ['Shareholders', String(stats.shareholders), 'las la-users'],
                        ['Units held', stats.units, 'las la-layer-group'],
                        ['Dividends paid', stats.distributed, 'las la-hand-holding-usd'],
                    ] as const
                ).map(([label, value, icon]) => (
                    <div className="col-sm-4" key={label}>
                        <div className="card">
                            <div className="card-body d-flex align-items-center gap-3">
                                <i className={`${icon} fs-2 text--primary`} aria-hidden />
                                <div>
                                    <h4 className="mb-0">{value}</h4>
                                    <small className="text-muted">{t(label)}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
            <ul className="nav nav-tabs mb-3">
                <li className="nav-item">
                    <Link className={`nav-link ${tab === 'open' ? 'active' : ''}`} href={urls.open}>
                        {t('Pending & processing')}
                    </Link>
                </li>
                <li className="nav-item">
                    <Link className={`nav-link ${tab === 'history' ? 'active' : ''}`} href={urls.history}>
                        {t('History')}
                    </Link>
                </li>
            </ul>
            <DataTable
                rows={batches.data}
                rowKey={(b) => b.id}
                links={batches.links}
                empty={tab === 'open' ? t('No batch is waiting.') : t('No finished batch yet.')}
                columns={[
                    { label: t('Batch'), cell: (b) => <span className="fw-bold">#{b.id}</span> },
                    {
                        label: t('Pays'),
                        cell: (b) => (
                            <>
                                {b.perUnit} {t('per unit')}
                                <small className="text-muted d-block">
                                    {b.plan} · {b.scope}
                                </small>
                            </>
                        ),
                    },
                    {
                        label: t('Members'),
                        cell: (b) => (
                            <>
                                {b.users}
                                <small className="text-muted d-block">{t(':n units', { n: b.units })}</small>
                            </>
                        ),
                    },
                    { label: t('Total'), cell: (b) => <strong>{b.amount}</strong> },
                    {
                        label: t('Status'),
                        cell: (b) => (
                            <>
                                <span className={`badge badge--${b.status[1]}`}>{t(b.status[0])}</span>
                                {b.error && (
                                    <small className="text--danger d-block" style={{ maxWidth: 220 }}>
                                        {b.error}
                                    </small>
                                )}
                            </>
                        ),
                    },
                    {
                        label: t('Created'),
                        cell: (b) => (
                            <>
                                {b.date}
                                <small className="text-muted d-block">{b.by}</small>
                            </>
                        ),
                    },
                    {
                        label: t('Action'),
                        cell: (b) => (
                            <div className="button--group">
                                <Link href={b.url} className="btn btn-sm btn-outline--primary">
                                    <i className="las la-desktop" /> {t('Details')}
                                </Link>
                                {b.canCancel && (
                                    <ConfirmButton url={withId(urls.cancel, b.id)} question={t('Cancel batch #:id? Nobody is paid.', { id: b.id })} className="btn btn-sm btn-outline--danger">
                                        <i className="las la-times" /> {t('Cancel')}
                                    </ConfirmButton>
                                )}
                                {b.canReverse && (
                                    <ConfirmButton
                                        url={withId(urls.reverse, b.id)}
                                        question={t("Reverse batch #:id? :amount is taken back from :n members' balances, even if that takes a balance below zero.", { id: b.id, amount: b.amount, n: b.users })}
                                        className="btn btn-sm btn-outline--warning"
                                    >
                                        <i className="las la-undo" /> {t('Reverse')}
                                    </ConfirmButton>
                                )}
                            </div>
                        ),
                    },
                ]}
            />
        </>
    );
}

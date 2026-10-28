import { Link } from '@inertiajs/react';
import DataTable from '@/Components/Admin/DataTable';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';
import type { Paginated } from '@/types';

type Dividend = { id: number; reference: string; investment: number; plan: string; units: string; perUnit: string; amount: string; status: [string, string]; batch: string | null; date: string };

type Props = {
    title: string;
    member: { name: string; username: string; email: string; url: string };
    total: string;
    dividends: Paginated<Dividend>;
    urls: { back: string };
};

/** The dividends one member received. */
export default function Member({ title, member, total, dividends, urls }: Props) {
    const t = useTrans();
    return (
        <>
            <PageHeader title={title}>
                <Link href={urls.back} className="btn btn-sm btn-outline--primary">
                    <i className="la la-undo" /> {t('Back')}
                </Link>
            </PageHeader>
            <div className="card mb-4">
                <div className="card-body d-flex flex-wrap justify-content-between gap-3">
                    <div>
                        <h5 className="mb-1">
                            <Link href={member.url}>{member.name}</Link>
                        </h5>
                        <small className="text-muted">
                            @{member.username} · {member.email}
                        </small>
                    </div>
                    <div className="text-end">
                        <small className="text-muted d-block">{t('Dividends received')}</small>
                        <span className="fs-4 fw-bold text--primary">{total}</span>
                    </div>
                </div>
            </div>
            <DataTable
                rows={dividends.data}
                rowKey={(d) => d.id}
                links={dividends.links}
                empty={t('No dividends yet.')}
                columns={[
                    { label: t('Reference'), cell: (d) => <small>{d.reference}</small> },
                    { label: t('Holding'), cell: (d) => `#${d.investment}` },
                    { label: t('Plan'), cell: (d) => d.plan },
                    { label: t('Units Held'), cell: (d) => d.units },
                    { label: t('Rate/Unit'), cell: (d) => d.perUnit },
                    { label: t('Amount'), cell: (d) => <strong>{d.amount}</strong> },
                    { label: t('Status'), cell: (d) => <span className={`badge badge--${d.status[1]}`}>{t(d.status[0])}</span> },
                    { label: t('Date'), cell: (d) => (d.batch ? <Link href={d.batch}>{d.date}</Link> : d.date) },
                ]}
            />
        </>
    );
}

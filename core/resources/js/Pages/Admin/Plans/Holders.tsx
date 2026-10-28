import { Link } from '@inertiajs/react';
import DataTable from '@/Components/Admin/DataTable';
import { ConfirmButton, withId } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';
import type { Paginated } from '@/types';

type Holding = { id: number; member: { name: string; username: string; url: string } | null; unitCost: string; units: string; paid: string; value: string; active: boolean; date: string };

type Props = {
    plan: { id: number; name: string; price: string };
    holdings: Paginated<Holding>;
    urls: { status: string; back: string };
};

/** A plan's holders. A disabled holding is left out of dividend batches. */
export default function Holders({ plan, holdings, urls }: Props) {
    const t = useTrans();
    return (
        <>
            <PageHeader title={plan.name}>
                <Link href={urls.back} className="btn btn-sm btn-outline--primary">
                    <i className="la la-undo" /> {t('Back')}
                </Link>
            </PageHeader>
            <DataTable
                rows={holdings.data}
                rowKey={(h) => h.id}
                links={holdings.links}
                empty={t('Nobody holds this plan yet')}
                columns={[
                    {
                        label: t('Member'),
                        cell: (h) =>
                            h.member ? (
                                <>
                                    <span className="fw-bold d-block">{h.member.name}</span>
                                    <Link href={h.member.url}>@{h.member.username}</Link>
                                </>
                            ) : (
                                '—'
                            ),
                    },
                    { label: t('Unit cost'), cell: (h) => h.unitCost },
                    { label: t('Units'), cell: (h) => h.units },
                    { label: t('Paid'), cell: (h) => h.paid },
                    { label: t('Value'), cell: (h) => h.value },
                    { label: t('Bought'), cell: (h) => h.date },
                    { label: t('Status'), cell: (h) => (h.active ? <span className="badge badge--success">{t('Active')}</span> : <span className="badge badge--warning">{t('Disabled')}</span>) },
                    {
                        label: t('Action'),
                        cell: (h) =>
                            h.active ? (
                                <ConfirmButton url={withId(urls.status, h.id)} data={{ state: 'disable' }} question={t('Disable this holding? It gets no dividends while disabled.')} className="btn btn-sm btn-outline--danger">
                                    <i className="la la-eye-slash" /> {t('Disable')}
                                </ConfirmButton>
                            ) : (
                                <ConfirmButton url={withId(urls.status, h.id)} data={{ state: 'enable' }} question={t('Enable this holding again?')} className="btn btn-sm btn-outline--success">
                                    <i className="la la-eye" /> {t('Enable')}
                                </ConfirmButton>
                            ),
                    },
                ]}
            />
        </>
    );
}

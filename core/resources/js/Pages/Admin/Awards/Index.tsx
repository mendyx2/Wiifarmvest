import { Link } from '@inertiajs/react';
import { ConfirmButton, withId } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';

type Award = {
    id: number;
    name: string;
    description: string;
    image: string | null;
    order: number;
    totalPv: string;
    leftPv: string;
    rightPv: string;
    payment: string;
    prerequisite: string | null;
    earned: number;
    unpaid: number;
    active: boolean;
    edit: string;
};

type Props = {
    awards: Award[];
    urls: { create: string; payments: string; toggle: string; destroy: string };
};

/** Award Management: the ranks members earn from their PV, in order. */
export default function Awards({ awards, urls }: Props) {
    const t = useTrans();
    const unpaid = awards.reduce((n, a) => n + a.unpaid, 0);
    return (
        <>
            <PageHeader title={t('Award Management')}>
                <Link href={urls.payments} className="btn btn-sm btn-outline--info">
                    <i className="las la-money-bill" /> {t('Payments')} {unpaid > 0 && <span className="badge badge--danger ms-1">{unpaid}</span>}
                </Link>
                <Link href={urls.create} className="btn btn-sm btn-outline--primary">
                    <i className="las la-plus" /> {t('Add Award')}
                </Link>
            </PageHeader>
            <div className="row gy-4">
                {awards.map((a) => (
                    <div className="col-xxl-3 col-xl-4 col-md-6" key={a.id}>
                        <div className={`card h-100 ${a.active ? '' : 'opacity-75'}`}>
                            <div className="card-body">
                                <div className="d-flex justify-content-between mb-2">
                                    <span className="badge badge--dark">#{a.order}</span>
                                    <span className={`badge badge--${a.active ? 'success' : 'warning'}`}>{a.active ? t('Active') : t('Inactive')}</span>
                                </div>
                                <div className="text-center mb-2">
                                    {a.image ? <img src={a.image} alt="" style={{ width: 72, height: 72, objectFit: 'contain' }} /> : <i className="las la-trophy text--warning" style={{ fontSize: 56 }} aria-hidden />}
                                </div>
                                <h5 className="text-center mb-1">{a.name}</h5>
                                <p className="small text-muted text-center">{a.description || t('No description provided.')}</p>
                                <div className="row text-center g-1 small mb-2">
                                    {(
                                        [
                                            ['Total PV', a.totalPv],
                                            ['Left PV', a.leftPv],
                                            ['Right PV', a.rightPv],
                                        ] as const
                                    ).map(([label, value]) => (
                                        <div className="col-4" key={label}>
                                            <div className="border rounded py-1">
                                                <small className="text-muted d-block">{t(label)}</small>
                                                <b>{value}</b>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                                <p className="mb-1 small">
                                    <i className="las la-coins" /> {t('Pays')} <b>{a.payment}</b>
                                </p>
                                {a.prerequisite && (
                                    <p className="mb-1 small">
                                        <i className="las la-link" /> {t('Requires')} <b>{a.prerequisite}</b>
                                    </p>
                                )}
                                <p className="mb-3 small">
                                    <i className="las la-user-check" /> {t(':n earned', { n: a.earned })}
                                    {a.unpaid > 0 && <span className="text--danger"> · {t(':n unpaid', { n: a.unpaid })}</span>}
                                </p>
                                <div className="d-flex flex-wrap gap-2">
                                    <Link href={a.edit} className="btn btn-sm btn-outline--primary">
                                        <i className="la la-pen" /> {t('Edit')}
                                    </Link>
                                    <ConfirmButton
                                        url={withId(urls.toggle, a.id)}
                                        data={{ state: a.active ? 'disable' : 'enable' }}
                                        question={a.active ? t('Deactivate :name? Nobody earns it while inactive.', { name: a.name }) : t('Activate :name?', { name: a.name })}
                                        className={`btn btn-sm ${a.active ? 'btn-outline--warning' : 'btn-outline--success'}`}
                                    >
                                        <i className={`la ${a.active ? 'la-eye-slash' : 'la-eye'}`} /> {a.active ? t('Deactivate') : t('Activate')}
                                    </ConfirmButton>
                                    {a.earned === 0 && (
                                        <ConfirmButton url={withId(urls.destroy, a.id)} method="delete" question={t('Delete the award :name?', { name: a.name })} className="btn btn-sm btn-outline--danger">
                                            <i className="la la-trash" /> {t('Delete')}
                                        </ConfirmButton>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                ))}
                {awards.length === 0 && <p className="text-muted">{t('No awards yet.')}</p>}
            </div>
        </>
    );
}

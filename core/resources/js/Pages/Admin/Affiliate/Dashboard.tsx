import { Link } from '@inertiajs/react';
import BoxWidget from '@/Components/Admin/BoxWidget';
import { BadgeCell, UserCell, type AdminUser } from '@/Components/Admin/Cells';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';
import type { AffiliateOrderRow } from './Orders';

type Props = {
    stats: { clicks: string; orders: string; revenue: string; bonus: string; pickup: number };
    top: { user: AdminUser; orders: number }[];
    recent: AffiliateOrderRow[];
    urls: { orders: string; pickup: string };
};

/** Affiliate Program → Dashboard: the /shop affiliate program at a glance. */
export default function AffiliateDashboard({ stats, top, recent, urls }: Props) {
    const t = useTrans();
    return (
        <>
            <PageHeader title={t('Affiliate Dashboard')} />
            <div className="row gy-4">
                <div className="col-xxl col-md-4 col-sm-6">
                    <BoxWidget outline bg="primary" icon="las la-mouse-pointer" title={t('Total Clicks')} value={stats.clicks} />
                </div>
                <div className="col-xxl col-md-4 col-sm-6">
                    <BoxWidget outline bg="info" icon="las la-box" title={t('Total Orders')} value={stats.orders} />
                </div>
                <div className="col-xxl col-md-4 col-sm-6">
                    <BoxWidget outline bg="success" icon="las la-money-bill-wave" title={t('Total Revenue')} value={stats.revenue} note={t('Paid and fulfilled orders')} />
                </div>
                <div className="col-xxl col-md-4 col-sm-6">
                    <BoxWidget outline bg="warning" icon="las la-gift" title={t('Bonus Paid')} value={stats.bonus} />
                </div>
                <div className="col-xxl col-md-4 col-sm-6">
                    <BoxWidget outline bg="danger" icon="las la-hourglass-half" title={t('Awaiting Pickup')} value={String(stats.pickup)} link={urls.pickup} inertia />
                </div>
            </div>
            <div className="row gy-4 mt-1">
                <div className="col-lg-5">
                    <div className="card h-100">
                        <div className="card-header">
                            <h5 className="card-title mb-0">{t('Top Affiliates')}</h5>
                        </div>
                        <div className="card-body p-0">
                            <div className="table-responsive">
                                <table className="table table--light style--two mb-0">
                                    <thead>
                                        <tr>
                                            <th>{t('Member')}</th>
                                            <th>{t('Orders')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {top.length ? (
                                            top.map((row, i) => (
                                                <tr key={i}>
                                                    <td>
                                                        <UserCell user={row.user} />
                                                    </td>
                                                    <td>{row.orders}</td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td colSpan={2} className="text-center text-muted py-3">
                                                    {t('No affiliate activity yet.')}
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="col-lg-7">
                    <div className="card h-100">
                        <div className="card-header d-flex justify-content-between align-items-center">
                            <h5 className="card-title mb-0">{t('Recent Orders')}</h5>
                            <Link href={urls.orders} className="btn btn-sm btn-outline--primary">
                                {t('View All')}
                            </Link>
                        </div>
                        <div className="card-body p-0">
                            <div className="table-responsive">
                                <table className="table table--light style--two mb-0">
                                    <thead>
                                        <tr>
                                            <th>{t('Code')}</th>
                                            <th>{t('Affiliate')}</th>
                                            <th>{t('Total')}</th>
                                            <th>{t('Status')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {recent.length ? (
                                            recent.map((o) => (
                                                <tr key={o.id}>
                                                    <td>
                                                        <Link href={o.url}>
                                                            <code>{o.code}</code>
                                                        </Link>
                                                    </td>
                                                    <td>{o.affiliate?.fullname ?? t('Direct')}</td>
                                                    <td>{o.total}</td>
                                                    <td>
                                                        <BadgeCell badge={o.status} />
                                                    </td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td colSpan={4} className="text-center text-muted py-3">
                                                    {t('No orders yet.')}
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

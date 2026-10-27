import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { BadgeCell } from '@/Components/Admin/Cells';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';
import type { AffiliateOrderRow } from './Orders';

type Props = {
    title: string;
    order: AffiliateOrderRow & {
        phone: string | null;
        paystack: string | null;
        credited: boolean;
        stockist: { name: string; url: string } | null;
        redeemed: string | null;
        placed: string;
        ip: string | null;
    };
    items: { id: number; name: string; quantity: number; unit: string; line: string; bonus: string }[];
    urls: { index: string };
};

/** One /shop order: its items, the buyer, the affiliate and who handed it over. */
export default function AffiliateOrderDetail({ title, order, items, urls }: Props) {
    const t = useTrans();
    const rows: [string, ReactNode][] = [
        [t('Status'), <BadgeCell badge={order.status} />],
        [t('Buyer'), order.buyer.name],
        [t('Email'), order.buyer.email],
        [t('Phone'), order.phone ?? '—'],
        [t('State'), order.state ?? '—'],
        [t('Payment Method'), t(order.payment)],
        [t('Paystack Ref'), order.paystack ?? '—'],
        [t('Affiliate'), order.affiliate ? <Link href={order.affiliate.url}>{order.affiliate.fullname}</Link> : t('Direct (no affiliate)')],
        [t('Bonus Credited'), order.credited ? t('Yes') : t('No')],
        [t('Redeemed By'), order.stockist ? <Link href={order.stockist.url}>{order.stockist.name}</Link> : '—'],
        [t('Redeemed At'), order.redeemed ?? '—'],
        [t('Placed At'), order.placed],
        [t('IP Address'), order.ip ?? '—'],
    ];
    return (
        <>
            <PageHeader title={title}>
                <Link href={urls.index} className="btn btn-sm btn-outline--dark">
                    <i className="las la-undo" /> {t('Back')}
                </Link>
            </PageHeader>
            <div className="row gy-4">
                <div className="col-lg-7">
                    <div className="card">
                        <div className="card-header">
                            <h5 className="card-title mb-0">{t('Items')}</h5>
                        </div>
                        <div className="card-body p-0">
                            <div className="table-responsive--md table-responsive">
                                <table className="table table--light style--two mb-0">
                                    <thead>
                                        <tr>
                                            <th>{t('Product')}</th>
                                            <th>{t('Qty')}</th>
                                            <th>{t('Unit Price')}</th>
                                            <th>{t('Line Total')}</th>
                                            <th>{t('Bonus')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {items.map((i) => (
                                            <tr key={i.id}>
                                                <td data-label={t('Product')}>{i.name}</td>
                                                <td data-label={t('Qty')}>{i.quantity}</td>
                                                <td data-label={t('Unit Price')}>{i.unit}</td>
                                                <td data-label={t('Line Total')}>{i.line}</td>
                                                <td data-label={t('Bonus')}>{i.bonus}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colSpan={3}>{t('Total')}</th>
                                            <th>{order.total}</th>
                                            <th />
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="col-lg-5">
                    <div className="card">
                        <div className="card-header">
                            <h5 className="card-title mb-0">{t('Order Info')}</h5>
                        </div>
                        <ul className="list-group list-group-flush">
                            {rows.map(([k, v]) => (
                                <li className="list-group-item d-flex justify-content-between gap-2" key={k}>
                                    <span className="text-muted">{k}</span>
                                    <span className="text-end">{v}</span>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            </div>
        </>
    );
}

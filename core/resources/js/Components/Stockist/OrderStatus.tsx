import { useTrans } from '@/lib/i18n';

const ICON: Record<string, string> = {
    pending: 'la-clock',
    approved: 'la-check',
    processing: 'la-cog',
    shipped: 'la-shipping-fast',
    delivered: 'la-check-circle',
    cancelled: 'la-times',
};

/** A company (stockist) order's status as the "sl-status" badge. */
export default function OrderStatus({ status }: { status: string }) {
    const t = useTrans();
    const known = status in ICON;
    const label = status.charAt(0).toUpperCase() + status.slice(1);
    return (
        <span className={`sl-status sl-status-${known ? status : 'closed'}`}>
            {known && <i className={`las ${ICON[status]} me-1`} />}
            {t(label)}
        </span>
    );
}

export { ICON as ORDER_STATUS_ICON };

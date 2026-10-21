import { useTrans } from '@/lib/i18n';

/** Completed / Pending / Failed badge used by the investment pages. */
export default function DividendStatus({ status }: { status: 'completed' | 'pending' | 'failed' | string }) {
    const t = useTrans();
    if (status === 'completed') return <span className="badge badge-success-light"><i className="fas fa-check-circle me-1" />{t('Completed')}</span>;
    if (status === 'pending') return <span className="badge badge-warning-light"><i className="fas fa-hourglass-half me-1" />{t('Pending')}</span>;
    return <span className="badge badge-danger-light"><i className="fas fa-times-circle me-1" />{t('Failed')}</span>;
}

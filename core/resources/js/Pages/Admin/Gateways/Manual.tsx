import { Link } from '@inertiajs/react';
import GatewayTabs from '@/Components/Admin/GatewayTabs';
import MethodTable from '@/Components/Admin/MethodTable';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';

type Gateway = { id: number; name: string; image: string; currency: string; limits: string; active: boolean; url: string };

type Props = { gateways: Gateway[]; urls: { tabs: { automatic: string; manual: string }; create: string; status: string } };

/** Payment Gateways → Manual: deposit methods confirmed by an admin. */
export default function ManualGateways({ gateways, urls }: Props) {
    const t = useTrans();
    return (
        <>
            <PageHeader title={t('Manual Gateways')}>
                <Link href={urls.create} className="btn btn-outline--primary">
                    <i className="las la-plus" /> {t('Add New')}
                </Link>
            </PageHeader>
            <GatewayTabs tabs={urls.tabs} active="manual" />
            <MethodTable rows={gateways} statusUrl={urls.status} empty={t('No manual gateway yet')} />
        </>
    );
}

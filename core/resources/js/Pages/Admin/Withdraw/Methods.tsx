import { Link } from '@inertiajs/react';
import MethodTable from '@/Components/Admin/MethodTable';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';

type Method = { id: number; name: string; image: string; currency: string; limits: string; charge: string; active: boolean; url: string };

type Props = { methods: Method[]; urls: { create: string; status: string } };

/** Withdrawals → Withdrawal Methods. */
export default function WithdrawMethods({ methods, urls }: Props) {
    const t = useTrans();
    return (
        <>
            <PageHeader title={t('Withdrawal Methods')}>
                <Link href={urls.create} className="btn btn-outline--primary">
                    <i className="las la-plus" /> {t('Add New')}
                </Link>
            </PageHeader>
            <MethodTable rows={methods} statusUrl={urls.status} empty={t('No withdrawal method yet')} extra />
        </>
    );
}

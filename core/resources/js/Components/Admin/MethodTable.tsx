import { Link } from '@inertiajs/react';
import DataTable from '@/Components/Admin/DataTable';
import { ConfirmButton, withId } from '@/Components/Admin/Form';
import { StatusBadge } from '@/Components/Admin/GatewayTabs';
import { useTrans } from '@/lib/i18n';

type Row = { id: number; name: string; image: string; currency: string; limits: string; charge?: string; active: boolean; url: string };

/** The table shared by manual gateways and withdrawal methods. */
export default function MethodTable({ rows, statusUrl, empty, extra }: { rows: Row[]; statusUrl: string; empty: string; extra?: boolean }) {
    const t = useTrans();
    return (
        <DataTable
            rows={rows}
            rowKey={(g) => g.id}
            empty={empty}
            columns={[
                {
                    label: t('Method'),
                    cell: (g) => (
                        <span className="d-inline-flex align-items-center gap-2">
                            <img src={g.image} alt="" width={36} height={36} style={{ objectFit: 'contain' }} />
                            {g.name}
                        </span>
                    ),
                },
                { label: t('Currency'), cell: (g) => g.currency },
                { label: t('Limit'), cell: (g) => g.limits },
                ...(extra ? [{ label: t('Charge'), cell: (g: Row) => g.charge ?? '' }] : []),
                { label: t('Status'), cell: (g) => <StatusBadge active={g.active} /> },
                {
                    label: t('Action'),
                    cell: (g) => (
                        <div className="button--group">
                            <Link href={g.url} className="btn btn-sm btn-outline--primary">
                                <i className="la la-pencil" /> {t('Edit')}
                            </Link>
                            <ConfirmButton
                                url={withId(statusUrl, g.id)}
                                data={{ state: g.active ? 'disable' : 'enable' }}
                                question={g.active ? t('Are you sure to disable :name?', { name: g.name }) : t('Are you sure to enable :name?', { name: g.name })}
                                className={`btn btn-sm ${g.active ? 'btn-outline--danger' : 'btn-outline--success'}`}
                            >
                                <i className={`la ${g.active ? 'la-eye-slash' : 'la-eye'}`} /> {g.active ? t('Disable') : t('Enable')}
                            </ConfirmButton>
                        </div>
                    ),
                },
            ]}
        />
    );
}

import { UserCell, WhenCell, type AdminUser, type When } from '@/Components/Admin/Cells';
import DataTable from '@/Components/Admin/DataTable';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';
import type { Paginated } from '@/types';

type Row = {
    id: number;
    number: number;
    when: When;
    previous: AdminUser;
    new: AdminUser;
    current: boolean;
    admin: { name: string; username: string } | null;
};

type Props = { title: string; member: AdminUser; sponsor: AdminUser; logs: Paginated<Row> };

/** Every sponsor change made to one member (from the member page). */
export default function SponsorHistory({ title, member, sponsor, logs }: Props) {
    const t = useTrans();
    return (
        <>
            <PageHeader title={title} />
            <div className="row gy-4">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <div className="row gy-3 align-items-center">
                                <div className="col-md-4">
                                    <small className="text-muted d-block">{t('User')}</small>
                                    <UserCell user={member} />
                                </div>
                                <div className="col-md-4">
                                    <small className="text-muted d-block">{t('Current Sponsor')}</small>
                                    {sponsor ? <UserCell user={sponsor} /> : <span className="fw-bold">{t('N/A')}</span>}
                                </div>
                                <div className="col-md-4">
                                    <small className="text-muted d-block">{t('Total Sponsor Changes')}</small>
                                    <span className="fw-bold fs-4 text--primary">{logs.total}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="col-lg-12">
                    <DataTable
                        responsive="sm"
                        rows={logs.data}
                        rowKey={(r) => r.id}
                        links={logs.links}
                        empty={t('No sponsor change found')}
                        columns={[
                            { label: '#', cell: (r) => r.number },
                            { label: t('Changed At'), cell: (r) => <WhenCell when={r.when} /> },
                            { label: t('Previous Sponsor'), cell: (r) => <UserCell user={r.previous} /> },
                            {
                                label: t('New Sponsor'),
                                cell: (r) => (
                                    <>
                                        <UserCell user={r.new} />
                                        {r.current && (
                                            <>
                                                <br />
                                                <span className="badge badge--success">{t('Current')}</span>
                                            </>
                                        )}
                                    </>
                                ),
                            },
                            {
                                label: t('Changed By'),
                                cell: (r) =>
                                    r.admin ? (
                                        <>
                                            <span className="fw-bold">{r.admin.name}</span>
                                            <br />
                                            <span className="small">{r.admin.username}</span>
                                        </>
                                    ) : (
                                        <span className="text-muted">{t('N/A')}</span>
                                    ),
                            },
                        ]}
                    />
                </div>
            </div>
        </>
    );
}

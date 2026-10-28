import { Link } from '@inertiajs/react';
import { useState } from 'react';
import DataTable from '@/Components/Admin/DataTable';
import { ConfirmButton, withId } from '@/Components/Admin/Form';
import GatewayTabs, { StatusBadge } from '@/Components/Admin/GatewayTabs';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';

type Gateway = { id: number; name: string; supported: number; enabled: number; active: boolean; url: string };

type Props = { gateways: Gateway[]; urls: { tabs: { automatic: string; manual: string }; status: string } };

/** Payment Gateways → Automatic: the built-in gateways, with their currency counts and on/off state. */
export default function AutomaticGateways({ gateways, urls }: Props) {
    const t = useTrans();
    const [q, setQ] = useState('');
    const shown = q.trim() ? gateways.filter((g) => g.name.toLowerCase().includes(q.trim().toLowerCase())) : gateways;

    return (
        <>
            <PageHeader title={t('Automatic Gateways')}>
                <input type="search" className="form-control bg--white" style={{ maxWidth: 260 }} placeholder={t('Search') + '...'} aria-label={t('Search gateways')} value={q} onChange={(e) => setQ(e.target.value)} />
            </PageHeader>
            <GatewayTabs tabs={urls.tabs} active="automatic" />
            <DataTable
                rows={shown}
                rowKey={(g) => g.id}
                empty={t('No gateway found')}
                columns={[
                    { label: t('Gateway'), cell: (g) => g.name },
                    { label: t('Supported Currency'), cell: (g) => g.supported },
                    { label: t('Enabled Currency'), cell: (g) => g.enabled },
                    { label: t('Status'), cell: (g) => <StatusBadge active={g.active} /> },
                    {
                        label: t('Action'),
                        cell: (g) => (
                            <div className="button--group">
                                <Link href={g.url} className="btn btn-sm btn-outline--primary">
                                    <i className="la la-pencil" /> {t('Edit')}
                                </Link>
                                <ConfirmButton
                                    url={withId(urls.status, g.id)}
                                    data={{ state: g.active ? 'disable' : 'enable' }}
                                    question={g.active ? t('Are you sure to disable this gateway?') : t('Are you sure to enable this gateway?')}
                                    className={`btn btn-sm ${g.active ? 'btn-outline--danger' : 'btn-outline--success'}`}
                                >
                                    <i className={`la ${g.active ? 'la-eye-slash' : 'la-eye'}`} /> {g.active ? t('Disable') : t('Enable')}
                                </ConfirmButton>
                            </div>
                        ),
                    },
                ]}
            />
        </>
    );
}

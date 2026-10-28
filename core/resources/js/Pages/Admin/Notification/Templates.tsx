import { Link } from '@inertiajs/react';
import DataTable from '@/Components/Admin/DataTable';
import { NotificationNav, type NotificationTabs } from '@/Components/Admin/NotificationNav';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';

type Template = { id: number; name: string; subject: string; email: boolean; sms: boolean; push: boolean };

type Props = { templates: Template[]; urls: { tabs: NotificationTabs; edit: string } };

/** Notification Setting → Notification Templates: each notification, with its email / SMS / push template and whether it is sent. */
export default function Templates({ templates, urls }: Props) {
    const t = useTrans();
    const edit = (type: string, id: number) => urls.edit.replace(':type', type).replace(':id', String(id));
    const button = (tpl: Template, type: 'email' | 'sms' | 'push', label: string, color: string) => (
        <div className="btn-group btn-group-sm">
            <Link href={edit(type, tpl.id)} className={`btn btn-outline--${color}`}>
                {label}
            </Link>
            <span className={`btn btn--${color}`} title={tpl[type] ? t('Sent') : t('Not sent')} aria-label={tpl[type] ? t('Sent') : t('Not sent')}>
                <i className={`las ${tpl[type] ? 'la-check' : 'la-times'}`} />
            </span>
        </div>
    );

    return (
        <>
            <PageHeader title={t('Notification Templates')} />
            <NotificationNav tabs={urls.tabs} active="templates" />
            <DataTable
                responsive="sm"
                rows={templates}
                rowKey={(x) => x.id}
                empty={t('No data found')}
                columns={[
                    { label: t('Name'), cell: (x) => x.name },
                    { label: t('Subject'), cell: (x) => x.subject },
                    {
                        label: t('Edit Template'),
                        cell: (x) => (
                            <div className="d-flex flex-wrap gap-1 justify-content-end">
                                {button(x, 'email', t('Email'), 'primary')}
                                {button(x, 'sms', t('SMS'), 'info')}
                                {button(x, 'push', t('Push'), 'success')}
                            </div>
                        ),
                    },
                ]}
            />
        </>
    );
}

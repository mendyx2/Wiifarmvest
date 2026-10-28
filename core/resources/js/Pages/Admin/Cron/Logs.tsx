import { Link } from '@inertiajs/react';
import DataTable from '@/Components/Admin/DataTable';
import { ConfirmButton, withId } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';
import type { Paginated } from '@/types';

type Log = { id: number; start: string; end: string; duration: number; error: string | null };

type Props = { title: string; logs: Paginated<Log>; urls: { resolve: string; flush: string; jobs: string } };

/** One cron job's runs, with errors to mark as resolved. */
export default function CronLogs({ title, logs, urls }: Props) {
    const t = useTrans();
    return (
        <>
            <PageHeader title={title}>
                <ConfirmButton url={urls.flush} question={t('Are you sure to flush all logs?')} className="btn btn-outline--danger">
                    <i className="la la-history" /> {t('Flush Logs')}
                </ConfirmButton>
                <Link href={urls.jobs} className="btn btn-outline--dark">
                    <i className="las la-undo" /> {t('Cron Jobs')}
                </Link>
            </PageHeader>
            <DataTable
                rows={logs.data}
                rowKey={(l) => l.id}
                links={logs.links}
                empty={t('No log yet')}
                columns={[
                    { label: t('Start At'), cell: (l) => l.start },
                    { label: t('End At'), cell: (l) => l.end },
                    { label: t('Execution Time'), cell: (l) => `${l.duration} ${t('Seconds')}` },
                    { label: t('Error'), cell: (l) => (l.error ? <span className="text--danger text-break">{l.error}</span> : '—') },
                    {
                        label: t('Actions'),
                        cell: (l) =>
                            l.error ? (
                                <ConfirmButton url={withId(urls.resolve, l.id)} question={t('Are you sure to resolved this log?')} className="btn btn-sm btn-outline--success">
                                    <i className="la la-check" /> {t('Resolved')}
                                </ConfirmButton>
                            ) : (
                                '—'
                            ),
                    },
                ]}
            />
        </>
    );
}

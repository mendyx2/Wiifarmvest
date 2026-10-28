import { Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { WhenCell, type When } from '@/Components/Admin/Cells';
import DataTable from '@/Components/Admin/DataTable';
import { ConfirmButton, DialogHeader, FieldError, withId } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import Modal from '@/Components/Modal';
import { useCopy } from '@/lib/clipboard';
import { useTrans } from '@/lib/i18n';

type JobForm = { id?: number; name: string; next_run: string; cron_schedule_id: string; url: string };
type Job = { id: number; name: string; alias: string; schedule: string; nextRun: When; lastRun: When; running: boolean; default: boolean; errors: number; form: JobForm & { id: number } };

type Props = {
    crons: Job[];
    schedules: { id: number; name: string }[];
    command: string;
    secretSet: boolean;
    urls: { store: string; update: string; delete: string; run: string; pause: string; logs: string; schedules: string };
};

/** Cron Jobs: the scheduled jobs, run now, pause / play, edit, logs. */
export default function CronJobs({ crons, schedules, command, secretSet, urls }: Props) {
    const t = useTrans();
    // null: closed; 0: adding; otherwise the job being edited
    const [editing, setEditing] = useState<Job | 0 | null>(null);
    const { copied, copy } = useCopy();

    return (
        <>
            <PageHeader title={t('Cron Jobs')}>
                <button type="button" className="btn btn-outline--primary" onClick={() => setEditing(0)}>
                    <i className="las la-plus" /> {t('Add')}
                </button>
                <Link href={urls.schedules} className="btn btn-outline--info">
                    <i className="las la-clock" /> {t('Cron Schedule')}
                </Link>
            </PageHeader>
            <div className="card bl--5 border--primary mb-4">
                <div className="card-body">
                    <p className="mb-2">{t("Set this command in your server's crontab to run every minute:")}</p>
                    <div className="input-group">
                        <input className="form-control font-monospace" readOnly value={command} aria-label={t('Cron command')} />
                        <button type="button" className="input-group-text" aria-label={t('Copy')} onClick={() => copy(command)}>
                            <i className={copied ? 'las la-check' : 'las la-copy'} />
                        </button>
                    </div>
                    {secretSet ? (
                        <small className="text-muted">{t('Replace <CRON_SECRET> with the CRON_SECRET value from the server environment.')}</small>
                    ) : (
                        <small className="text--danger">{t('CRON_SECRET is not set on the server, so the cron URL refuses every call. Set it in the environment.')}</small>
                    )}
                </div>
            </div>
            <DataTable
                rows={crons}
                rowKey={(c) => c.id}
                empty={t('No cron job found')}
                columns={[
                    {
                        label: t('Name'),
                        cell: (c) => (
                            <>
                                {c.name}{' '}
                                {c.errors > 0 && (
                                    <i className="las la-exclamation-triangle text--danger" title={t(':n run(s) with an error', { n: c.errors })} aria-label={t(':n run(s) with an error', { n: c.errors })} />
                                )}
                                <br />
                                <code>{c.alias}</code>
                            </>
                        ),
                    },
                    { label: t('Schedule'), cell: (c) => c.schedule },
                    { label: t('Next Run'), cell: (c) => (c.nextRun ? <WhenCell when={c.nextRun} /> : '—') },
                    { label: t('Last Run'), cell: (c) => (c.lastRun ? <WhenCell when={c.lastRun} /> : t('Never')) },
                    { label: t('Is Running'), cell: (c) => <span className={`badge ${c.running ? 'badge--success' : 'badge--dark'}`}>{c.running ? t('Running') : t('Pause')}</span> },
                    { label: t('Type'), cell: (c) => <span className={`badge ${c.default ? 'badge--success' : 'badge--primary'}`}>{c.default ? t('Default') : t('Customizable')}</span> },
                    {
                        label: t('Actions'),
                        cell: (c) => (
                            <div className="d-flex flex-wrap gap-1 justify-content-end">
                                <ConfirmButton url={withId(urls.run, c.id)} question={t('Run :name now?', { name: c.name })} className="btn btn-sm btn-outline--success" title={t('Run Now')}>
                                    <i className="las la-play-circle" /> {t('Run Now')}
                                </ConfirmButton>
                                <ConfirmButton
                                    url={withId(urls.pause, c.id)}
                                    data={{ state: c.running ? 'disable' : 'enable' }}
                                    question={c.running ? t('Pause :name? It will not run until you play it again.', { name: c.name }) : t('Play :name again?', { name: c.name })}
                                    className="btn btn-sm btn-outline--warning"
                                >
                                    <i className={`las ${c.running ? 'la-pause' : 'la-play'}`} /> {c.running ? t('Pause') : t('Play')}
                                </ConfirmButton>
                                <button type="button" className="btn btn-sm btn-outline--primary" onClick={() => setEditing(c)}>
                                    <i className="las la-pen" /> {t('Edit')}
                                </button>
                                <Link href={withId(urls.logs, c.id)} className="btn btn-sm btn-outline--info">
                                    <i className="las la-history" /> {t('Logs')}
                                </Link>
                                {!c.default && (
                                    <ConfirmButton url={withId(urls.delete, c.id)} question={t('Are you sure to delete this cron?')} className="btn btn-sm btn-outline--danger">
                                        <i className="las la-trash" /> {t('Delete')}
                                    </ConfirmButton>
                                )}
                            </div>
                        ),
                    },
                ]}
            />
            {editing !== null && <JobDialog job={editing || null} schedules={schedules} url={editing ? urls.update : urls.store} onClose={() => setEditing(null)} />}
        </>
    );
}

function JobDialog({ job, schedules, url, onClose }: { job: Job | null; schedules: { id: number; name: string }[]; url: string; onClose: () => void }) {
    const t = useTrans();
    const form = useForm<JobForm>(job ? job.form : { name: '', next_run: '', cron_schedule_id: String(schedules[0]?.id ?? ''), url: '' });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, onSuccess: onClose });
    };
    const builtIn = job?.default ?? false;
    return (
        <Modal open onClose={onClose} labelledBy="cron-title">
            <DialogHeader id="cron-title" title={job ? t('Edit Cron Job') : t('Add Cron Job')} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    <div className="form-group">
                        <label htmlFor="cron-name">{t('Name')}</label>
                        <input id="cron-name" className="form-control" required value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                        <FieldError message={form.errors.name} />
                    </div>
                    <div className="form-group">
                        <label htmlFor="cron-next">{t('Next Run')}</label>
                        <input id="cron-next" type="datetime-local" className="form-control" required value={form.data.next_run} onChange={(e) => form.setData('next_run', e.target.value)} />
                        <FieldError message={form.errors.next_run} />
                    </div>
                    <div className="form-group">
                        <label htmlFor="cron-schedule">{t('Schedule')}</label>
                        <select id="cron-schedule" className="form-control" required value={form.data.cron_schedule_id} onChange={(e) => form.setData('cron_schedule_id', e.target.value)}>
                            {schedules.map((s) => (
                                <option key={s.id} value={s.id}>
                                    {s.name}
                                </option>
                            ))}
                        </select>
                        <FieldError message={form.errors.cron_schedule_id} />
                    </div>
                    {!builtIn && (
                        <div className="form-group">
                            <label htmlFor="cron-url">{t('Url')}</label>
                            <input id="cron-url" type="url" className="form-control" required placeholder="https://" value={form.data.url} onChange={(e) => form.setData('url', e.target.value)} />
                            <FieldError message={form.errors.url} />
                        </div>
                    )}
                </div>
                <div className="modal-footer">
                    <button type="submit" className="btn btn--primary h-45 w-100" disabled={form.processing}>
                        {t('Submit')}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

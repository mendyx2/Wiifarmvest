import { Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import DataTable from '@/Components/Admin/DataTable';
import { ConfirmButton, DialogHeader, FieldError, withId } from '@/Components/Admin/Form';
import { StatusBadge } from '@/Components/Admin/GatewayTabs';
import PageHeader from '@/Components/Admin/PageHeader';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';
import type { Paginated } from '@/types';

type Schedule = { id: number; name: string; interval: number; active: boolean };

type Props = { schedules: Paginated<Schedule>; urls: { store: string; status: string; jobs: string } };

const human = (s: number) => (s % 86400 === 0 ? `${s / 86400}d` : s % 3600 === 0 ? `${s / 3600}h` : s % 60 === 0 ? `${s / 60}m` : `${s}s`);

/** Cron Schedules: how often jobs run (an interval in seconds). */
export default function Schedules({ schedules, urls }: Props) {
    const t = useTrans();
    const [editing, setEditing] = useState<Schedule | 0 | null>(null);
    return (
        <>
            <PageHeader title={t('Cron Schedules')}>
                <button type="button" className="btn btn-outline--primary" onClick={() => setEditing(0)}>
                    <i className="las la-plus" /> {t('Add New')}
                </button>
                <Link href={urls.jobs} className="btn btn-outline--dark">
                    <i className="las la-undo" /> {t('Cron Jobs')}
                </Link>
            </PageHeader>
            <DataTable
                rows={schedules.data}
                rowKey={(s) => s.id}
                links={schedules.links}
                empty={t('No schedule found')}
                columns={[
                    { label: t('Name'), cell: (s) => s.name },
                    { label: t('Interval'), cell: (s) => `${s.interval} ${t('Seconds')} (${human(s.interval)})` },
                    { label: t('Status'), cell: (s) => <StatusBadge active={s.active} /> },
                    {
                        label: t('Actions'),
                        cell: (s) => (
                            <div className="button--group">
                                <button type="button" className="btn btn-sm btn-outline--primary" onClick={() => setEditing(s)}>
                                    <i className="las la-pen" /> {t('Edit')}
                                </button>
                                <ConfirmButton
                                    url={withId(urls.status, s.id)}
                                    data={{ state: s.active ? 'disable' : 'enable' }}
                                    question={s.active ? t('Are you sure to disable this schedule?') : t('Are you sure to enable this schedule?')}
                                    className={`btn btn-sm ${s.active ? 'btn-outline--danger' : 'btn-outline--success'}`}
                                >
                                    <i className={`la ${s.active ? 'la-eye-slash' : 'la-eye'}`} /> {s.active ? t('Disable') : t('Enable')}
                                </ConfirmButton>
                            </div>
                        ),
                    },
                ]}
            />
            {editing !== null && <ScheduleDialog schedule={editing || null} url={urls.store} onClose={() => setEditing(null)} />}
        </>
    );
}

function ScheduleDialog({ schedule, url, onClose }: { schedule: Schedule | null; url: string; onClose: () => void }) {
    const t = useTrans();
    const form = useForm({ id: schedule?.id ?? '', name: schedule?.name ?? '', interval: schedule ? String(schedule.interval) : '' });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, onSuccess: onClose });
    };
    return (
        <Modal open onClose={onClose} labelledBy="sch-title">
            <DialogHeader id="sch-title" title={schedule ? t('Update Schedule') : t('Add Schedule')} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    <div className="form-group">
                        <label htmlFor="sch-name">{t('Name')}</label>
                        <input id="sch-name" className="form-control" required value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                        <FieldError message={form.errors.name} />
                    </div>
                    <div className="form-group">
                        <label htmlFor="sch-interval">{t('Interval')}</label>
                        <div className="input-group">
                            <input id="sch-interval" type="number" min="1" className="form-control" required value={form.data.interval} onChange={(e) => form.setData('interval', e.target.value)} />
                            <span className="input-group-text">{t('Seconds')}</span>
                        </div>
                        <FieldError message={form.errors.interval} />
                    </div>
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

import { router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState, type FormEvent } from 'react';
import BoxWidget from '@/Components/Admin/BoxWidget';
import type { Channel } from '@/Components/Admin/ChannelPicker';
import NotificationFields, { Error, type MessageData } from '@/Components/Admin/NotificationFields';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';
import './NotifyAll.css';

type Run = { via: Channel; subject: string | null; audience: string; total: number; sent: number; batch: number; cooling: number };

type Props = {
    channels: Channel[];
    audiences: { value: string; label: string }[];
    activeUsers: number;
    /** A run in progress (kept in the session), or null */
    run: Run | null;
    urls: { send: string; count: string; users: string };
};

type Member = { id: number; username: string; email: string; fullname: string };

type Form = MessageData & {
    being_sent_to: string;
    user: number[];
    number_of_top_deposited_user: string;
    number_of_days: string;
    start: string;
    batch: string;
    cooling_time: string;
};

const VIA_NAME: Record<Channel, string> = { email: 'Email', sms: 'SMS', push: 'Push notification' };

/**
 * Users → Send Notification (/admin/users/send-notification): one message
 * to a group of members, sent in batches with a pause between them. The
 * server keeps the run in the session; this page waits the cooling period,
 * then asks for the next batch until all are sent.
 */
export default function NotifyAll(props: Props) {
    const t = useTrans();
    return (
        <>
            <PageHeader title={t('Notification to Verified Users')} />
            {props.run ? <Progress run={props.run} url={props.urls.send} /> : <Compose {...props} />}
        </>
    );
}

function Compose({ channels, audiences, activeUsers, urls }: Props) {
    const t = useTrans();
    const form = useForm<Form>({
        via: channels[0],
        subject: '',
        message: '',
        image: null,
        being_sent_to: audiences[0]?.value ?? '',
        user: [],
        number_of_top_deposited_user: '10',
        number_of_days: '10',
        start: '1',
        batch: '50',
        cooling_time: '10',
    });
    const audience = form.data.being_sent_to;
    const count = useAudienceCount(urls.count, audience);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(urls.send, { forceFormData: true, preserveScroll: true });
    };
    const set = (next: Partial<Form>) => form.setData({ ...form.data, ...next });
    const errors = form.errors as Partial<Record<string, string>>;

    return (
        <div className="card">
            <form onSubmit={submit}>
                <div className="card-body">
                    <NotificationFields channels={channels} data={form.data} errors={form.errors} onChange={set} />

                    <div className="form-group">
                        <label htmlFor="notify-audience" className="required">
                            {t('Being Sent To')}
                        </label>
                        <select id="notify-audience" className="form-control" value={audience} onChange={(e) => set({ being_sent_to: e.target.value })}>
                            {audiences.map((a) => (
                                <option key={a.value} value={a.value}>
                                    {a.label}
                                </option>
                            ))}
                        </select>
                        <small className="text--info d-block mt-1" aria-live="polite">
                            <i className="las la-info-circle" />{' '}
                            {audience === 'allUsers'
                                ? t(':count active members', { count: activeUsers })
                                : count === undefined
                                  ? t('Counting…')
                                  : count === null
                                    ? t('Counted when sending starts')
                                    : t(':count active members found to send the notification', { count })}
                        </small>
                        <Error message={errors.being_sent_to} />
                    </div>

                    {audience === 'selectedUsers' && <MemberPicker url={urls.users} value={form.data.user} onChange={(user) => set({ user })} error={errors.user} />}
                    {audience === 'topDepositedUsers' && (
                        <NumberField id="notify-top" label={t('Number Of Top Deposited User')} value={form.data.number_of_top_deposited_user} min={1} onChange={(v) => set({ number_of_top_deposited_user: v })} error={errors.number_of_top_deposited_user} />
                    )}
                    {audience === 'notLoginUsers' && (
                        <NumberField id="notify-days" label={t('Number Of Days')} unit={t('Days')} value={form.data.number_of_days} min={0} onChange={(v) => set({ number_of_days: v })} error={errors.number_of_days} />
                    )}

                    <div className="row">
                        <div className="col-md-4">
                            <NumberField id="notify-start" label={t('Start From')} value={form.data.start} min={1} onChange={(v) => set({ start: v })} error={errors.start} hint={t('1 = the first member of the group')} />
                        </div>
                        <div className="col-md-4">
                            <NumberField id="notify-batch" label={t('Per Batch')} unit={t('User')} value={form.data.batch} min={1} max={1000} onChange={(v) => set({ batch: v })} error={errors.batch} />
                        </div>
                        <div className="col-md-4">
                            <NumberField id="notify-cooling" label={t('Cooling Period')} unit={t('Seconds')} value={form.data.cooling_time} min={1} max={3600} onChange={(v) => set({ cooling_time: v })} error={errors.cooling_time} />
                        </div>
                    </div>
                </div>
                <div className="card-footer">
                    <button className="btn w-100 h-45 btn--primary" type="submit" disabled={form.processing}>
                        {t('Submit')}
                    </button>
                </div>
            </form>
        </div>
    );
}

/** Members in an audience, for the hint under the select: undefined while loading, null when it depends on more input. */
function useAudienceCount(url: string, audience: string) {
    const [counts, setCounts] = useState<Record<string, number | null>>({});
    useEffect(() => {
        if (!audience || audience === 'allUsers' || audience in counts) return;
        const ctl = new AbortController();
        fetch(url.replace(':audience', encodeURIComponent(audience)), { headers: { Accept: 'application/json' }, signal: ctl.signal })
            .then((r) => (r.ok ? r.json() : { count: null }))
            .then((r: { count: number | null }) => setCounts((c) => ({ ...c, [audience]: r.count })))
            .catch(() => undefined);
        return () => ctl.abort();
    }, [url, audience, counts]);
    return counts[audience];
}

function NumberField(props: { id: string; label: string; value: string; onChange: (v: string) => void; min?: number; max?: number; unit?: string; error?: string; hint?: string }) {
    const input = <input id={props.id} className="form-control" type="number" required min={props.min} max={props.max} value={props.value} onChange={(e) => props.onChange(e.target.value)} />;
    return (
        <div className="form-group">
            <label htmlFor={props.id} className="required">
                {props.label}
            </label>
            {props.unit ? (
                <div className="input-group">
                    {input}
                    <span className="input-group-text">{props.unit}</span>
                </div>
            ) : (
                input
            )}
            {props.hint && <small className="text-muted">{props.hint}</small>}
            <Error message={props.error} />
        </div>
    );
}

/** "Selected Users": search active members by username or email and tick them. */
function MemberPicker({ url, value, onChange, error }: { url: string; value: number[]; onChange: (ids: number[]) => void; error?: string }) {
    const t = useTrans();
    const [search, setSearch] = useState('');
    const [results, setResults] = useState<Member[]>([]);
    const [more, setMore] = useState(false);
    const [loading, setLoading] = useState(false);
    const [chosen, setChosen] = useState<Member[]>([]);

    useEffect(() => {
        const ctl = new AbortController();
        const timer = setTimeout(() => {
            setLoading(true);
            fetch(`${url}?search=${encodeURIComponent(search.trim())}`, { headers: { Accept: 'application/json' }, signal: ctl.signal })
                .then((r) => r.json())
                .then((r: { users: { data: Member[] }; more: boolean }) => {
                    setResults(r.users.data);
                    setMore(r.more);
                })
                .catch(() => undefined)
                .finally(() => setLoading(false));
        }, 350);
        return () => {
            clearTimeout(timer);
            ctl.abort();
        };
    }, [url, search]);

    // Keep the chosen list in step with the form (it is cleared on reset).
    const picked = chosen.filter((m) => value.includes(m.id));
    const toggle = (m: Member) => {
        if (value.includes(m.id)) {
            onChange(value.filter((id) => id !== m.id));
        } else {
            setChosen([...picked, m]);
            onChange([...value, m.id]);
        }
    };

    return (
        <div className="form-group member-picker">
            <label htmlFor="notify-member-search" className="required">
                {t('Select User')}
            </label>
            {picked.length > 0 && (
                <div className="member-picker__chosen">
                    {picked.map((m) => (
                        <span key={m.id} className="member-picker__chip">
                            {m.username}
                            <button type="button" aria-label={t('Remove :name', { name: m.username })} onClick={() => toggle(m)}>
                                ×
                            </button>
                        </span>
                    ))}
                </div>
            )}
            <input id="notify-member-search" type="search" className="form-control" placeholder={t('Search by username or email')} value={search} onChange={(e) => setSearch(e.target.value)} />
            <ul className="member-picker__list" aria-busy={loading}>
                {results.map((m) => (
                    <li key={m.id}>
                        <label>
                            <input type="checkbox" checked={value.includes(m.id)} onChange={() => toggle(m)} />
                            <span>
                                <strong>{m.username}</strong> <small>{m.email}</small>
                            </span>
                        </label>
                    </li>
                ))}
                {!loading && results.length === 0 && <li className="member-picker__empty">{t('No member found')}</li>}
                {more && <li className="member-picker__empty">{t('More members match: type more of the username or email.')}</li>}
            </ul>
            <Error message={error} />
        </div>
    );
}

/** A run in progress: counts down the cooling period, then asks for the next batch. */
function Progress({ run, url }: { run: Run; url: string }) {
    const t = useTrans();
    const [left, setLeft] = useState(run.cooling);
    const [busy, setBusy] = useState(false);
    const leaving = useRef(false);
    const via = t(VIA_NAME[run.via]);

    // A new batch result restarts the clock.
    useEffect(() => setLeft(run.cooling), [run.sent, run.cooling]);

    useEffect(() => {
        if (busy) return;
        if (left <= 0) {
            setBusy(true);
            leaving.current = true;
            router.post(url, { continue: 1 }, {
                preserveScroll: true,
                // Wait a full period again even if the batch failed, rather than retrying at once.
                onFinish: () => {
                    leaving.current = false;
                    setLeft(run.cooling);
                    setBusy(false);
                },
            });
            return;
        }
        const timer = setTimeout(() => setLeft((s) => s - 1), 1000);
        return () => clearTimeout(timer);
    }, [left, busy, url, run.cooling]);

    // Leaving stops the run: warn first (closing the tab, or a link in the panel).
    useEffect(() => {
        const unload = (e: BeforeUnloadEvent) => {
            if (!leaving.current) e.preventDefault();
        };
        window.addEventListener('beforeunload', unload);
        const off = router.on('before', (e) => {
            if (leaving.current || e.detail.visit.url.pathname === new URL(url, location.href).pathname) return;
            if (!window.confirm(t('Notifications are still being sent. Leave this page and pause sending?'))) e.preventDefault();
        });
        return () => {
            window.removeEventListener('beforeunload', unload);
            off();
        };
    }, [url, t]);

    const cancel = () => {
        if (!window.confirm(t('Stop sending? Members not reached yet will not get this message.'))) return;
        leaving.current = true;
        router.post(url, { cancel: 1 }, { onFinish: () => (leaving.current = false) });
    };

    return (
        <div className="row mb-4 justify-content-center notify-run">
            <div className="col-lg-8">
                <div className="row gy-4 justify-content-center">
                    <div className="col-sm-6">
                        <BoxWidget outline bg="primary" icon="las la-list" title={t(':via should be sent', { via })} value={String(run.total)} />
                    </div>
                    <div className="col-sm-6">
                        <BoxWidget outline bg="success" icon="las la-check-circle" title={t(':via has been sent', { via })} value={String(run.sent)} />
                    </div>
                    <div className="col-sm-6">
                        <BoxWidget outline bg="warning" icon="las la-paper-plane" title={t(':via has yet to be sent', { via })} value={String(run.total - run.sent)} />
                    </div>
                    <div className="col-sm-6">
                        <BoxWidget outline bg="primary" icon="las la-envelope" title={t(':via per batch', { via })} value={String(run.batch)} />
                    </div>
                    <div className="col-12">
                        <div className="card">
                            <div className="card-body p-5 text-center">
                                <p className="mb-3">
                                    <strong>{run.subject ?? via}</strong> → {run.audience}
                                </p>
                                <div className="notify-countdown" role="timer" aria-live="off">
                                    <svg viewBox="0 0 100 100" aria-hidden="true">
                                        <circle r="45" cx="50" cy="50" style={{ strokeDashoffset: `${314 - (314 * left) / Math.max(run.cooling, 1)}px` }} />
                                    </svg>
                                    <span className="notify-countdown__count">{busy ? <i className="las la-spinner la-spin" /> : left}</span>
                                </div>
                                <p className="mt-3" aria-live="polite">
                                    {busy ? t('Sending the next batch…') : t('The next batch goes out in :s seconds. Keep this page open.', { s: left })}
                                </p>
                                <div className="progress my-3" role="progressbar" aria-valuemin={0} aria-valuemax={run.total} aria-valuenow={run.sent}>
                                    <div className="progress-bar bg--success" style={{ width: `${(100 * run.sent) / Math.max(run.total, 1)}%` }} />
                                </div>
                                <p className="text--primary">{t(':sent out of :total sent', { sent: run.sent, total: run.total })}</p>
                                <button type="button" className="btn btn-outline--danger mt-2" onClick={cancel} disabled={busy}>
                                    <i className="las la-stop-circle" /> {t('Stop Sending')}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

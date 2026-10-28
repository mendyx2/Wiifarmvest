import { Link, useForm } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';
import BoxWidget from '@/Components/Admin/BoxWidget';
import type { AdminUser, Badge } from '@/Components/Admin/Cells';
import { ConfirmButton, DialogHeader, FieldError } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import StockistTopUp from '@/Components/Admin/StockistTopUp';
import Modal from '@/Components/Modal';
import Pagination from '@/Components/Pagination';
import { useTrans } from '@/lib/i18n';
import type { Paginated } from '@/types';

type Stockist = {
    id: number;
    name: string;
    user: AdminUser;
    state: string | null;
    mega: boolean;
    active: boolean;
    reason: string | null;
    wallet: string;
    walletRaw: number;
    storeType: string;
    phone: string;
    email: string;
    registration: string;
    description: string;
    verified: string | boolean;
    since: string;
};
type Owner = { fullname: string; username: string; url: string; email: string; mobile: string; joined: string; balance: string; rebate: string };
type Sum = { total: string; n: number; units: number };
type Numbers = {
    units: string;
    products: number;
    low: number;
    out: number;
    value: string;
    redeemed: Sum;
    sent: Sum & { rebate: string };
    received: Sum;
    pending: number;
    transfers: number;
    sentAll: number;
    receivedAll: number;
};
type Stock = { id: number; name: string; sku: string | null; image: string; quantity: number; level: 'ok' | 'low' | 'out'; min: number; price: string | null; value: string | null; restocked: string | null };
type Transfer = { id: number; code: string; date: string; out: boolean; other: { name: string; username: string | null }; units: number; products: number; amount: string; rebate: string | null; status: Badge; statusLabel: string; url: string };
type Activity = { date: string; label: string; note: string | null; plus: boolean; amount: string; balance: string; ref: string; source: string };
type Tab = 'overview' | 'inventory' | 'transfers' | 'activity';

type Props = {
    title: string;
    stockist: Stockist;
    owner: Owner | null;
    numbers: Numbers;
    inventory: Stock[];
    transfers: Paginated<Transfer>;
    direction: string;
    activity: Activity[];
    tab: Tab;
    topup: boolean;
    urls: { topup: string; status: string; transfers: string; report: string; self: string; dashboard: string };
};

const TABS: Tab[] = ['overview', 'inventory', 'transfers', 'activity'];
const plural = (n: number, word: string) => `${n.toLocaleString()} ${word}${n === 1 ? '' : 's'}`;

/** One stockist: business and owner, stock, transfers with other stockists, and wallet activity. */
export default function StockistDetails(props: Props) {
    const t = useTrans();
    const { stockist: s, owner, numbers: n, urls } = props;
    const [tab, setTab] = useState<Tab>(() => {
        const hash = typeof location !== 'undefined' ? (location.hash.slice(1) as Tab) : props.tab;
        return TABS.includes(hash) ? hash : props.tab;
    });
    const [topUp, setTopUp] = useState(props.topup);
    const [deactivating, setDeactivating] = useState(false);
    const initials = s.name.split(/\s+/).filter(Boolean).slice(0, 2).map((w) => w[0]).join('').toUpperCase() || 'S';

    // A transfer page link or direction filter lands on the Transfers tab.
    useEffect(() => setTab((cur) => (props.tab === 'transfers' ? 'transfers' : cur)), [props.tab, props.transfers.current_page, props.direction]);
    const open = (name: Tab) => {
        setTab(name);
        history.replaceState(history.state, '', `${location.pathname}${location.search}#${name}`);
    };

    return (
        <div className="db-wrap asd">
            <PageHeader title={props.title}>
                <Link className="btn btn-sm btn-outline--dark" href={urls.dashboard}>
                    <i className="las la-undo" /> {t('Back')}
                </Link>
            </PageHeader>

            <section className="asd-hero">
                <div className="asd-hero__id">
                    <span className="asd-hero__avatar">{initials}</span>
                    <div>
                        <p className="asd-hero__tags">
                            <span className={`asd-tier ${s.mega ? 'asd-tier--mega' : ''}`}>{s.mega ? t('Mega stockist') : t('Mini stockist')}</span>
                            <span className={`asd-state ${s.active ? '' : 'is-off'}`}>
                                <i className="las la-circle" /> {s.active ? t('Active') : t('Inactive')}
                            </span>
                        </p>
                        <h2 className="asd-hero__name">{s.name}</h2>
                        <p className="asd-hero__meta">
                            <span>
                                <i className="las la-map-marker" /> {s.state ?? t('No state')}
                            </span>
                            {owner && (
                                <span>
                                    <i className="las la-user" /> <Link href={owner.url}>{owner.fullname}</Link> @{owner.username}
                                </span>
                            )}
                            <span>
                                <i className="las la-calendar" /> {t('Stockist since :date', { date: s.since })}
                            </span>
                        </p>
                        {!s.active && s.reason && <p className="asd-hero__meta text--danger mb-0">{t('Reason')}: {s.reason}</p>}
                    </div>
                </div>
                <div className="asd-hero__actions">
                    <button type="button" className="asd-btn asd-btn--primary" onClick={() => setTopUp(true)}>
                        <i className="las la-wallet" /> {t('Top up wallet')}
                    </button>
                    <Link href={urls.transfers} className="asd-btn">
                        <i className="las la-exchange-alt" /> {t('All transfers')}
                    </Link>
                    <Link href={urls.report} className="asd-btn">
                        <i className="las la-clipboard-list" /> {t('Redemption report')}
                    </Link>
                    {s.active ? (
                        <button type="button" className="asd-btn" onClick={() => setDeactivating(true)}>
                            <i className="las la-ban" /> {t('Deactivate')}
                        </button>
                    ) : (
                        <ConfirmButton url={urls.status} data={{ state: 'activate' }} className="asd-btn" question={t('Activate :name again?', { name: s.name })}>
                            <i className="las la-check-circle" /> {t('Activate')}
                        </ConfirmButton>
                    )}
                </div>
            </section>

            <div className="row gy-4 fr-boxes">
                <div className="col-xl-3 col-sm-6">
                    <BoxWidget outline bg="success" icon="las la-wallet" title={t('Stockist wallet')} value={s.wallet} note={t('Used to buy stock')} />
                </div>
                <div className="col-xl-3 col-sm-6">
                    <BoxWidget outline bg="primary" icon="las la-boxes" title={t('Units on hand')} value={n.units} note={t(':p products · :l low · :o out', { p: n.products, l: n.low, o: n.out })} />
                </div>
                <div className="col-xl-3 col-sm-6">
                    <BoxWidget outline bg="warning" icon="las la-coins" title={t('Stock value')} value={n.value} note={t('At :state stockist prices', { state: s.state ?? t('state') })} />
                </div>
                <div className="col-xl-3 col-sm-6">
                    {s.mega ? (
                        <BoxWidget outline bg="info" icon="las la-share-square" title={t('Sold to Mini stockists')} value={n.sent.total} note={`${plural(n.sent.n, t('transfer'))} · ${n.sent.rebate} ${t('rebate')}`} />
                    ) : (
                        <BoxWidget outline bg="info" icon="las la-truck-loading" title={t('Bought from Mega stockists')} value={n.received.total} note={plural(n.received.n, t('transfer'))} />
                    )}
                </div>
            </div>

            <div className="asd-tabs" role="tablist" aria-label={t('Stockist sections')}>
                {(
                    [
                        ['overview', 'las la-id-card', t('Overview'), null],
                        ['inventory', 'las la-boxes', t('Inventory'), props.inventory.length],
                        ['transfers', 'las la-exchange-alt', t('Stock transfers'), n.transfers],
                        ['activity', 'las la-receipt', t('Wallet activity'), null],
                    ] as const
                ).map(([key, icon, label, count]) => (
                    <button key={key} type="button" role="tab" id={`tab-btn-${key}`} aria-controls={`tab-${key}`} aria-selected={tab === key} className={`asd-tab ${tab === key ? 'is-active' : ''}`} onClick={() => open(key)}>
                        <i className={icon} /> {label} {count !== null && <span>{count}</span>}
                        {key === 'transfers' && n.pending > 0 && <em title={t('Pending')}>{t(':n pending', { n: n.pending })}</em>}
                    </button>
                ))}
            </div>

            {tab === 'overview' && <Overview stockist={s} owner={owner} numbers={n} />}
            {tab === 'inventory' && <Inventory rows={props.inventory} units={n.units} value={n.value} />}
            {tab === 'transfers' && <Transfers transfers={props.transfers} direction={props.direction} numbers={n} mega={s.mega} self={urls.self} />}
            {tab === 'activity' && <ActivityTab rows={props.activity} />}

            {topUp && <StockistTopUp name={s.name} balance={s.walletRaw} url={urls.topup} onClose={() => setTopUp(false)} />}
            {deactivating && <DeactivateDialog name={s.name} url={urls.status} onClose={() => setDeactivating(false)} />}
        </div>
    );
}

function DeactivateDialog({ name, url, onClose }: { name: string; url: string; onClose: () => void }) {
    const t = useTrans();
    const form = useForm({ state: 'deactivate', status_reason: '' });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, onSuccess: onClose });
    };
    return (
        <Modal open onClose={onClose} labelledBy="deactivate-title">
            <DialogHeader id="deactivate-title" title={t('Deactivate :name', { name })} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    <p className="small text-muted">{t('An inactive stockist is hidden from the member stockist search.')}</p>
                    <div className="form-group">
                        <label htmlFor="deactivate-reason">{t('Reason')}</label>
                        <textarea id="deactivate-reason" className="form-control" rows={3} maxLength={255} required value={form.data.status_reason} onChange={(e) => form.setData('status_reason', e.target.value)} />
                        <FieldError message={form.errors.status_reason} />
                    </div>
                </div>
                <div className="modal-footer">
                    <button type="submit" className="btn btn--danger w-100 h-45" disabled={form.processing || !form.data.status_reason.trim()}>
                        {t('Deactivate')}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

function Facts({ rows }: { rows: [string, React.ReactNode][] }) {
    return (
        <dl className="asd-facts">
            {rows.map(([k, v]) => (
                <div key={k}>
                    <dt>{k}</dt>
                    <dd>{v}</dd>
                </div>
            ))}
        </dl>
    );
}

function Overview({ stockist: s, owner, numbers: n }: { stockist: Stockist; owner: Owner | null; numbers: Numbers }) {
    const t = useTrans();
    return (
        <section className="asd-panel" id="tab-overview" role="tabpanel" aria-labelledby="tab-btn-overview">
            <div className="row gy-4">
                <div className="col-lg-4">
                    <div className="asd-card h-100">
                        <h6 className="asd-card__title">
                            <i className="las la-store" /> {t('Business')}
                        </h6>
                        <Facts
                            rows={[
                                [t('Name'), s.name],
                                [t('Store type'), s.storeType],
                                [t('State'), s.state ?? '—'],
                                [t('Phone'), s.phone ? <a href={`tel:${s.phone}`}>{s.phone}</a> : '—'],
                                [t('Email'), s.email ? <a href={`mailto:${s.email}`}>{s.email}</a> : '—'],
                                [t('Registration no.'), s.registration || t('Not provided')],
                                [t('Verified'), s.verified ? (typeof s.verified === 'string' ? `${t('Yes')}, ${s.verified}` : t('Yes')) : t('No')],
                            ]}
                        />
                        {s.description && <p className="asd-desc">{s.description}</p>}
                    </div>
                </div>
                <div className="col-lg-4">
                    <div className="asd-card h-100">
                        <h6 className="asd-card__title">
                            <i className="las la-user-circle" /> {t('Owner')}
                        </h6>
                        {owner ? (
                            <>
                                <Facts
                                    rows={[
                                        [t('Name'), <Link href={owner.url}>{owner.fullname}</Link>],
                                        [t('Username'), `@${owner.username}`],
                                        [t('Email'), owner.email || '—'],
                                        [t('Mobile'), owner.mobile || '—'],
                                        [t('Member since'), owner.joined],
                                    ]}
                                />
                                <div className="asd-balances">
                                    <div>
                                        <span>{t('Money Box')}</span>
                                        <strong>{owner.balance}</strong>
                                    </div>
                                    <div>
                                        <span>{t('Stockist rebate')}</span>
                                        <strong>{owner.rebate}</strong>
                                    </div>
                                </div>
                            </>
                        ) : (
                            <p className="text-muted mb-0">{t('No member account linked.')}</p>
                        )}
                    </div>
                </div>
                <div className="col-lg-4">
                    <div className="asd-card h-100">
                        <h6 className="asd-card__title">
                            <i className="las la-chart-line" /> {t('Activity')}
                        </h6>
                        <ul className="asd-kpis">
                            <Kpi tone="green" icon="las la-hand-holding-heart" value={n.redeemed.total} note={`${plural(n.redeemed.n, t('redemption'))} & POS · ${plural(n.redeemed.units, t('unit'))}`} />
                            <Kpi tone="blue" icon="las la-share-square" value={n.sent.total} note={`${t('Sold to Mini stockists')} · ${plural(n.sent.n, t('transfer'))} · ${plural(n.sent.units, t('unit'))}`} />
                            <Kpi tone="amber" icon="las la-truck-loading" value={n.received.total} note={`${t('Bought from Mega stockists')} · ${plural(n.received.n, t('transfer'))} · ${plural(n.received.units, t('unit'))}`} />
                            <Kpi tone="rose" icon="las la-hourglass-half" value={String(n.pending)} note={t('Transfers waiting for a Mega stockist')} />
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    );
}

function Kpi({ tone, icon, value, note }: { tone: string; icon: string; value: string; note: string }) {
    return (
        <li>
            <span className={`asd-kpis__icon asd-kpis__icon--${tone}`}>
                <i className={icon} />
            </span>
            <div>
                <strong>{value}</strong>
                <small>{note}</small>
            </div>
        </li>
    );
}

function Inventory({ rows, units, value }: { rows: Stock[]; units: string; value: string }) {
    const t = useTrans();
    return (
        <section className="asd-panel" id="tab-inventory" role="tabpanel" aria-labelledby="tab-btn-inventory">
            <div className="asd-card p-0">
                <div className="table-responsive--md table-responsive">
                    <table className="table table--light style--two mb-0 asd-table">
                        <thead>
                            <tr>
                                <th>{t('Product')}</th>
                                <th>{t('On hand')}</th>
                                <th>{t('Min level')}</th>
                                <th>{t('State price')}</th>
                                <th>{t('Value')}</th>
                                <th>{t('Last restocked')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.length ? (
                                rows.map((r) => (
                                    <tr key={r.id}>
                                        <td className="text-start" data-label={t('Product')}>
                                            <div className="asd-product">
                                                <img src={r.image} alt="" loading="lazy" />
                                                <span>
                                                    <strong>{r.name}</strong>
                                                    <small>SKU {r.sku ?? '—'}</small>
                                                </span>
                                            </div>
                                        </td>
                                        <td data-label={t('On hand')}>
                                            <span className={`asd-qty asd-qty--${r.level}`}>{r.quantity.toLocaleString()}</span>
                                        </td>
                                        <td data-label={t('Min level')}>{r.min.toLocaleString()}</td>
                                        <td data-label={t('State price')}>{r.price ?? '—'}</td>
                                        <td data-label={t('Value')}>
                                            <strong>{r.value ?? '—'}</strong>
                                        </td>
                                        <td data-label={t('Last restocked')}>{r.restocked ?? '—'}</td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={6} className="text-center text-muted py-5">
                                        <i className="las la-box-open d-block fs-1 mb-2" />
                                        {t('No stock yet.')}
                                    </td>
                                </tr>
                            )}
                        </tbody>
                        {rows.length > 0 && (
                            <tfoot>
                                <tr>
                                    <th className="text-start">{t('Total')}</th>
                                    <th>{units}</th>
                                    <th colSpan={2} />
                                    <th>{value}</th>
                                    <th />
                                </tr>
                            </tfoot>
                        )}
                    </table>
                </div>
            </div>
        </section>
    );
}

function Transfers({ transfers, direction, numbers: n, mega, self }: { transfers: Paginated<Transfer>; direction: string; numbers: Numbers; mega: boolean; self: string }) {
    const t = useTrans();
    const tabs: [string, string, number][] = [
        ['', t('All'), n.transfers],
        ['sent', t('Sent to Minis'), n.sentAll],
        ['received', t('Received from Megas'), n.receivedAll],
    ];
    return (
        <section className="asd-panel" id="tab-transfers" role="tabpanel" aria-labelledby="tab-btn-transfers">
            <div className="asd-subbar">
                <nav className="atx-tabs" aria-label={t('Direction')}>
                    {tabs.map(([key, label, count]) => (
                        <Link key={key} href={key ? `${self}?direction=${key}` : self} preserveScroll className={`atx-tab ${direction === key ? 'is-active' : ''}`}>
                            {label} <span>{count}</span>
                        </Link>
                    ))}
                </nav>
                <span className="asd-subbar__hint">{mega ? t('A Mega stockist sends stock to the Mini stockists in its state.') : t('A Mini stockist buys from a Mega stockist in its state.')}</span>
            </div>
            <div className="asd-card p-0">
                <div className="table-responsive--md table-responsive">
                    <table className="table table--light style--two mb-0 asd-table">
                        <thead>
                            <tr>
                                <th>{t('Transfer')}</th>
                                <th>{t('Direction')}</th>
                                <th>{t('Stock')}</th>
                                <th>{t('Amount')}</th>
                                <th>{t('Rebate')}</th>
                                <th>{t('Status')}</th>
                                <th aria-label={t('Action')} />
                            </tr>
                        </thead>
                        <tbody>
                            {transfers.data.length ? (
                                transfers.data.map((r) => (
                                    <tr key={r.id}>
                                        <td data-label={t('Transfer')}>
                                            <span className="atx-code">{r.code}</span>
                                            <small className="d-block text-muted">{r.date}</small>
                                        </td>
                                        <td className="text-start" data-label={t('Direction')}>
                                            <span className={`asd-dir ${r.out ? 'asd-dir--out' : 'asd-dir--in'}`}>
                                                <i className={`las la-${r.out ? 'arrow-up' : 'arrow-down'}`} /> {r.out ? t('Sent') : t('Received')}
                                            </span>
                                            <span className="asd-dir__who">
                                                {r.out ? t('to') : t('from')} <strong>{r.other.name}</strong> {r.other.username && <small>@{r.other.username}</small>}
                                            </span>
                                        </td>
                                        <td data-label={t('Stock')}>
                                            {plural(r.units, t('unit'))}
                                            <small className="d-block text-muted">{plural(r.products, t('product'))}</small>
                                        </td>
                                        <td data-label={t('Amount')}>
                                            <strong className={r.out && r.rebate ? 'atx-plus' : ''}>{r.amount}</strong>
                                        </td>
                                        <td data-label={t('Rebate')}>{r.rebate ?? '—'}</td>
                                        <td data-label={t('Status')}>
                                            <span className={`badge ${r.status.class}`} title={r.statusLabel}>
                                                {r.status.label}
                                            </span>
                                        </td>
                                        <td>
                                            <Link href={r.url} className="btn btn-sm btn-outline--primary">
                                                <i className="las la-eye" /> {t('View')}
                                            </Link>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={7} className="text-center text-muted py-5">
                                        <i className="las la-exchange-alt d-block fs-1 mb-2" />
                                        {direction ? t('Nothing :d yet.', { d: direction }) : t('No stock transfers yet.')}
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                {transfers.last_page > 1 && (
                    <div className="asd-card__foot">
                        <Pagination links={transfers.links} />
                    </div>
                )}
            </div>
        </section>
    );
}

function ActivityTab({ rows }: { rows: Activity[] }) {
    const t = useTrans();
    return (
        <section className="asd-panel" id="tab-activity" role="tabpanel" aria-labelledby="tab-btn-activity">
            <div className="asd-card p-0">
                <div className="table-responsive--md table-responsive">
                    <table className="table table--light style--two mb-0 asd-table">
                        <thead>
                            <tr>
                                <th>{t('Date')}</th>
                                <th>{t('Activity')}</th>
                                <th>{t('Reference')}</th>
                                <th>{t('Amount')}</th>
                                <th>{t('Balance after')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.length ? (
                                rows.map((r, i) => (
                                    <tr key={`${r.ref}-${i}`}>
                                        <td data-label={t('Date')}>{r.date}</td>
                                        <td className="text-start" data-label={t('Activity')}>
                                            {r.label}
                                            {r.note && <small className="d-block text-muted">{r.note}</small>}
                                            <small className="d-block">
                                                <span className="asd-source">{r.source}</span>
                                            </small>
                                        </td>
                                        <td data-label={t('Reference')}>
                                            <code>{r.ref}</code>
                                        </td>
                                        <td data-label={t('Amount')}>
                                            <strong className={r.plus ? 'atx-plus' : 'atx-minus'}>
                                                {r.plus ? '+' : '-'}
                                                {r.amount}
                                            </strong>
                                        </td>
                                        <td data-label={t('Balance after')}>{r.balance}</td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={5} className="text-center text-muted py-5">
                                        <i className="las la-receipt d-block fs-1 mb-2" />
                                        {t('No wallet activity yet.')}
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                <p className="asd-card__foot asd-card__note">{t('Latest 15 entries: admin top-ups, POS sales, redemptions and stock transfers.')}</p>
            </div>
        </section>
    );
}

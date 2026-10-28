import { Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import type { AdminUser } from '@/Components/Admin/Cells';
import Drawer from '@/Components/Admin/Drawer';
import { ConfirmButton, FieldError, withId } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import Pagination from '@/Components/Pagination';
import { useTrans } from '@/lib/i18n';
import type { Paginated } from '@/types';

type Award = { id: number; title: string; requiredPv: number; amount: number; active: boolean; qualified: number; credited: number; url: string };
type Member = { id: number; user: AdminUser; email: string; pv: string; top: string | null; more: number; next: { title: string; percent: number } | null };

type Props = {
    awards: Award[];
    members: Paginated<Member>;
    urls: { store: string; update: string; destroy: string; toggle: string };
};

const money = (n: number) => `₦${n.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const pv = (n: number) => n.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const initials = (u: AdminUser) => (u ? u.fullname.split(/\s+/).map((w) => w[0] ?? '').join('').slice(0, 2).toUpperCase() : 'U');

/** Shop → Repurchase Awards: cash awards for reaching a product-PV total, and the PV leaderboard. */
export default function RepurchaseAwards({ awards, members, urls }: Props) {
    const t = useTrans();
    const [editing, setEditing] = useState<Pick<Award, 'id' | 'title' | 'requiredPv' | 'amount'> | null>(null);
    const firstRank = (members.current_page - 1) * members.per_page + 1;

    return (
        <div className="rpa-page">
            <PageHeader title={t('Repurchase (Unilevel) Awards')}>
                <button type="button" className="btn btn-outline--primary h-45" onClick={() => setEditing({ id: 0, title: '', requiredPv: 0, amount: 0 })}>
                    <i className="las la-plus" /> {t('Add Award')}
                </button>
            </PageHeader>

            <div className="rpa-awards-grid">
                {awards.length ? (
                    awards.map((a) => (
                        <div className={`rpa-award-card ${a.active ? '' : 'rpa-award-card--off'}`} key={a.id}>
                            <div className="rpa-award-card-top">
                                <div className="rpa-award-icon">
                                    <i className="las la-medal" />
                                </div>
                                <div className="rpa-award-meta">
                                    <div className="rpa-award-title">{a.title}</div>
                                    <div className={`rpa-award-status ${a.active ? 'rpa-status-on' : 'rpa-status-off'}`}>{a.active ? t('Active') : t('Inactive')}</div>
                                </div>
                                <div className="rpa-award-card-actions">
                                    <button type="button" className="rpa-card-btn rpa-card-btn-edit" title={t('Edit')} aria-label={t('Edit :name', { name: a.title })} onClick={() => setEditing(a)}>
                                        <i className="las la-pen" />
                                    </button>
                                    <ConfirmButton
                                        url={withId(urls.toggle, a.id)}
                                        data={{ state: a.active ? 'off' : 'on' }}
                                        className={`rpa-card-btn ${a.active ? 'rpa-card-btn-deactivate' : 'rpa-card-btn-activate'}`}
                                        title={a.active ? t('Deactivate') : t('Activate')}
                                        question={a.active ? t('Deactivate :name? It can no longer be paid until you activate it again.', { name: a.title }) : t('Activate :name?', { name: a.title })}
                                    >
                                        <i className={`las ${a.active ? 'la-toggle-on' : 'la-toggle-off'}`} />
                                    </ConfirmButton>
                                    {a.credited === 0 && (
                                        <ConfirmButton url={withId(urls.destroy, a.id)} method="delete" className="rpa-card-btn rpa-card-btn-del" title={t('Delete')} question={t('Delete :name?', { name: a.title })}>
                                            <i className="las la-trash-alt" />
                                        </ConfirmButton>
                                    )}
                                </div>
                            </div>
                            <div className="rpa-award-stats">
                                <div className="rpa-stat">
                                    <span className="rpa-stat-label">{t('Required PV')}</span>
                                    <span className="rpa-stat-val rpa-stat-pv">{pv(a.requiredPv)}</span>
                                </div>
                                <div className="rpa-stat-sep" />
                                <div className="rpa-stat">
                                    <span className="rpa-stat-label">{t('Reward Amount')}</span>
                                    <span className="rpa-stat-val rpa-stat-amt">{money(a.amount)}</span>
                                </div>
                                <div className="rpa-stat-sep" />
                                <div className="rpa-stat">
                                    <span className="rpa-stat-label">{t('Qualified / Paid')}</span>
                                    <Link href={a.url} className="rpa-stat-val rpa-stat-count">
                                        {t(':q users', { q: a.qualified })} · {a.credited}
                                    </Link>
                                </div>
                            </div>
                        </div>
                    ))
                ) : (
                    <div className="rpa-empty-awards">
                        <i className="las la-medal" />
                        <p>{t('No awards configured yet. Add one using the button above.')}</p>
                    </div>
                )}
            </div>

            <div className="rpa-board-card">
                <div className="rpa-board-head">
                    <div className="rpa-board-head-left">
                        <i className="las la-chart-bar" />
                        <span>{t('Member PV Leaderboard')}</span>
                    </div>
                    <span className="rpa-board-count">{t(':n members with PV', { n: members.total })}</span>
                </div>
                <div className="table-responsive">
                    <table className="table rpa-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{t('Member')}</th>
                                <th>{t('Username')}</th>
                                <th className="text-center">{t('Total PV')}</th>
                                <th>{t('Qualifies For')}</th>
                                <th className="text-right">{t('Progress')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {members.data.length ? (
                                members.data.map((m, i) => {
                                    const rank = firstRank + i;
                                    return (
                                        <tr key={m.id}>
                                            <td className="rpa-rank">{rank <= 3 ? <span className={`rpa-rank-medal rpa-rank-${rank}`}>{rank}</span> : <span className="rpa-rank-num">{rank}</span>}</td>
                                            <td>
                                                <div className="rpa-member">
                                                    <div className="rpa-avatar">{initials(m.user)}</div>
                                                    <div>
                                                        <div className="rpa-member-name">{m.user?.fullname ?? '—'}</div>
                                                        <div className="rpa-member-email">{m.email}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="rpa-username">{m.user ? <Link href={m.user.url}>{m.user.username}</Link> : '—'}</td>
                                            <td className="text-center">
                                                <span className="rpa-pv-chip">{m.pv} PV</span>
                                            </td>
                                            <td>
                                                {m.top ? (
                                                    <>
                                                        <span className="rpa-qualified-badge">{m.top}</span>
                                                        {m.more > 0 && <span className="rpa-more-badge">+{m.more} {t('more')}</span>}
                                                    </>
                                                ) : (
                                                    <span className="rpa-none-badge">{t('None yet')}</span>
                                                )}
                                            </td>
                                            <td>
                                                {m.next ? (
                                                    <>
                                                        <div className="rpa-progress-wrap">
                                                            <div className="rpa-progress-bar" role="progressbar" aria-valuenow={m.next.percent} aria-valuemin={0} aria-valuemax={100}>
                                                                <div className="rpa-progress-fill" style={{ width: `${m.next.percent}%` }} />
                                                            </div>
                                                            <span className="rpa-progress-pct">{m.next.percent}%</span>
                                                        </div>
                                                        <div className="rpa-progress-label">
                                                            {t('Next')}: {m.next.title}
                                                        </div>
                                                    </>
                                                ) : (
                                                    <span className="rpa-complete-badge">
                                                        <i className="las la-check-circle" /> {t('All qualified')}
                                                    </span>
                                                )}
                                            </td>
                                        </tr>
                                    );
                                })
                            ) : (
                                <tr>
                                    <td colSpan={6}>
                                        <div className="rpa-table-empty">
                                            <i className="las la-users" />
                                            <p>{t('No members have accumulated PV yet')}</p>
                                        </div>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                {members.last_page > 1 && (
                    <div className="rpa-pagination">
                        <Pagination links={members.links} />
                    </div>
                )}
            </div>

            <Drawer prefix="rpa" open={editing !== null} title={editing?.id ? t('Edit Award') : t('Add Award')} onClose={() => setEditing(null)}>
                {editing && <AwardForm key={editing.id} award={editing} url={editing.id ? withId(urls.update, editing.id) : urls.store} onDone={() => setEditing(null)} />}
            </Drawer>
        </div>
    );
}

function AwardForm({ award, url, onDone }: { award: Pick<Award, 'id' | 'title' | 'requiredPv' | 'amount'>; url: string; onDone: () => void }) {
    const t = useTrans();
    const form = useForm({ title: award.title, required_pv: award.id ? String(award.requiredPv) : '', amount: award.id ? String(award.amount) : '' });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, onSuccess: onDone });
    };
    return (
        <form onSubmit={submit}>
            <div className="rpa-form-field">
                <label className="rpa-form-label" htmlFor="rpa-title">
                    {t('Award Title')} <span className="pf-req">*</span>
                </label>
                <input id="rpa-title" className="rpa-form-input" placeholder={t('e.g. Silver Star')} required maxLength={150} value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} />
                <FieldError message={form.errors.title} />
            </div>
            <div className="rpa-form-field">
                <label className="rpa-form-label" htmlFor="rpa-pv">
                    {t('Required PV')} <span className="pf-req">*</span>
                </label>
                <input id="rpa-pv" className="rpa-form-input" type="number" step="any" min={0.01} placeholder={t('e.g. 5000')} required value={form.data.required_pv} onChange={(e) => form.setData('required_pv', e.target.value)} />
                <p className="rpa-form-hint">{t('Cumulative product PV the member must reach')}</p>
                <FieldError message={form.errors.required_pv} />
            </div>
            <div className="rpa-form-field">
                <label className="rpa-form-label" htmlFor="rpa-amount">
                    {t('Reward Amount (₦)')} <span className="pf-req">*</span>
                </label>
                <div className="pf-input-prefix">
                    <span className="pf-prefix">₦</span>
                    <input id="rpa-amount" className="rpa-form-input" type="number" step="any" min={0.01} placeholder="0.00" required style={{ paddingLeft: 28 }} value={form.data.amount} onChange={(e) => form.setData('amount', e.target.value)} />
                </div>
                <FieldError message={form.errors.amount} />
            </div>
            <button type="submit" className="rpa-form-submit" disabled={form.processing}>
                {t('Save Award')}
            </button>
        </form>
    );
}

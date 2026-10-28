import { Link } from '@inertiajs/react';
import type { AdminUser } from '@/Components/Admin/Cells';
import { ConfirmButton } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import Pagination from '@/Components/Pagination';
import { useTrans } from '@/lib/i18n';
import type { Paginated } from '@/types';

type Member = { id: number; userId: number; user: AdminUser; email: string; pv: string; surplus: string; credited: boolean; creditUrl: string | null };

type Props = {
    award: { id: number; title: string; requiredPv: string; amount: string; active: boolean; credited: number };
    members: Paginated<Member>;
    urls: { index: string };
};

/** The members who reached one award's PV, and the button that pays it. */
export default function RepurchaseQualified({ award, members, urls }: Props) {
    const t = useTrans();
    const first = (members.current_page - 1) * members.per_page + 1;

    return (
        <div className="rpa-page">
            <PageHeader title={t('Qualified Users — :name', { name: award.title })}>
                <Link className="btn btn-sm btn-outline--dark" href={urls.index}>
                    <i className="las la-undo" /> {t('Back to Awards')}
                </Link>
            </PageHeader>

            <div className="rpa-qual-banner">
                <div className="rpa-qual-banner-icon">
                    <i className="las la-medal" />
                </div>
                <div className="rpa-qual-banner-info">
                    <h2 className="rpa-qual-title">{award.title}</h2>
                    <p className="rpa-qual-subtitle">{t('Members who reached :pv PV and qualify for the :amount award', { pv: award.requiredPv, amount: award.amount })}</p>
                </div>
                <div className="rpa-qual-chips">
                    <Chip label={t('Required PV')} value={award.requiredPv} cls="rpa-qual-chip-pv" />
                    <Chip label={t('Reward')} value={award.amount} cls="rpa-qual-chip-amt" />
                    <Chip label={t('Qualified')} value={String(members.total)} cls="rpa-qual-chip-count" />
                    <Chip label={t('Credited')} value={String(award.credited)} cls="rpa-qual-chip-credited" />
                </div>
            </div>

            {!award.active && (
                <div className="alert alert-warning" role="alert">
                    {t('This award is switched off. Activate it on the awards page before paying it.')}
                </div>
            )}

            <div className="rpa-board-card">
                <div className="table-responsive">
                    <table className="table rpa-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{t('Member')}</th>
                                <th>{t('Username')}</th>
                                <th className="text-center">{t('Total PV')}</th>
                                <th className="text-center">{t('Surplus PV')}</th>
                                <th className="text-center">{t('Reward')}</th>
                                <th className="text-center">{t('Status')}</th>
                                <th className="text-right">{t('Action')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {members.data.length ? (
                                members.data.map((m, i) => (
                                    <tr key={m.id} className={m.credited ? 'rpa-row-paid' : ''}>
                                        <td className="rpa-rank">
                                            <span className="rpa-rank-num">{first + i}</span>
                                        </td>
                                        <td>
                                            <div className="rpa-member">
                                                <div className={`rpa-avatar rpa-avatar--${m.credited ? 'paid' : 'pending'}`}>
                                                    {(m.user?.fullname ?? 'U')
                                                        .split(/\s+/)
                                                        .map((w) => w[0] ?? '')
                                                        .join('')
                                                        .slice(0, 2)
                                                        .toUpperCase()}
                                                </div>
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
                                        <td className="text-center">
                                            <span className="rpa-surplus-chip">+{m.surplus}</span>
                                        </td>
                                        <td className="text-center">
                                            <span className="rpa-reward-amount">{award.amount}</span>
                                        </td>
                                        <td className="text-center">
                                            {m.credited ? (
                                                <span className="rpa-paid-badge">
                                                    <i className="las la-check-circle" /> {t('Credited')}
                                                </span>
                                            ) : (
                                                <span className="rpa-pending-pill">{t('Pending')}</span>
                                            )}
                                        </td>
                                        <td className="text-right">
                                            {m.credited ? (
                                                <span className="rpa-credited-label">
                                                    <i className="las la-lock" /> {t('Paid')}
                                                </span>
                                            ) : (
                                                m.creditUrl &&
                                                award.active && (
                                                    <ConfirmButton
                                                        url={m.creditUrl}
                                                        className="rpa-credit-btn"
                                                        question={t("Credit :amount to :name for ':award'?", { amount: award.amount, name: m.user?.fullname ?? '', award: award.title })}
                                                    >
                                                        <i className="las la-coins" /> {t('Credit User')}
                                                    </ConfirmButton>
                                                )
                                            )}
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={8}>
                                        <div className="rpa-table-empty">
                                            <i className="las la-user-times" />
                                            <p>{t('No members have qualified for this award yet')}</p>
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
        </div>
    );
}

function Chip({ label, value, cls }: { label: string; value: string; cls: string }) {
    return (
        <div className="rpa-qual-chip">
            <span className="rpa-qual-chip-label">{label}</span>
            <span className={`rpa-qual-chip-val ${cls}`}>{value}</span>
        </div>
    );
}

import { Head, usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import Modal from '@/Components/Modal';
import Pagination from '@/Components/Pagination';
import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';
import type { PageProps, Paginated, PaymentDetail } from '@/types';

/** Formatted by WithdrawController@withdrawLog. */
type Withdrawal = {
    id: number;
    trx: string;
    gateway: string;
    date: string;
    time: string;
    net: string;
    amount: string;
    charge: string;
    rate: string;
    finalAmount: string;
    status: { label: string; tone: string };
    details: PaymentDetail[];
    adminFeedback: string | null;
};

type Props = PageProps<{
    withdraws: Paginated<Withdrawal>;
    stats: { withdrawn: string; pending: string; rejected: string; requests: number };
    blocked: boolean;
    blockReason: string | null;
    search: string;
    withdrawUrl: string;
}>;

export default function WithdrawHistory({ withdraws, stats, blocked, blockReason, search, withdrawUrl }: Props) {
    const t = useTrans();
    const { url } = usePage();
    const [query, setQuery] = useState(search);
    const [selected, setSelected] = useState<Withdrawal | null>(null);
    // Shown once when the page opens if an admin has suspended withdrawals.
    const [showSuspended, setShowSuspended] = useState(blocked);

    function submit(event: FormEvent) {
        event.preventDefault();
        visitWithQuery(url, { search: query });
    }

    const statCards = [
        { tone: 'green', icon: 'la-check-circle', label: t('Total Withdrawn'), value: stats.withdrawn, sub: t('Approved payouts') },
        { tone: 'amber', icon: 'la-clock', label: t('Pending'), value: stats.pending, sub: t('Awaiting approval') },
        { tone: 'rose', icon: 'la-times-circle', label: t('Rejected'), value: stats.rejected, sub: t('Declined requests') },
        { tone: 'blue', icon: 'la-list-alt', label: t('Total Requests'), value: String(stats.requests), sub: t('All time') },
    ];

    return (
        <div className="nc-wrap" id="ncWrap">
            <Head title={t('Withdrawal Log')} />
            <br />
            <div className="wl-page">
                <div className="wl-stats">
                    {statCards.map((card) => (
                        <div className={`wl-stat wl-stat-${card.tone}`} key={card.tone}>
                            <div className="wl-stat-icon"><i className={`las ${card.icon}`} /></div>
                            <div className="wl-stat-body">
                                <p className="wl-stat-label">{card.label}</p>
                                <h4 className="wl-stat-val">{card.value}</h4>
                                <p className="wl-stat-sub">{card.sub}</p>
                            </div>
                        </div>
                    ))}
                </div>

                <div className="wl-toolbar">
                    <form className="wl-search-form" onSubmit={submit} role="search">
                        <i className="las la-search wl-search-icon" />
                        <input
                            className="wl-search-input"
                            type="search"
                            aria-label={t('Search')}
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            placeholder={t('Search by transaction ID…')}
                        />
                    </form>
                    <a href={withdrawUrl} className="wl-new-btn">
                        <i className="las la-plus" /> {t('Withdraw Now')}
                    </a>
                </div>

                <div className="wl-card">
                    <div className="wl-list-head">
                        <span>{t('Gateway / Trx')}</span>
                        <span>{t('Date')}</span>
                        <span>{t('Amount')}</span>
                        <span>{t('Conversion')}</span>
                        <span>{t('Status')}</span>
                        <span>{t('Detail')}</span>
                    </div>

                    {withdraws.data.length === 0 ? (
                        <div className="wl-empty">
                            <div className="wl-empty-icon"><i className="las la-arrow-circle-up" /></div>
                            <h6>{t('No Withdrawals Yet')}</h6>
                            <p>{t('Your withdrawal requests will appear here')}</p>
                            <a href={withdrawUrl} className="wl-empty-btn">{t('Make a Withdrawal')}</a>
                        </div>
                    ) : (
                        withdraws.data.map((withdraw) => (
                            <div className="wl-row" key={withdraw.id}>
                                <div className="wl-col-gw">
                                    <div className="wl-gw-name">{withdraw.gateway}</div>
                                    <div className="wl-gw-trx">{withdraw.trx}</div>
                                </div>
                                <div className="wl-col-date">
                                    <span className="wl-date-main">{withdraw.date}</span>
                                    <span className="wl-date-sub">{withdraw.time}</span>
                                </div>
                                <div className="wl-col-amt">
                                    <span className="wl-amt-net">{withdraw.net}</span>
                                    <span className="wl-amt-gross">
                                        {withdraw.amount} − <span className="wl-charge">{withdraw.charge}</span>
                                    </span>
                                </div>
                                <div className="wl-col-conv">
                                    <span className="wl-conv-rate">{withdraw.rate}</span>
                                    <span className="wl-conv-final">{withdraw.finalAmount}</span>
                                </div>
                                <div className="wl-col-status">
                                    <span className={`wl-badge wl-badge-${withdraw.status.tone}`}>{t(withdraw.status.label)}</span>
                                </div>
                                <div className="wl-col-action">
                                    <button
                                        type="button"
                                        className="wl-detail-btn"
                                        title={t('View Details')}
                                        aria-label={t('View Details')}
                                        onClick={() => setSelected(withdraw)}
                                    >
                                        <i className="las la-eye" />
                                    </button>
                                </div>
                            </div>
                        ))
                    )}
                </div>

                <Pagination links={withdraws.links} className="wl-pagination" />
            </div>

            {/* .wl-modal is offset 20px until its old jQuery overlay "opens" it; cancel that here. */}
            <Modal
                open={selected !== null}
                onClose={() => setSelected(null)}
                contentClassName="wl-modal"
                contentStyle={{ transform: 'none', margin: 0 }}
                labelledBy="withdraw-detail-title"
            >
                <div className="wl-modal-head">
                    <span className="wl-modal-title" id="withdraw-detail-title">
                        <i className="las la-receipt" /> {t('Withdrawal Details')}
                    </span>
                    <button className="wl-modal-close" type="button" aria-label={t('Close')} onClick={() => setSelected(null)}>
                        <i className="las la-times" />
                    </button>
                </div>
                <div className="wl-modal-body">
                    {/* React escapes these values. The old jQuery version inserted them as raw HTML. */}
                    <ul className="wl-detail-list">
                        {selected?.details.map((detail, i) => (
                            <li className="wl-detail-item" key={i}>
                                <span className="wl-di-key">{detail.name}</span>
                                <span className="wl-di-val">
                                    {detail.isFile ? (
                                        detail.value && (
                                            <a href={detail.value}>
                                                <i className="las la-paperclip" /> {t('Attachment')}
                                            </a>
                                        )
                                    ) : (
                                        detail.value
                                    )}
                                </span>
                            </li>
                        ))}
                    </ul>
                    {selected?.adminFeedback && (
                        <div className="wl-feedback">
                            <strong>{t('Admin Feedback')}</strong>
                            <p>{selected.adminFeedback}</p>
                        </div>
                    )}
                </div>
            </Modal>

            <Modal
                open={showSuspended}
                onClose={() => setShowSuspended(false)}
                contentClassName="wl-suspend-modal"
                labelledBy="withdrawSuspendedLabel"
                staticBackdrop
            >
                <div className="modal-header wl-suspend-head">
                    <h5 className="modal-title wl-suspend-title" id="withdrawSuspendedLabel">
                        <i className="las la-ban" /> {t('Withdraw Suspended')}
                    </h5>
                    <button type="button" className="btn-close btn-close-white" aria-label={t('Close')} onClick={() => setShowSuspended(false)} />
                </div>
                <div className="modal-body wl-suspend-body">
                    <div className="wl-suspend-icon-wrap"><i className="las la-lock" /></div>
                    <p className="wl-suspend-intro">{t('Your withdrawal access has been temporarily suspended by the administrator.')}</p>
                    {blockReason && (
                        <div className="wl-suspend-reason">
                            <span className="wl-suspend-reason-label">{t('Reason')}</span>
                            <p className="wl-suspend-reason-text">{blockReason}</p>
                        </div>
                    )}
                    <p className="wl-suspend-note">
                        {t('Please contact')} <strong>{t('support')}</strong> {t('to resolve this.')}
                    </p>
                </div>
                <div className="modal-footer wl-suspend-foot">
                    <button type="button" className="btn wl-suspend-close-btn" onClick={() => setShowSuspended(false)}>
                        {t('I Understand')}
                    </button>
                </div>
            </Modal>
        </div>
    );
}

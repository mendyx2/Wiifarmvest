import { Head, usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import Modal from '@/Components/Modal';
import Pagination from '@/Components/Pagination';
import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';
import type { PageProps, Paginated, PaymentDetail } from '@/types';

/** Formatted by UserController@depositHistory. */
type Deposit = {
    id: number;
    trx: string;
    gateway: string;
    date: string;
    ago: string;
    total: string;
    amount: string;
    charge: string;
    rate: string;
    finalAmount: string;
    status: { label: string; tone: string; when: string | null };
    /** Manual gateway: has user-submitted details to show */
    manual: boolean;
    details: PaymentDetail[];
    adminFeedback: string | null;
};

type Props = PageProps<{
    deposits: Paginated<Deposit>;
    search: string;
    depositUrl: string;
}>;

export default function DepositHistory({ deposits, search, depositUrl }: Props) {
    const t = useTrans();
    const { url } = usePage();
    const [query, setQuery] = useState(search);
    const [selected, setSelected] = useState<Deposit | null>(null);

    function submit(event: FormEvent) {
        event.preventDefault();
        visitWithQuery(url, { search: query });
    }

    return (
        <div className="dph-page">
            <Head title={t('Deposit History')} />

            <div className="dph-header-card">
                <div className="dph-header-left">
                    <div className="dph-header-icon"><i className="las la-history" /></div>
                    <div>
                        <h6 className="dph-header-title">{t('Deposit History')}</h6>
                        <p className="dph-header-sub">{t('All your funding transactions in one place')}</p>
                    </div>
                </div>
                {/* The deposit page is still Blade, so a normal link (not <Link>). */}
                <a href={depositUrl} className="dph-deposit-btn">
                    <i className="las la-plus" /> {t('Deposit Now')}
                </a>
            </div>

            <div className="dph-search-card">
                <form className="dph-search-form" onSubmit={submit} role="search">
                    <div className="dph-search-wrap">
                        <i className="las la-search" />
                        <input
                            className="dph-search-input"
                            type="search"
                            aria-label={t('Search')}
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            placeholder={t('Search by transaction ID or gateway...')}
                        />
                    </div>
                    <button className="dph-search-btn" type="submit">{t('Search')}</button>
                </form>
            </div>

            <div className="dph-list-card">
                <div className="dph-list-head">
                    <span>{t('Gateway / Transaction')}</span>
                    <span className="dph-col-hide">{t('Date')}</span>
                    <span>{t('Amount')}</span>
                    <span className="dph-col-hide">{t('Conversion')}</span>
                    <span>{t('Status')}</span>
                    <span>{t('Info')}</span>
                </div>

                {deposits.data.length === 0 ? (
                    <div className="dph-empty">
                        <div className="dph-empty-icon"><i className="las la-inbox" /></div>
                        <p>{t('Data not found')}</p>
                        <a href={depositUrl} className="dph-deposit-btn">
                            <i className="las la-plus" /> {t('Make Your First Deposit')}
                        </a>
                    </div>
                ) : (
                    deposits.data.map((deposit) => (
                        <div className="dph-row" key={deposit.id}>
                            <div className="dph-cell dph-cell--gateway">
                                <div className="dph-gw-icon"><i className="las la-university" /></div>
                                <div>
                                    <p className="dph-gw-name">{deposit.gateway}</p>
                                    <p className="dph-trx">{deposit.trx}</p>
                                    <p className="dph-date-mobile">
                                        <i className="las la-calendar" /> {deposit.date} &bull; {deposit.ago}
                                    </p>
                                </div>
                            </div>

                            <div className="dph-cell dph-col-hide dph-cell--date">
                                <p className="dph-date-main">{deposit.date}</p>
                                <p className="dph-date-ago">{deposit.ago}</p>
                            </div>

                            <div className="dph-cell dph-cell--amount">
                                <p className="dph-amount">{deposit.total}</p>
                                <p className="dph-charge" title={t('Processing Charge')}>
                                    {deposit.amount} + <span className="dph-charge-val">{deposit.charge}</span>
                                </p>
                            </div>

                            <div className="dph-cell dph-col-hide dph-cell--conv">
                                <p className="dph-conv-rate">{deposit.rate}</p>
                                <p className="dph-conv-final"><strong>{deposit.finalAmount}</strong></p>
                            </div>

                            <div className="dph-cell dph-cell--status">
                                <span>
                                    <span className={`badge badge--${deposit.status.tone}`}>{t(deposit.status.label)}</span>
                                    {deposit.status.when && (
                                        <>
                                            <br />
                                            {deposit.status.when}
                                        </>
                                    )}
                                </span>
                            </div>

                            <div className="dph-cell dph-cell--action">
                                {deposit.manual ? (
                                    <button
                                        type="button"
                                        className="dph-detail-btn"
                                        aria-label={t('Transaction Details')}
                                        onClick={() => setSelected(deposit)}
                                    >
                                        <i className="las la-eye" />
                                    </button>
                                ) : (
                                    <span className="dph-auto-badge" title={t('Automatically processed')}>
                                        <i className="las la-check-circle" />
                                    </span>
                                )}
                            </div>
                        </div>
                    ))
                )}
            </div>

            <Pagination links={deposits.links} className="dph-pagination" />

            <Modal open={selected !== null} onClose={() => setSelected(null)} contentClassName="pd-modal-content" labelledBy="deposit-detail-title">
                <div className="pd-modal-header">
                    <div className="pd-modal-icon"><i className="las la-receipt" /></div>
                    <h6 className="pd-modal-title" id="deposit-detail-title">{t('Transaction Details')}</h6>
                    <button className="pd-modal-close" type="button" aria-label={t('Close')} onClick={() => setSelected(null)}>
                        <i className="las la-times" />
                    </button>
                </div>
                <div className="pd-modal-body">
                    {/* React escapes these values. The old jQuery version inserted them as raw HTML. */}
                    <ul className="dph-modal-list">
                        {selected?.details.map((detail, i) => (
                            <li className="dph-modal-item" key={i}>
                                <span className="dph-modal-key">{detail.name}</span>
                                {detail.isFile ? (
                                    detail.value && (
                                        <a className="dph-modal-link" href={detail.value}>
                                            <i className="las la-file" /> {t('Attachment')}
                                        </a>
                                    )
                                ) : (
                                    <span className="dph-modal-val">{detail.value}</span>
                                )}
                            </li>
                        ))}
                    </ul>
                    {selected?.adminFeedback && (
                        <div className="dph-feedback">
                            <strong>{t('Admin Feedback')}</strong>
                            <p>{selected.adminFeedback}</p>
                        </div>
                    )}
                </div>
                <div className="pd-modal-footer">
                    <button className="pd-modal-btn-cancel" type="button" onClick={() => setSelected(null)}>
                        {t('Close')}
                    </button>
                </div>
            </Modal>
        </div>
    );
}

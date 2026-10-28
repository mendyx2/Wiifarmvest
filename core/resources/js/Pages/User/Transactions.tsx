import { Head, usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import EmptyState from '@/Components/EmptyState';
import Pagination from '@/Components/Pagination';
import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';
import type { PageProps, Paginated } from '@/types';

/** Already formatted by UserController@transactions (currency, dates, locale). */
type Transaction = {
    id: number;
    trx: string;
    isCredit: boolean;
    details: string;
    amount: string;
    charge: string | null;
    postBalance: string;
    ago: string;
    date: string;
    dayMonth: string;
    year: string;
};

type Filters = { search: string; trx_type: string; remark: string };

type Props = PageProps<{
    transactions: Paginated<Transaction>;
    remarks: { value: string; label: string }[];
    filters: Filters;
}>;

export default function Transactions({ transactions, remarks, filters }: Props) {
    const t = useTrans();
    const { url } = usePage();
    const [values, setValues] = useState<Filters>(filters);

    function applyFilters(event: FormEvent) {
        event.preventDefault();
        visitWithQuery(url, values);
    }

    const set = (key: keyof Filters) => (e: { target: { value: string } }) =>
        setValues({ ...values, [key]: e.target.value });

    return (
        <div className="nc-wrap" id="ncWrap">
            <Head title={t('Transactions')} />
            <br />
            <div className="txn-page">
                <div className="txn-filter-card">
                    <form className="txn-filter-form" onSubmit={applyFilters}>
                        <div className="txn-filter-field">
                            <label htmlFor="search">{t('Transaction Ref')}</label>
                            <div className="txn-filter-input-wrap">
                                <i className="las la-search" />
                                <input
                                    id="search"
                                    type="search"
                                    value={values.search}
                                    onChange={set('search')}
                                    placeholder={t('Search reference...')}
                                />
                            </div>
                        </div>
                        <div className="txn-filter-field">
                            <label htmlFor="trx_type">{t('Type')}</label>
                            <select id="trx_type" value={values.trx_type} onChange={set('trx_type')}>
                                <option value="">{t('All Types')}</option>
                                <option value="+">{t('Credit (+)')}</option>
                                <option value="-">{t('Debit (−)')}</option>
                            </select>
                        </div>
                        <div className="txn-filter-field">
                            <label htmlFor="remark">{t('Remark')}</label>
                            <select id="remark" value={values.remark} onChange={set('remark')}>
                                <option value="">{t('All Remarks')}</option>
                                {remarks.map((r) => (
                                    <option key={r.value} value={r.value}>
                                        {t(r.label)}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="txn-filter-field txn-filter-btn-wrap">
                            <label>&nbsp;</label>
                            <button type="submit" className="txn-filter-btn">
                                <i className="las la-filter" /> {t('Filter')}
                            </button>
                        </div>
                    </form>
                </div>

                <div className="txn-list-card">
                    <div className="txn-list-head">
                        <h6 className="txn-list-title">
                            <i className="las la-exchange-alt" /> {t('Transactions')}
                        </h6>
                        {transactions.total > 0 && (
                            <span className="txn-count-pill">{t(':count records', { count: transactions.total })}</span>
                        )}
                    </div>

                    {transactions.data.length === 0 ? (
                        <EmptyState
                            prefix="txn"
                            icon="la-file-invoice-dollar"
                            title={t('No transactions yet')}
                            text={t('Your transaction history will appear here.')}
                        />
                    ) : (
                        transactions.data.map((trx) => {
                            const kind = trx.isCredit ? 'credit' : 'debit';
                            return (
                                <div className="txn-row" key={trx.id}>
                                    <div className={`txn-type-icon ${kind}`}>
                                        <i className={`las ${trx.isCredit ? 'la-arrow-down' : 'la-arrow-up'}`} />
                                    </div>
                                    <div className="txn-row-info">
                                        <p className="txn-row-desc">{trx.details}</p>
                                        <p className="txn-row-ref">
                                            <i className="las la-hashtag" /> {trx.trx}
                                            <span className="txn-row-dot">·</span>
                                            {trx.ago}
                                            <span className="txn-row-dot">·</span>
                                            <span className="txn-inline-date">{trx.date}</span>
                                        </p>
                                    </div>
                                    <div className="txn-row-right">
                                        <p className={`txn-row-amount ${kind}`}>
                                            {trx.isCredit ? '+' : '−'}
                                            {trx.amount}
                                        </p>
                                        {trx.charge && (
                                            <p className="txn-row-charge">
                                                {t('Fee')}: {trx.charge}
                                            </p>
                                        )}
                                        <p className="txn-row-bal">
                                            {t('Bal')}: {trx.postBalance}
                                        </p>
                                    </div>
                                    <div className="txn-row-date">
                                        <span>{trx.dayMonth}</span>
                                        <span className="txn-row-year">{trx.year}</span>
                                    </div>
                                </div>
                            );
                        })
                    )}
                </div>

                <Pagination links={transactions.links} className="mt-3" />
            </div>
        </div>
    );
}

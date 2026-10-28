import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import { UserCell, WhenCell, type AdminUser, type When } from '@/Components/Admin/Cells';
import DataTable from '@/Components/Admin/DataTable';
import DateRangeField, { dateQuery, type DateRange } from '@/Components/Admin/DateRangeField';
import FilterCard from '@/Components/Admin/FilterCard';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';
import type { Paginated } from '@/types';

type Row = { id: number; user: AdminUser; trx: string; when: When; amount: string; credit: boolean; postBalance: string; details: string };

type Props = {
    title: string;
    transactions: Paginated<Row>;
    /** Remark choices; null on the commission pages, which show one remark only */
    remarks: { value: string; label: string }[] | null;
    filters: { search: string; trx_type: string; remark: string; date: DateRange };
};

/**
 * Report → Transaction History, and the Referral / Stage-Out / Binary
 * commission logs (same table, one remark each).
 */
export default function Transactions({ title, transactions, remarks, filters }: Props) {
    const t = useTrans();
    const url = usePage().url;
    const [search, setSearch] = useState(filters.search);
    const [trxType, setTrxType] = useState(filters.trx_type);
    const [remark, setRemark] = useState(filters.remark);
    const [date, setDate] = useState(filters.date);

    const apply = () => visitWithQuery(url, { search: search.trim(), trx_type: remarks ? trxType : '', remark: remarks ? remark : '', date: dateQuery(date) });

    return (
        <>
            <PageHeader title={t(title)} />
            <FilterCard onSubmit={apply}>
                <div className="flex-grow-1">
                    <label htmlFor="trx-search">{t('TRX/Username')}</label>
                    <input id="trx-search" className="form-control" type="search" value={search} onChange={(e) => setSearch(e.target.value)} placeholder={t('Search')} />
                </div>
                {remarks && (
                    <>
                        <div className="flex-grow-1">
                            <label htmlFor="trx-type">{t('Type')}</label>
                            <select id="trx-type" className="form-control" value={trxType} onChange={(e) => setTrxType(e.target.value)}>
                                <option value="">{t('All')}</option>
                                <option value="+">{t('Plus')}</option>
                                <option value="-">{t('Minus')}</option>
                            </select>
                        </div>
                        <div className="flex-grow-1">
                            <label htmlFor="trx-remark">{t('Remark')}</label>
                            <select id="trx-remark" className="form-control" value={remark} onChange={(e) => setRemark(e.target.value)}>
                                <option value="">{t('All')}</option>
                                {remarks.map((r) => (
                                    <option key={r.value} value={r.value}>
                                        {r.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </>
                )}
                <div className="flex-grow-1">
                    <DateRangeField value={date} onChange={setDate} />
                </div>
            </FilterCard>

            <DataTable
                rows={transactions.data}
                rowKey={(r) => r.id}
                links={transactions.links}
                columns={[
                    { label: t('User'), cell: (r) => <UserCell user={r.user} searchUrl={url} /> },
                    { label: t('TRX'), cell: (r) => <strong>{r.trx}</strong> },
                    { label: t('Transacted'), cell: (r) => <WhenCell when={r.when} /> },
                    { label: t('Amount'), className: 'budget', cell: (r) => <span className={`fw-bold ${r.credit ? 'text--success' : 'text--danger'}`}>{r.amount}</span> },
                    { label: t('Post Balance'), className: 'budget', cell: (r) => r.postBalance },
                    { label: t('Details'), cell: (r) => r.details },
                ]}
            />
        </>
    );
}

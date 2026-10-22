import { Head, Link } from '@inertiajs/react';
import Pagination from '@/Components/Pagination';
import { useTrans } from '@/lib/i18n';
import type { PageProps, Paginated } from '@/types';

type Total = { type: string; label: string; amount: string; qty: number; count: number; url: string };
type Row = { id: number; type: string; typeLabel: string; reference: string; customer: string | null; qty: number; amount: string; date: string };

type Props = PageProps<{
    totals: Total[];
    grandAmount: string;
    grandQty: number;
    type: string | null;
    typeLabel: string | null;
    redemptions: Paginated<Row>;
    allUrl: string;
    dashboardUrl: string;
}>;

const CARD: Record<string, { tone: string; icon: string }> = {
    cash: { tone: 'success', icon: 'la-money-bill-wave' },
    invoice_code: { tone: 'info', icon: 'la-file-invoice' },
    welcome_pack: { tone: 'gold', icon: 'la-gift' },
    affiliate_invoice: { tone: 'primary', icon: 'la-share-alt' },
};

export default function StockistRedemptions({ totals, grandAmount, grandQty, type, typeLabel, redemptions, allUrl, dashboardUrl }: Props) {
    const t = useTrans();

    return (
        <div className="nc-wrap" id="ncWrap">
            <Head title={t('Redemption Report')} />
            <br />
            <div className="sl-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div>
                    <h4 className="sl-page-title mb-1">{t('Redemption Report')}</h4>
                    <p className="sl-page-subtitle mb-0">{t("Stock and money you've moved, broken down by cash, invoice code, welcome pack code and affiliate invoice code.")}</p>
                </div>
                <Link href={dashboardUrl} className="sl-btn sl-btn-outline"><i className="las la-arrow-left me-1" /> {t('Back to Dashboard')}</Link>
            </div>

            <div className="row g-3 mb-4">
                {totals.map((total) => {
                    const card = CARD[total.type] ?? { tone: 'info', icon: 'la-receipt' };
                    return (
                        <div className="col-6 col-md-3" key={total.type}>
                            <Link href={total.url} className="text-decoration-none" preserveScroll>
                                <div className={`sl-stat-card sl-stat-${card.tone}`}>
                                    <div className="sl-stat-icon"><i className={`las ${card.icon}`} /></div>
                                    <div className="sl-stat-body">
                                        <p className="sl-stat-label">{t(total.label)}</p>
                                        <h3 className="sl-stat-value">{total.amount}</h3>
                                        <p className="sl-stat-note">
                                            {t(':qty unit(s)', { qty: total.qty })} &middot; {total.type === 'cash' ? t(':count sale(s)', { count: total.count }) : t(':count redemption(s)', { count: total.count })}
                                        </p>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    );
                })}
            </div>

            <div className="sl-alert-success d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
                <strong>{t('Grand Total: :amount', { amount: grandAmount })}</strong>
                <span>{t(':qty unit(s) redeemed across all channels', { qty: grandQty })}</span>
            </div>

            <div className="d-flex flex-wrap align-items-center gap-2 mb-3">
                <Link href={allUrl} className={`sl-btn ${!type ? 'sl-btn-primary' : 'sl-btn-outline'}`} preserveScroll aria-current={!type ? 'page' : undefined}>{t('All')}</Link>
                {totals.map((total) => (
                    <Link key={total.type} href={total.url} preserveScroll className={`sl-btn ${type === total.type ? 'sl-btn-primary' : 'sl-btn-outline'}`} aria-current={type === total.type ? 'page' : undefined}>
                        {t(total.label)}
                    </Link>
                ))}
            </div>

            <div className="sl-card">
                <div className="sl-card-header">
                    {t('Redemption History')}
                    {typeLabel && <> &mdash; {t(typeLabel)}</>}
                </div>
                {redemptions.data.length === 0 ? (
                    <div className="sl-empty-state">
                        <div className="sl-empty-icon"><i className="las la-receipt" /></div>
                        <p className="sl-empty-title">{t('No Redemptions Yet')}</p>
                        <p className="sl-empty-sub">{t('Redemptions across cash, invoice code, welcome pack and affiliate orders will appear here.')}</p>
                    </div>
                ) : (
                    <>
                        <div className="table-responsive">
                            <table className="sl-table">
                                <thead>
                                    <tr>
                                        <th>{t('Type')}</th>
                                        <th>{t('Reference')}</th>
                                        <th>{t('Customer')}</th>
                                        <th>{t('Qty')}</th>
                                        <th>{t('Amount')}</th>
                                        <th>{t('Date')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {redemptions.data.map((r) => (
                                        <tr key={r.id}>
                                            <td data-label={t('Type')}><span className={`sl-type-badge sl-type-${r.type}`}>{t(r.typeLabel)}</span></td>
                                            <td data-label={t('Reference')}>{r.reference}</td>
                                            <td data-label={t('Customer')}>{r.customer ?? <span className="text-muted">{t('Walk-in')}</span>}</td>
                                            <td data-label={t('Qty')}>{r.qty}</td>
                                            <td data-label={t('Amount')} className="fw-600" style={{ color: 'var(--sl-green)' }}>{r.amount}</td>
                                            <td data-label={t('Date')}>{r.date}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="sl-card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <small className="text-muted">{t('Showing :from to :to of :total redemptions', { from: redemptions.from ?? 0, to: redemptions.to ?? 0, total: redemptions.total })}</small>
                            <Pagination links={redemptions.links} />
                        </div>
                    </>
                )}
            </div>
        </div>
    );
}

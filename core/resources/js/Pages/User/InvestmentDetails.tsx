import { Head, Link, usePage } from '@inertiajs/react';
import DividendStatus from '@/Components/DividendStatus';
import Pagination from '@/Components/Pagination';
import { useTrans } from '@/lib/i18n';
import type { PageProps, Paginated } from '@/types';
import type { Dividend } from './InvestmentPortfolio';
import './InvestmentDetails.css';

type Props = PageProps<{
    investment: { id: number; plan: string; date: string; units: number; unitCost: string; total: string; invested: string; totalReturn: string };
    totalDividends: string;
    roi: number;
    dividends: Paginated<Dividend>;
    recent: { id: number; date: string; reference: string; amount: string }[];
    moreCount: number;
    pdfUrl: string;
    portfolioUrl: string;
}>;

const DOWNLOAD_ICON = (
    <svg width="22px" height="22px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path opacity="0.5" d="M3 15C3 17.8284 3 19.2426 3.87868 20.1213C4.75736 21 6.17157 21 9 21H15C17.8284 21 19.2426 21 20.1213 20.1213C21 19.2426 21 17.8284 21 15" stroke="#ffffff" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
        <path d="M12 3V16M12 16L16 11.625M12 16L8 11.625" stroke="#ffffff" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
);

export default function InvestmentDetails({ investment, totalDividends, roi, dividends, recent, moreCount, pdfUrl, portfolioUrl }: Props) {
    const t = useTrans();
    const { csrfToken } = usePage().props;

    // The receipt and certificate come back as PDF files, so these are normal
    // form posts (a download), not Inertia visits.
    const pdfForm = (check: 1 | 2, label: string, cls: string) => (
        <form action={pdfUrl} method="POST">
            <input type="hidden" name="_token" value={csrfToken} />
            <input type="hidden" name="rivest_id" value={investment.id} />
            <input type="hidden" name="check" value={check} />
            <button type="submit" className={`btn btn-sm ${cls} mt-2 w-100`}>{label} {DOWNLOAD_ICON}</button>
        </form>
    );

    return (
        <div className="page-investment-details">
            <Head title={t('Investment Details')} />
            <div className="container-fluid px-3 px-lg-5 py-4">
                <div className="mb-5">
                    <div className="d-flex justify-content-between align-items-center flex-wrap">
                        <h3 className="portfolio-title"><i className="fas fa-briefcase me-2" />{investment.plan}</h3>
                        <Link href={portfolioUrl} className="btn btn-back"><i className="fas fa-arrow-left me-2" />{t('Back to Shares Portfolio')}</Link>
                    </div>
                </div>
                <div className="row g-4">
                    <div className="col-lg-8">
                        <div className="card border-0 shadow-sm mb-4 card-detail-section">
                            <div className="card-body">
                                <h5 className="detail-section-title">{t('Investment Details')}</h5>
                                <div className="row mb-4">
                                    <Detail cls="col-6 col-md-6 mb-4" icon="fa-tag" label={t('Shares')}><h5 className="detail-value">{investment.plan}</h5></Detail>
                                    <Detail cls="col-6 col-md-5 mb-4" icon="fa-calendar" label={t('Purchase Date')}><h5 className="detail-value">{investment.date}</h5></Detail>
                                </div>
                                <div className="row mb-4">
                                    <Detail cls="col-6 col-md-5 mb-1" icon="fa-cubes" label={t('Units Purchased')}>
                                        <div className="d-flex align-items-center gap-2"><span className="badge badge-detail-units">{investment.units}</span><small className="text-muted">{t('Units')}</small></div>
                                    </Detail>
                                    <Detail cls="col-6 col-md-5 mb-1" icon="fa-dollar-sign" label={t('Unit Cost')}><h5 className="detail-value">{investment.unitCost}</h5></Detail>
                                </div>
                                <hr className="detail-divider" />
                                <div className="row">
                                    <Detail cls="col-6" icon="fa-chart-line" label={t('Total Investment')}><h4 className="detail-value-primary">{investment.total}</h4></Detail>
                                </div>
                            </div>
                        </div>

                        <div className="card border-0 shadow-sm card-detail-section">
                            <div className="card-header border-bottom py-4">
                                <div className="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 className="card-title-custom">{t('Dividends from This Investment')}</h5>
                                        <small className="card-subtitle">{t('All dividend payments received')}</small>
                                    </div>
                                    <span className="badge badge-danger-gradient">{t(':count Total', { count: dividends.total })}</span>
                                </div>
                            </div>
                            <div className="card-body p-0">
                                {dividends.data.length > 0 ? (
                                    <>
                                        {dividends.data.map((d) => (
                                            <div className="card border-0 shadow-sm mb-4 card-detail-section" key={d.id}>
                                                <div className="card-body">
                                                    <div className="row mb-4">
                                                        <Detail cls="col-6 col-md-6 mb-4" icon="fa-tag" label={t('Reference')}><h5 className="detail-value">{d.reference}</h5></Detail>
                                                        <Detail cls="col-6 col-md-5 mb-4" icon="fa-calendar" label={t('Date')}><h5 className="detail-value">{d.date}</h5></Detail>
                                                        <Detail cls="col-md-5" icon="fa-info-circle" label={t('Status')}><DividendStatus status={d.status} /></Detail>
                                                    </div>
                                                    <div className="row mb-4">
                                                        <Detail cls="col-6 col-md-5 mb-1" icon="fa-cubes" label={t('Rate/Unit')}>
                                                            <div className="d-flex align-items-center gap-2"><span className="badge badge-detail-units">{d.units}</span><small className="text-muted">{t('Units')}</small></div>
                                                        </Detail>
                                                        <Detail cls="col-6 col-md-5 mb-1" icon="fa-dollar-sign" label={t('Dividend Amount')}><h5 className="detail-value">{d.amount}</h5></Detail>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                        {dividends.last_page > 1 && <div className="card-footer bg-light"><Pagination links={dividends.links} /></div>}
                                    </>
                                ) : (
                                    <div className="empty-state">
                                        <i className="fas fa-gift" />
                                        <p className="empty-state-text">{t('No dividends received yet for this investment.')}</p>
                                    </div>
                                )}
                            </div>
                        </div>

                        {pdfForm(1, t('Generate PDF Receipt'), 'btn-outline-success')}
                        {pdfForm(2, t('Download Certificate'), 'btn-outline-secondary')}
                    </div>

                    <div className="col-lg-4">
                        <div className="card border-0 shadow-sm mb-4 card-dividend-highlight">
                            <div className="card-body text-center">
                                <i className="fas fa-gift dividend-highlight-icon" />
                                <p className="detail-label-large">{t('Total Dividends Earned')}</p>
                                <h2 className="dividend-total-amount">{totalDividends}</h2>
                                <small className="highlight-desc">{t('From this investment')}</small>
                            </div>
                        </div>

                        <div className="card border-0 shadow-sm mb-4 card-detail-section">
                            <div className="card-header border-bottom py-4"><h5 className="card-title-custom"><i className="fas fa-chart-pie me-2" />{t('Performance')}</h5></div>
                            <div className="card-body">
                                <div className="mb-4">
                                    <label className="detail-label-row"><span>{t('Investment ROI')}</span><strong className="text-success">{roi.toFixed(2)}%</strong></label>
                                    <div className="progress-custom" role="progressbar" aria-valuenow={Math.min(roi, 100)} aria-valuemin={0} aria-valuemax={100}>
                                        <div className="progress-bar-custom" style={{ width: `${Math.min(roi, 100)}%` }} />
                                    </div>
                                </div>
                                <div className="list-group list-group-flush">
                                    <div className="detail-list-item"><span>{t('Total Invested')}</span><strong>{investment.invested}</strong></div>
                                    <div className="detail-list-item"><span>{t('Dividends Earned')}</span><strong className="text-success">{totalDividends}</strong></div>
                                    <div className="detail-list-item"><span>{t('Total Return')}</span><strong className="text-dark">{investment.totalReturn}</strong></div>
                                </div>
                            </div>
                        </div>

                        <div className="card border-0 shadow-sm card-detail-section">
                            <div className="card-header border-bottom py-4"><h5 className="card-title-custom"><i className="fas fa-history me-2" />{t('Recent Activity')}</h5></div>
                            <div className="card-body">
                                {recent.length > 0 ? (
                                    <>
                                        <div className="list-group list-group-flush">
                                            {recent.map((d) => (
                                                <div className="recent-dividend-item" key={d.id}>
                                                    <div>
                                                        <strong className="recent-date">{d.date}</strong>
                                                        <small className="recent-ref">{d.reference}</small>
                                                        <span className="badge badge-success-light">{d.amount}</span>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                        {moreCount > 0 && (
                                            <div className="alert alert-more"><i className="fas fa-ellipsis-h me-2" /><small><strong>{t('And :count more dividends...', { count: moreCount })}</strong></small></div>
                                        )}
                                    </>
                                ) : (
                                    <p className="empty-state-small-text"><i className="fas fa-inbox" /> {t('No dividend history yet')}</p>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

function Detail({ cls, icon, label, children }: { cls: string; icon: string; label: string; children: React.ReactNode }) {
    return (
        <div className={cls}>
            <label className="detail-label"><i className={`fas ${icon}`} />{label}</label>
            {children}
        </div>
    );
}

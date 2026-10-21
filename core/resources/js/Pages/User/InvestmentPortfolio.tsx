import { Head, Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import DividendStatus from '@/Components/DividendStatus';
import Modal from '@/Components/Modal';
import Pagination from '@/Components/Pagination';
import { useTrans } from '@/lib/i18n';
import type { PageProps, Paginated } from '@/types';
import './InvestmentPortfolio.css';

type Investment = { id: number; plan: string; date: string; units: number; unitCost: string; totalValue: string; url: string };
export type Dividend = { id: number; date: string; time: string; reference: string; plan: string; units: number; perUnit: string; amount: string; status: string };
type Plan = { id: number; name: string; price: number; priceLabel: string };

type Props = PageProps<{
    totalUnits: number;
    investmentValue: string;
    totalDividends: string;
    investments: Investment[];
    dividendsByPlan: { plan: string; amount: string }[];
    dividends: Paginated<Dividend>;
    plans: Plan[];
    balance: string;
    purchaseUrl: string;
}>;

const MIN_UNITS = 5;
const COLORS = ['primary', 'danger', 'info', 'warning', 'success'];
const ngn = (value: number) => new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(value);

export default function InvestmentPortfolio(props: Props) {
    const { totalUnits, investmentValue, totalDividends, investments, dividendsByPlan, dividends } = props;
    const t = useTrans();
    const [buying, setBuying] = useState(false);

    const kpis = [
        { label: t('Total Shares'), value: String(totalUnits), cls: 'kpi-value-primary', icon: 'fa-share-alt', desc: t('Units held') },
        { label: t('Investment Value'), value: investmentValue, cls: 'kpi-value-primary', icon: 'fa-wallet', desc: null },
        { label: t('Total Dividends'), value: totalDividends, cls: 'kpi-value-info', icon: 'fa-gift', desc: t('Total earnings from dividends') },
    ];

    return (
        <div className="page-investment-portfolio">
            <Head title={t('My Shares Portfolio')} />
            <div className="container-fluid px-3 px-lg-5 py-4">
                <div className="mb-5">
                    <div className="d-flex justify-content-between align-items-center flex-wrap">
                        <h3 className="portfolio-title"><i className="fas fa-briefcase me-2" />{t('Investment Portfolio')}</h3>
                        <button type="button" className="btn btn-buy-shares" onClick={() => setBuying(true)}>
                            <i className="fas fa-plus-circle me-2" />{t('Buy Shares')}
                        </button>
                    </div>
                </div>

                <div className="row g-3 mb-5">
                    {kpis.map((kpi) => (
                        <div className="col-lg-3 col-md-6" key={kpi.label}>
                            <div className="kpi-card">
                                <div className="kpi-card-body">
                                    <div className="d-flex align-items-start justify-content-between mb-3">
                                        <div>
                                            <p className="kpi-label">{kpi.label}</p>
                                            <h4 className={`kpi-value ${kpi.cls}`}>{kpi.value}</h4>
                                        </div>
                                        <div className="kpi-icon kpi-icon-primary"><i className={`fas ${kpi.icon}`} /></div>
                                    </div>
                                    {kpi.desc && <small className="kpi-desc">{kpi.desc}</small>}
                                </div>
                            </div>
                        </div>
                    ))}
                </div>

                <div className="row g-4">
                    <div className="col-lg-8">
                        <div className="card border-0 shadow-sm card-dividend-summary">
                            <div className="card-header border-bottom py-4">
                                <div className="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 className="card-title-custom">{t('My Investments')}</h5>
                                        <small className="card-subtitle">{t('All your active and inactive investments')}</small>
                                    </div>
                                    <span className="badge badge-primary-gradient">{t(':count Active', { count: investments.length })}</span>
                                </div>
                            </div>
                            <div className="card-body p-0">
                                {investments.length > 0 ? (
                                    <div className="investment-cards">
                                        <div className="row g-3">
                                            {investments.map((inv) => (
                                                <div className="col-12" key={inv.id}>
                                                    <div className="investment-card p-4">
                                                        <div className="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-start">
                                                            <div className="investment-card-main">
                                                                <div className="d-flex align-items-center gap-3 mb-3">
                                                                    <div className="investment-card-icon bg-primary-light text-primary"><i className="fas fa-briefcase" /></div>
                                                                    <div>
                                                                        <h6 className="plan-name mb-1">{inv.plan}</h6>
                                                                        <p className="plan-date mb-0 text-muted"><i className="fas fa-calendar me-1" />{inv.date}</p>
                                                                    </div>
                                                                </div>
                                                                <div className="investment-card-meta row gx-2 gy-2">
                                                                    <div className="col-5 col-sm-5"><div className="investment-meta-item"><span>{t('Units')}</span><strong>{inv.units}</strong></div></div>
                                                                    <div className="col-6 col-sm-6"><div className="investment-meta-item"><span>{t('Unit Cost')}</span><strong>{inv.unitCost}</strong></div></div>
                                                                    <div className="col-12 col-sm-4"><div className="investment-meta-item"><span>{t('Total Value')}</span><strong>{inv.totalValue}</strong></div></div>
                                                                </div>
                                                            </div>
                                                            <div className="investment-card-actions text-lg-end">
                                                                <Link href={inv.url} className="btn btn-sm btn-action"><i className="fas fa-arrow-right me-1" />{t('View')}</Link>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                ) : (
                                    <div className="empty-state">
                                        <i className="fas fa-inbox" />
                                        <p className="empty-state-text">{t("You don't have any investments yet.")}</p>
                                        {/* Linked to /user/plan, which fails (PlanController has no planIndex). */}
                                        <button type="button" className="btn btn-primary-gradient" onClick={() => setBuying(true)}>
                                            <i className="fas fa-plus me-2" />{t('Start Investing')}
                                        </button>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="col-lg-4">
                        <div className="card border-0 shadow-sm card-dividend-summary">
                            <div className="card-header border-bottom py-4">
                                <h5 className="card-title-custom"><i className="fas fa-chart-pie me-2" />{t('Dividend Summary')}</h5>
                            </div>
                            <div className="card-body">
                                {dividendsByPlan.length > 0 ? (
                                    <>
                                        <div className="list-group list-group-flush">
                                            {dividendsByPlan.map((item, i) => {
                                                const color = COLORS[i % COLORS.length];
                                                return (
                                                    <div className="dividend-item" key={item.plan}>
                                                        <div className="d-flex align-items-center gap-3">
                                                            <div className={`dividend-icon dividend-icon-${color}`}><i className="fas fa-cubes" /></div>
                                                            <div>
                                                                <strong className="dividend-plan">{item.plan}</strong>
                                                                <small className="dividend-label">{t('Dividend Earnings')}</small>
                                                            </div>
                                                        </div>
                                                        <span className={`badge badge-${color}-light`}>{item.amount}</span>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                        <div className="alert alert-dividend-summary">
                                            <i className="fas fa-info-circle" />
                                            <span className="ms-2"><strong>{t('Total earned:')}</strong> {totalDividends}</span>
                                        </div>
                                    </>
                                ) : (
                                    <div className="empty-state-small">
                                        <i className="fas fa-gift" />
                                        <p>{t('No dividends yet.')}<br /><small>{t("They'll appear as they are distributed.")}</small></p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                <div className="row mt-5">
                    <div className="col-12">
                        <div className="card border-0 shadow-sm">
                            <div className="card-header border-bottom py-4">
                                <div className="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 className="card-title-custom">{t('Dividend History')}</h5>
                                        <small className="card-subtitle">{t('All dividend transactions received')}</small>
                                    </div>
                                    <span className="badge badge-danger-gradient">{t(':count Total', { count: dividends.total })}</span>
                                </div>
                            </div>
                            <div className="card-body p-0">
                                {dividends.data.length > 0 ? (
                                    <>
                                        <div className="table-responsive">
                                            <table className="table table-custom mb-0">
                                                <thead>
                                                    <tr>
                                                        <th className="ps-3">{t('Date')}</th>
                                                        <th>{t('Reference')}</th>
                                                        <th>{t('Investment')}</th>
                                                        <th className="text-center">{t('Units')}</th>
                                                        <th>{t('Rate/Unit')}</th>
                                                        <th>{t('Amount')}</th>
                                                        <th className="pe-3">{t('Status')}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {dividends.data.map((d) => (
                                                        <tr key={d.id}>
                                                            <td className="py-4 ps-3"><small className="date-value">{d.date}</small><br /><small className="date-time">{d.time}</small></td>
                                                            <td className="py-4"><code className="reference-code">{d.reference}</code></td>
                                                            <td className="py-4">{d.plan}</td>
                                                            <td className="py-4 text-center"><span className="badge badge-light">{d.units}</span></td>
                                                            <td className="py-4">{d.perUnit}</td>
                                                            <td className="py-4"><span className="amount-success">{d.amount}</span></td>
                                                            <td className="py-4 pe-3"><DividendStatus status={d.status} /></td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                        {dividends.last_page > 1 && (
                                            <div className="card-footer bg-light"><Pagination links={dividends.links} /></div>
                                        )}
                                    </>
                                ) : (
                                    <div className="empty-state">
                                        <i className="fas fa-gift" />
                                        <p className="empty-state-text">{t('No dividend transactions yet.')}</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <BuySharesModal open={buying} onClose={() => setBuying(false)} plans={props.plans} balance={props.balance} purchaseUrl={props.purchaseUrl} />
        </div>
    );
}

function BuySharesModal({ open, onClose, plans, balance, purchaseUrl }: { open: boolean; onClose: () => void; plans: Plan[]; balance: string; purchaseUrl: string }) {
    const t = useTrans();
    // Like the Blade modal: opens on the first plan with 5 units.
    const form = useForm({ plan_id: plans[0] ? String(plans[0].id) : '', qtys: String(MIN_UNITS) });
    const plan = plans.find((p) => String(p.id) === form.data.plan_id);
    const units = parseInt(form.data.qtys, 10);
    const total = plan && units >= MIN_UNITS ? plan.price * units : 0;

    function close() {
        form.reset();
        form.clearErrors();
        onClose();
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        if (Number.isNaN(units) || units < MIN_UNITS) {
            form.setData('qtys', String(MIN_UNITS));
            return;
        }
        // On success the server redirects back here with a toast and the new totals.
        form.post(purchaseUrl, { preserveScroll: true, onSuccess: close });
    }

    return (
        <Modal open={open} onClose={close} contentClassName="border-0 shadow-lg" labelledBy="buySharesModalLabel" size="lg">
            <div className="modal-header bg-gradient-primary text-white border-0">
                <h5 className="modal-title fw-bold" id="buySharesModalLabel"><i className="fas fa-coins me-2" />{t('Buy Shares')}</h5>
                <button type="button" className="btn-close btn-close-white" aria-label={t('Close')} onClick={close} />
            </div>
            <div className="modal-body p-4">
                <form onSubmit={submit}>
                    <div className="mb-4">
                        <label htmlFor="modalPlanSelect" className="form-label fw-semibold text-dark"><i className="fas fa-list-ul me-1" />{t('Select Investment Plan')}</label>
                        <div className="input-group input-group-lg">
                            <span className="input-group-text bg-light border-end-0"><i className="fas fa-chart-line text-primary" /></span>
                            <select className="form-select border-start-0 ps-0" id="modalPlanSelect" value={form.data.plan_id} onChange={(e) => form.setData('plan_id', e.target.value)} required>
                                <option value="">{t('Choose your investment plan...')}</option>
                                {plans.map((p) => (
                                    <option key={p.id} value={p.id}>{p.name} - {t(':price per unit', { price: p.priceLabel })}</option>
                                ))}
                            </select>
                        </div>
                        <div className="form-text text-muted"><i className="fas fa-info-circle me-1" />{t('Choose the plan that best fits your investment goals')}</div>
                        {form.errors.plan_id && <small className="text-danger">{form.errors.plan_id}</small>}
                    </div>

                    <div className="mb-4">
                        <label htmlFor="modalUnitsInput" className="form-label fw-semibold text-dark"><i className="fas fa-hashtag me-1" />{t('Number of Units')}</label>
                        <div className="input-group input-group-lg">
                            <span className="input-group-text bg-light border-end-0"><i className="fas fa-cubes text-success" /></span>
                            <input
                                type="number"
                                className="form-control border-start-0 ps-0"
                                id="modalUnitsInput"
                                min={MIN_UNITS}
                                value={form.data.qtys}
                                onChange={(e) => form.setData('qtys', e.target.value)}
                                // Raise to the minimum only when leaving the field, so typing "12" is not interrupted.
                                onBlur={() => (Number.isNaN(units) || units < MIN_UNITS) && form.setData('qtys', String(MIN_UNITS))}
                                required
                            />
                            <span className="input-group-text bg-light"><small className="text-muted">{t('units')}</small></span>
                        </div>
                        <div className="form-text text-muted"><i className="fas fa-exclamation-triangle me-1 text-warning" />{t('Minimum purchase: 5 units')}</div>
                        {form.errors.qtys && <small className="text-danger">{form.errors.qtys}</small>}
                    </div>

                    <div className="mb-4">
                        <div className="card border-0 bg-light">
                            <div className="card-body p-3">
                                <div className="row align-items-center">
                                    <div className="col-md-8"><h6 className="mb-1 fw-semibold text-dark"><i className="fas fa-calculator me-2 text-primary" />{t('Total Investment Amount')}</h6></div>
                                    <div className="col-md-4 text-end"><h4 className="mb-0 fw-bold text-success" id="modalTotalAmount" aria-live="polite">{total > 0 ? ngn(total) : '₦0.00'}</h4></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="mb-4">
                        <div className="alert alert-info border-0">
                            <div className="d-flex align-items-center">
                                <i className="fas fa-wallet me-3 text-info fs-4" />
                                <div>
                                    <strong>{t('Available Balance:')}</strong> <span className="fw-bold text-info">{balance}</span>
                                    <br />
                                    <small className="text-muted">{t('This amount will be deducted from your account')}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="d-flex gap-3 justify-content-end">
                        <button type="button" className="btn btn-outline-secondary px-4" onClick={close}><i className="fas fa-times me-2" />{t('Cancel')}</button>
                        <button type="submit" className="btn btn-buy-shares px-4" disabled={form.processing}>
                            {form.processing ? <><i className="fas fa-spinner fa-spin me-2" />{t('Processing...')}</> : <><i className="fas fa-shopping-cart me-2" />{t('Purchase Shares')}</>}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    );
}

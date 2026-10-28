import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FieldError } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';

type Settings = {
    cash_on_pickup_enabled: boolean;
    paystack_enabled: boolean;
    cookie_days: string;
    pending_order_expiry_days: string;
    stockist_pickup_fee_type: string;
    stockist_pickup_fee_value: string;
};

/** Affiliate Program → Affiliate Settings. */
export default function AffiliateSettings({ settings, urls }: { settings: Settings; urls: { update: string } }) {
    const t = useTrans();
    const form = useForm<Settings>(settings);
    const { data, setData, errors } = form;
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(urls.update, { preserveScroll: true });
    };
    const bothOff = !data.cash_on_pickup_enabled && !data.paystack_enabled;
    const percent = data.stockist_pickup_fee_type === 'percentage';

    return (
        <>
            <PageHeader title={t('Affiliate Settings')} />
            <div className="row">
                <div className="col-lg-8">
                    <div className="card">
                        <div className="card-body">
                            <form onSubmit={submit}>
                                <fieldset className="mb-4">
                                    <legend className="fs-6 fw-bold">{t('Payment methods')}</legend>
                                    <div className="form-check form-switch mb-2">
                                        <input className="form-check-input" type="checkbox" role="switch" id="as-cash" checked={data.cash_on_pickup_enabled} onChange={(e) => setData('cash_on_pickup_enabled', e.target.checked)} />
                                        <label className="form-check-label" htmlFor="as-cash">
                                            {t('Enable Cash on Pickup')}
                                        </label>
                                    </div>
                                    <div className="form-check form-switch">
                                        <input className="form-check-input" type="checkbox" role="switch" id="as-paystack" checked={data.paystack_enabled} onChange={(e) => setData('paystack_enabled', e.target.checked)} />
                                        <label className="form-check-label" htmlFor="as-paystack">
                                            {t('Enable Paystack Online Payment')}
                                        </label>
                                    </div>
                                    {bothOff && <small className="text--danger d-block mt-1">{t('Keep at least one payment method on, or the shop cannot take orders.')}</small>}
                                    <FieldError message={errors.paystack_enabled} />
                                </fieldset>
                                <div className="row">
                                    <div className="col-md-6 mb-3">
                                        <label className="form-label" htmlFor="as-cookie">
                                            {t('Attribution Cookie Window (days)')}
                                        </label>
                                        <input id="as-cookie" type="number" min={1} max={365} className="form-control" required value={data.cookie_days} onChange={(e) => setData('cookie_days', e.target.value)} />
                                        <small className="text-muted">{t('How long a click keeps crediting the affiliate for a later purchase.')}</small>
                                        <FieldError message={errors.cookie_days} />
                                    </div>
                                    <div className="col-md-6 mb-3">
                                        <label className="form-label" htmlFor="as-expiry">
                                            {t('Pending Order Expiry (days)')}
                                        </label>
                                        <input id="as-expiry" type="number" min={1} max={90} className="form-control" required value={data.pending_order_expiry_days} onChange={(e) => setData('pending_order_expiry_days', e.target.value)} />
                                        <small className="text-muted">{t('Unpaid / unpicked-up orders auto-cancel after this many days.')}</small>
                                        <FieldError message={errors.pending_order_expiry_days} />
                                    </div>
                                </div>
                                <hr />
                                <h6 className="fw-bold mb-1">{t('Stockist Pickup Handling Fee')}</h6>
                                <p className="text-muted small">{t('Paid to the Stockist who confirms pickup for a Cash on Pickup order.')}</p>
                                <div className="row">
                                    <div className="col-md-6 mb-3">
                                        <label className="form-label" htmlFor="as-fee-type">
                                            {t('Fee Type')}
                                        </label>
                                        <select id="as-fee-type" className="form-control" value={data.stockist_pickup_fee_type} onChange={(e) => setData('stockist_pickup_fee_type', e.target.value)}>
                                            <option value="fixed">{t('Fixed Amount')}</option>
                                            <option value="percentage">{t('Percentage of Order')}</option>
                                        </select>
                                    </div>
                                    <div className="col-md-6 mb-3">
                                        <label className="form-label" htmlFor="as-fee">
                                            {t('Fee Value')} {percent ? '(%)' : '(₦)'}
                                        </label>
                                        <input id="as-fee" type="number" step="any" min={0} max={percent ? 100 : undefined} className="form-control" value={data.stockist_pickup_fee_value} onChange={(e) => setData('stockist_pickup_fee_value', e.target.value)} />
                                        <FieldError message={errors.stockist_pickup_fee_value} />
                                    </div>
                                </div>
                                <button type="submit" className="btn btn--primary mt-2" disabled={form.processing || bothOff}>
                                    {t('Save Settings')}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

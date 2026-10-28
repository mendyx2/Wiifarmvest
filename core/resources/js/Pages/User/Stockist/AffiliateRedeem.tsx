import { Head, router, usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import Sku from '@/Components/Shop/Sku';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type Order = { code: string; buyer: string; payment: string; total: string; items: { name: string; sku: string | null; quantity: number; total: string }[] };

type Props = PageProps<{ code: string; order: Order | null; error: string | null; redeemUrl: string; confirmUrl: string }>;

export default function AffiliateRedeem({ code, order, error, redeemUrl, confirmUrl }: Props) {
    const t = useTrans();
    const errors = (usePage().props as { errors?: Record<string, string> }).errors ?? {};
    const [value, setValue] = useState(code);
    const [busy, setBusy] = useState(false);
    const message = errors.order ?? error;

    const verify = (event: FormEvent) => {
        event.preventDefault();
        if (!value.trim()) return;
        router.get(redeemUrl, { code: value.trim().toUpperCase() }, { preserveState: true, preserveScroll: true, onStart: () => setBusy(true), onFinish: () => setBusy(false) });
    };

    const confirm = () => {
        if (!order || busy) return;
        router.post(confirmUrl, { order_code: order.code }, {
            preserveScroll: true,
            onStart: () => setBusy(true),
            onFinish: () => setBusy(false),
            onSuccess: () => setValue(''),
        });
    };

    return (
        <div className="nc-wrap" id="ncWrap">
            <Head title={t('Redeem Affiliate Products')} />
            <br />
            <div className="sl-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div>
                    <h4 className="sl-page-title mb-1">{t('Redeem Affiliate Products')}</h4>
                    <p className="sl-page-subtitle mb-0">{t("Enter a buyer's redemption code to hand over their /shop order.")}</p>
                </div>
            </div>
            <div className="row g-4">
                <div className="col-lg-5">
                    <form className="aff-stat-card" onSubmit={verify}>
                        <label className="shop-form-label" htmlFor="orderCodeInput">{t('Redemption Code')}</label>
                        <div className="input-group mb-3">
                            <input type="text" id="orderCodeInput" className="form-control text-uppercase" placeholder="AF-XXXXXXXX" autoComplete="off" value={value} onChange={(e) => setValue(e.target.value)} />
                            <button className="btn btn-success" type="submit" disabled={busy || !value.trim()}>{t('Verify')}</button>
                        </div>
                        {message && <div className="alert alert-danger py-2 px-3 small mb-0" role="alert">{message}</div>}
                    </form>
                </div>
                <div className="col-lg-7">
                    {order && (
                        <div className="aff-stat-card">
                            <h6 className="fw-bold mb-3">{t('Order Details')}</h6>
                            <table className="table table-sm">
                                <tbody>
                                    <tr><td className="text-muted">{t('Code')}</td><td className="fw-bold">{order.code}</td></tr>
                                    <tr><td className="text-muted">{t('Buyer')}</td><td>{order.buyer}</td></tr>
                                    <tr><td className="text-muted">{t('Payment')}</td><td>{t(order.payment)}</td></tr>
                                    <tr><td className="text-muted">{t('Total')}</td><td className="fw-bold">{order.total}</td></tr>
                                </tbody>
                            </table>
                            <table className="table table-sm">
                                <thead><tr><th>{t('Product')}</th><th>{t('Qty')}</th><th>{t('Line Total')}</th></tr></thead>
                                <tbody>
                                    {order.items.map((item, i) => (
                                        <tr key={i}>
                                            <td>{item.name} <Sku value={item.sku} className="ms-1" /></td>
                                            <td>{item.quantity}</td>
                                            <td>{item.total}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                            <button type="button" className="shop-buy-btn" onClick={confirm} disabled={busy}>
                                <i className={`las ${busy ? 'la-spinner la-spin' : 'la-check'}`} /> {t('Confirm Pickup')}
                            </button>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

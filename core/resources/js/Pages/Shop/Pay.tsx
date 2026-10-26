import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { withPublicLayout } from '@/Layouts/PublicLayout';
import { useTrans } from '@/lib/i18n';
import { showNotifications } from '@/lib/notify';

type Props = {
    order: { code: string; total: string };
    paystack: { key: string; email: string; amount: number; currency: string; ref: string; metadata: Record<string, string> };
    urls: { callback: string; script: string };
};

type PaystackHandler = { openIframe: () => void };
declare global {
    interface Window {
        PaystackPop?: { setup: (options: Record<string, unknown>) => PaystackHandler };
    }
}

/** Paystack's inline script, loaded once. */
let paystackScript: Promise<void> | null = null;
function loadPaystack(src: string): Promise<void> {
    if (window.PaystackPop) return Promise.resolve();
    paystackScript ??= new Promise<void>((resolve, reject) => {
        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        script.onload = () => resolve();
        script.onerror = () => {
            paystackScript = null;
            script.remove();
            reject(new Error('Paystack did not load'));
        };
        document.head.appendChild(script);
    });
    return paystackScript;
}

/**
 * Online payment for a shop order (/shop/order/{code}/pay), with Paystack's
 * pop-up. On success Paystack hands back a reference and the browser goes to
 * the callback, which checks the payment with Paystack before marking the order paid.
 */
export default function ShopPay({ order, paystack, urls }: Props) {
    const t = useTrans();
    const [ready, setReady] = useState(!!window.PaystackPop);

    useEffect(() => {
        loadPaystack(urls.script)
            .then(() => setReady(true))
            .catch(() => showNotifications([['error', t('The payment window could not load. Check your connection and refresh the page.')]]));
    }, [urls.script]); // eslint-disable-line react-hooks/exhaustive-deps

    const pay = () => {
        if (!window.PaystackPop) return;
        window.PaystackPop.setup({
            ...paystack,
            callback: (response: { reference: string }) => {
                // A normal page load: the callback is a server redirect, like the old page.
                window.location.href = `${urls.callback}?reference=${encodeURIComponent(response.reference)}`;
            },
            onClose: () => {},
        }).openIframe();
    };

    return (
        <div className="shop-wrap">
            <Head title={t('Complete Payment')} />
            <div className="shop-container shop-container--narrow">
                <div className="shop-success-card">
                    <div className="shop-success-icon">
                        <i className="las la-lock" />
                    </div>
                    <h4 className="fw-bold mb-1">{t('Complete Your Payment')}</h4>
                    <p className="text-muted">
                        {t('Order')} <strong>{order.code}</strong>
                    </p>
                    <div className="shop-detail-price">{order.total}</div>
                    <button type="button" className="shop-buy-btn w-100 mt-3" id="payNowBtn" disabled={!ready} onClick={pay}>
                        <i className={ready ? 'las la-credit-card' : 'las la-spinner la-spin'} /> {t('Pay Now')}
                    </button>
                </div>
            </div>
        </div>
    );
}

ShopPay.layout = withPublicLayout;

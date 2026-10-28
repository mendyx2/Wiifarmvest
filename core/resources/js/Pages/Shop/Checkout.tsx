import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Sku from '@/Components/Shop/Sku';
import { withPublicLayout } from '@/Layouts/PublicLayout';
import { useTrans } from '@/lib/i18n';

type Props = {
    lines: { id: number; name: string; sku: string | null; quantity: number; lineTotal: string }[];
    subtotal: string;
    currency: string;
    states: { id: number; name: string }[];
    cashOnPickup: boolean;
    urls: { cart: string; submit: string };
};

/** Buyer details and payment choice (/shop/checkout). Pay Online continues to Shop/Pay. */
export default function ShopCheckout({ lines, subtotal, currency, states, cashOnPickup, urls }: Props) {
    const t = useTrans();
    const form = useForm({
        name: '',
        email: '',
        phone: '',
        state_id: '',
        payment_method: 'paystack',
        website: '', // honeypot, see ShopCheckoutController@store
    });
    const { data, setData, errors, processing } = form;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(urls.submit, { preserveScroll: true });
    };

    const fieldError = (name: keyof typeof errors) =>
        errors[name] ? (
            <div className="shop-field-error" id={`${name}-error`}>
                {errors[name]}
            </div>
        ) : null;

    return (
        <div className="shop-wrap">
            <Head title={t('Checkout')} />
            <div className="shop-container">
                <Link href={urls.cart} className="shop-back-link">
                    <i className="las la-arrow-left" /> {t('Back to Cart')}
                </Link>
                <h2 className="shop-related-heading mt-0">{t('Checkout')}</h2>

                <div className="shop-checkout-grid">
                    <form onSubmit={submit} className="shop-form-card" noValidate>
                        {/* Honeypot: hidden from real users, bots tend to fill every field */}
                        <div className="shop-honeypot" aria-hidden="true">
                            <label htmlFor="website">Website</label>
                            <input type="text" name="website" id="website" tabIndex={-1} autoComplete="off" value={data.website} onChange={(e) => setData('website', e.target.value)} />
                        </div>

                        <div className="shop-field">
                            <label className="shop-form-label" htmlFor="buyer-name">{t('Full Name')}</label>
                            <input
                                type="text"
                                id="buyer-name"
                                className="shop-input"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                autoComplete="name"
                                required
                                aria-invalid={!!errors.name}
                                aria-describedby={errors.name ? 'name-error' : undefined}
                            />
                            {fieldError('name')}
                        </div>

                        <div className="shop-form-row">
                            <div className="shop-field">
                                <label className="shop-form-label" htmlFor="buyer-email">{t('Email Address')}</label>
                                <input
                                    type="email"
                                    id="buyer-email"
                                    className="shop-input"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    autoComplete="email"
                                    required
                                    aria-invalid={!!errors.email}
                                    aria-describedby={errors.email ? 'email-error' : undefined}
                                />
                                {fieldError('email')}
                            </div>
                            <div className="shop-field">
                                <label className="shop-form-label" htmlFor="buyer-phone">{t('Phone Number (optional)')}</label>
                                <input
                                    type="tel"
                                    id="buyer-phone"
                                    className="shop-input"
                                    value={data.phone}
                                    onChange={(e) => setData('phone', e.target.value)}
                                    autoComplete="tel"
                                    aria-invalid={!!errors.phone}
                                    aria-describedby={errors.phone ? 'phone-error' : undefined}
                                />
                                {fieldError('phone')}
                            </div>
                        </div>

                        <div className="shop-field">
                            <label className="shop-form-label" htmlFor="buyer-state">{t('Delivery / Pickup State')}</label>
                            <select
                                id="buyer-state"
                                className="shop-input"
                                value={data.state_id}
                                onChange={(e) => setData('state_id', e.target.value)}
                                required
                                aria-invalid={!!errors.state_id}
                                aria-describedby={errors.state_id ? 'state_id-error' : undefined}
                            >
                                <option value="">{t('Select your state')}</option>
                                {states.map((state) => (
                                    <option key={state.id} value={state.id}>
                                        {state.name}
                                    </option>
                                ))}
                            </select>
                            {fieldError('state_id')}
                        </div>

                        <div role="radiogroup" aria-labelledby="pay-method-label">
                            <div className="shop-form-label" id="pay-method-label">{t('Payment Method')}</div>
                            <div className="shop-pay-options">
                                <label className="shop-pay-option">
                                    <input type="radio" name="payment_method" value="paystack" checked={data.payment_method === 'paystack'} onChange={() => setData('payment_method', 'paystack')} />
                                    <i className="las la-credit-card" />
                                    <div>
                                        <div className="fw-bold small">{t('Pay Online')}</div>
                                        <div className="text-muted shop-pay-hint">{t('Card, bank, USSD via Paystack')}</div>
                                    </div>
                                </label>
                                <label className={`shop-pay-option ${cashOnPickup ? '' : 'disabled'}`}>
                                    <input
                                        type="radio"
                                        name="payment_method"
                                        value="cash_on_pickup"
                                        disabled={!cashOnPickup}
                                        checked={data.payment_method === 'cash_on_pickup'}
                                        onChange={() => setData('payment_method', 'cash_on_pickup')}
                                    />
                                    <i className="las la-hand-holding-usd" />
                                    <div>
                                        <div className="fw-bold small">{t('Cash on Pickup')}</div>
                                        <div className="text-muted shop-pay-hint">{cashOnPickup ? t('Pay when you collect your order') : t('Currently unavailable')}</div>
                                    </div>
                                </label>
                            </div>
                            {fieldError('payment_method')}
                        </div>

                        <button type="submit" className="shop-buy-btn w-100 mt-3" disabled={processing}>
                            <i className={processing ? 'las la-spinner la-spin' : 'las la-lock'} /> {t('Place Order')}
                        </button>
                    </form>

                    <div className="shop-summary">
                        <p className="shop-summary-heading">{t('Order Summary')}</p>
                        {lines.map((line) => (
                            <div className="shop-summary-row" key={line.id}>
                                <span>
                                    {line.name} &times; {line.quantity} <Sku value={line.sku} className="ms-1" />
                                </span>
                                <span>{line.lineTotal}</span>
                            </div>
                        ))}
                        <div className="shop-summary-total">
                            <span>{t('Total')}</span>
                            <span>
                                {subtotal} <small className="shop-cur">{currency}</small>
                            </span>
                        </div>
                        {/* The old note said the total depends on the state. It does not:
                            the shop has one price everywhere (ShopCartService::resolve). */}
                        <p className="text-muted small mt-2 mb-0">{t('Prices are the same in every state. Your state tells us where you will collect your order.')}</p>
                    </div>
                </div>
            </div>
        </div>
    );
}

ShopCheckout.layout = withPublicLayout;

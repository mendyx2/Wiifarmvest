import { useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { ConfirmButton, FieldError, withId } from '@/Components/Admin/Form';
import GatewayTabs from '@/Components/Admin/GatewayTabs';
import ImageUploader from '@/Components/Admin/ImageUploader';
import PageHeader from '@/Components/Admin/PageHeader';
import { useCopy } from '@/lib/clipboard';
import { useTrans } from '@/lib/i18n';

type Param = { key: string; title: string; secret: boolean; value?: string; stored?: boolean };
type Currency = {
    id: number | null;
    currency: string;
    symbol: string;
    name: string;
    min_amount: string;
    max_amount: string;
    fixed_charge: string;
    percent_charge: string;
    rate: string;
    param: Record<string, string>;
    stored: Record<string, boolean>;
};

type Props = {
    gateway: { name: string; code: string; image: string; crypto: boolean; extra: { key: string; title: string; url: string }[] };
    global: Param[];
    perCurrency: Param[];
    currencies: Currency[];
    available: { code: string; symbol: string }[];
    siteCurrency: string;
    urls: { tabs: { automatic: string; manual: string }; update: string; remove: string };
};

/**
 * One automatic gateway: its picture, the provider's callback URLs, its API
 * settings, and each currency it accepts. Saved secrets are never shown;
 * leaving their field empty keeps them.
 */
export default function AutomaticEdit({ gateway, global, perCurrency, currencies, available, siteCurrency, urls }: Props) {
    const t = useTrans();
    const [adding, setAdding] = useState('');
    const form = useForm<{ image: File | null; global: Record<string, string>; currency: Currency[] }>({
        image: null,
        global: Object.fromEntries(global.map((p) => [p.key, p.value ?? ''])),
        currency: currencies,
    });
    const notAdded = available.filter((a) => !form.data.currency.some((c) => c.currency === a.code));
    const err = form.errors as Record<string, string>;
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({ ...d, currency: d.currency.map(({ id: _id, stored: _s, ...c }) => c) }));
        form.post(urls.update, { preserveScroll: true, forceFormData: true });
    };
    const setCurrency = (i: number, patch: Partial<Currency>) => form.setData('currency', form.data.currency.map((c, j) => (j === i ? { ...c, ...patch } : c)));
    const addCurrency = () => {
        const a = available.find((x) => x.code === adding);
        if (!a) return;
        form.setData('currency', [
            ...form.data.currency,
            { id: null, currency: a.code, symbol: gateway.crypto ? a.code : a.symbol, name: `${gateway.name} - ${a.code}`, min_amount: '', max_amount: '', fixed_charge: '0', percent_charge: '0', rate: '', param: Object.fromEntries(perCurrency.map((p) => [p.key, ''])), stored: {} },
        ]);
        setAdding('');
    };

    return (
        <>
            <PageHeader title={t('Update Gateway')} />
            <GatewayTabs tabs={urls.tabs} active="automatic" />
            <form onSubmit={submit}>
                <div className="card mb-4">
                    <div className="card-body">
                        <div className="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <h3 className="m-0">{gateway.name}</h3>
                            {notAdded.length > 0 && (
                                <div className="input-group w-auto">
                                    <select className="form-control" aria-label={t('Select currency')} value={adding} onChange={(e) => setAdding(e.target.value)}>
                                        <option value="">{t('Select currency')}</option>
                                        {notAdded.map((a) => (
                                            <option key={a.code} value={a.code}>
                                                {a.code}
                                            </option>
                                        ))}
                                    </select>
                                    <button type="button" className="btn btn--primary" disabled={!adding} onClick={addCurrency}>
                                        {t('Add new')}
                                    </button>
                                </div>
                            )}
                        </div>
                        <div className="row">
                            <div className="col-md-3 form-group">
                                <ImageUploader image={gateway.image} onChange={(f) => form.setData('image', f)} className="w-100" />
                                <FieldError message={err.image} />
                            </div>
                            <div className="col-md-9">
                                {gateway.extra.length > 0 && (
                                    <>
                                        <h4 className="mb-3">{t('Configurations')}</h4>
                                        <div className="row">
                                            {gateway.extra.map((x) => (
                                                <CopyField key={x.key} label={x.title} value={x.url} note={x.key === 'cron' ? t("Set the URL to your server's cron job to validate the payment.") : undefined} />
                                            ))}
                                        </div>
                                    </>
                                )}
                                <h4 className="mb-3">{t('Global Setting for :name', { name: gateway.name })}</h4>
                                <div className="row">
                                    {global.map((p) => (
                                        <ParamField key={p.key} id={`g-${p.key}`} param={p} stored={!!p.stored} value={form.data.global[p.key] ?? ''} onChange={(v) => form.setData('global', { ...form.data.global, [p.key]: v })} error={err[`global.${p.key}`]} />
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {form.data.currency.map((c, i) => (
                    <div className="card mb-4 border--primary" key={c.currency}>
                        <div className="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
                            <h5 className="m-0">
                                {gateway.name} - {c.currency}
                            </h5>
                            {c.id ? (
                                <ConfirmButton url={withId(urls.remove, c.id)} question={t('Are you sure to delete this gateway currency?')} className="btn btn-sm btn--danger">
                                    <i className="la la-trash-o" /> {t('Remove')}
                                </ConfirmButton>
                            ) : (
                                <button type="button" className="btn btn-sm btn--danger" onClick={() => form.setData('currency', form.data.currency.filter((_, j) => j !== i))}>
                                    <i className="la la-times" /> {t('Remove')}
                                </button>
                            )}
                        </div>
                        <div className="card-body">
                            <div className="form-group">
                                <label htmlFor={`c${i}-name`}>{t('Name')}</label>
                                <input id={`c${i}-name`} className="form-control" required value={c.name} onChange={(e) => setCurrency(i, { name: e.target.value })} />
                                <FieldError message={err[`currency.${i}.name`]} />
                            </div>
                            <div className="row">
                                {(
                                    [
                                        ['min_amount', t('Minimum Amount'), siteCurrency],
                                        ['max_amount', t('Maximum Amount'), siteCurrency],
                                        ['fixed_charge', t('Fixed Charge'), siteCurrency],
                                        ['percent_charge', t('Percent Charge'), '%'],
                                        ['rate', t('Rate'), c.currency],
                                    ] as [keyof Currency, string, string][]
                                ).map(([key, label, suffix]) => (
                                    <div className="col-md-4 col-xl form-group" key={key}>
                                        <label htmlFor={`c${i}-${key}`}>{label}</label>
                                        <div className="input-group">
                                            {key === 'rate' && <span className="input-group-text">1 {siteCurrency} =</span>}
                                            <input id={`c${i}-${key}`} type="number" step="any" min="0" className="form-control" required value={String(c[key])} onChange={(e) => setCurrency(i, { [key]: e.target.value })} />
                                            <span className="input-group-text">{suffix}</span>
                                        </div>
                                        <FieldError message={err[`currency.${i}.${key}`]} />
                                    </div>
                                ))}
                                <div className="col-md-4 col-xl-2 form-group">
                                    <label htmlFor={`c${i}-symbol`}>{t('Symbol')}</label>
                                    <input id={`c${i}-symbol`} className="form-control" required value={c.symbol} onChange={(e) => setCurrency(i, { symbol: e.target.value })} />
                                    <FieldError message={err[`currency.${i}.symbol`]} />
                                </div>
                            </div>
                            {perCurrency.length > 0 && (
                                <>
                                    <h6 className="mt-2 mb-3">{t('Configuration')}</h6>
                                    <div className="row">
                                        {perCurrency.map((p) => (
                                            <ParamField key={p.key} id={`c${i}-p-${p.key}`} param={p} stored={!!c.stored[p.key]} value={c.param[p.key] ?? ''} onChange={(v) => setCurrency(i, { param: { ...c.param, [p.key]: v } })} error={err[`currency.${i}.param.${p.key}`]} />
                                        ))}
                                    </div>
                                </>
                            )}
                        </div>
                    </div>
                ))}
                {err['currency.0.currency'] && <p className="text--danger">{err['currency.0.currency']}</p>}
                <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing}>
                    {t('Submit')}
                </button>
            </form>
        </>
    );
}

function ParamField({ id, param, stored, value, onChange, error }: { id: string; param: Param; stored: boolean; value: string; onChange: (v: string) => void; error?: string }) {
    const t = useTrans();
    return (
        <div className="col-lg-6 form-group">
            <label htmlFor={id}>{param.title}</label>
            <input
                id={id}
                type={param.secret ? 'password' : 'text'}
                autoComplete={param.secret ? 'new-password' : 'off'}
                className="form-control"
                required={!param.secret || !stored}
                placeholder={param.secret && stored ? t('Saved: leave blank to keep it') : ''}
                value={value}
                onChange={(e) => onChange(e.target.value)}
            />
            {param.secret && stored && (
                <small className="text--success">
                    <i className="las la-lock" /> {t('A value is saved. Type a new one only to replace it.')}
                </small>
            )}
            <FieldError message={error} />
        </div>
    );
}

function CopyField({ label, value, note }: { label: string; value: string; note?: string }) {
    const t = useTrans();
    const { copied, copy } = useCopy();
    return (
        <div className="col-lg-6 form-group">
            <label>{label}</label>
            <div className="input-group">
                <input className="form-control" readOnly value={value} aria-label={label} />
                <button type="button" className="input-group-text" title={t('Copy')} aria-label={t('Copy :name', { name: label })} onClick={() => copy(value)}>
                    <i className={copied ? 'las la-check' : 'las la-copy'} />
                </button>
            </div>
            {note && (
                <small>
                    <i className="las la-info-circle" /> {note}
                </small>
            )}
        </div>
    );
}

import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FieldError } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';

type GeneralForm = {
    site_name: string;
    timezone: string;
    cur_text: string;
    cur_sym: string;
    base_color: string;
    secondary_color: string;
    paginate_number: string;
    currency_format: string;
    bal_trans_fixed_charge: string;
    bal_trans_per_charge: string;
};

type MatchingForm = { total_bv: string; bv_price: string; max_bv: string; cary_flash: string; matching_bonus_time: string; matching_when: string };

type Props = {
    form: GeneralForm;
    timezones: string[];
    /** null when this admin may not change the matching bonus */
    matching: MatchingForm | null;
    urls: { update: string; matching: string };
};

const HOURS = Array.from({ length: 24 }, (_, i) => i + 1);
const DAYS: [string, string][] = [
    ['sat', 'Saturday'],
    ['sun', 'Sunday'],
    ['mon', 'Monday'],
    ['tue', 'Tuesday'],
    ['wed', 'Wednesday'],
    ['thu', 'Thursday'],
    ['fri', 'Friday'],
];

/** Settings → General Setting: site name, currency, colours, timezone, pagination, transfer charges; and the matching bonus. */
export default function GeneralSetting({ form: initial, timezones, matching, urls }: Props) {
    const t = useTrans();
    const form = useForm<GeneralForm>(initial);
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(urls.update, { preserveScroll: true });
    };
    const text = (key: keyof GeneralForm, label: string) => (
        <div className="col-sm-6 form-group">
            <label htmlFor={`gs-${key}`}>{label}</label>
            <input id={`gs-${key}`} className="form-control" required value={form.data[key]} onChange={(e) => form.setData(key, e.target.value)} />
            <FieldError message={form.errors[key]} />
        </div>
    );
    const color = (key: 'base_color' | 'secondary_color', label: string) => {
        const value = form.data[key].replace('#', '');
        return (
            <div className="col-sm-6 form-group">
                <label htmlFor={`gs-${key}`}>{label}</label>
                <div className="input-group">
                    <input type="color" className="form-control form-control-color" style={{ maxWidth: 60 }} aria-label={label} value={/^[0-9a-f]{6}$/i.test(value) ? `#${value}` : '#000000'} onChange={(e) => form.setData(key, e.target.value.slice(1))} />
                    <input id={`gs-${key}`} className="form-control" maxLength={7} value={value} onChange={(e) => form.setData(key, e.target.value.replace('#', ''))} />
                </div>
                <FieldError message={form.errors[key]} />
            </div>
        );
    };

    return (
        <>
            <PageHeader title={t('General Setting')} />
            <div className="card mb-4">
                <div className="card-body">
                    <form onSubmit={submit}>
                        <div className="row">
                            {text('site_name', t('Site Title'))}
                            <div className="col-sm-6 form-group">
                                <label htmlFor="gs-timezone">{t('Timezone')}</label>
                                <select id="gs-timezone" className="form-control" value={form.data.timezone} onChange={(e) => form.setData('timezone', e.target.value)}>
                                    {timezones.map((tz, i) => (
                                        <option key={tz} value={i}>
                                            {tz}
                                        </option>
                                    ))}
                                </select>
                                <FieldError message={form.errors.timezone} />
                            </div>
                            {text('cur_text', t('Currency'))}
                            {text('cur_sym', t('Currency Symbol'))}
                            {color('base_color', t('Site Base Color'))}
                            {color('secondary_color', t('Site Secondary Color'))}
                            <div className="col-sm-6 form-group">
                                <label htmlFor="gs-paginate">{t('Record to Display Per page')}</label>
                                <select id="gs-paginate" className="form-control" value={form.data.paginate_number} onChange={(e) => form.setData('paginate_number', e.target.value)}>
                                    {!['20', '50', '100'].includes(form.data.paginate_number) && <option value={form.data.paginate_number}>{form.data.paginate_number}</option>}
                                    <option value="20">{t('20 items per page')}</option>
                                    <option value="50">{t('50 items per page')}</option>
                                    <option value="100">{t('100 items per page')}</option>
                                </select>
                                <FieldError message={form.errors.paginate_number} />
                            </div>
                            <div className="col-sm-6 form-group">
                                <label htmlFor="gs-format">{t('Currency Showing Format')}</label>
                                <select id="gs-format" className="form-control" value={form.data.currency_format} onChange={(e) => form.setData('currency_format', e.target.value)}>
                                    <option value="1">{t('Show Currency Text and Symbol Both')}</option>
                                    <option value="2">{t('Show Currency Text Only')}</option>
                                    <option value="3">{t('Show Currency Symbol Only')}</option>
                                </select>
                            </div>
                            <div className="col-sm-6 form-group">
                                <label htmlFor="gs-fixed">{t('Balance Transfer Fixed Charge')}</label>
                                <div className="input-group">
                                    <input id="gs-fixed" type="number" step="any" min="0" className="form-control" required value={form.data.bal_trans_fixed_charge} onChange={(e) => form.setData('bal_trans_fixed_charge', e.target.value)} />
                                    <span className="input-group-text">{form.data.cur_text}</span>
                                </div>
                                <FieldError message={form.errors.bal_trans_fixed_charge} />
                            </div>
                            <div className="col-sm-6 form-group">
                                <label htmlFor="gs-percent">{t('Balance Transfer Percentage Charge')}</label>
                                <div className="input-group">
                                    <input id="gs-percent" type="number" step="any" min="0" max="100" className="form-control" required value={form.data.bal_trans_per_charge} onChange={(e) => form.setData('bal_trans_per_charge', e.target.value)} />
                                    <span className="input-group-text">%</span>
                                </div>
                                <FieldError message={form.errors.bal_trans_per_charge} />
                            </div>
                        </div>
                        <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing}>
                            {t('Submit')}
                        </button>
                    </form>
                </div>
            </div>
            {matching && <MatchingBonus initial={matching} url={urls.matching} currency={form.data.cur_text} />}
        </>
    );
}

function MatchingBonus({ initial, url, currency }: { initial: MatchingForm; url: string; currency: string }) {
    const t = useTrans();
    const when = initial.matching_when;
    const form = useForm({
        total_bv: initial.total_bv,
        bv_price: initial.bv_price,
        max_bv: initial.max_bv,
        cary_flash: initial.cary_flash,
        matching_bonus_time: initial.matching_bonus_time,
        daily_time: initial.matching_bonus_time === 'daily' && when ? when : '1',
        weekly_time: initial.matching_bonus_time === 'weekly' && when ? when : 'sat',
        monthly_time: initial.matching_bonus_time === 'monthly' && when ? when : '1',
    });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true });
    };
    const schedule = form.data.matching_bonus_time;

    return (
        <div className="card">
            <div className="card-header">
                <h4 className="card-title m-0">{t('Matching Bonus')}</h4>
            </div>
            <form onSubmit={submit}>
                <div className="card-body">
                    <div className="row align-items-center g-3">
                        <div className="col-md-4">
                            <div className="input-group">
                                <input type="number" min="0" className="form-control" aria-label={t('BV')} required value={form.data.total_bv} onChange={(e) => form.setData('total_bv', e.target.value)} />
                                <span className="input-group-text">{t('BV')}</span>
                            </div>
                            <FieldError message={form.errors.total_bv} />
                        </div>
                        <div className="col-md-1 text-center">
                            <h2 className="m-0" aria-hidden>
                                =
                            </h2>
                        </div>
                        <div className="col-md-3">
                            <div className="input-group">
                                <input type="number" step="any" min="0" className="form-control" aria-label={t('Price')} required value={form.data.bv_price} onChange={(e) => form.setData('bv_price', e.target.value)} />
                                <span className="input-group-text">{currency}</span>
                            </div>
                            <FieldError message={form.errors.bv_price} />
                        </div>
                        <div className="col-md-4">
                            <div className="input-group">
                                <span className="input-group-text">{t('MAX')}</span>
                                <input type="number" min="0" className="form-control" aria-label={t('Maximum BV')} required value={form.data.max_bv} onChange={(e) => form.setData('max_bv', e.target.value)} />
                                <span className="input-group-text">{t('BV')}</span>
                            </div>
                            <FieldError message={form.errors.max_bv} />
                        </div>
                    </div>
                    <div className="row mt-3">
                        <div className="col-lg-4 form-group">
                            <label htmlFor="mb-flush">{t('Carry / Flush')}</label>
                            <select id="mb-flush" className="form-control" value={form.data.cary_flash} onChange={(e) => form.setData('cary_flash', e.target.value)}>
                                <option value="0">{t('Carry (Cut Only Paid BV)')}</option>
                                <option value="1">{t('Flush (Cut Weak Leg Value)')}</option>
                                <option value="2">{t('Flush (Cut All BV and reset to 0)')}</option>
                            </select>
                        </div>
                        <div className="col-md-4 form-group">
                            <label htmlFor="mb-time">{t('Matching Bonus Time')}</label>
                            <select id="mb-time" className="form-control" value={schedule} onChange={(e) => form.setData('matching_bonus_time', e.target.value)}>
                                <option value="daily">{t('Daily')}</option>
                                <option value="weekly">{t('Weekly')}</option>
                                <option value="monthly">{t('Monthly')}</option>
                            </select>
                        </div>
                        <div className="col-md-4 form-group">
                            {schedule === 'daily' && (
                                <>
                                    <label htmlFor="mb-daily">{t('Daily Time')}</label>
                                    <select id="mb-daily" className="form-control" value={form.data.daily_time} onChange={(e) => form.setData('daily_time', e.target.value)}>
                                        {HOURS.map((h) => (
                                            <option key={h} value={h}>
                                                {String(h).padStart(2, '0')}:00
                                            </option>
                                        ))}
                                    </select>
                                    <FieldError message={form.errors.daily_time} />
                                </>
                            )}
                            {schedule === 'weekly' && (
                                <>
                                    <label htmlFor="mb-weekly">{t('Weekly Time')}</label>
                                    <select id="mb-weekly" className="form-control" value={form.data.weekly_time} onChange={(e) => form.setData('weekly_time', e.target.value)}>
                                        {DAYS.map(([v, l]) => (
                                            <option key={v} value={v}>
                                                {t(l)}
                                            </option>
                                        ))}
                                    </select>
                                    <FieldError message={form.errors.weekly_time} />
                                </>
                            )}
                            {schedule === 'monthly' && (
                                <>
                                    <label htmlFor="mb-monthly">{t('Monthly Time')}</label>
                                    <select id="mb-monthly" className="form-control" value={form.data.monthly_time} onChange={(e) => form.setData('monthly_time', e.target.value)}>
                                        <option value="1">{t('1st day of Month')}</option>
                                        <option value="15">{t('15th day of Month')}</option>
                                    </select>
                                    <FieldError message={form.errors.monthly_time} />
                                </>
                            )}
                        </div>
                    </div>
                    <button type="submit" className="btn btn--primary h-45 w-100" disabled={form.processing}>
                        {t('Update')}
                    </button>
                </div>
            </form>
        </div>
    );
}

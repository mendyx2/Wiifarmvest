import { Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { FieldError } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';

type Member = { id: number; name: string; username: string; email: string; mobile: string; balance: number; balanceLabel: string; joined: string };

type Props = {
    states: { id: number; name: string; code: string }[];
    urls: { search: string; activate: string; dashboard: string };
};

/**
 * Activate New Stockist: find the member, then fill in the store. The
 * activation cost comes out of the member's Money Box and opens the
 * stockist wallet. The old page wrote the member's name into the page as
 * raw HTML and loaded select2 from an outside CDN.
 */
export default function Activate({ states, urls }: Props) {
    const t = useTrans();
    const [username, setUsername] = useState('');
    const [searching, setSearching] = useState(false);
    const [message, setMessage] = useState('');
    const [member, setMember] = useState<Member | null>(null);
    const form = useForm({ user_id: '', store_type: '', activation_cost: '', state_id: '', business_name: '', business_email: '', business_phone: '' });

    const search = async (e: FormEvent) => {
        e.preventDefault();
        if (!username.trim()) return;
        setSearching(true);
        setMessage('');
        setMember(null);
        try {
            const r = await fetch(`${urls.search}?username=${encodeURIComponent(username.trim())}`, { headers: { Accept: 'application/json' } });
            const body = (await r.json()) as { success: boolean; message?: string; user?: Member };
            if (body.success && body.user) {
                setMember(body.user);
                form.setData((d) => ({ ...d, user_id: String(body.user!.id), business_email: d.business_email || body.user!.email, business_phone: d.business_phone || body.user!.mobile }));
            } else {
                setMessage(body.message ?? t('No member with that username.'));
            }
        } catch {
            setMessage(t('The search failed. Please try again.'));
        } finally {
            setSearching(false);
        }
    };

    const cost = parseFloat(form.data.activation_cost) || 0;
    const short = member ? cost > member.balance : false;
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(urls.activate);
    };
    const reset = () => {
        setMember(null);
        setUsername('');
        form.reset();
        form.clearErrors();
    };

    return (
        <>
            <PageHeader title={t('Activate New Stockist')}>
                <Link href={urls.dashboard} className="btn btn-outline--primary">
                    <i className="las la-arrow-left" /> {t('Back to Dashboard')}
                </Link>
            </PageHeader>
            <div className="card mb-4">
                <div className="card-body">
                    <form onSubmit={search} role="search">
                        <label htmlFor="sk-username" className="form-label fw-bold">
                            {t('Find the member by username')}
                        </label>
                        <div className="input-group">
                            <input id="sk-username" className="form-control" autoComplete="off" placeholder={t('Enter username...')} value={username} onChange={(e) => setUsername(e.target.value)} />
                            <button className="btn btn--primary" type="submit" disabled={searching || !username.trim()}>
                                <i className={searching ? 'las la-spinner la-spin' : 'las la-search'} /> {searching ? t('Searching...') : t('Search')}
                            </button>
                        </div>
                    </form>
                    {message && (
                        <div className="alert alert-warning mt-3 mb-0" role="alert">
                            {message}
                        </div>
                    )}
                    <FieldError message={form.errors.user_id} />
                </div>
            </div>

            {member && (
                <div className="card">
                    <div className="card-header">
                        <h5 className="card-title mb-0">
                            <i className="las la-user-check text--success" /> {member.name} <small className="text-muted">@{member.username}</small>
                        </h5>
                    </div>
                    <div className="card-body">
                        <ul className="list-group list-group-flush mb-4">
                            <li className="list-group-item d-flex justify-content-between">
                                <span>{t('Email')}</span> <span>{member.email}</span>
                            </li>
                            <li className="list-group-item d-flex justify-content-between">
                                <span>{t('Money Box')}</span> <strong className="text--success">{member.balanceLabel}</strong>
                            </li>
                            <li className="list-group-item d-flex justify-content-between">
                                <span>{t('Member since')}</span> <span>{member.joined}</span>
                            </li>
                        </ul>
                        <form onSubmit={submit}>
                            <fieldset className="form-group">
                                <legend className="form-label fw-bold fs-6">
                                    {t('Store Type')} <span className="text--danger">*</span>
                                </legend>
                                <div className="d-flex gap-4 flex-wrap">
                                    {[
                                        ['1', t('Mega Store'), 'las la-store-alt'],
                                        ['2', t('Mini Store'), 'las la-store'],
                                    ].map(([value, label, icon]) => (
                                        <label className="form-check d-flex align-items-center gap-2 mb-0" key={value}>
                                            <input className="form-check-input" type="radio" name="store_type" value={value} required checked={form.data.store_type === value} onChange={() => form.setData('store_type', value)} />
                                            <i className={icon} /> {label}
                                        </label>
                                    ))}
                                </div>
                                <FieldError message={form.errors.store_type} />
                            </fieldset>
                            <div className="row">
                                <div className="col-md-6 form-group">
                                    <label htmlFor="sk-cost" className="required">
                                        {t('Activation Cost')}
                                    </label>
                                    <div className="input-group">
                                        <span className="input-group-text">₦</span>
                                        <input id="sk-cost" className="form-control" type="number" step="0.01" min="0" required value={form.data.activation_cost} onChange={(e) => form.setData('activation_cost', e.target.value)} />
                                    </div>
                                    <small className={short ? 'text--danger' : 'text-muted'}>
                                        {short ? t('More than the member has (:balance).', { balance: member.balanceLabel }) : t("Taken from the member's Money Box; it opens the stockist wallet.")}
                                    </small>
                                    <FieldError message={form.errors.activation_cost} />
                                </div>
                                <div className="col-md-6 form-group">
                                    <label htmlFor="sk-state" className="required">
                                        {t('State')}
                                    </label>
                                    <select id="sk-state" className="form-control" required value={form.data.state_id} onChange={(e) => form.setData('state_id', e.target.value)}>
                                        <option value="">{t('Select State')}</option>
                                        {states.map((s) => (
                                            <option key={s.id} value={s.id}>
                                                {s.name} ({s.code})
                                            </option>
                                        ))}
                                    </select>
                                    <FieldError message={form.errors.state_id} />
                                </div>
                                <div className="col-md-12 form-group">
                                    <label htmlFor="sk-name" className="required">
                                        {t('Business Name')}
                                    </label>
                                    <input id="sk-name" className="form-control" required maxLength={255} value={form.data.business_name} onChange={(e) => form.setData('business_name', e.target.value)} />
                                    <FieldError message={form.errors.business_name} />
                                </div>
                                <div className="col-md-6 form-group">
                                    <label htmlFor="sk-email" className="required">
                                        {t('Business Email')}
                                    </label>
                                    <input id="sk-email" className="form-control" type="email" required maxLength={255} value={form.data.business_email} onChange={(e) => form.setData('business_email', e.target.value)} />
                                    <FieldError message={form.errors.business_email} />
                                </div>
                                <div className="col-md-6 form-group">
                                    <label htmlFor="sk-phone" className="required">
                                        {t('Business Phone')}
                                    </label>
                                    <input id="sk-phone" className="form-control" type="tel" required maxLength={20} value={form.data.business_phone} onChange={(e) => form.setData('business_phone', e.target.value)} />
                                    <FieldError message={form.errors.business_phone} />
                                </div>
                            </div>
                            <div className="d-flex gap-2 flex-wrap">
                                <button type="submit" className="btn btn--success btn-lg" disabled={form.processing || short}>
                                    <i className="las la-check-circle" /> {t('Activate Stockist')}
                                </button>
                                <button type="button" className="btn btn--dark btn-lg" onClick={reset}>
                                    {t('Cancel')}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </>
    );
}

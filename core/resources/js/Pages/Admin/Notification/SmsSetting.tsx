import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FieldError } from '@/Components/Admin/Form';
import { NotificationNav, SecretInput, Shortcodes, TestSend, type NotificationTabs } from '@/Components/Admin/NotificationNav';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';

type Pair = { name: string; value: string };
type Form = {
    sms_method: string;
    clickatell_api_key: string;
    message_bird_api_key: string;
    nexmo_api_key: string;
    nexmo_api_secret: string;
    infobip_username: string;
    infobip_password: string;
    sms_broadcast_username: string;
    sms_broadcast_password: string;
    account_sid: string;
    auth_token: string;
    from: string;
    text_magic_username: string;
    apiv2_key: string;
    custom_api_method: string;
    custom_api_url: string;
    headers: Pair[];
    body: Pair[];
};
type SecretKey = 'clickatell_api_key' | 'message_bird_api_key' | 'nexmo_api_secret' | 'infobip_password' | 'sms_broadcast_password' | 'auth_token' | 'apiv2_key';

type Props = {
    form: Form;
    stored: Record<SecretKey, boolean>;
    urls: { tabs: NotificationTabs; update: string; test: string };
};

const METHODS: [string, string][] = [
    ['clickatell', 'Clickatell'],
    ['infobip', 'Infobip'],
    ['messageBird', 'Message Bird'],
    ['nexmo', 'Nexmo'],
    ['smsBroadcast', 'Sms Broadcast'],
    ['twilio', 'Twilio'],
    ['textMagic', 'Text Magic'],
    ['custom', 'Custom API'],
];

/** Notification Setting → SMS Setting: the SMS gateway and its keys, and a test SMS. */
export default function SmsSetting({ form: initial, stored, urls }: Props) {
    const t = useTrans();
    const form = useForm<Form>(initial);
    const submit = (e: FormEvent) => {
        e.preventDefault();
        const blank = Object.fromEntries(Object.keys(stored).map((k) => [k, '']));
        form.post(urls.update, { preserveScroll: true, onSuccess: () => form.setData((d) => ({ ...d, ...blank })) });
    };
    type TextKey = Exclude<keyof Form, 'headers' | 'body'>;
    const text = (key: TextKey, label: string) => (
        <div className="col-md-6">
            <div className="form-group">
                <label htmlFor={`sms-${key}`}>{label}</label>
                <input id={`sms-${key}`} className="form-control" value={form.data[key]} onChange={(e) => form.setData(key, e.target.value)} />
                <FieldError message={form.errors[key]} />
            </div>
        </div>
    );
    const secret = (key: SecretKey, label: string) => (
        <div className="col-md-6">
            <SecretInput id={`sms-${key}`} label={label} value={form.data[key]} stored={stored[key]} onChange={(v) => form.setData(key, v)} error={form.errors[key]} />
        </div>
    );
    const pairs = (key: 'headers' | 'body', title: string) => (
        <div className="mb-3">
            <div className="d-flex justify-content-between align-items-center mb-2">
                <h6 className="m-0">{title}</h6>
                <button type="button" className="btn btn-sm btn-outline--primary" onClick={() => form.setData(key, [...form.data[key], { name: '', value: '' }])}>
                    <i className="las la-plus" /> {t('Add')}
                </button>
            </div>
            {form.data[key].map((row, i) => (
                <div className="d-flex gap-2 mb-2" key={i}>
                    <input className="form-control" aria-label={`${title} ${i + 1} ${t('Name')}`} placeholder={t('Name')} value={row.name} onChange={(e) => form.setData(key, form.data[key].map((r, j) => (j === i ? { ...r, name: e.target.value } : r)))} />
                    <input className="form-control" aria-label={`${title} ${i + 1} ${t('Value')}`} placeholder={t('Value')} value={row.value} onChange={(e) => form.setData(key, form.data[key].map((r, j) => (j === i ? { ...r, value: e.target.value } : r)))} />
                    <button type="button" className="btn btn--danger" aria-label={t('Remove')} onClick={() => form.setData(key, form.data[key].filter((_, j) => j !== i))}>
                        <i className="las la-times" />
                    </button>
                </div>
            ))}
        </div>
    );
    const method = form.data.sms_method;

    return (
        <>
            <PageHeader title={t('SMS Notification Settings')} />
            <NotificationNav tabs={urls.tabs} active="sms" />
            <div className="card">
                <form onSubmit={submit}>
                    <div className="card-body">
                        <div className="form-group">
                            <label htmlFor="sms-method">{t('Sms Send Method')}</label>
                            <select id="sms-method" className="form-control" value={method} onChange={(e) => form.setData('sms_method', e.target.value)}>
                                {METHODS.map(([v, l]) => (
                                    <option key={v} value={v}>
                                        {t(l)}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <h6 className="mt-4 mb-3">{t(':name Configuration', { name: METHODS.find(([v]) => v === method)?.[1] ?? '' })}</h6>
                        <div className="row">
                            {method === 'clickatell' && secret('clickatell_api_key', t('API Key'))}
                            {method === 'infobip' && (
                                <>
                                    {text('infobip_username', t('Username'))}
                                    {secret('infobip_password', t('Password'))}
                                </>
                            )}
                            {method === 'messageBird' && secret('message_bird_api_key', t('API Key'))}
                            {method === 'nexmo' && (
                                <>
                                    {text('nexmo_api_key', t('API Key'))}
                                    {secret('nexmo_api_secret', t('API Secret'))}
                                </>
                            )}
                            {method === 'smsBroadcast' && (
                                <>
                                    {text('sms_broadcast_username', t('Username'))}
                                    {secret('sms_broadcast_password', t('Password'))}
                                </>
                            )}
                            {method === 'twilio' && (
                                <>
                                    {text('account_sid', t('Account SID'))}
                                    {secret('auth_token', t('Auth Token'))}
                                    {text('from', t('From Number'))}
                                </>
                            )}
                            {method === 'textMagic' && (
                                <>
                                    {text('text_magic_username', t('Username'))}
                                    {secret('apiv2_key', t('Apiv2 Key'))}
                                </>
                            )}
                        </div>
                        {method === 'custom' && (
                            <>
                                <div className="row">
                                    <div className="col-md-3 form-group">
                                        <label htmlFor="sms-custom-method">{t('Method')}</label>
                                        <select id="sms-custom-method" className="form-control" value={form.data.custom_api_method} onChange={(e) => form.setData('custom_api_method', e.target.value)}>
                                            <option value="get">{t('GET')}</option>
                                            <option value="post">{t('POST')}</option>
                                        </select>
                                    </div>
                                    <div className="col-md-9 form-group">
                                        <label htmlFor="sms-custom-url">{t('API URL')}</label>
                                        <input id="sms-custom-url" type="url" className="form-control" placeholder="https://" value={form.data.custom_api_url} onChange={(e) => form.setData('custom_api_url', e.target.value)} />
                                        <FieldError message={form.errors.custom_api_url} />
                                    </div>
                                </div>
                                <Shortcodes codes={{ message: 'Message', number: 'Number' }} />
                                {pairs('headers', t('Headers'))}
                                {pairs('body', t('Body'))}
                            </>
                        )}
                    </div>
                    <div className="card-footer">
                        <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing}>
                            {t('Submit')}
                        </button>
                    </div>
                </form>
            </div>
            <TestSend url={urls.test} field="mobile" type="tel" label={t('Test SMS Setup')} placeholder={t('Mobile')} />
        </>
    );
}

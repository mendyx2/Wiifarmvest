import { Head, useForm } from '@inertiajs/react';
import { useRef, type FormEvent } from 'react';
import { useCopy } from '@/lib/clipboard';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type Props = PageProps<{
    enabled: boolean;
    /** Only sent while 2FA is off: the new secret to add to the authenticator app. */
    secret: string | null;
    qrCodeUrl: string | null;
    enableUrl: string;
    disableUrl: string;
}>;

const AUTHENTICATOR_URL = 'https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2&hl=en';

export default function TwoFactor({ enabled, secret, qrCodeUrl, enableUrl, disableUrl }: Props) {
    const t = useTrans();
    const { copied, copy } = useCopy();
    const keyInput = useRef<HTMLInputElement>(null);
    const form = useForm({ key: secret ?? '', code: '' });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post(enabled ? disableUrl : enableUrl, {
            preserveScroll: true,
            onFinish: () => form.reset('code'),
        });
    }

    return (
        <div className="nc-wrap" id="ncWrap">
            <Head title={t('2FA Security')} />
            <br />
            <div className="row justify-content-center gy-4">
                {!enabled && secret && (
                    <div className="col-md-6">
                        <div className="card custom--card p-0">
                            <div className="card-header bg-success mb-4 p-3">
                                <h4 className="p-0 m-0 text-white">{t('Add Your Account')}</h4>
                            </div>
                            <div className="card-body p-4">
                                <h6 className="mb-3">
                                    {t('Use the QR code or setup key on your Google Authenticator app to add your account. ')}
                                </h6>
                                {qrCodeUrl && (
                                    <div className="form--group mx-auto text-center">
                                        <img className="mx-auto" src={qrCodeUrl} alt={t('QR code')} />
                                    </div>
                                )}
                                <div className="form-group form--group">
                                    <label className="form--label" htmlFor="setup-key">{t('Setup Key')}</label>
                                    <div className="input-group">
                                        <input
                                            ref={keyInput}
                                            id="setup-key"
                                            type="text"
                                            value={secret}
                                            className="form-control form--control"
                                            readOnly
                                            onFocus={(e) => e.target.select()}
                                        />
                                        <button type="button" className="input-group-text" onClick={() => secret && copy(secret, keyInput.current)} aria-label={t('Copy')}>
                                            <i className={copied ? 'fas fa-check' : 'fas fa-copy'} />
                                        </button>
                                    </div>
                                </div>
                                <label>
                                    <i className="fas fa-info-circle" /> {t('Help')}
                                </label>
                                <p>
                                    {t('Google Authenticator is a multifactor app for mobile devices. It generates timed codes used during the 2-step verification process. To use Google Authenticator, install the Google Authenticator application on your mobile device.')}{' '}
                                    <a className="text--base" href={AUTHENTICATOR_URL} target="_blank" rel="noopener noreferrer">
                                        {t('Download')}
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                <div className="col-md-6">
                    <div className="card custom--card p-0">
                        <div className="card-header bg-success mb-4 p-3">
                            <h4 className="p-0 m-0 text-white">{enabled ? t('Disable 2FA Security') : t('Enable 2FA Security')}</h4>
                        </div>
                        <form onSubmit={submit} className="card-body p-4">
                            <div className="form--group mb-3">
                                <label className="form--label" htmlFor="code">{t('Google Authenticator OTP')}</label>
                                <input
                                    id="code"
                                    type="text"
                                    inputMode="numeric"
                                    autoComplete="one-time-code"
                                    className="form-control form--control"
                                    value={form.data.code}
                                    onChange={(e) => form.setData('code', e.target.value)}
                                    required
                                />
                                {form.errors.code && <small className="d-block mt-1" style={{ color: '#DC2626' }}>{form.errors.code}</small>}
                            </div>
                            <button type="submit" className="btn btn--base w-100" disabled={form.processing}>
                                {t('Submit')}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
}

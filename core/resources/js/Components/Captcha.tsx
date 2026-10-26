import { useEffect, useRef, useState } from 'react';
import { useTrans } from '@/lib/i18n';

/** App\Support\CaptchaPresenter::props() */
export type CaptchaConfig = {
    google: string | null;
    custom: { digits: [string, number][]; secret: string; color: string } | null;
};

/** The extra fields App\Lib\Captcha::verify() reads. */
export type CaptchaFields = { 'g-recaptcha-response'?: string; captcha?: string; captcha_secret?: string };

declare global {
    interface Window {
        grecaptcha?: { render: (el: HTMLElement, opts: { sitekey: string; callback: (token: string) => void; 'expired-callback': () => void }) => number; reset: (id?: number) => void };
        __onRecaptchaLoad?: () => void;
    }
}

/**
 * The captchas an admin can switch on in Extensions. Renders nothing when
 * neither is on. `onChange` gets the fields to send with the form. Give it a
 * new `key` after a failed submit so a fresh custom code is shown (the
 * server sends a new one with every page response).
 */
export default function Captcha({ config, onChange }: { config: CaptchaConfig; onChange: (fields: CaptchaFields) => void }) {
    const t = useTrans();
    const box = useRef<HTMLDivElement>(null);
    const [answer, setAnswer] = useState('');
    const [google, setGoogle] = useState('');

    useEffect(() => {
        onChange({
            ...(config.google ? { 'g-recaptcha-response': google } : {}),
            ...(config.custom ? { captcha: answer, captcha_secret: config.custom.secret } : {}),
        });
    }, [answer, google, config.custom?.secret]); // eslint-disable-line react-hooks/exhaustive-deps

    // Google reCAPTCHA 2: load the script once, then draw the checkbox in our box.
    useEffect(() => {
        const siteKey = config.google;
        if (!siteKey || !box.current) return;
        const el = box.current;
        const draw = () =>
            window.grecaptcha?.render(el, {
                sitekey: siteKey,
                callback: (token) => setGoogle(token),
                'expired-callback': () => setGoogle(''),
            });
        if (window.grecaptcha?.render) {
            draw();
        } else {
            window.__onRecaptchaLoad = draw;
            if (!document.getElementById('recaptcha-script')) {
                const script = Object.assign(document.createElement('script'), {
                    id: 'recaptcha-script',
                    src: 'https://www.google.com/recaptcha/api.js?onload=__onRecaptchaLoad&render=explicit',
                    async: true,
                    defer: true,
                });
                document.head.append(script);
            }
        }
    }, [config.google]);

    if (!config.google && !config.custom) return null;

    return (
        <div className="captcha-block mb-3">
            {config.google && <div ref={box} className="mb-3" />}
            {config.custom && (
                <div className="form-group">
                    <link href="https://fonts.googleapis.com/css?family=Henny+Penny&display=swap" rel="stylesheet" />
                    <div
                        className="captcha-code mb-2"
                        aria-hidden="true"
                        style={{
                            height: 46,
                            lineHeight: '46px',
                            width: '100%',
                            background: '#003',
                            color: config.custom.color,
                            fontSize: 26,
                            fontWeight: 'bold',
                            letterSpacing: 20,
                            fontFamily: "'Henny Penny', cursive",
                            userSelect: 'none',
                            display: 'flex',
                            justifyContent: 'center',
                            borderRadius: 8,
                        }}
                    >
                        {config.custom.digits.map(([digit, tilt], i) => (
                            <span key={i} style={{ display: 'inline-block', transform: `rotate(${tilt}deg)` }}>
                                {digit}
                            </span>
                        ))}
                    </div>
                    <label className="form-label" htmlFor="captcha">
                        {t('Captcha')}
                    </label>
                    <input id="captcha" type="text" className="form-control" placeholder={t('Enter Captcha Code')} value={answer} onChange={(e) => setAnswer(e.target.value)} required autoComplete="off" />
                </div>
            )}
        </div>
    );
}

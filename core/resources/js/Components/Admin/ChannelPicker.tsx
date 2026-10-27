import { useTrans } from '@/lib/i18n';

export type Channel = 'email' | 'sms' | 'push';

const CHANNELS: Record<Channel, { icon: string; label: string }> = {
    email: { icon: 'las la-envelope', label: 'Send Via Email' },
    sms: { icon: 'las la-mobile-alt', label: 'Send Via SMS' },
    push: { icon: 'las la-bell', label: 'Send Via Firebase' },
};

/** The "Send via" cards of the notification pages (only the channels switched on in settings). */
export default function ChannelPicker({ channels, value, onChange }: { channels: Channel[]; value: Channel; onChange: (c: Channel) => void }) {
    const t = useTrans();
    return (
        <div className="row" role="radiogroup" aria-label={t('Send via')}>
            {channels.map((c) => (
                <div className="col-xxl-2 col-xl-3 col-lg-4 col-md-3 col-sm-6" key={c}>
                    <button
                        type="button"
                        role="radio"
                        aria-checked={value === c}
                        className={`notification-via mb-4 w-100 ${value === c ? 'active' : ''}`}
                        data-method={c}
                        onClick={() => onChange(c)}
                    >
                        <span className="active-badge">
                            <i className="las la-check" />
                        </span>
                        <span className="send-via-method d-block">
                            <i className={CHANNELS[c].icon} />
                            <h5>{t(CHANNELS[c].label)}</h5>
                        </span>
                    </button>
                </div>
            ))}
        </div>
    );
}

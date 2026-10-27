import { useTrans } from '@/lib/i18n';
import ChannelPicker, { type Channel } from './ChannelPicker';
import RichTextEditor from './RichTextEditor';

export type MessageData = { via: Channel; subject: string; message: string; image: File | null };

type Props = {
    channels: Channel[];
    data: MessageData;
    errors: Partial<Record<keyof MessageData, string>>;
    onChange: (next: Partial<MessageData>) => void;
};

const escape = (text: string) => text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>');
const plain = (html: string) => new DOMParser().parseFromString(html.replace(/<br\s*\/?>/gi, '\n'), 'text/html').body.textContent ?? '';

/**
 * Channel, subject, image and message: the part the two Send Notification
 * pages share. Email gets a formatting toolbar; SMS and push are plain text
 * (the text carries over when the channel changes). Only push takes an
 * image, and SMS has no subject.
 */
export default function NotificationFields({ channels, data, errors, onChange }: Props) {
    const t = useTrans();

    const pick = (via: Channel) => {
        if (via === data.via) return;
        const wasEmail = data.via === 'email';
        const isEmail = via === 'email';
        const message = wasEmail === isEmail ? data.message : isEmail ? escape(data.message) : plain(data.message);
        onChange({ via, message, image: via === 'push' ? data.image : null });
    };

    return (
        <>
            <ChannelPicker channels={channels} value={data.via} onChange={pick} />
            {data.via !== 'sms' && (
                <div className="form-group">
                    <label htmlFor="notify-subject" className="required">
                        {t('Subject')}
                    </label>
                    <input
                        id="notify-subject"
                        type="text"
                        className="form-control"
                        placeholder={t('Subject / Title')}
                        maxLength={255}
                        value={data.subject}
                        onChange={(e) => onChange({ subject: e.target.value })}
                    />
                    <Error message={errors.subject} />
                </div>
            )}
            {data.via === 'push' && (
                <div className="form-group">
                    <label htmlFor="notify-image">{t('Image (optional)')}</label>
                    <input id="notify-image" type="file" className="form-control" accept=".png,.jpg,.jpeg" onChange={(e) => onChange({ image: e.target.files?.[0] ?? null })} />
                    <small className="mt-3 text-muted">
                        {t('Supported Files')}: <b>.png, .jpg, .jpeg</b>
                    </small>
                    <Error message={errors.image} />
                </div>
            )}
            <div className="form-group">
                <label htmlFor="notify-message" className="required">
                    {t('Message')}
                </label>
                {data.via === 'email' ? (
                    <RichTextEditor id="notify-message" label={t('Message')} value={data.message} onChange={(message) => onChange({ message })} />
                ) : (
                    <textarea id="notify-message" className="form-control" rows={8} value={data.message} onChange={(e) => onChange({ message: e.target.value })} />
                )}
                <Error message={errors.message} />
            </div>
            <Error message={errors.via} />
        </>
    );
}

export function Error({ message }: { message?: string }) {
    return message ? <small className="text--danger d-block mt-1">{message}</small> : null;
}

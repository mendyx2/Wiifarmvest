import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import type { AdminUser } from '@/Components/Admin/Cells';
import type { Channel } from '@/Components/Admin/ChannelPicker';
import NotificationFields, { type MessageData } from '@/Components/Admin/NotificationFields';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';

type Props = {
    title: string;
    member: NonNullable<AdminUser>;
    channels: Channel[];
    urls: { send: string };
};

/** Users → member → Send Notification: one email, SMS or push message to this member. */
export default function NotifyOne({ title, member, channels, urls }: Props) {
    const t = useTrans();
    const form = useForm<MessageData>({ via: channels[0], subject: '', message: '', image: null });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(urls.send, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => form.reset('subject', 'message', 'image'),
        });
    };

    return (
        <>
            <PageHeader title={title}>
                <Link href={member.url} className="btn btn-outline--primary">
                    <i className="las la-undo" /> {t('Back')}
                </Link>
            </PageHeader>
            <div className="card">
                <form onSubmit={submit}>
                    <div className="card-body">
                        <p className="text-muted mb-4">
                            {t('To')}: <strong>{member.fullname}</strong> (@{member.username})
                        </p>
                        <NotificationFields channels={channels} data={form.data} errors={form.errors} onChange={(next) => form.setData({ ...form.data, ...next })} />
                    </div>
                    <div className="card-footer">
                        <button type="submit" className="btn w-100 h-45 btn--primary" disabled={form.processing}>
                            {t('Submit')}
                        </button>
                    </div>
                </form>
            </div>
        </>
    );
}

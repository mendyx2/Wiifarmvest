import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FieldError } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import RichTextEditor from '@/Components/Admin/RichTextEditor';
import { useTrans } from '@/lib/i18n';

type Props = { form: { notice: string; free_user_notice: string }; url: string };

/** Notice: the two notices shown on the member dashboard (all members, and free members). */
export default function Notice({ form: initial, url }: Props) {
    const t = useTrans();
    const form = useForm(initial);
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true });
    };

    return (
        <>
            <PageHeader title={t('Notice Settings')} />
            <div className="card">
                <form onSubmit={submit}>
                    <div className="card-body">
                        <div className="form-group">
                            <RichTextEditor id="notice-all" label={t('All user notice')} value={form.data.notice} onChange={(html) => form.setData('notice', html)} />
                            <FieldError message={form.errors.notice} />
                        </div>
                        <div className="form-group">
                            <RichTextEditor id="notice-free" label={t('Free user notice')} value={form.data.free_user_notice} onChange={(html) => form.setData('free_user_notice', html)} />
                            <FieldError message={form.errors.free_user_notice} />
                        </div>
                        <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing}>
                            {t('Update')}
                        </button>
                    </div>
                </form>
            </div>
        </>
    );
}

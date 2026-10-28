import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import FormBuilder, { toGenerator, type BuilderField } from '@/Components/Admin/FormBuilder';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';

type Props = { fields: BuilderField[]; extensions: string[]; url: string };

/** Settings → KYC Setting: the form members fill in to be verified. */
export default function KycSetting({ fields, extensions, url }: Props) {
    const t = useTrans();
    const form = useForm<{ fields: BuilderField[] }>({ fields });
    const dirty = JSON.stringify(form.data.fields) !== JSON.stringify(fields);
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({ form_generator: toGenerator(d.fields) }));
        form.post(url, { preserveScroll: true });
    };

    return (
        <>
            <PageHeader title={t('KYC Setting')} />
            {dirty && (
                <div className="alert alert-warning" role="status">
                    <i className="las la-exclamation-triangle" /> {t("You've to click on the submit button to apply the changes")}
                </div>
            )}
            <form onSubmit={submit}>
                <FormBuilder fields={form.data.fields} onChange={(f) => form.setData('fields', f)} extensions={extensions} errors={form.errors as Record<string, string>} />
                <button type="submit" className="btn btn--primary w-100 h-45 mt-3" disabled={form.processing}>
                    {t('Submit')}
                </button>
            </form>
        </>
    );
}

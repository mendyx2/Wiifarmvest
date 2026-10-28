import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FieldError } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';

type Props = {
    title: string;
    content: string;
    /** The form field the controller reads: css, sitemap or robots */
    field: string;
    mode: 'css' | 'xml' | 'text';
    note: string;
    /** Where the file can be seen on the site */
    public?: string;
    url: string;
};

/**
 * Settings → Custom CSS, Sitemap XML, Robots TXT: one text file the site
 * serves, edited in a monospace box (Tab inserts spaces instead of leaving it).
 */
export default function TextFile({ title, content, field, mode, note, public: publicUrl, url }: Props) {
    const t = useTrans();
    const form = useForm<Record<string, string>>({ [field]: content });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true });
    };

    return (
        <>
            <PageHeader title={t(title)}>
                {publicUrl && (
                    <a href={publicUrl} target="_blank" rel="noreferrer" className="btn btn-outline--primary">
                        <i className="las la-external-link-alt" /> {t('Open')}
                    </a>
                )}
            </PageHeader>
            <div className="card bl--5 border--primary mb-4">
                <div className="card-body">
                    <p className="text--primary mb-0">{t(note)}</p>
                    {mode === 'css' && <p className="text--warning mb-0 mt-2">{t('Please do not change anything without proper knowledge of it. The website may misbehave due to a mistake here.')}</p>}
                </div>
            </div>
            <div className="card">
                <form onSubmit={submit}>
                    <div className="card-body">
                        <label htmlFor="text-file" className="visually-hidden">
                            {t(title)}
                        </label>
                        <textarea
                            id="text-file"
                            className="form-control font-monospace"
                            rows={22}
                            spellCheck={false}
                            style={{ fontSize: 13, whiteSpace: 'pre', tabSize: 4 }}
                            value={form.data[field]}
                            onChange={(e) => form.setData(field, e.target.value)}
                            onKeyDown={(e) => {
                                if (e.key !== 'Tab' || e.shiftKey) return;
                                e.preventDefault();
                                const el = e.currentTarget;
                                const { selectionStart: s, selectionEnd: end } = el;
                                form.setData(field, el.value.slice(0, s) + '    ' + el.value.slice(end));
                                requestAnimationFrame(() => el.setSelectionRange(s + 4, s + 4));
                            }}
                        />
                        <FieldError message={form.errors[field]} />
                    </div>
                    <div className="card-footer">
                        <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing}>
                            {t('Submit')}
                        </button>
                    </div>
                </form>
            </div>
        </>
    );
}

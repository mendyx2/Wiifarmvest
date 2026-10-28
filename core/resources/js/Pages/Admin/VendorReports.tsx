import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import DataTable from '@/Components/Admin/DataTable';
import { FieldError } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';

type Report = { type: string; message: string; status: string; tone: string };

/** Report & Request: bug reports and feature requests sent to the script's vendor. */
export default function VendorReports({ error, reports, url }: { error: string | null; reports: Report[]; url: string }) {
    const t = useTrans();
    const form = useForm({ type: 'bug', message: '' });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, onSuccess: () => form.reset('message') });
    };
    return (
        <>
            <PageHeader title={t('Your Listed Report & Request')} />
            <div className="row gy-4">
                <div className="col-xl-8">
                    {error ? (
                        <div className="card bl--5 border--warning">
                            <div className="card-body text--warning">{t(error)}</div>
                        </div>
                    ) : (
                        <DataTable
                            rows={reports}
                            rowKey={(r) => r.type + r.message}
                            empty={t('No reports sent yet.')}
                            columns={[
                                { label: t('Type'), cell: (r) => r.type },
                                { label: t('Message'), cell: (r) => <span style={{ whiteSpace: 'pre-wrap' }}>{r.message}</span> },
                                { label: t('Status'), cell: (r) => <span className={`badge badge--${r.tone}`}>{r.status}</span> },
                            ]}
                        />
                    )}
                </div>
                <div className="col-xl-4">
                    <div className="card">
                        <form onSubmit={submit} className="card-body">
                            <p className="text-muted small">{t('This is sent to the vendor of the original script, with the purchase code and site address.')}</p>
                            <div className="form-group">
                                <label htmlFor="vr-type">{t('Type')}</label>
                                <select id="vr-type" className="form-select" value={form.data.type} onChange={(e) => form.setData('type', e.target.value)}>
                                    <option value="bug">{t('Report Bug')}</option>
                                    <option value="feature">{t('Feature Request')}</option>
                                </select>
                            </div>
                            <div className="form-group">
                                <label htmlFor="vr-message">{t('Message')}</label>
                                <textarea id="vr-message" className="form-control" rows={5} required maxLength={5000} value={form.data.message} onChange={(e) => form.setData('message', e.target.value)} />
                                <FieldError message={form.errors.message} />
                            </div>
                            <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing}>
                                {t('Submit')}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}

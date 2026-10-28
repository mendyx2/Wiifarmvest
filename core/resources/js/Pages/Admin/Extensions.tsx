import { useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import DataTable from '@/Components/Admin/DataTable';
import { ConfirmButton, DialogHeader, FieldError, withId } from '@/Components/Admin/Form';
import { StatusBadge } from '@/Components/Admin/GatewayTabs';
import PageHeader from '@/Components/Admin/PageHeader';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';

type Field = { key: string; title: string; secret: boolean; value: string; stored: boolean };
type Extension = { id: number; name: string; image: string; active: boolean; description: string; support: string | null; fields: Field[] };

type Props = { extensions: Extension[]; urls: { update: string; status: string } };

/** Extensions: third-party scripts (reCAPTCHA, chat, analytics) and their keys. */
export default function Extensions({ extensions, urls }: Props) {
    const t = useTrans();
    const [q, setQ] = useState('');
    const [configuring, setConfiguring] = useState<Extension | null>(null);
    const [helping, setHelping] = useState<Extension | null>(null);
    const shown = q.trim() ? extensions.filter((e) => e.name.toLowerCase().includes(q.trim().toLowerCase())) : extensions;

    return (
        <>
            <PageHeader title={t('Extensions')}>
                <input type="search" className="form-control bg--white" style={{ maxWidth: 260 }} placeholder={t('Search') + '...'} aria-label={t('Search extensions')} value={q} onChange={(e) => setQ(e.target.value)} />
            </PageHeader>
            <DataTable
                rows={shown}
                rowKey={(e) => e.id}
                empty={t('No extension found')}
                columns={[
                    {
                        label: t('Extension'),
                        cell: (e) => (
                            <span className="d-inline-flex align-items-center gap-2">
                                <img src={e.image} alt="" width={40} height={40} style={{ objectFit: 'contain' }} />
                                {e.name}
                            </span>
                        ),
                    },
                    { label: t('Status'), cell: (e) => <StatusBadge active={e.active} /> },
                    {
                        label: t('Action'),
                        cell: (e) => (
                            <div className="button--group">
                                <button type="button" className="btn btn-sm btn-outline--primary" onClick={() => setConfiguring(e)}>
                                    <i className="la la-cogs" /> {t('Configure')}
                                </button>
                                <button type="button" className="btn btn-sm btn-outline--dark" onClick={() => setHelping(e)}>
                                    <i className="la la-question" /> {t('Help')}
                                </button>
                                <ConfirmButton
                                    url={withId(urls.status, e.id)}
                                    data={{ state: e.active ? 'disable' : 'enable' }}
                                    question={e.active ? t('Are you sure to disable this extension?') : t('Are you sure to enable this extension?')}
                                    className={`btn btn-sm ${e.active ? 'btn-outline--danger' : 'btn-outline--success'}`}
                                >
                                    <i className={`la ${e.active ? 'la-eye-slash' : 'la-eye'}`} /> {e.active ? t('Disable') : t('Enable')}
                                </ConfirmButton>
                            </div>
                        ),
                    },
                ]}
            />
            {configuring && <ConfigureDialog extension={configuring} url={withId(urls.update, configuring.id)} onClose={() => setConfiguring(null)} />}
            <Modal open={helping !== null} onClose={() => setHelping(null)} labelledBy="ext-help">
                <DialogHeader id="ext-help" title={t('Need Help?')} onClose={() => setHelping(null)} />
                {helping && (
                    <div className="modal-body">
                        {/* Seeded help text from the extensions table */}
                        <div className="mb-2" dangerouslySetInnerHTML={{ __html: helping.description }} />
                        {helping.support && <img src={helping.support} alt="" className="img-fluid" />}
                    </div>
                )}
            </Modal>
        </>
    );
}

function ConfigureDialog({ extension, url, onClose }: { extension: Extension; url: string; onClose: () => void }) {
    const t = useTrans();
    const form = useForm<Record<string, string>>(Object.fromEntries(extension.fields.map((f) => [f.key, f.value])));
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, onSuccess: onClose });
    };
    return (
        <Modal open onClose={onClose} labelledBy="ext-title">
            <DialogHeader id="ext-title" title={`${t('Update Extension')}: ${extension.name}`} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    {extension.fields.map((f) => (
                        <div className="form-group" key={f.key}>
                            <label htmlFor={`ext-${f.key}`}>{f.title}</label>
                            <input
                                id={`ext-${f.key}`}
                                type={f.secret ? 'password' : 'text'}
                                autoComplete={f.secret ? 'new-password' : 'off'}
                                className="form-control"
                                required={!f.secret || !f.stored}
                                placeholder={f.secret && f.stored ? t('Saved: leave blank to keep it') : ''}
                                value={form.data[f.key]}
                                onChange={(e) => form.setData(f.key, e.target.value)}
                            />
                            {f.secret && f.stored && (
                                <small className="text--success">
                                    <i className="las la-lock" /> {t('A value is saved. Type a new one only to replace it.')}
                                </small>
                            )}
                            <FieldError message={form.errors[f.key]} />
                        </div>
                    ))}
                </div>
                <div className="modal-footer">
                    <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing}>
                        {t('Submit')}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

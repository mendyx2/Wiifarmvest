import { Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import DataTable from '@/Components/Admin/DataTable';
import { ConfirmButton, DialogHeader, FieldError, withId } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';

type Generation = { id: number; number: number; title: string; percentage: number; description: string; active: boolean; projects: number };

type Props = {
    generations: Generation[];
    available: number[];
    urls: { store: string; update: string; destroy: string; toggle: string; allocate: string };
};

/** Unilevel generations: the percentage of a product's PV paid at each depth of the buyer's upline. */
export default function Generations({ generations, available, urls }: Props) {
    const t = useTrans();
    const [editing, setEditing] = useState<Generation | 0 | null>(null);
    const active = generations.filter((g) => g.active);
    const total = active.reduce((sum, g) => sum + g.percentage, 0);

    return (
        <>
            <PageHeader title={t('Unilevel Generations')}>
                <Link href={urls.allocate} className="btn btn-sm btn-outline--info">
                    <i className="las la-project-diagram" /> {t('Allocate to projects')}
                </Link>
                <button type="button" className="btn btn-sm btn-outline--primary" disabled={available.length === 0} onClick={() => setEditing(0)}>
                    <i className="las la-plus" /> {t('Add Generation')}
                </button>
            </PageHeader>
            <div className={`card bl--5 mb-4 ${total > 100 ? 'border--danger' : 'border--primary'}`}>
                <div className="card-body">
                    <p className={`mb-0 ${total > 100 ? 'text--danger' : 'text--primary'}`}>
                        {t(":n active generations pay :total% of a product's PV in total (generation 1 is the buyer).", { n: active.length, total: +total.toFixed(4) })}
                        {total > 100 && ' ' + t('That is more than the PV itself.')}
                    </p>
                </div>
            </div>
            <DataTable
                rows={generations}
                rowKey={(g) => g.id}
                empty={t('No generations yet.')}
                columns={[
                    { label: t('Gen'), cell: (g) => <span className="fw-bold">{g.number}</span> },
                    {
                        label: t('Title'),
                        cell: (g) => (
                            <>
                                {g.title}
                                {g.description && <small className="text-muted d-block">{g.description}</small>}
                            </>
                        ),
                    },
                    { label: t('Percentage'), cell: (g) => `${g.percentage}%` },
                    { label: t('Projects'), cell: (g) => g.projects },
                    { label: t('Status'), cell: (g) => (g.active ? <span className="badge badge--success">{t('Active')}</span> : <span className="badge badge--warning">{t('Inactive')}</span>) },
                    {
                        label: t('Action'),
                        cell: (g) => (
                            <div className="button--group">
                                <button type="button" className="btn btn-sm btn-outline--primary" onClick={() => setEditing(g)}>
                                    <i className="la la-pen" /> {t('Edit')}
                                </button>
                                <ConfirmButton
                                    url={withId(urls.toggle, g.id)}
                                    data={{ state: g.active ? 'disable' : 'enable' }}
                                    question={g.active ? t('Disable generation :n? It pays nothing while disabled.', { n: g.number }) : t('Enable generation :n?', { n: g.number })}
                                    className={`btn btn-sm ${g.active ? 'btn-outline--warning' : 'btn-outline--success'}`}
                                >
                                    <i className={`la ${g.active ? 'la-eye-slash' : 'la-eye'}`} /> {g.active ? t('Disable') : t('Enable')}
                                </ConfirmButton>
                                <ConfirmButton
                                    url={withId(urls.destroy, g.id)}
                                    method="delete"
                                    question={g.projects ? t('Delete generation :n? :p project(s) use it and stop paying it.', { n: g.number, p: g.projects }) : t('Delete generation :n?', { n: g.number })}
                                    className="btn btn-sm btn-outline--danger"
                                >
                                    <i className="la la-trash" /> {t('Delete')}
                                </ConfirmButton>
                            </div>
                        ),
                    },
                ]}
            />
            {editing !== null && <GenerationDialog generation={editing || null} available={available} urls={urls} onClose={() => setEditing(null)} />}
        </>
    );
}

function GenerationDialog({ generation, available, urls, onClose }: { generation: Generation | null; available: number[]; urls: Props['urls']; onClose: () => void }) {
    const t = useTrans();
    const numbers = generation ? [generation.number, ...available].sort((a, b) => a - b) : available;
    const form = useForm({ number: String(generation?.number ?? available[0] ?? ''), title: generation?.title ?? '', percentage: generation ? String(generation.percentage) : '', description: generation?.description ?? '' });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(generation ? withId(urls.update, generation.id) : urls.store, { preserveScroll: true, onSuccess: onClose });
    };
    return (
        <Modal open onClose={onClose} labelledBy="gen-title">
            <DialogHeader id="gen-title" title={generation ? t('Edit Generation') : t('Add Generation')} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    <div className="row">
                        <div className="col-sm-4 form-group">
                            <label htmlFor="gen-number">{t('Generation')}</label>
                            <select id="gen-number" className="form-select" value={form.data.number} onChange={(e) => form.setData('number', e.target.value)}>
                                {numbers.map((n) => (
                                    <option key={n} value={n}>
                                        {n}
                                    </option>
                                ))}
                            </select>
                            <FieldError message={form.errors.number} />
                        </div>
                        <div className="col-sm-8 form-group">
                            <label htmlFor="gen-percentage">{t('Percentage of PV')}</label>
                            <div className="input-group">
                                <input id="gen-percentage" type="number" step="any" min="0" max="100" className="form-control" required value={form.data.percentage} onChange={(e) => form.setData('percentage', e.target.value)} />
                                <span className="input-group-text">%</span>
                            </div>
                            <FieldError message={form.errors.percentage} />
                        </div>
                    </div>
                    <div className="form-group">
                        <label htmlFor="gen-name">{t('Title')}</label>
                        <input id="gen-name" className="form-control" required maxLength={100} value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} />
                        <FieldError message={form.errors.title} />
                    </div>
                    <div className="form-group mb-0">
                        <label htmlFor="gen-description">
                            {t('Description')} <small className="text-muted">({t('optional')})</small>
                        </label>
                        <textarea id="gen-description" className="form-control" rows={2} maxLength={500} value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} />
                    </div>
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

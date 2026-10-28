import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';

type Props = {
    projects: { id: number; title: string; users: number; url: string }[];
    generations: { id: number; number: number; title: string; percentage: number; active: boolean }[];
    selected: { id: number; title: string; assigned: number[]; url: string } | null;
    urls: { generations: string };
};

/** Which unilevel generations each project pays. */
export default function Allocate({ projects, generations, selected, urls }: Props) {
    const t = useTrans();
    return (
        <>
            <PageHeader title={t('Allocate Generations')}>
                <Link href={urls.generations} className="btn btn-sm btn-outline--primary">
                    <i className="las la-layer-group" /> {t('Generations')}
                </Link>
            </PageHeader>
            <div className="row gy-4">
                <div className="col-lg-4">
                    <div className="card">
                        <div className="card-header">
                            <h5 className="card-title mb-0">{t('Projects')}</h5>
                        </div>
                        <div className="list-group list-group-flush">
                            {projects.map((p) => (
                                <Link
                                    key={p.id}
                                    href={p.url}
                                    className={`list-group-item list-group-item-action d-flex justify-content-between ${selected?.id === p.id ? 'active text-white' : ''}`}
                                    aria-current={selected?.id === p.id ? 'true' : undefined}
                                >
                                    <span className={selected?.id === p.id ? 'text-white fw-bold' : ''}>{p.title}</span>
                                    <small>{t(':n members', { n: p.users })}</small>
                                </Link>
                            ))}
                            {projects.length === 0 && <p className="text-muted p-3 mb-0">{t('No active projects.')}</p>}
                        </div>
                    </div>
                </div>
                <div className="col-lg-8">{selected ? <AllocationForm key={selected.id} selected={selected} generations={generations} urls={urls} /> : <p className="text-muted">{t('Choose a project.')}</p>}</div>
            </div>
        </>
    );
}

function AllocationForm({ selected, generations, urls }: { selected: NonNullable<Props['selected']>; generations: Props['generations']; urls: Props['urls'] }) {
    const t = useTrans();
    const form = useForm<{ generations: number[] }>({ generations: selected.assigned });
    const on = new Set(form.data.generations);
    const total = generations.filter((g) => g.active && on.has(g.id)).reduce((s, g) => s + g.percentage, 0);
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(selected.url, { preserveScroll: true });
    };
    return (
        <div className="card">
            <div className="card-header d-flex justify-content-between align-items-center">
                <h5 className="card-title mb-0">{selected.title}</h5>
                <span className="badge badge--primary">{t(':total% of PV', { total: +total.toFixed(4) })}</span>
            </div>
            <form onSubmit={submit} className="card-body">
                {generations.length === 0 ? (
                    <p className="text-muted">
                        {t('No generations yet.')} <Link href={urls.generations}>{t('Add one')}</Link>
                    </p>
                ) : (
                    <div className="row g-2 mb-3">
                        {generations.map((g) => (
                            <div className="col-sm-6" key={g.id}>
                                <div className={`form-check border rounded p-2 ps-5 h-100 ${!g.active ? 'opacity-50' : ''}`}>
                                    <input
                                        id={`alloc-${g.id}`}
                                        type="checkbox"
                                        className="form-check-input"
                                        checked={on.has(g.id)}
                                        disabled={!g.active && !on.has(g.id)}
                                        onChange={(e) => form.setData('generations', e.target.checked ? [...form.data.generations, g.id] : form.data.generations.filter((id) => id !== g.id))}
                                    />
                                    <label className="form-check-label w-100" htmlFor={`alloc-${g.id}`}>
                                        <b>
                                            {t('Gen')} {g.number}
                                        </b>{' '}
                                        · {g.title} <span className="float-end">{g.percentage}%</span>
                                        {!g.active && <small className="text-muted d-block">{t('Disabled: pays nothing')}</small>}
                                    </label>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
                <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing}>
                    {t('Save')}
                </button>
            </form>
        </div>
    );
}

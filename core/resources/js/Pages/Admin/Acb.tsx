import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import DataTable from '@/Components/Admin/DataTable';
import { ConfirmButton, FieldError, withId } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import SearchForm from '@/Components/Admin/SearchForm';
import { useTrans } from '@/lib/i18n';
import type { Paginated } from '@/types';

type Member = { id: number; member: { name: string; username: string; email: string; url: string } | null; balance: string; notes: string; added: string };

type Props = {
    total: number;
    rates: { gen: number; percent: number }[];
    search: string;
    members: Paginated<Member>;
    urls: { self: string; store: string; destroy: string };
};

/** ACB Members: who earns the ACB bonus from their downline's purchases. */
export default function Acb({ total, rates, search, members, urls }: Props) {
    const t = useTrans();
    const form = useForm({ username: '', notes: '' });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(urls.store, { preserveScroll: true, onSuccess: () => form.reset() });
    };
    return (
        <>
            <PageHeader title={t('ACB Members')}>
                <SearchForm url={urls.self} value={search} placeholder="Name, username or email" />
            </PageHeader>
            <div className="row gy-4">
                <div className="col-xl-4">
                    <div className="card mb-4">
                        <div className="card-body">
                            <h4 className="mb-1">{total}</h4>
                            <small className="text-muted d-block mb-3">{t('ACB members')}</small>
                            <p className="mb-0 small">
                                {t('Bonus on a downline purchase')}: <b>{rates.map((r) => `${r.percent}%`).join(' · ')}</b> ({t(':n generations of upline; only ACB members receive it.', { n: rates.length })})
                            </p>
                        </div>
                    </div>
                    <div className="card">
                        <div className="card-header">
                            <h5 className="card-title mb-0">{t('Add a Member to ACB')}</h5>
                        </div>
                        <form onSubmit={submit} className="card-body">
                            <div className="form-group">
                                <label htmlFor="acb-username">{t('Username')}</label>
                                <input id="acb-username" className="form-control" required maxLength={40} placeholder="johndoe" value={form.data.username} onChange={(e) => form.setData('username', e.target.value.trim())} />
                                <FieldError message={form.errors.username} />
                            </div>
                            <div className="form-group">
                                <label htmlFor="acb-notes">
                                    {t('Note')} <small className="text-muted">({t('optional')})</small>
                                </label>
                                <textarea id="acb-notes" className="form-control" rows={2} maxLength={500} placeholder={t('Why this member qualifies...')} value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)} />
                            </div>
                            <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing}>
                                {t('Add to ACB')}
                            </button>
                        </form>
                    </div>
                </div>
                <div className="col-xl-8">
                    <DataTable
                        rows={members.data}
                        rowKey={(m) => m.id}
                        links={members.links}
                        empty={search ? t('No member matches this search.') : t('No ACB members yet.')}
                        columns={[
                            {
                                label: t('Member'),
                                cell: (m) =>
                                    m.member ? (
                                        <Link href={m.member.url}>
                                            {m.member.name}
                                            <small className="text-muted d-block">@{m.member.username}</small>
                                        </Link>
                                    ) : (
                                        '—'
                                    ),
                            },
                            { label: t('ACB Balance'), cell: (m) => <strong>{m.balance}</strong> },
                            { label: t('Note'), cell: (m) => <small>{m.notes || '—'}</small> },
                            { label: t('Added'), cell: (m) => m.added },
                            {
                                label: t('Action'),
                                cell: (m) => (
                                    <ConfirmButton
                                        url={withId(urls.destroy, m.id)}
                                        method="delete"
                                        question={t('Remove @:u from ACB? Their ACB balance stays; they earn no new ACB bonus.', { u: m.member?.username ?? '' })}
                                        className="btn btn-sm btn-outline--danger"
                                    >
                                        <i className="la la-user-minus" /> {t('Remove')}
                                    </ConfirmButton>
                                ),
                            },
                        ]}
                    />
                </div>
            </div>
        </>
    );
}

import { Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import Drawer from '@/Components/Admin/Drawer';
import { ConfirmButton, FieldError, withId } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';

type State = { id: number; name: string; code: string; prices: number; stockists: number };

type Props = {
    states: State[];
    urls: { store: string; update: string; destroy: string; products: string };
};

/** Shop → Manage States: the states products are priced in and stockists work in. */
export default function States({ states, urls }: Props) {
    const t = useTrans();
    // null: closed; id 0: adding
    const [editing, setEditing] = useState<Pick<State, 'id' | 'name' | 'code'> | null>(null);

    return (
        <div className="state-page">
            <PageHeader title={t('Manage States')}>
                <Link className="btn btn-sm btn-outline--dark" href={urls.products}>
                    <i className="las la-store" /> {t('Products')}
                </Link>
            </PageHeader>
            <div className="state-layout">
                <div className="state-table-col">
                    <div className="state-card">
                        <div className="state-card-head">
                            <span className="state-count">{states.length === 1 ? t('1 state') : t(':n states', { n: states.length })}</span>
                            <button type="button" className="state-add-btn" onClick={() => setEditing({ id: 0, name: '', code: '' })}>
                                <i className="las la-plus" /> {t('Add State')}
                            </button>
                        </div>
                        <div className="table-responsive">
                            <table className="table state-tbl">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{t('State Name')}</th>
                                        <th>{t('Code')}</th>
                                        <th>{t('Used by')}</th>
                                        <th className="text-right">{t('Actions')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {states.length ? (
                                        states.map((s, i) => {
                                            const inUse = s.stockists > 0;
                                            return (
                                                <tr key={s.id}>
                                                    <td className="state-row-num">{i + 1}</td>
                                                    <td className="state-row-name">{s.name}</td>
                                                    <td>
                                                        <span className="state-code-chip">{s.code}</span>
                                                    </td>
                                                    <td className="text-muted small">
                                                        {t(':p product price(s) · :s stockist(s)', { p: s.prices, s: s.stockists })}
                                                    </td>
                                                    <td className="text-right">
                                                        <div className="state-row-actions">
                                                            <button type="button" className="state-action-edit" title={t('Edit')} aria-label={t('Edit :name', { name: s.name })} onClick={() => setEditing(s)}>
                                                                <i className="las la-pen" />
                                                            </button>
                                                            {inUse ? (
                                                                <button type="button" className="state-action-del" disabled title={t('Stockists work in this state, so it cannot be deleted')} aria-label={t('Delete :name', { name: s.name })}>
                                                                    <i className="las la-trash-alt" />
                                                                </button>
                                                            ) : (
                                                                <ConfirmButton
                                                                    url={withId(urls.destroy, s.id)}
                                                                    method="delete"
                                                                    className="state-action-del"
                                                                    title={t('Delete :name', { name: s.name })}
                                                                    question={s.prices ? t('Delete :name? Its :n product price(s) are removed too.', { name: s.name, n: s.prices }) : t('Delete :name?', { name: s.name })}
                                                                >
                                                                    <i className="las la-trash-alt" />
                                                                </ConfirmButton>
                                                            )}
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        })
                                    ) : (
                                        <tr>
                                            <td colSpan={5}>
                                                <div className="state-empty-row">
                                                    <i className="las la-map" />
                                                    <p>{t('No states yet. Add your first state to get started.')}</p>
                                                </div>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <Drawer prefix="state" open={editing !== null} title={editing?.id ? t('Edit State') : t('Add State')} onClose={() => setEditing(null)}>
                {editing && <StateForm key={editing.id} state={editing} url={editing.id ? withId(urls.update, editing.id) : urls.store} onDone={() => setEditing(null)} />}
            </Drawer>
        </div>
    );
}

function StateForm({ state, url, onDone }: { state: Pick<State, 'id' | 'name' | 'code'>; url: string; onDone: () => void }) {
    const t = useTrans();
    const form = useForm({ name: state.name, code: state.code });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, onSuccess: onDone });
    };
    return (
        <form onSubmit={submit}>
            <div className="state-form-field">
                <label className="state-form-label" htmlFor="state-name">
                    {t('State Name')} <span className="pf-req">*</span>
                </label>
                <input id="state-name" className="state-form-input" placeholder={t('e.g. Lagos')} required maxLength={100} value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                <FieldError message={form.errors.name} />
            </div>
            <div className="state-form-field">
                <label className="state-form-label" htmlFor="state-code">
                    {t('State Code')} <span className="pf-req">*</span>
                </label>
                <input
                    id="state-code"
                    className="state-form-input state-form-input--upper"
                    placeholder={t('e.g. LG')}
                    required
                    maxLength={10}
                    value={form.data.code}
                    onChange={(e) => form.setData('code', e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, ''))}
                />
                <p className="state-form-hint">{t('Short uppercase abbreviation used for filtering and display')}</p>
                <FieldError message={form.errors.code} />
            </div>
            <button type="submit" className="state-form-submit" disabled={form.processing}>
                {t('Save State')}
            </button>
        </form>
    );
}

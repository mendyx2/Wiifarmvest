import { Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import DataTable from '@/Components/Admin/DataTable';
import { DialogHeader, FieldError, withId } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';
import type { Paginated } from '@/types';

type Payment = { id: number; member: { name: string; username: string; url: string } | null; award: string; image: string | null; earned: string; amount: string; paid: boolean; paidAt: string | null; note: string };

type Props = {
    status: 0 | 1 | null;
    counts: { all: number; unpaid: number; paid: number };
    payments: Paginated<Payment>;
    urls: { self: string; pay: string; index: string };
};

/** Award Payments: awards members earned, and paying them into the awards wallet. */
export default function Payments({ status, counts, payments, urls }: Props) {
    const t = useTrans();
    const [paying, setPaying] = useState<Payment | null>(null);
    const tabs: [string, 0 | 1 | null, number][] = [
        ['All', null, counts.all],
        ['Unpaid', 0, counts.unpaid],
        ['Paid', 1, counts.paid],
    ];
    return (
        <>
            <PageHeader title={t('Award Payments')}>
                <Link href={urls.index} className="btn btn-sm btn-outline--primary">
                    <i className="las la-trophy" /> {t('Awards')}
                </Link>
            </PageHeader>
            <ul className="nav nav-tabs mb-3">
                {tabs.map(([label, value, n]) => (
                    <li className="nav-item" key={label}>
                        <Link className={`nav-link ${status === value ? 'active' : ''}`} href={value === null ? urls.self : `${urls.self}?status=${value}`} preserveScroll>
                            {t(label)} <span className="badge badge--dark ms-1">{n}</span>
                        </Link>
                    </li>
                ))}
            </ul>
            <DataTable
                rows={payments.data}
                rowKey={(p) => p.id}
                links={payments.links}
                empty={t('Nothing here.')}
                columns={[
                    {
                        label: t('Member'),
                        cell: (p) =>
                            p.member ? (
                                <Link href={p.member.url}>
                                    {p.member.name}
                                    <small className="text-muted d-block">@{p.member.username}</small>
                                </Link>
                            ) : (
                                '—'
                            ),
                    },
                    {
                        label: t('Award'),
                        cell: (p) => (
                            <span className="d-inline-flex align-items-center gap-2">
                                {p.image && <img src={p.image} alt="" width={28} height={28} style={{ objectFit: 'contain' }} />}
                                {p.award}
                            </span>
                        ),
                    },
                    { label: t('Earned'), cell: (p) => p.earned },
                    { label: t('Amount'), cell: (p) => <strong>{p.amount}</strong> },
                    {
                        label: t('Status'),
                        cell: (p) =>
                            p.paid ? (
                                <>
                                    <span className="badge badge--success">{t('Paid')}</span>
                                    <small className="text-muted d-block">{p.paidAt}</small>
                                    {p.note && <small className="d-block">{p.note}</small>}
                                </>
                            ) : (
                                <span className="badge badge--warning">{t('Unpaid')}</span>
                            ),
                    },
                    {
                        label: t('Action'),
                        cell: (p) =>
                            p.paid ? null : (
                                <button type="button" className="btn btn-sm btn--success" onClick={() => setPaying(p)}>
                                    <i className="las la-money-bill" /> {t('Pay')}
                                </button>
                            ),
                    },
                ]}
            />
            {paying && <PayDialog payment={paying} url={withId(urls.pay, paying.id)} onClose={() => setPaying(null)} />}
        </>
    );
}

function PayDialog({ payment, url, onClose }: { payment: Payment; url: string; onClose: () => void }) {
    const t = useTrans();
    const form = useForm({ note: '' });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, onSuccess: onClose });
    };
    return (
        <Modal open onClose={onClose} labelledBy="pay-title">
            <DialogHeader id="pay-title" title={t('Pay Award')} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    <p>{t('Pay :amount to @:u for :award? It goes into their awards wallet.', { amount: payment.amount, u: payment.member?.username ?? '', award: payment.award })}</p>
                    <label htmlFor="pay-note">
                        {t('Note')} <small className="text-muted">({t('optional')})</small>
                    </label>
                    <textarea id="pay-note" className="form-control" rows={2} maxLength={500} value={form.data.note} onChange={(e) => form.setData('note', e.target.value)} />
                    <FieldError message={form.errors.note} />
                </div>
                <div className="modal-footer">
                    <button type="submit" className="btn btn--success w-100 h-45" disabled={form.processing}>
                        {t('Pay')}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

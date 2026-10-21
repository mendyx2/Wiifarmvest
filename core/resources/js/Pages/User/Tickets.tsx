import { Head, Link } from '@inertiajs/react';
import Pagination from '@/Components/Pagination';
import { useTrans } from '@/lib/i18n';
import type { PageProps, Paginated } from '@/types';

type Badge = { label: string; tone: string };
type Ticket = { id: number; number: string; subject: string; status: Badge; priority: Badge | null; lastReply: string; url: string };

type Props = PageProps<{ tickets: Paginated<Ticket>; openUrl: string }>;

export default function Tickets({ tickets, openUrl }: Props) {
    const t = useTrans();

    return (
        <>
            <Head title={t('Support Tickets')} />
            <div className="text-end mb-3">
                <Link className="btn btn--base" href={openUrl}>
                    <i className="fas fa-plus" /> {t('New Ticket')}
                </Link>
            </div>
            <div className="card custom--card p-0">
                <div className="card-body">
                    <div className="table-responsive">
                        <table className="custom--table table">
                            <thead>
                                <tr>
                                    <th>{t('Subject')}</th>
                                    <th>{t('Status')}</th>
                                    <th>{t('Priority')}</th>
                                    <th>{t('Last Reply')}</th>
                                    <th>{t('Action')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {tickets.data.length === 0 ? (
                                    <tr>
                                        <td className="text-center" colSpan={5}>{t('Data not found')}</td>
                                    </tr>
                                ) : (
                                    tickets.data.map((ticket) => (
                                        <tr key={ticket.id}>
                                            <td>
                                                <Link className="fw-bold" href={ticket.url}>
                                                    [{t('Ticket')}#{ticket.number}] {ticket.subject}
                                                </Link>
                                            </td>
                                            <td><span className={`badge badge--${ticket.status.tone}`}>{t(ticket.status.label)}</span></td>
                                            <td>{ticket.priority && <span className={`badge badge--${ticket.priority.tone}`}>{t(ticket.priority.label)}</span>}</td>
                                            <td>{ticket.lastReply}</td>
                                            <td>
                                                <Link className="btn btn--base btn--sm" href={ticket.url} aria-label={t('View ticket')}>
                                                    <i className="las la-desktop" />
                                                </Link>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <Pagination links={tickets.links} className="mt-4" />
        </>
    );
}

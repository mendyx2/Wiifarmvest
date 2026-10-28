import { Link } from '@inertiajs/react';
import DataTable from '@/Components/Admin/DataTable';
import PageHeader from '@/Components/Admin/PageHeader';
import SearchForm from '@/Components/Admin/SearchForm';
import { useTrans } from '@/lib/i18n';
import type { Paginated } from '@/types';

export type Tone = [label: string, tone: string];

const TAB_LABELS: Record<string, string> = { all: 'All', pending: 'Pending', answered: 'Answered', closed: 'Closed' };

type Ticket = { id: number; number: string; subject: string; name: string; username: string; userUrl: string | null; status: Tone; priority: Tone; lastReply: string; url: string };

type Props = {
    title: string;
    tab: string;
    tabs: { key: string; label: string; url: string }[];
    search: string;
    tickets: Paginated<Ticket>;
};

/** Support Tickets: one list with a tab per status (the four Blade lists were one template). */
export default function Tickets({ title, tab, tabs, search, tickets }: Props) {
    const t = useTrans();
    const current = tabs.find((x) => x.key === tab) ?? tabs[0];

    return (
        <>
            <PageHeader title={t(title)}>
                <SearchForm url={current.url} value={search} placeholder="Ticket, subject or name" />
            </PageHeader>
            <ul className="nav nav-tabs mb-3">
                {tabs.map((x) => (
                    <li className="nav-item" key={x.key}>
                        <Link className={`nav-link ${x.key === tab ? 'active' : ''}`} href={x.url} preserveScroll>
                            {t(TAB_LABELS[x.key] ?? x.label)}
                        </Link>
                    </li>
                ))}
            </ul>
            <DataTable
                rows={tickets.data}
                rowKey={(r) => r.id}
                links={tickets.links}
                empty={t('No tickets')}
                columns={[
                    {
                        label: t('Subject'),
                        cell: (r) => (
                            <Link href={r.url} className="fw-bold">
                                [{t('Ticket')}#{r.number}] {r.subject}
                            </Link>
                        ),
                    },
                    {
                        label: t('Submitted By'),
                        cell: (r) =>
                            r.userUrl ? (
                                <Link href={r.userUrl}>
                                    {r.name}
                                    {r.username && <small className="text-muted d-block">@{r.username}</small>}
                                </Link>
                            ) : (
                                <span className="fw-bold">{r.name}</span>
                            ),
                    },
                    { label: t('Status'), cell: (r) => <span className={`badge badge--${r.status[1]}`}>{t(r.status[0])}</span> },
                    { label: t('Priority'), cell: (r) => <span className={`badge badge--${r.priority[1]}`}>{t(r.priority[0])}</span> },
                    { label: t('Last Reply'), cell: (r) => r.lastReply },
                    {
                        label: t('Action'),
                        cell: (r) => (
                            <Link href={r.url} className="btn btn-sm btn-outline--primary">
                                <i className="las la-desktop" /> {t('Details')}
                            </Link>
                        ),
                    },
                ]}
            />
        </>
    );
}

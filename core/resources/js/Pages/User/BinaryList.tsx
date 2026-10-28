import { Head, Link, usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import Pagination from '@/Components/Pagination';
import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';
import type { PageProps, Paginated } from '@/types';

type Slot = { username: string } | { url: string };
type Member = {
    id: number;
    number: number;
    fullname: string;
    username: string;
    level: number;
    leg: 'Left' | 'Right';
    plan: string | null;
    left: Slot;
    right: Slot;
    joined: string;
    ago: string;
};

type Props = PageProps<{ members: Paginated<Member>; total: number; search: string; notInTree: boolean; distributorUrl: string }>;

export default function BinaryList({ members, total, search, notInTree, distributorUrl }: Props) {
    const t = useTrans();
    const base = usePage().url.split('?')[0];
    const [query, setQuery] = useState(search);
    const fmt = (n: number) => n.toLocaleString('en-US');

    function submit(event: FormEvent) {
        event.preventDefault();
        visitWithQuery(base, { username: query.trim() });
    }

    const slot = (s: Slot, side: 'left' | 'right', owner: string, withTitle: boolean) =>
        'username' in s ? (
            <span className={`bx-slot bx-slot--filled bx-slot--${side === 'left' ? 'l' : 'r'}`}><i className="las la-user" />{s.username}</span>
        ) : (
            <Link href={s.url} className="bx-slot bx-slot--empty" title={withTitle ? t("Register into :name's :side slot", { name: owner, side: side === 'left' ? t('left') : t('right') }) : undefined}>
                <i className="las la-plus" /> {t('Empty')}
            </Link>
        );
    const legBadge = (m: Member) => {
        const left = m.leg.toLowerCase() === 'left';
        return <span className={`bx-badge ${left ? 'bx-badge--left' : 'bx-badge--right'}`}><i className={`las la-${left ? 'arrow-left' : 'arrow-right'}`} /> {t(m.leg)}</span>;
    };
    const range = `${members.from ?? 0}–${members.to ?? 0}`;

    return (
        <div className="bx-page">
            <Head title={t('Binary List')} />
            <div className="bx-hero">
                <div className="bx-hero-glow1" />
                <div className="bx-hero-glow2" />
                <div className="bx-hero-body">
                    <div className="bx-hero-left">
                        <div className="bx-hero-eyebrow"><span className="bx-live-dot" /> {t('Genealogy Network')}</div>
                        <h2 className="bx-hero-title">{t('Binary List')}</h2>
                    </div>
                    <div className="bx-hero-bubble">
                        <span className="bx-bubble-num">{fmt(total)}</span>
                        <span className="bx-bubble-lbl">{t('Members')}</span>
                    </div>
                </div>
            </div>

            <div className="bx-search-card">
                <form className="bx-search-form" onSubmit={submit} role="search">
                    <div className="bx-search-box">
                        <i className="las la-search bx-search-ico" />
                        <input type="text" className="bx-search-inp" aria-label={t('Search by username…')} placeholder={t('Search by username…')} value={query} onChange={(e) => setQuery(e.target.value)} autoComplete="off" spellCheck={false} />
                        {search && <Link href={base} className="bx-search-x" title={t('Clear')} aria-label={t('Clear')}><i className="las la-times" /></Link>}
                    </div>
                    <button type="submit" className="bx-search-btn"><i className="las la-search" /> <span>{t('Search')}</span></button>
                </form>
            </div>

            {notInTree ? (
                <div className="bx-state bx-state--miss">
                    <div className="bx-state-orb" />
                    <div className="bx-state-ico"><i className="las la-user-slash" /></div>
                    <h5 className="bx-state-title">{t('Not in your Genealogy Tree')}</h5>
                    <p className="bx-state-msg"><strong>{search}</strong> {t('is not a member of your downline network.')}</p>
                    <Link href={base} className="bx-state-cta bx-state-cta--miss"><i className="las la-arrow-left" /> {t('Back to Full List')}</Link>
                </div>
            ) : members.data.length === 0 ? (
                <div className="bx-state bx-state--empty">
                    <div className="bx-state-orb" />
                    <div className="bx-state-ico"><i className="las la-sitemap" /></div>
                    <h5 className="bx-state-title">{t('No Members Yet')}</h5>
                    <p className="bx-state-msg">{t('Your binary network is empty. Add your first distributor to start building your tree.')}</p>
                    <Link href={distributorUrl} className="bx-state-cta bx-state-cta--empty"><i className="las la-user-plus" /> {t('Add Distributor')}</Link>
                </div>
            ) : (
                <>
                    {search && <div className="bx-match-notice"><i className="las la-check-circle" /> {t('Match found in your network for')} <strong>{search}</strong></div>}

                    <div className="bx-table-card d-none d-md-block">
                        <div className="bx-toolbar">
                            <span className="bx-toolbar-count">{t('Showing')} <strong>{range}</strong> {t('of')} <strong>{fmt(total)}</strong> {t('members')}</span>
                        </div>
                        <div className="bx-table-scroll">
                            <table className="bx-table">
                                <thead>
                                    <tr>
                                        <th className="bx-th-ctr">#</th>
                                        <th>{t('Member')}</th>
                                        <th>{t('Level')}</th>
                                        <th>{t('Leg')}</th>
                                        <th>{t('Plan')}</th>
                                        <th>{t('Left')}</th>
                                        <th>{t('Right')}</th>
                                        <th>{t('Joined')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {members.data.map((m) => (
                                        <tr key={m.id} className={`bx-row ${m.leg.toLowerCase() === 'left' ? 'bx-row--left' : 'bx-row--right'}`}>
                                            <td className="bx-td-ctr"><span className="bx-seq">{m.number}</span></td>
                                            <td>
                                                <div className="bx-member">
                                                    <div className={`bx-av bx-av-${(m.number - 1) % 8}`}>{(m.fullname || m.username).charAt(0).toUpperCase()}</div>
                                                    <div className="bx-member-text">
                                                        <span className="bx-fullname">{m.fullname}</span>
                                                        <span className="bx-username"><i className="las la-at" />{m.username}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span className="bx-badge bx-badge--lvl"><i className="las la-layer-group" /> L{m.level}</span></td>
                                            <td>{legBadge(m)}</td>
                                            <td><span className="bx-plan">{m.plan ?? '—'}</span></td>
                                            <td>{slot(m.left, 'left', m.username, true)}</td>
                                            <td>{slot(m.right, 'right', m.username, true)}</td>
                                            <td><span className="bx-date">{m.joined}</span><span className="bx-ago">{m.ago}</span></td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {members.last_page > 1 && <div className="bx-pager"><Pagination links={members.links} /></div>}
                    </div>

                    <div className="bx-stack d-md-none">
                        <p className="bx-stack-meta">{t('Showing')} <strong>{range}</strong> {t('of')} <strong>{fmt(total)}</strong></p>
                        {members.data.map((m) => (
                            <div key={m.id} className={`bx-card ${m.leg.toLowerCase() === 'left' ? 'bx-card--left' : 'bx-card--right'}`}>
                                <div className="bx-card-stripe" />
                                <div className="bx-card-head">
                                    <div className={`bx-card-av bx-av-${(m.number - 1) % 8}`}>{(m.fullname || m.username).charAt(0).toUpperCase()}</div>
                                    <div className="bx-card-id">
                                        <div className="bx-card-name">{m.fullname}</div>
                                        <div className="bx-card-handle"><i className="las la-at" />{m.username}</div>
                                        <div className="bx-card-tags">
                                            <span className="bx-badge bx-badge--lvl"><i className="las la-layer-group" /> L{m.level}</span>
                                            {legBadge(m)}
                                            {m.plan && <span className="bx-badge bx-badge--plan">{m.plan}</span>}
                                        </div>
                                    </div>
                                    <span className="bx-card-seq">#{m.number}</span>
                                </div>
                                <div className="bx-card-pv">
                                    <div className="bx-pv-half"><span className="bx-pv-lbl"><span className="bx-pv-dot bx-pv-dot--l" />{t('Left')}</span>{slot(m.left, 'left', m.username, false)}</div>
                                    <div className="bx-pv-sep" />
                                    <div className="bx-pv-half"><span className="bx-pv-lbl"><span className="bx-pv-dot bx-pv-dot--r" />{t('Right')}</span>{slot(m.right, 'right', m.username, false)}</div>
                                </div>
                                <div className="bx-card-foot"><i className="las la-calendar-check" /> {m.joined} <span className="bx-card-ago">&middot; {m.ago}</span></div>
                            </div>
                        ))}
                        {members.last_page > 1 && <div className="bx-pager bx-pager--mob"><Pagination links={members.links} /></div>}
                    </div>
                </>
            )}
        </div>
    );
}

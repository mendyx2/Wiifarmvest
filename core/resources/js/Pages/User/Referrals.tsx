import { Head } from '@inertiajs/react';
import { useRef } from 'react';
import Pagination from '@/Components/Pagination';
import { useCopy } from '@/lib/clipboard';
import { useTrans } from '@/lib/i18n';
import type { PageProps, Paginated } from '@/types';

type Referral = { id: number; number: number; fullname: string; username: string; joined: string; ago: string };

type Props = PageProps<{
    referrals: Paginated<Referral>;
    refUrl: string;
    refCode: string;
}>;

const AVATAR_COLORS = ['#059669', '#2563EB', '#7C3AED', '#D97706', '#DB2777', '#0D9488', '#EA580C', '#4F46E5'];

export default function Referrals({ referrals, refUrl, refCode }: Props) {
    const t = useTrans();
    const { copied, copy } = useCopy(2500);
    const linkInput = useRef<HTMLInputElement>(null);
    const count = referrals.total;
    const share = encodeURIComponent(refUrl);

    async function copyLink() {
        if (await copy(refUrl, linkInput.current)) {
            window.notify?.('success', t('Referral link copied!'));
        }
    }

    const shareLinks = [
        { cls: 's-wa', title: 'WhatsApp', icon: 'fab fa-whatsapp', href: `https://wa.me/?text=${encodeURIComponent(`${t('Join me!')} ${refUrl}`)}` },
        { cls: 's-tg', title: 'Telegram', icon: 'fab fa-telegram-plane', href: `https://t.me/share/url?url=${share}` },
        { cls: 's-fb', title: 'Facebook', icon: 'fab fa-facebook-f', href: `https://www.facebook.com/sharer/sharer.php?u=${share}` },
        { cls: 's-tw', title: 'X / Twitter', icon: 'fab fa-twitter', href: `https://twitter.com/intent/tweet?url=${share}` },
        {
            cls: 's-em',
            title: 'Email',
            icon: 'las la-envelope',
            href: `mailto:?subject=${encodeURIComponent(t('Join me'))}&body=${encodeURIComponent(`${t('Join using my referral link:')} ${refUrl}`)}`,
        },
    ];

    return (
        <div className="ref-page">
            <Head title={t('My Referral')} />

            <div className="ref-stats-strip">
                <div className="ref-stat">
                    <div className="ref-stat-icon green"><i className="las la-user-friends" /></div>
                    <div>
                        <p className="ref-stat-label">{t('Total Referrals')}</p>
                        <p className="ref-stat-value">{count}</p>
                        <p className="ref-stat-sub">{t('All time')}</p>
                    </div>
                </div>
                <div className="ref-stat">
                    <div className="ref-stat-icon blue"><i className="las la-link" /></div>
                    <div>
                        <p className="ref-stat-label">{t('Ref Code')}</p>
                        <p className="ref-stat-value" style={{ fontSize: '1.1rem', letterSpacing: '.06em' }}>{refCode}</p>
                        <p className="ref-stat-sub">{t('Your unique code')}</p>
                    </div>
                </div>
                <div className="ref-stat">
                    <div className="ref-stat-icon violet"><i className="las la-chart-line" /></div>
                    <div>
                        <p className="ref-stat-label">{t('Network')}</p>
                        <p className="ref-stat-value">{count > 0 ? t('Active') : t('None')}</p>
                        <p className="ref-stat-sub">{count === 1 ? t('1 member') : t(':count members', { count })}</p>
                    </div>
                </div>
            </div>

            <div className="ref-invite-card">
                <div className="ref-invite-header">
                    <p className="ref-invite-title">{t('Referral Program')}</p>
                    <h5 className="ref-invite-heading">{t('Invite & Earn Together')}</h5>
                    <p className="ref-invite-sub">{t('Share your link and grow your network')}</p>
                </div>
                <div className="ref-invite-body">
                    <div className="ref-code-row">
                        <span className="ref-code-label">{t('Your Code')}</span>
                        <div className="ref-code-badge">{refCode}</div>
                    </div>
                    <div className="ref-link-group">
                        <input ref={linkInput} type="text" value={refUrl} readOnly aria-label={t('Referral link')} onFocus={(e) => e.target.select()} />
                        <button type="button" className={`ref-link-copy ${copied ? 'copied' : ''}`} onClick={copyLink}>
                            <i className="las la-copy" />
                            <span>{copied ? t('Copied!') : t('Copy')}</span>
                        </button>
                    </div>
                    <div className="ref-share-row">
                        <span className="ref-share-label">{t('Share via:')}</span>
                        {shareLinks.map((link) => (
                            <a
                                key={link.cls}
                                href={link.href}
                                className={`ref-share-btn ${link.cls}`}
                                title={link.title}
                                aria-label={link.title}
                                target={link.cls === 's-em' ? undefined : '_blank'}
                                rel="noopener noreferrer"
                            >
                                <i className={link.icon} />
                            </a>
                        ))}
                    </div>
                </div>
            </div>

            <div className="ref-members-card">
                <div className="ref-members-head">
                    <h6 className="ref-members-title">
                        <i className="las la-users" /> {t('Referral Members')}
                    </h6>
                    {count > 0 && <span className="ref-count-pill">{t(':count total', { count })}</span>}
                </div>
                <div className="ref-cards-grid">
                    {referrals.data.length === 0 ? (
                        <div className="ref-empty">
                            <div className="ref-empty-icon"><i className="las la-user-friends" /></div>
                            <h6>{t('No referrals yet')}</h6>
                            <p>{t('Share your referral link or code above to start building your network.')}</p>
                        </div>
                    ) : (
                        referrals.data.map((ref) => (
                            <div className="ref-mc" key={ref.id}>
                                <div className="ref-mc-top">
                                    <div className="ref-mc-avatar" style={{ background: AVATAR_COLORS[(ref.number - 1) % AVATAR_COLORS.length] }}>
                                        {(ref.fullname || ref.username).charAt(0).toUpperCase()}
                                    </div>
                                    <div className="ref-mc-num">#{ref.number}</div>
                                </div>
                                <p className="ref-mc-name">{ref.fullname}</p>
                                <p className="ref-mc-username">@<span>{ref.username}</span></p>
                                <div className="ref-mc-divider" />
                                <div className="ref-mc-foot">
                                    <i className="las la-calendar-alt" />
                                    <span>{ref.joined}</span>
                                    <span className="ref-mc-ago">{ref.ago}</span>
                                </div>
                            </div>
                        ))
                    )}
                </div>
            </div>

            <Pagination links={referrals.links} className="mt-3" />
        </div>
    );
}

import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';

export type AdminUser = { fullname: string; username: string; url: string } | null;
export type When = { date: string; ago: string } | null;
export type Badge = { label: string; class: string };

/**
 * The "User" cell: full name, and @username under it. With `searchUrl`, the
 * username filters the current list by that member (the Blade pages linked
 * to "?search=<username>"); otherwise it opens the member's admin page.
 */
export function UserCell({ user, searchUrl }: { user: AdminUser; searchUrl?: string }) {
    const t = useTrans();
    if (!user) return <span className="text-muted">{t('Deleted user')}</span>;
    return (
        <>
            <span className="fw-bold">{user.fullname}</span>
            <br />
            <span className="small">
                {searchUrl ? (
                    <a
                        href={`${searchUrl.split('?')[0]}?search=${encodeURIComponent(user.username)}`}
                        onClick={(e) => {
                            e.preventDefault();
                            visitWithQuery(searchUrl, { search: user.username });
                        }}
                    >
                        @{user.username}
                    </a>
                ) : (
                    // The member's admin page is still Blade: a normal link.
                    <a href={user.url}>@{user.username}</a>
                )}
            </span>
        </>
    );
}

/** A date, with "2 days ago" under it. */
export function WhenCell({ when }: { when: When }) {
    if (!when) return <>—</>;
    return (
        <>
            {when.date}
            <br />
            {when.ago}
        </>
    );
}

export function BadgeCell({ badge }: { badge: Badge }) {
    return <span className={`badge ${badge.class}`}>{badge.label}</span>;
}

import { Link, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type CSSProperties, type ReactNode } from 'react';
import { useTrans } from '@/lib/i18n';
import type { NavBadge, NavEntry, NavLink } from '@/types';

/**
 * The member dashboard around every React page: sidebar, top bar and footer.
 * It replaces partials/dashboard*.blade.php for Inertia pages.
 *
 * It is a persistent layout (see app.tsx): React keeps it mounted when you move
 * between pages, so only the page body changes. The menu itself, the badges
 * and the "active" highlight come from the server on every visit
 * (App\Support\DashboardLayout), so they never go stale.
 */
export default function DashboardLayout({ children }: { children: ReactNode }) {
    const { layout, siteName } = usePage().props;
    const url = usePage().url;
    const t = useTrans();
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [menuOpen, setMenuOpen] = useState(false);
    const [openGroup, setOpenGroup] = useState<string | null>(() => activeGroup(layout?.nav));
    const avatarRef = useRef<HTMLDivElement>(null);

    // A new page: close the mobile sidebar and the avatar menu, and open the
    // group that holds the current page (the Blade sidebar did that on load).
    useEffect(() => {
        setSidebarOpen(false);
        setMenuOpen(false);
        const group = activeGroup(layout?.nav);
        if (group) setOpenGroup(group);
    }, [url]); // eslint-disable-line react-hooks/exhaustive-deps

    // The open mobile sidebar covers the page, so the page must not scroll behind it.
    useEffect(() => {
        document.body.style.overflow = sidebarOpen ? 'hidden' : '';
        return () => {
            document.body.style.overflow = '';
        };
    }, [sidebarOpen]);

    useEffect(() => {
        if (!menuOpen) return;
        const onClick = (e: MouseEvent) => {
            if (!avatarRef.current?.contains(e.target as Node)) setMenuOpen(false);
        };
        const onKey = (e: KeyboardEvent) => e.key === 'Escape' && setMenuOpen(false);
        document.addEventListener('click', onClick);
        document.addEventListener('keydown', onKey);
        return () => {
            document.removeEventListener('click', onClick);
            document.removeEventListener('keydown', onKey);
        };
    }, [menuOpen]);

    // Guests never reach a dashboard page, but don't crash if one does.
    if (!layout) return <>{children}</>;

    const { user, counts, links } = layout;

    return (
        <>
            <section className="bank-dashboard">
                <div className="bank-layout">
                    <aside className={`bank-sidebar${sidebarOpen ? ' open' : ''}`} id="bankSidebar">
                        <div className="bank-sidebar-inner">
                            <div className="bank-logo">
                                <MenuAnchor href={layout.homeUrl} reload={layout.homeReload}>
                                    <img src={layout.logo} alt="logo" className="bank-logo-img" />
                                </MenuAnchor>
                                <button type="button" className="bank-sidebar-close d-lg-none" aria-label={t('Close menu')} onClick={() => setSidebarOpen(false)}>
                                    <i className="las la-times" />
                                </button>
                            </div>

                            <div className="bank-profile">
                                <div className="bank-avatar">{user.initials}</div>
                                <div className="bank-profile-info">
                                    <p className="bank-profile-name">{user.fullname}</p>
                                    <p className="bank-profile-id">{user.idLabel}</p>
                                </div>
                            </div>

                            <nav className="bank-nav">
                                {layout.nav.map((entry, i) => (
                                    <NavEntryView
                                        key={entry.type === 'group' ? entry.id : `${entry.type}-${i}`}
                                        entry={entry}
                                        openGroup={openGroup}
                                        onToggle={(id) => setOpenGroup((current) => (current === id ? null : id))}
                                    />
                                ))}
                            </nav>
                        </div>
                    </aside>

                    <div className={`bank-overlay d-lg-none${sidebarOpen ? ' active' : ''}`} onClick={() => setSidebarOpen(false)} />

                    <main className="bank-main">
                        <div className="bank-topbar">
                            <div className="bank-topbar-left">
                                <button type="button" className="bank-menu-toggle d-lg-none" aria-label={t('Open menu')} onClick={() => setSidebarOpen(true)}>
                                    <i className="las la-bars" />
                                </button>
                                <div className="bank-topbar-greeting d-none d-md-block">
                                    <h6 className="mb-0">
                                        {layout.greeting}, <strong>{user.firstname}</strong>
                                    </h6>
                                    <p className="mb-0 text-muted small">{layout.today}</p>
                                </div>
                            </div>
                            <div className="bank-topbar-right">
                                <Link href={links.deposit} className="bank-topbar-btn">
                                    <i className="las la-plus" />
                                    <span className="d-none d-sm-inline">{t('Fund Account')}</span>
                                </Link>
                                <Link href={links.cart} className="bank-topbar-icon bank-cart-icon" title={t('Cart')}>
                                    <i className="las la-shopping-cart" />
                                    <span className={`bank-cart-badge${counts.cart > 0 ? ' bank-cart-badge-active' : ''}`}>{counts.cart}</span>
                                </Link>
                                <Link href={links.notifications} className="bank-topbar-icon bank-notif-icon" title={t('Notifications')}>
                                    <i className="las la-bell" />
                                    <span className={`bank-notif-badge${counts.notifications > 0 ? ' bank-notif-badge-active' : ''}`}>
                                        {counts.notifications > 99 ? '99+' : counts.notifications}
                                    </span>
                                </Link>
                                <div className="bank-avatar-dropdown" ref={avatarRef}>
                                    <button
                                        type="button"
                                        className="bank-topbar-avatar"
                                        aria-expanded={menuOpen}
                                        aria-haspopup="true"
                                        onClick={() => setMenuOpen((open) => !open)}
                                    >
                                        {user.initials.charAt(0)}
                                    </button>
                                    <div className={`bank-avatar-menu${menuOpen ? ' open' : ''}`} role="menu">
                                        <div className="bank-avatar-menu-header">
                                            <p className="bank-avatar-menu-name">{user.fullname}</p>
                                            <p className="bank-avatar-menu-email">{user.email}</p>
                                        </div>
                                        <Link href={links.profile} className="bank-avatar-menu-item" role="menuitem">
                                            <i className="las la-user-cog" /> {t('Profile Settings')}
                                        </Link>
                                        <Link href={links.password} className="bank-avatar-menu-item" role="menuitem">
                                            <i className="las la-key" /> {t('Change Password')}
                                        </Link>
                                        <Link href={links.twofactor} className="bank-avatar-menu-item" role="menuitem">
                                            <i className="las la-shield-alt" /> {t('2FA Security')}
                                        </Link>
                                        <div className="bank-avatar-menu-divider" />
                                        <a href={links.logout} className="bank-avatar-menu-item bank-avatar-menu-logout" role="menuitem">
                                            <i className="las la-sign-out-alt" /> {t('Sign Out')}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bank-content">{children}</div>
                    </main>
                </div>
            </section>

            <footer className="ft-root">
                <div className="ft-top">
                    <div className="ft-brand">
                        <span className="ft-brand-logo">{siteName}</span>
                        <p className="ft-brand-tagline">{t('Empowering financial freedom, one step at a time.')}</p>
                    </div>
                    {layout.socials.length > 0 && (
                        <div className="ft-socials">
                            {layout.socials.map((social, i) => (
                                <a
                                    key={i}
                                    href={social.url}
                                    title={social.title}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="ft-social-btn"
                                    // Icon markup set by the admin in Frontend Manager (the Blade footer echoed it as is).
                                    dangerouslySetInnerHTML={{ __html: social.icon }}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </footer>
        </>
    );
}

function NavEntryView({ entry, openGroup, onToggle }: { entry: NavEntry; openGroup: string | null; onToggle: (id: string) => void }) {
    if (entry.type === 'label') {
        return <p className="bank-nav-label">{entry.text}</p>;
    }

    if (entry.type === 'links') {
        return (
            <ul>
                {entry.items.map((item) => (
                    <li key={item.url + item.label}>
                        <NavLinkView item={item} />
                    </li>
                ))}
            </ul>
        );
    }

    return (
        <div className={`bk-nav-group${openGroup === entry.id ? ' open' : ''}`}>
            <button type="button" className="bk-nav-group-toggle" aria-expanded={openGroup === entry.id} onClick={() => onToggle(entry.id)}>
                <span className="bank-nav-icon">
                    <i className={`las ${entry.icon}`} />
                </span>
                <span>{entry.label}</span>
                {entry.badge && <Badge badge={entry.badge} inGroupToggle />}
                <i className="las la-angle-right bk-nav-chevron" />
            </button>
            <ul className="bk-nav-group-body">
                {entry.items.map((item) => (
                    <li key={item.url + item.label}>
                        <NavLinkView item={item} />
                    </li>
                ))}
            </ul>
        </div>
    );
}

function NavLinkView({ item }: { item: NavLink }) {
    const className = ['bank-nav-link', item.active && 'active', item.logout && 'bank-nav-logout'].filter(Boolean).join(' ');

    return (
        <MenuAnchor href={item.url} reload={item.reload} className={className}>
            <span className="bank-nav-icon">
                <i className={`las ${item.icon}`} />
            </span>
            <span>{item.label}</span>
            {item.badge && <Badge badge={item.badge} />}
        </MenuAnchor>
    );
}

/** An Inertia link, or a plain link (full page load) when the target page is still Blade. */
function MenuAnchor({ href, reload, className, children }: { href: string; reload: boolean; className?: string; children: ReactNode }) {
    return reload ? (
        <a href={href} className={className}>
            {children}
        </a>
    ) : (
        <Link href={href} className={className}>
            {children}
        </Link>
    );
}

function Badge({ badge, inGroupToggle = false }: { badge: NavBadge; inGroupToggle?: boolean }) {
    if (badge.tone === 'pos') {
        return <span className="pos-nav-badge">{badge.text}</span>;
    }

    if (badge.tone === 'danger') {
        return <span className="bank-nav-badge bank-nav-badge-danger">{badge.text}</span>;
    }

    // Project colour is set by the admin per project: a 10% tint behind the text.
    const style: CSSProperties | undefined = badge.tone === 'project' && badge.color ? { background: `${badge.color}1a`, color: badge.color } : undefined;

    return (
        <span className={`bank-nav-badge bank-nav-pill bank-nav-pill-${badge.tone}${inGroupToggle ? ' bank-nav-pill-toggle' : ''}`} style={style} title={badge.title}>
            {badge.text}
        </span>
    );
}

function activeGroup(nav: NavEntry[] | undefined): string | null {
    const group = nav?.find((entry) => entry.type === 'group' && entry.active);
    return group && group.type === 'group' ? group.id : null;
}

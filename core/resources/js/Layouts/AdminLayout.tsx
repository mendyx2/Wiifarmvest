import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type ReactNode } from 'react';
import { useTrans } from '@/lib/i18n';
import type { AdminChrome, AdminNavLink } from '@/types';
import './AdminLayout.css';

/**
 * The admin panel's sidebar and top bar: the same markup and classes as
 * admin/partials/sidenav.blade.php and topnav.blade.php (styles in
 * assets/admin/css/app.css), without jQuery or Bootstrap's JavaScript.
 * Data: the shared "adminLayout" prop (App\Support\AdminLayout).
 *
 * app.tsx puts every page under Pages/Admin/ in this layout. It stays
 * mounted between admin visits, so an open menu group stays open.
 */
export default function AdminLayout({ children }: { children: ReactNode }) {
    const t = useTrans();
    const { adminLayout: layout } = usePage().props;
    const url = usePage().url;
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [openGroups, setOpenGroups] = useState<Set<string>>(() => activeGroups(layout));
    const [menu, setMenu] = useState<'notifications' | 'profile' | null>(null);
    const menuRef = useRef<HTMLUListElement>(null);
    const sidebarRef = useRef<HTMLDivElement>(null);

    // A new page: close the mobile sidebar and the menus, and open the group of the current page.
    useEffect(() => {
        setSidebarOpen(false);
        setMenu(null);
        setOpenGroups((open) => new Set([...open, ...activeGroups(layout)]));
    }, [url]); // eslint-disable-line react-hooks/exhaustive-deps

    // Bring the current link into view (the Blade sidebar scrolled to it with jQuery).
    useEffect(() => {
        const active = sidebarRef.current?.querySelector('.sidebar-menu-item.active');
        active?.scrollIntoView({ block: 'center' });
    }, []);

    useEffect(() => {
        if (!menu) return;
        const onClick = (e: MouseEvent) => {
            if (!menuRef.current?.contains(e.target as Node)) setMenu(null);
        };
        const onKey = (e: KeyboardEvent) => e.key === 'Escape' && setMenu(null);
        document.addEventListener('click', onClick);
        document.addEventListener('keydown', onKey);
        return () => {
            document.removeEventListener('click', onClick);
            document.removeEventListener('keydown', onKey);
        };
    }, [menu]);

    if (!layout) return <>{children}</>;

    const toggleGroup = (id: string) =>
        setOpenGroups((open) => {
            const next = new Set(open);
            if (next.has(id)) next.delete(id);
            else next.add(id);
            return next;
        });
    const toggleMenu = (name: 'notifications' | 'profile') => setMenu((open) => (open === name ? null : name));
    const { notifications, links, react } = layout;

    return (
        <div className="page-wrapper default-version">
            <div className={`sidebar bg--dark ${sidebarOpen ? 'open' : ''}`} ref={sidebarRef}>
                <button type="button" className="res-sidebar-close-btn" aria-label={t('Close menu')} onClick={() => setSidebarOpen(false)}>
                    <i className="las la-times" />
                </button>
                <div className="sidebar__inner">
                    <div className="sidebar__logo">
                        <AdminLink href={links.dashboard} reload={!react.dashboard} className="sidebar__main-logo">
                            <img src={layout.logo} alt="logo" />
                        </AdminLink>
                    </div>
                    <div className="sidebar__menu-wrapper">
                        <ul className="sidebar__menu">
                            {layout.nav.map((entry, i) => {
                                if (entry.type === 'header') {
                                    return (
                                        <li key={i} className="sidebar__menu-header">
                                            {entry.title}
                                        </li>
                                    );
                                }
                                if (entry.type === 'link') {
                                    return <SidebarLink key={i} item={entry} />;
                                }
                                const open = openGroups.has(entry.id);
                                return (
                                    <li key={i} className="sidebar-menu-item sidebar-dropdown">
                                        {/* An <a>, like the Blade sidebar: app.css styles ".sidebar-dropdown > a" (arrow, padding). */}
                                        <a
                                            href="#"
                                            role="button"
                                            className={open ? 'side-menu--open' : ''}
                                            aria-expanded={open}
                                            onClick={(e) => {
                                                e.preventDefault();
                                                toggleGroup(entry.id);
                                            }}
                                        >
                                            <i className={`menu-icon ${entry.icon}`} />
                                            <span className="menu-title">{entry.title}</span>
                                            {entry.alert && (
                                                <span className="menu-badge menu-badge-level-one bg--warning ms-auto">
                                                    <i className="fas fa-exclamation" />
                                                </span>
                                            )}
                                        </a>
                                        <div className={`sidebar-submenu ${open ? 'sidebar-submenu__open' : ''}`}>
                                            <ul>
                                                {entry.items.map((item, j) => (
                                                    <SidebarLink key={j} item={item} />
                                                ))}
                                            </ul>
                                        </div>
                                    </li>
                                );
                            })}
                        </ul>
                    </div>
                    <div className="version-info text-center text-uppercase">
                        <span className="text--primary">{layout.version.name}</span>{' '}
                        <span className="text--success">
                            {layout.version.version}
                            <br />
                            {t('Society')}
                        </span>
                    </div>
                </div>
            </div>
            {sidebarOpen && <div className="admin-sidebar-backdrop" onClick={() => setSidebarOpen(false)} aria-hidden="true" />}

            <nav className="navbar-wrapper bg--dark d-flex flex-wrap">
                <div className="navbar__left">
                    <button type="button" className="res-sidebar-open-btn me-3" aria-label={t('Open menu')} onClick={() => setSidebarOpen(true)}>
                        <i className="las la-bars" />
                    </button>
                    <AdminSearch url={links.search} />
                </div>
                <div className="navbar__right">
                    <ul className="navbar__action-list" ref={menuRef}>
                        <li>
                            <a href={links.website} target="_blank" rel="noopener noreferrer" className="primary--layer admin-top-btn" title={t('Visit Website')} aria-label={t('Visit Website')}>
                                <i className="las la-globe" />
                            </a>
                        </li>
                        <li className={`dropdown ${menu === 'notifications' ? 'show' : ''}`}>
                            <button
                                type="button"
                                className="primary--layer notification-bell"
                                aria-haspopup="true"
                                aria-expanded={menu === 'notifications'}
                                title={t('Unread Notifications')}
                                aria-label={t('Unread Notifications')}
                                onClick={() => toggleMenu('notifications')}
                            >
                                <i className={`las la-bell ${notifications.count > 0 ? 'icon-left-right' : ''}`} />
                                {notifications.count > 0 && <span className="notification-count">{notifications.count <= 9 ? notifications.count : '9+'}</span>}
                            </button>
                            <div className={`dropdown-menu dropdown-menu--md p-0 border-0 box--shadow1 dropdown-menu-right ${menu === 'notifications' ? 'show' : ''}`}>
                                <div className="dropdown-menu__header">
                                    <span className="caption">{t('Notification')}</span>
                                    {notifications.count > 0 && <p>{t('You have :count unread notification', { count: notifications.count })}</p>}
                                </div>
                                <div className={`dropdown-menu__body ${notifications.items.length ? '' : 'd-flex justify-content-center align-items-center'}`}>
                                    {notifications.items.length ? (
                                        notifications.items.map((n) => (
                                            // Marks it read, then goes to its page (often Blade): a full load.
                                            <a key={n.id} href={n.url} className="dropdown-menu__item">
                                                <div className="navbar-notifi">
                                                    <div className="navbar-notifi__right">
                                                        <h6 className="notifi__title">{n.title}</h6>
                                                        <span className="time">
                                                            <i className="far fa-clock" /> {n.time}
                                                        </span>
                                                    </div>
                                                </div>
                                            </a>
                                        ))
                                    ) : (
                                        <div className="empty-notification text-center">
                                            <p className="mt-3">{t('No unread notification found')}</p>
                                        </div>
                                    )}
                                </div>
                                <div className="dropdown-menu__footer">
                                    <AdminLink href={links.notifications} reload={!react.notifications} className="view-all-message">
                                        {t('View all notifications')}
                                    </AdminLink>
                                </div>
                            </div>
                        </li>
                        <li>
                            <AdminLink href={links.settings} reload={!react.settings} className="primary--layer admin-top-btn" title={t('System Setting')} aria-label={t('System Setting')}>
                                <i className="las la-wrench" />
                            </AdminLink>
                        </li>
                        <li className={`dropdown d-flex profile-dropdown ${menu === 'profile' ? 'show' : ''}`}>
                            <button type="button" aria-haspopup="true" aria-expanded={menu === 'profile'} onClick={() => toggleMenu('profile')}>
                                <span className="navbar-user">
                                    <span className="navbar-user__thumb">
                                        <img src={layout.admin.image} alt="" />
                                    </span>
                                    <span className="navbar-user__info">
                                        <span className="navbar-user__name">{layout.admin.username}</span>
                                    </span>
                                    <span className="icon">
                                        <i className="las la-chevron-circle-down" />
                                    </span>
                                </span>
                            </button>
                            <div className={`dropdown-menu dropdown-menu--sm p-0 border-0 box--shadow1 dropdown-menu-right ${menu === 'profile' ? 'show' : ''}`}>
                                <AdminLink href={links.profile} reload={!react.profile} className="dropdown-menu__item d-flex align-items-center px-3 py-2">
                                    <i className="dropdown-menu__icon las la-user-circle" />
                                    <span className="dropdown-menu__caption">{t('Profile')}</span>
                                </AdminLink>
                                <AdminLink href={`${links.profile}#password`} reload={!react.profile} className="dropdown-menu__item d-flex align-items-center px-3 py-2">
                                    <i className="dropdown-menu__icon las la-key" />
                                    <span className="dropdown-menu__caption">{t('Password')}</span>
                                </AdminLink>
                                {/* Ends the session: a normal page load */}
                                <a href={links.logout} className="dropdown-menu__item d-flex align-items-center px-3 py-2">
                                    <i className="dropdown-menu__icon las la-sign-out-alt" />
                                    <span className="dropdown-menu__caption">{t('Logout')}</span>
                                </a>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>

            <div className="container-fluid px-3 px-sm-0">
                <div className="body-wrapper">
                    <div className="bodywrapper__inner">{children}</div>
                </div>
            </div>
        </div>
    );
}

export const withAdminLayout = (page: ReactNode) => <AdminLayout>{page}</AdminLayout>;

function activeGroups(layout: AdminChrome | null | undefined) {
    return new Set((layout?.nav ?? []).flatMap((entry) => (entry.type === 'group' && entry.active ? [entry.id] : [])));
}

/** A link to an admin page: an Inertia visit for React pages, a full page load for Blade ones. */
function AdminLink({ href, reload, className, children, title, ...rest }: { href: string; reload: boolean; className?: string; children: ReactNode; title?: string; 'aria-label'?: string }) {
    if (reload) {
        return (
            <a href={href} className={className} title={title} {...rest}>
                {children}
            </a>
        );
    }
    return (
        <Link href={href} className={className} title={title} {...rest}>
            {children}
        </Link>
    );
}

function SidebarLink({ item }: { item: AdminNavLink }) {
    return (
        <li className={`sidebar-menu-item ${item.active ? 'active' : ''}`}>
            <AdminLink href={item.url} reload={item.reload} className="nav-link">
                <i className={`menu-icon ${item.icon}`} />
                <span className="menu-title">{item.title}</span>
                {item.counter !== null && <span className="menu-badge bg--info ms-auto">{item.counter}</span>}
            </AdminLink>
        </li>
    );
}

type SearchEntry = { title: string; parent: string; url: string; keywords: string[]; react: boolean };

/** Loaded on first use, once per page load: it never changes while an admin works. */
let searchIndex: Promise<SearchEntry[]> | null = null;
function loadSearchIndex(url: string) {
    searchIndex ??= fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
        .then((r) => (r.ok ? r.json() : Promise.reject(new Error(String(r.status)))))
        .catch((e) => {
            searchIndex = null;
            throw e;
        });
    return searchIndex;
}

/**
 * The top-bar search: finds menu pages and System Setting cards by title or
 * keyword (assets/admin/js/search.js did this with jQuery). Up/Down move
 * through the results, Enter opens one, Escape closes the list.
 */
function AdminSearch({ url }: { url: string }) {
    const t = useTrans();
    const [query, setQuery] = useState('');
    const [entries, setEntries] = useState<SearchEntry[] | null>(null);
    const [failed, setFailed] = useState(false);
    const [cursor, setCursor] = useState(0);
    const [open, setOpen] = useState(false);
    const boxRef = useRef<HTMLFormElement>(null);

    const load = () => {
        if (entries) return;
        loadSearchIndex(url)
            .then((list) => {
                setEntries(list);
                setFailed(false);
            })
            .catch(() => setFailed(true));
    };

    useEffect(() => {
        if (!open) return;
        const onClick = (e: MouseEvent) => {
            if (!boxRef.current?.contains(e.target as Node)) setOpen(false);
        };
        document.addEventListener('click', onClick);
        return () => document.removeEventListener('click', onClick);
    }, [open]);

    const q = query.trim().toLowerCase();
    const results = q && entries ? entries.filter((e) => e.title.toLowerCase().includes(q) || e.keywords.some((k) => k.toLowerCase().includes(q))) : [];

    const go = (entry: SearchEntry) => {
        setOpen(false);
        setQuery('');
        if (entry.react) router.visit(entry.url);
        else window.location.href = entry.url;
    };

    return (
        <form className="navbar-search" role="search" ref={boxRef} onSubmit={(e) => e.preventDefault()}>
            <input
                type="search"
                className="navbar-search-field"
                id="searchInput"
                autoComplete="off"
                placeholder={t('Search here...')}
                aria-label={t('Search admin pages')}
                aria-controls="admin-search-results"
                aria-expanded={open && !!q}
                value={query}
                onFocus={() => {
                    load();
                    setOpen(true);
                }}
                onChange={(e) => {
                    setQuery(e.target.value);
                    setCursor(0);
                    setOpen(true);
                }}
                onKeyDown={(e) => {
                    if (e.key === 'ArrowDown' && results.length) {
                        e.preventDefault();
                        setCursor((c) => (c + 1) % results.length);
                    } else if (e.key === 'ArrowUp' && results.length) {
                        e.preventDefault();
                        setCursor((c) => (c - 1 + results.length) % results.length);
                    } else if (e.key === 'Enter' && results[cursor]) {
                        e.preventDefault();
                        go(results[cursor]);
                    } else if (e.key === 'Escape') {
                        setOpen(false);
                    }
                }}
            />
            <i className="las la-search" />
            {open && q && (
                <ul className="search-list" id="admin-search-results" role="listbox">
                    {failed ? (
                        <li className="text-muted p-3">{t('Search is unavailable. Please reload the page.')}</li>
                    ) : !entries ? (
                        <li className="text-muted p-3">{t('Loading...')}</li>
                    ) : results.length ? (
                        results.map((entry, i) => (
                            <li key={`${entry.url}-${i}`} className={i === cursor ? 'active' : ''} role="option" aria-selected={i === cursor}>
                                <a
                                    className={`search-list-link ${i === cursor ? 'text-active' : ''}`}
                                    href={entry.url}
                                    onMouseEnter={() => setCursor(i)}
                                    onClick={(e) => {
                                        e.preventDefault();
                                        go(entry);
                                    }}
                                >
                                    {entry.parent}
                                    <p className="fw-bold text-color--3 d-block">{entry.title}</p>
                                </a>
                            </li>
                        ))
                    ) : (
                        <li className="text-muted">
                            <div className="empty-search text-center p-3">
                                <p className="text-muted mb-0">{t('No search result found')}</p>
                            </div>
                        </li>
                    )}
                </ul>
            )}
        </form>
    );
}

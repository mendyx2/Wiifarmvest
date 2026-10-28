import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type FormEvent, type ReactNode } from 'react';
import { useTrans } from '@/lib/i18n';
import './PublicLayout.css';

/**
 * Header and footer of the public pages: the shop and the terms page. Same
 * markup and classes as partials/shop_header.blade.php and shop_footer.blade.php
 * (styles in custom.css). Pages opt in with
 *   Page.layout = withPublicLayout
 * Data: the shared "publicLayout" prop (App\Support\PublicLayout).
 */
export default function PublicLayout({ children }: { children: ReactNode }) {
    const t = useTrans();
    const { publicLayout: layout, siteName } = usePage().props;
    const url = usePage().url;
    const [search, setSearch] = useState(layout?.search ?? '');
    const [menuOpen, setMenuOpen] = useState(false);
    const [stuck, setStuck] = useState(false);
    const accountRef = useRef<HTMLDivElement>(null);

    // A new page: close the account menu and show that page's search text.
    useEffect(() => {
        setMenuOpen(false);
        setSearch(layout?.search ?? '');
    }, [url]); // eslint-disable-line react-hooks/exhaustive-deps

    // Shadow under the header once the page scrolls (layouts/shop.blade.php did this with jQuery).
    useEffect(() => {
        const onScroll = () => setStuck(window.scrollY > 4);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    useEffect(() => {
        if (!menuOpen) return;
        const onClick = (e: MouseEvent) => {
            if (!accountRef.current?.contains(e.target as Node)) setMenuOpen(false);
        };
        const onKey = (e: KeyboardEvent) => e.key === 'Escape' && setMenuOpen(false);
        document.addEventListener('click', onClick);
        document.addEventListener('keydown', onKey);
        return () => {
            document.removeEventListener('click', onClick);
            document.removeEventListener('keydown', onKey);
        };
    }, [menuOpen]);

    if (!layout) return <>{children}</>;

    const { user, links } = layout;

    const submitSearch = (e: FormEvent) => {
        e.preventDefault();
        const q = search.trim();
        router.get(links.shop, q ? { q } : {});
    };

    return (
        <div className="public-shell">
            <header className={`shophd ${stuck ? 'shophd--stuck' : ''}`} id="shopHeader">
                <div className="shophd-bar">
                    <div className="shophd-container">
                        <Link href={links.shop} className="shophd-logo">
                            <img src={layout.logo} alt={siteName} />
                        </Link>

                        <form className="shophd-search" role="search" onSubmit={submitSearch}>
                            <input
                                type="text"
                                name="q"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder={t('Search products, e.g. Rice, Beans...')}
                                aria-label={t('Search products')}
                            />
                            <button type="submit" aria-label={t('Search')}>
                                <i className="las la-search" />
                            </button>
                        </form>

                        <div className="shophd-actions">
                            <div className={`dropdown shophd-account ${menuOpen ? 'show' : ''}`} ref={accountRef}>
                                <button className="shophd-icon-btn" type="button" aria-haspopup="menu" aria-expanded={menuOpen} onClick={() => setMenuOpen((open) => !open)}>
                                    <i className="las la-user-circle" />
                                    <span className="shophd-icon-label">{user ? t('Hi, :name', { name: shorten(user.firstname, 10) }) : t('Account')}</span>
                                    <i className="las la-angle-down shophd-caret" />
                                </button>
                                <div className={`dropdown-menu dropdown-menu-end shophd-dropdown ${menuOpen ? 'show' : ''}`} role="menu">
                                    {user ? (
                                        <>
                                            <div className="shophd-dropdown-head">
                                                {t('Signed in as')}
                                                <br />
                                                <strong>{user.fullname}</strong>
                                            </div>
                                            <Link className="dropdown-item" href={links.dashboard} role="menuitem">
                                                <i className="las la-tachometer-alt" /> {t('My Dashboard')}
                                            </Link>
                                            <Link className="dropdown-item" href={links.affiliate} role="menuitem">
                                                <i className="las la-link" /> {t('Affiliate Program')}
                                            </Link>
                                            <div className="dropdown-divider" />
                                            {/* Ends the session: a normal page load, like the dashboard's Sign Out */}
                                            <a className="dropdown-item" href={links.logout} role="menuitem">
                                                <i className="las la-sign-out-alt" /> {t('Sign Out')}
                                            </a>
                                        </>
                                    ) : (
                                        <>
                                            <div className="shophd-dropdown-head">{t('Welcome')}</div>
                                            <Link className="dropdown-item" href={links.login} role="menuitem">
                                                <i className="las la-sign-in-alt" /> {t('Login')}
                                            </Link>
                                            <Link className="dropdown-item shophd-dropdown-cta" href={links.register} role="menuitem">
                                                <i className="las la-user-plus" /> {t('Create Account')}
                                            </Link>
                                        </>
                                    )}
                                </div>
                            </div>

                            <Link href={links.cart} className="shophd-icon-btn shophd-cart-btn">
                                <span className="shophd-cart-icon-wrap">
                                    <i className="las la-shopping-cart" />
                                    <span className="shophd-cart-badge" id="shopHeaderCartCount">{layout.cartCount}</span>
                                </span>
                                <span className="shophd-icon-label">{t('Cart')}</span>
                            </Link>
                        </div>
                    </div>
                </div>

                {layout.categories.length > 0 && (
                    <div className="shophd-cats">
                        <div className="shophd-container shophd-cats-inner">
                            <Link href={links.shop} className={`shophd-cat-link ${layout.onList && layout.category === null ? 'active' : ''}`}>
                                {t('All Products')}
                            </Link>
                            {layout.categories.map((cat) => (
                                <Link key={cat.id} href={cat.url} className={`shophd-cat-link ${layout.category === cat.id ? 'active' : ''}`}>
                                    {cat.name}
                                </Link>
                            ))}
                        </div>
                    </div>
                )}
            </header>

            <main className="public-main">{children}</main>

            <footer className="shopft">
                <div className="shopft-main">
                    <div className="shophd-container shopft-grid">
                        <div className="shopft-col shopft-brand-col">
                            <Link href={links.shop} className="shopft-logo">
                                <img src={layout.logo} alt={siteName} />
                            </Link>
                            <p className="shopft-tagline">{t('Quality products, delivered or ready for pickup near you.')}</p>
                            {layout.socials.length > 0 && (
                                <div className="shopft-socials">
                                    {layout.socials.map((social, i) => (
                                        <a
                                            key={i}
                                            href={social.url}
                                            title={social.title}
                                            aria-label={social.title}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="shopft-social-btn"
                                            // The icon is <i> markup saved by the admin in Manage Frontend.
                                            dangerouslySetInnerHTML={{ __html: social.icon }}
                                        />
                                    ))}
                                </div>
                            )}
                        </div>

                        <div className="shopft-col">
                            <h6 className="shopft-heading">{t('Shop')}</h6>
                            <Link href={links.shop}>{t('All Products')}</Link>
                            {layout.categories.slice(0, 6).map((cat) => (
                                <Link key={cat.id} href={cat.url}>
                                    {cat.name}
                                </Link>
                            ))}
                            <Link href={links.cart}>{t('My Cart')}</Link>
                        </div>

                        <div className="shopft-col">
                            <h6 className="shopft-heading">{t('Customer Service')}</h6>
                            {layout.email && <a href={`mailto:${layout.email}`}>{t('Contact Us')}</a>}
                            <Link href={links.terms}>{t('Terms & Conditions')}</Link>
                        </div>

                        <div className="shopft-col">
                            <h6 className="shopft-heading">{t('Make Money With Us')}</h6>
                            <p className="shopft-text">{t('Become an affiliate — share your link and earn a bonus on every order.')}</p>
                            {user ? (
                                <Link href={links.affiliate} className="shopft-cta">
                                    {t('Go to Affiliate Dashboard')}
                                </Link>
                            ) : (
                                <Link href={links.login} className="shopft-cta">
                                    {t('Login to Get Your Link')}
                                </Link>
                            )}
                        </div>
                    </div>
                </div>

                <div className="shopft-bottom">
                    <div className="shophd-container shopft-bottom-inner">
                        <p className="shopft-copy">
                            &copy; {layout.year} {siteName}. {t('All rights reserved.')}
                        </p>
                        <div className="shopft-badges">
                            <span className="shopft-badge"><i className="las la-lock" /> {t('Secure Checkout')}</span>
                            <span className="shopft-badge"><i className="las la-credit-card" /> Paystack</span>
                            <span className="shopft-badge"><i className="las la-hand-holding-usd" /> {t('Cash on Pickup')}</span>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    );
}

export const withPublicLayout = (page: ReactNode) => <PublicLayout>{page}</PublicLayout>;

/** Laravel's Str::limit: cut at `max` characters and add "...". */
function shorten(text: string, max: number) {
    return text.length > max ? `${text.slice(0, max)}...` : text;
}

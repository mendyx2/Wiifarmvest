import { Head, Link } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import Pagination from '@/Components/Pagination';
import Sku from '@/Components/Shop/Sku';
import type { OrderStatus } from '@/Components/Shop/types';
import { useCopy } from '@/lib/clipboard';
import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';
import type { PageProps, Paginated } from '@/types';
import CopyNotice from '@/Components/Shop/CopyNotice';

type OrderRow = {
    id: number;
    code: string;
    date: string;
    state: string;
    amount: string;
    status: OrderStatus;
    invoiceCode: string | null;
    canCopy: boolean;
    itemCount: number;
    items: { name: string; sku: string | null; image: string | null; quantity: number; price: string }[];
    url: string;
};

type WelcomePackage = { id: number; title: string; amount: string; code: string; redeemed: boolean; redeemedAt: string | null };

type Filters = { status: string; sort: string; search: string };

type Props = PageProps<{
    orders: Paginated<OrderRow>;
    stats: { total: number; pending: number; redeemed: number; cancelled: number };
    filters: Filters;
    welcomePackages: WelcomePackage[];
    productsUrl: string;
}>;

const ICONS: Record<OrderStatus['key'], [string, string]> = {
    pending: ['pend', 'la-clock'],
    redeemed: ['ok', 'la-box-open'],
    cancelled: ['cancel', 'la-times-circle'],
};

const BADGE: Record<OrderStatus['key'], string> = { pending: 'pend', redeemed: 'ok', cancelled: 'cancel' };

function PackageCard({ wp, onCopied }: { wp: WelcomePackage; onCopied: (code: string) => void }) {
    const t = useTrans();
    const { copied, copy } = useCopy(2500);
    return (
        <div className={`ord-wp-card ${wp.redeemed ? 'ord-wp-card--done' : ''}`}>
            <div className="ord-wp-top">
                <div className="ord-wp-icon"><i className="las la-gift" /></div>
                <div className="ord-wp-info">
                    <p className="ord-wp-title">{wp.title}</p>
                    <p className="ord-wp-amount">{wp.amount}</p>
                </div>
                <span className={`ord-wp-badge ${wp.redeemed ? 'ord-wp-badge--done' : 'ord-wp-badge--pend'}`}>
                    {wp.redeemed ? <><i className="las la-check-circle" /> {t('Redeemed')}</> : <><i className="las la-hourglass-half" /> {t('Pending')}</>}
                </span>
            </div>
            <div className="ord-wp-code-row">
                <div className="ord-wp-code-wrap">
                    <span className="ord-wp-code-label">{t('Code')}</span>
                    <span className="ord-wp-code">{wp.code}</span>
                </div>
                {!wp.redeemed ? (
                    <button
                        className="ord-wp-copy-btn"
                        type="button"
                        title={t('Copy code')}
                        onClick={async () => (await copy(wp.code)) ? onCopied(wp.code) : window.notify?.('error', t('Failed to copy code.'))}
                    >
                        {copied ? <><i className="las la-check" /> {t('Copied!')}</> : <><i className="las la-copy" /> {t('Copy')}</>}
                    </button>
                ) : (
                    <span className="ord-wp-redeemed-date">
                        <i className="las la-calendar-check" /> {wp.redeemedAt}
                    </span>
                )}
            </div>
            {!wp.redeemed && (
                <p className="ord-wp-hint">
                    <i className="las la-map-marker-alt" /> {t('Show this code to any authorized stockist to claim your gift package')}
                </p>
            )}
        </div>
    );
}

export default function Orders({ orders, stats, filters, welcomePackages, productsUrl }: Props) {
    const t = useTrans();
    const { copy } = useCopy();
    const [copied, setCopied] = useState<{ code: string; kind: 'invoice' | 'package' } | null>(null);
    const [search, setSearch] = useState(filters.search);

    // Filters live in the URL (?status=&sort=&search=) and are applied by the server.
    const apply = (changes: Partial<Filters>) => {
        const next = { ...filters, search, ...changes };
        visitWithQuery(window.location.href, { status: next.status, search: next.search, sort: next.sort === 'newest' ? '' : next.sort });
    };

    // Search as the user types, after a short pause.
    const first = useRef(true);
    useEffect(() => {
        if (first.current) {
            first.current = false;
            return;
        }
        const timer = setTimeout(() => apply({ search }), 400);
        return () => clearTimeout(timer);
    }, [search]);

    const copyInvoice = async (code: string) => {
        if (await copy(code)) setCopied({ code, kind: 'invoice' });
        else window.notify?.('error', t('Failed to copy invoice code.'));
    };

    const filtering = filters.status !== '' || filters.search !== '';

    return (
        <div className="ord-page">
            <Head title={t('My Orders')} />

            {welcomePackages.length > 0 && (
                <div className="ord-wp-section">
                    <div className="ord-wp-section-head">
                        <i className="las la-gift" />
                        <span>{t('Welcome Packages')}</span>
                        <span className="ord-wp-count">{welcomePackages.length}</span>
                    </div>
                    <div className="ord-wp-grid">
                        {welcomePackages.map((wp) => (
                            <PackageCard wp={wp} key={wp.id} onCopied={(code) => setCopied({ code, kind: 'package' })} />
                        ))}
                    </div>
                </div>
            )}

            <div className="ord-header-card">
                <div className="ord-header-left">
                    <div className="ord-header-icon"><i className="las la-receipt" /></div>
                    <div>
                        <h6 className="ord-header-title">{t('My Orders')}</h6>
                        <p className="ord-header-sub">{t('Track and manage your purchase history')}</p>
                    </div>
                </div>
                <Link href={productsUrl} className="ord-shop-btn">
                    <i className="las la-store" /> {t('Continue Shopping')}
                </Link>
            </div>

            {stats.total === 0 ? (
                <div className="ord-empty">
                    <div className="ord-empty-icon"><i className="las la-receipt" /></div>
                    <h6>{t('No Orders Yet')}</h6>
                    <p>{t("You haven't placed any orders. Start shopping to see your order history here.")}</p>
                    <Link href={productsUrl} className="ord-empty-btn">
                        <i className="las la-store" /> {t('Start Shopping')}
                    </Link>
                </div>
            ) : (
                <>
                    <div className="ord-stats">
                        <div className="ord-stat">
                            <span className="ord-stat-val">{stats.total}</span>
                            <span className="ord-stat-label">{t('Total Orders')}</span>
                        </div>
                        <div className="ord-stat">
                            <span className="ord-stat-val pend">{stats.pending}</span>
                            <span className="ord-stat-label">{t('Pending')}</span>
                        </div>
                        <div className="ord-stat">
                            <span className="ord-stat-val ok">{stats.redeemed}</span>
                            <span className="ord-stat-label">{t('Redeemed')}</span>
                        </div>
                        <div className="ord-stat">
                            <span className="ord-stat-val warn">{stats.cancelled}</span>
                            <span className="ord-stat-label">{t('Cancelled')}</span>
                        </div>
                    </div>

                    <div className="ord-filter-bar">
                        <div className="ord-filter-group">
                            <label className="ord-filter-label" htmlFor="statusFilter">{t('Status')}</label>
                            <select className="ord-select" id="statusFilter" value={filters.status} onChange={(e) => apply({ status: e.target.value })}>
                                <option value="">{t('All Orders')}</option>
                                <option value="pending">{t('Pending')}</option>
                                <option value="redeemed">{t('Redeemed')}</option>
                                <option value="cancelled">{t('Cancelled')}</option>
                            </select>
                        </div>
                        <div className="ord-filter-group">
                            <label className="ord-filter-label" htmlFor="sortBy">{t('Sort By')}</label>
                            <select className="ord-select" id="sortBy" value={filters.sort} onChange={(e) => apply({ sort: e.target.value })}>
                                <option value="newest">{t('Newest First')}</option>
                                <option value="oldest">{t('Oldest First')}</option>
                                <option value="amount_high">{t('Amount: High to Low')}</option>
                                <option value="amount_low">{t('Amount: Low to High')}</option>
                            </select>
                        </div>
                        <div className="ord-filter-group ord-search-group">
                            <label className="ord-filter-label" htmlFor="searchOrders">{t('Search')}</label>
                            <div className="ord-search-wrap">
                                <i className="las la-search" />
                                <input
                                    className="ord-search-input"
                                    type="text"
                                    id="searchOrders"
                                    placeholder={t('Search by invoice code...')}
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                />
                            </div>
                        </div>
                    </div>

                    <div className="ord-list">
                        {orders.data.map((order) => {
                            const [tone, icon] = ICONS[order.status.key];
                            return (
                                <div className="ord-card" key={order.id}>
                                    <div className="ord-card-head">
                                        <div className="ord-card-icon-wrap">
                                            <div className={`ord-status-icon ${tone}`}><i className={`las ${icon}`} /></div>
                                        </div>
                                        <div className="ord-card-meta">
                                            <p className="ord-invoice">{t('Order #:code', { code: order.code })}</p>
                                            <p className="ord-date"><i className="las la-calendar" /> {order.date}</p>
                                            <p className="ord-state"><i className="las la-map-marker-alt" /> {order.state}</p>
                                        </div>
                                        <div className="ord-card-right">
                                            <p className="ord-amount">{order.amount}</p>
                                            <span className={`ord-badge ord-badge--${BADGE[order.status.key]}`}>{t(order.status.label)}</span>
                                        </div>
                                    </div>

                                    <div className="ord-card-items">
                                        <p className="ord-items-label"><i className="las la-boxes" /> {t('Items (:count)', { count: order.itemCount })}</p>
                                        <div className="ord-items-row">
                                            {order.items.map((item, i) => (
                                                <div className="ord-item-chip" key={i}>
                                                    {item.image ? (
                                                        <img src={item.image} alt={item.name} />
                                                    ) : (
                                                        <div className="ord-item-chip-placeholder"><i className="las la-image" /></div>
                                                    )}
                                                    <div>
                                                        <p className="ord-item-name">{item.name}</p>
                                                        <Sku value={item.sku} />
                                                        <p className="ord-item-qty">{t('Qty: :qty', { qty: item.quantity })} &bull; {item.price}</p>
                                                    </div>
                                                </div>
                                            ))}
                                            {order.itemCount > 3 && <div className="ord-item-more">{t('+:count more', { count: order.itemCount - 3 })}</div>}
                                        </div>
                                    </div>

                                    <div className="ord-card-foot">
                                        <div className="ord-card-foot-left">
                                            {order.canCopy && (
                                                <span className="ord-foot-note pend"><i className="las la-exclamation-circle" /> {t('Ready for redemption')}</span>
                                            )}
                                            {order.status.key === 'redeemed' && (
                                                <span className="ord-foot-note ok"><i className="las la-check-circle" /> {t('Successfully redeemed')}</span>
                                            )}
                                            {order.invoiceCode && (
                                                <span className="ord-foot-invoice">{t('Invoice:')} <code>{order.invoiceCode}</code></span>
                                            )}
                                        </div>
                                        <div className="ord-card-foot-right">
                                            {order.canCopy && order.invoiceCode && (
                                                <button className="ord-btn-copy" type="button" onClick={() => copyInvoice(order.invoiceCode!)}>
                                                    <i className="las la-copy" /> {t('Copy Code')}
                                                </button>
                                            )}
                                            <Link href={order.url} className="ord-btn-view">
                                                <i className="las la-eye" /> {t('View Order')}
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                        {orders.data.length === 0 && filtering && (
                            <p className="text-center text-muted my-4">{t('No orders match your filters.')}</p>
                        )}
                    </div>

                    {orders.last_page > 1 && (
                        <div className="ord-pagination">
                            <small className="ord-page-info">
                                {t('Showing :from–:to of :total orders', { from: orders.from ?? 0, to: orders.to ?? 0, total: orders.total })}
                            </small>
                            <Pagination links={orders.links} />
                        </div>
                    )}
                </>
            )}

            <CopyNotice copied={copied} onClose={() => setCopied(null)} />
        </div>
    );
}

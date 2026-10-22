import { Head, Link } from '@inertiajs/react';
import Sku from '@/Components/Shop/Sku';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type Item = { id: number; name: string; sku: string | null; image: string | null; quantity: number; status: 'in_stock' | 'low_stock' | 'out_of_stock' };

type Props = PageProps<{
    canOrderFromCompany: boolean;
    stats: { products: number; low: number; out: number; wallet: string };
    items: Item[];
    lowStock: number;
    cartCount: number;
    urls: { restock: string; orders: string; cart: string | null; reports: string };
}>;

const STATUS: Record<Item['status'], [string, string]> = {
    out_of_stock: ['sl-status-out-of-stock', 'Out of Stock'],
    low_stock: ['sl-status-low-stock', 'Low Stock'],
    in_stock: ['sl-status-in-stock', 'In Stock'],
};

export default function InventoryDashboard({ canOrderFromCompany, stats, items, lowStock, cartCount, urls }: Props) {
    const t = useTrans();
    const restockCta = canOrderFromCompany ? t('Order Products') : t('Buy from Mega');
    const low = items.filter((i) => i.status === 'low_stock');
    const out = items.filter((i) => i.status === 'out_of_stock');

    return (
        <div className="nc-wrap" id="ncWrap">
            <Head title={t('Inventory Management')} />
            <br />
            <div className="sl-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div>
                    <h4 className="sl-page-title mb-1">{t('Inventory Management')}</h4>
                    <p className="sl-page-subtitle mb-0">
                        {canOrderFromCompany ? t('Manage your product inventory and place orders.') : t('Your stock. To restock, buy from a Mega stockist in your state.')}
                    </p>
                </div>
                <div className="d-flex gap-2 flex-wrap">
                    {urls.cart ? (
                        <Link href={urls.cart} className="sl-btn sl-btn-outline position-relative">
                            <i className="las la-shopping-cart me-1" /> {t('Cart')}
                            <span className="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" aria-label={t(':count items in cart', { count: cartCount })}>{cartCount}</span>
                        </Link>
                    ) : (
                        <Link href={urls.orders} className="sl-btn sl-btn-outline"><i className="las la-shopping-basket me-1" /> {t('My Orders')}</Link>
                    )}
                    <Link href={urls.restock} className="sl-btn sl-btn-primary">
                        <i className={`las la-${canOrderFromCompany ? 'shopping-bag' : 'truck-loading'} me-1`} /> {restockCta}
                    </Link>
                </div>
            </div>

            <div className="row g-3 mb-4">
                {[
                    { tone: 'primary', icon: 'la-boxes', label: t('Total Products'), value: String(stats.products) },
                    { tone: 'gold', icon: 'la-exclamation-triangle', label: t('Low Stock'), value: String(stats.low) },
                    { tone: 'danger', icon: 'la-times-circle', label: t('Out of Stock'), value: String(stats.out) },
                    { tone: 'success', icon: 'la-wallet', label: t('Wallet Balance'), value: stats.wallet },
                ].map((card) => (
                    <div className="col-6 col-sm-6 col-xl-3" key={card.label}>
                        <div className={`sl-stat-card sl-stat-${card.tone}`}>
                            <div className="sl-stat-icon"><i className={`las ${card.icon}`} /></div>
                            <div className="sl-stat-body">
                                <p className="sl-stat-label">{card.label}</p>
                                <h3 className="sl-stat-value">{card.value}</h3>
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            <div className="row g-4">
                <div className="col-12 col-lg-8">
                    <div className="sl-card">
                        <div className="sl-card-header"><i className="las la-boxes me-1" /> {t('Current Inventory')}</div>
                        <div className="sl-card-body p-0">
                            {items.length === 0 ? (
                                <div className="sl-empty-state">
                                    <div className="sl-empty-icon"><i className="las la-box-open" /></div>
                                    <p className="sl-empty-title">{t('No Inventory Yet')}</p>
                                    <p className="sl-empty-sub">{t("You haven't added any products to your inventory yet.")}</p>
                                    <Link href={urls.restock} className="sl-btn sl-btn-primary mt-3"><i className="las la-shopping-cart me-1" /> {restockCta}</Link>
                                </div>
                            ) : (
                                <div className="table-responsive">
                                    <table className="sl-table" aria-label={t('Current inventory')}>
                                        <thead>
                                            <tr>
                                                <th>{t('Product')}</th>
                                                <th>{t('Current Stock')}</th>
                                                <th>{t('Status')}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {items.map((item) => {
                                                const [cls, label] = STATUS[item.status];
                                                return (
                                                    <tr key={item.id}>
                                                        <td data-label={t('Product')}>
                                                            <div className="sl-table-name">
                                                                {item.image ? (
                                                                    <img src={item.image} alt="" className="sl-table-avatar" style={{ objectFit: 'cover' }} />
                                                                ) : (
                                                                    <div className="sl-table-avatar" style={{ background: '#F3F4F6', color: '#9CA3AF' }}><i className="las la-box" /></div>
                                                                )}
                                                                <div>
                                                                    <p className="fw-600 mb-0">{item.name}</p>
                                                                    <Sku value={item.sku} />
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td data-label={t('Current Stock')}>
                                                            <span className="fw-700" style={item.status !== 'in_stock' ? { color: item.status === 'low_stock' ? '#D97706' : '#DC2626' } : undefined}>{item.quantity}</span>
                                                        </td>
                                                        <td data-label={t('Status')}><span className={`sl-status ${cls}`}>{t(label)}</span></td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                <div className="col-12 col-lg-4">
                    <div className="sl-card mb-4">
                        <div className="sl-card-header"><i className="las la-bolt me-1" /> {t('Quick Actions')}</div>
                        <div className="sl-card-body d-grid gap-2">
                            <Link href={urls.restock} className="sl-btn sl-btn-primary"><i className="las la-shopping-cart me-1" /> {restockCta}</Link>
                            <Link href={urls.orders} className="sl-btn sl-btn-outline"><i className="las la-history me-1" /> {t('View Order History')}</Link>
                            <Link href={urls.reports} className="sl-btn sl-btn-outline"><i className="las la-chart-line me-1" /> {t('View Reports')}</Link>
                        </div>
                    </div>

                    {low.length > 0 && (
                        <div className="sl-card mb-4">
                            <div className="sl-card-header" style={{ background: '#FFFBEB', color: '#92400E' }}><i className="las la-exclamation-triangle me-1" /> {t('Low Stock Alerts')}</div>
                            <div className="sl-card-body">
                                {low.slice(0, 3).map((item) => (
                                    <div className="alert sl-alert-warning d-flex align-items-center gap-2 mb-2 py-2" key={item.id}>
                                        <i className="las la-exclamation-circle" />
                                        <div className="flex-grow-1">
                                            <strong>{item.name}</strong>
                                            <br />
                                            <small>{t('Stock: :qty (low below :min)', { qty: item.quantity, min: lowStock })}</small>
                                        </div>
                                    </div>
                                ))}
                                {low.length > 3 && <div className="text-center"><small className="text-muted">{t('+:count more items', { count: low.length - 3 })}</small></div>}
                                <div className="text-center mt-2">
                                    <Link href={urls.restock} className="sl-btn sl-btn-outline" style={{ fontSize: '.78rem', padding: '.4rem .9rem' }}>{t('Reorder Now')}</Link>
                                </div>
                            </div>
                        </div>
                    )}

                    {out.length > 0 && (
                        <div className="sl-card">
                            <div className="sl-card-header" style={{ background: '#FEF2F2', color: '#991B1B' }}><i className="las la-times-circle me-1" /> {t('Out of Stock')}</div>
                            <div className="sl-card-body">
                                {out.slice(0, 3).map((item) => (
                                    <div className="alert sl-alert-danger d-flex align-items-center gap-2 mb-2 py-2" key={item.id}>
                                        <i className="las la-times" />
                                        <div className="flex-grow-1">
                                            <strong>{item.name}</strong>
                                            <br />
                                            <small>{t('Completely out of stock')}</small>
                                        </div>
                                    </div>
                                ))}
                                <div className="text-center">
                                    <Link href={urls.restock} className="sl-btn sl-btn-outline" style={{ fontSize: '.78rem', padding: '.4rem .9rem' }}>{t('Restock Now')}</Link>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

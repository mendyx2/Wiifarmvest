import { Link, usePage } from '@inertiajs/react';
import BoxWidget from '@/Components/Admin/BoxWidget';
import type { AdminUser } from '@/Components/Admin/Cells';
import { ConfirmButton } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import Pagination from '@/Components/Pagination';
import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';
import type { Paginated } from '@/types';

type Ad = {
    id: number;
    title: string;
    video: boolean;
    media: string;
    thumb: string | null;
    active: boolean;
    order: number;
    product: string | null;
    shares: number;
    visits: number;
    orders: number;
    sales: string;
    hasSales: boolean;
    urls: { edit: string; status: string; delete: string };
};

type Props = {
    ads: Paginated<Ad>;
    filter: 'all' | 'live' | 'paused';
    counts: { all: number; live: number; paused: number };
    totals: { shares: string; visits: string; orders: number; revenue: string };
    channels: { key: string; label: string; icon: string; total: number; percent: number }[];
    promoters: { user: AdminUser; visits: number; orders: number }[];
    urls: { create: string };
};

/** Affiliate Program → Ad Materials & Media: pictures and clips members share with their own link. */
export default function AdManager({ ads, filter, counts, totals, channels, promoters, urls }: Props) {
    const t = useTrans();
    const url = usePage().url;

    return (
        <div className="db-wrap adm-ads">
            <PageHeader title={t('Ad Manager')}>
                <Link href={urls.create} className="adm-ads-new">
                    <i className="las la-plus" /> {t('New ad')}
                </Link>
            </PageHeader>
            <p className="text-muted">{t('Pictures and clips members post with their own affiliate link. Every visit and order is traced back to the ad.')}</p>
            <div className="row gy-4 fr-boxes">
                <div className="col-xl-3 col-sm-6">
                    <BoxWidget outline bg="success" icon="las la-bullhorn" title={t('Live ads')} value={counts.live.toLocaleString()} note={t(':n paused', { n: counts.paused })} />
                </div>
                <div className="col-xl-3 col-sm-6">
                    <BoxWidget outline bg="info" icon="las la-share" title={t('Shares')} value={totals.shares} note={t('Posts by members')} />
                </div>
                <div className="col-xl-3 col-sm-6">
                    <BoxWidget outline bg="warning" icon="las la-mouse-pointer" title={t('Visits')} value={totals.visits} note={t('Taps on shared ads')} />
                </div>
                <div className="col-xl-3 col-sm-6">
                    <BoxWidget outline bg="primary" icon="las la-shopping-cart" title={t('Sales from ads')} value={totals.revenue} note={t(':n paid order(s)', { n: totals.orders })} />
                </div>
            </div>

            <div className="row gy-4 mt-1">
                <div className="col-xl-8">
                    <div className="adm-ads-bar">
                        <nav className="adm-ads-tabs" aria-label={t('Filter ads')}>
                            {(['all', 'live', 'paused'] as const).map((key) => (
                                <button type="button" key={key} className={`adm-ads-tab border-0 ${filter === key ? 'is-active' : ''}`} aria-pressed={filter === key} onClick={() => visitWithQuery(url, { status: key === 'all' ? '' : key })}>
                                    {t(key.charAt(0).toUpperCase() + key.slice(1))} <span>{counts[key]}</span>
                                </button>
                            ))}
                        </nav>
                        <span className="adm-ads-bar__hint">{t('Lower sort number shows first on the member page')}</span>
                    </div>
                    {ads.data.length === 0 ? (
                        <div className="adm-ads-empty">
                            <i className="las la-photo-video" />
                            {filter === 'all' ? (
                                <>
                                    <h6>{t('No ads yet')}</h6>
                                    <p>{t('Upload a product photo or a short clip. Members will see it in their Ad Center with share buttons for WhatsApp, Facebook, Instagram, TikTok and more.')}</p>
                                    <Link href={urls.create} className="btn btn--primary btn-sm">
                                        <i className="las la-plus" /> {t('Create the first ad')}
                                    </Link>
                                </>
                            ) : (
                                <>
                                    <h6>{t('No :f ads', { f: filter })}</h6>
                                    <p>
                                        <button type="button" className="btn btn-link p-0" onClick={() => visitWithQuery(url, {})}>
                                            {t('Show all ads')}
                                        </button>
                                    </p>
                                </>
                            )}
                        </div>
                    ) : (
                        <div className="adm-ads-grid">
                            {ads.data.map((ad) => (
                                <article className={`adm-ad ${ad.active ? '' : 'is-paused'}`} key={ad.id}>
                                    <a href={ad.media} target="_blank" rel="noopener noreferrer" className="adm-ad__media" title={t('Open the file')}>
                                        {ad.thumb ? <img src={ad.thumb} alt={ad.title} loading="lazy" /> : <video src={`${ad.media}#t=0.5`} preload="metadata" muted playsInline />}
                                        <span className="adm-ad__type">
                                            <i className={ad.video ? 'las la-play' : 'las la-image'} /> {ad.video ? t('Video') : t('Picture')}
                                        </span>
                                    </a>
                                    <div className="adm-ad__body">
                                        <div className="adm-ad__status">
                                            <span className="adm-ad__dot" />
                                            {ad.active ? t('Live') : t('Paused')}
                                            <span className="adm-ad__order">#{ad.order}</span>
                                        </div>
                                        <h6 className="adm-ad__title">{ad.title}</h6>
                                        <p className="adm-ad__dest">
                                            <i className="las la-level-up-alt" /> {ad.product ?? t('Shop home')}
                                        </p>
                                        <dl className="adm-ad__stats">
                                            <div>
                                                <dt>{t('Shares')}</dt>
                                                <dd>{ad.shares.toLocaleString()}</dd>
                                            </div>
                                            <div>
                                                <dt>{t('Visits')}</dt>
                                                <dd>{ad.visits.toLocaleString()}</dd>
                                            </div>
                                            <div>
                                                <dt>{t('Orders')}</dt>
                                                <dd>{ad.orders.toLocaleString()}</dd>
                                            </div>
                                        </dl>
                                        <p className={`adm-ad__sales ${ad.hasSales ? '' : 'is-zero'}`}>
                                            <span>{t('Sales')}</span> {ad.sales}
                                        </p>
                                    </div>
                                    <div className="adm-ad__foot">
                                        <Link href={ad.urls.edit} className="adm-ad__btn">
                                            <i className="las la-pen" /> {t('Edit')}
                                        </Link>
                                        <ConfirmButton
                                            url={ad.urls.status}
                                            data={{ state: ad.active ? 'paused' : 'live' }}
                                            className="adm-ad__btn"
                                            question={ad.active ? t('Pause “:t”? Links already shared still open the shop.', { t: ad.title }) : t('Put “:t” live again?', { t: ad.title })}
                                        >
                                            <i className={ad.active ? 'las la-pause' : 'las la-play'} /> {ad.active ? t('Pause') : t('Go live')}
                                        </ConfirmButton>
                                        <ConfirmButton
                                            url={ad.urls.delete}
                                            className="adm-ad__btn adm-ad__btn--danger ms-auto"
                                            title={t('Delete')}
                                            question={t('Delete “:t”? Its file is removed and links already shared will open the shop instead.', { t: ad.title })}
                                        >
                                            <i className="las la-trash" />
                                        </ConfirmButton>
                                    </div>
                                </article>
                            ))}
                        </div>
                    )}
                    {ads.last_page > 1 && (
                        <div className="mt-4">
                            <Pagination links={ads.links} />
                        </div>
                    )}
                </div>
                <div className="col-xl-4">
                    <section className="adm-ads-panel">
                        <header className="adm-ads-panel__head">
                            <h6>{t('Where members share')}</h6>
                            <span>{t(':n total', { n: totals.shares })}</span>
                        </header>
                        {channels.length ? (
                            channels.map((c) => (
                                <div className={`adm-ch adm-ch--${c.key}`} key={c.key}>
                                    <span className="adm-ch__icon">
                                        <i className={c.icon} />
                                    </span>
                                    <div className="adm-ch__main">
                                        <div className="adm-ch__row">
                                            <span>{t(c.label)}</span>
                                            <strong>{c.total.toLocaleString()}</strong>
                                        </div>
                                        <div className="adm-ch__track">
                                            <span style={{ width: `${c.percent}%` }} />
                                        </div>
                                    </div>
                                </div>
                            ))
                        ) : (
                            <p className="adm-ads-panel__empty">{t('Nothing shared yet. Channels appear here as members post ads.')}</p>
                        )}
                    </section>
                    <section className="adm-ads-panel">
                        <header className="adm-ads-panel__head">
                            <h6>{t('Top promoters')}</h6>
                            <span>{t('By visits from ads')}</span>
                        </header>
                        {promoters.length ? (
                            promoters.map((row, i) => (
                                <div className="adm-promoter" key={i}>
                                    <span className="adm-promoter__rank">{i + 1}</span>
                                    <div className="adm-promoter__who">
                                        {row.user ? <Link href={row.user.url}>{row.user.fullname}</Link> : '—'}
                                        <small>@{row.user?.username}</small>
                                    </div>
                                    <div className="adm-promoter__nums">
                                        <strong>{row.visits.toLocaleString()}</strong> {t('visits')}
                                        <small>{t(':n order(s)', { n: row.orders })}</small>
                                    </div>
                                </div>
                            ))
                        ) : (
                            <p className="adm-ads-panel__empty">{t('No visits from ads yet.')}</p>
                        )}
                    </section>
                </div>
            </div>
        </div>
    );
}

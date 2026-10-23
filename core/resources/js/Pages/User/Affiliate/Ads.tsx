import { Head, Link, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState, type PointerEvent as ReactPointerEvent } from 'react';
import Pagination from '@/Components/Pagination';
import { useTrans } from '@/lib/i18n';
import type { PageProps, Paginated } from '@/types';

type Ad = {
    id: number;
    title: string;
    caption: string;
    kind: 'image' | 'video';
    /** The member's own tracked link for this ad */
    link: string;
    media: string;
    mime: string;
    poster: string | null;
    thumb: string | null;
    fileName: string;
    /** Product the link opens, or null for the shop */
    destination: string | null;
    visits: number;
    orders: number;
    logUrl: string;
};

type Channel = 'native' | 'facebook' | 'whatsapp' | 'x' | 'telegram' | 'instagram' | 'tiktok' | 'youtube' | 'copy' | 'download';
type UploadChannel = 'instagram' | 'tiktok' | 'youtube';

type Props = PageProps<{
    ads: Paginated<Ad>;
    stats: { ads: string; shared: string; visits: string; orders: string };
    icons: Record<Channel, string>;
    urls: { dashboard: string };
}>;

const LINK_CHANNELS: [Channel, string][] = [
    ['facebook', 'Facebook'],
    ['whatsapp', 'WhatsApp'],
    ['x', 'X'],
    ['telegram', 'Telegram'],
];
const FILE_CHANNELS: [UploadChannel, string][] = [
    ['instagram', 'Instagram'],
    ['tiktok', 'TikTok'],
    ['youtube', 'YouTube'],
];
const GUIDES: Record<UploadChannel, { name: string; url: string; tip: string }> = {
    instagram: {
        name: 'Instagram',
        url: 'https://www.instagram.com/',
        tip: 'Links in Instagram captions can’t be tapped. Put your link in your bio, or add it with a Link sticker on a Story.',
    },
    tiktok: { name: 'TikTok', url: 'https://www.tiktok.com/upload', tip: 'TikTok captions can’t hold a tappable link either. Add your link to your bio and say “link in bio”.' },
    youtube: { name: 'YouTube', url: 'https://www.youtube.com/upload', tip: 'Upload it as a Short if it’s vertical and under a minute. Links in the description can be clicked.' },
};

const isMobile = typeof navigator !== 'undefined' && (/Android|iPhone|iPad|iPod/i.test(navigator.userAgent) || (navigator.maxTouchPoints > 1 && /Macintosh/.test(navigator.userAgent)));

/** media URL → downloaded File, so a second tap can share at once (iPhones refuse a share that starts after a download). */
const files = new Map<string, File>();

function canShareFile(ad: Ad): boolean {
    if (!navigator.canShare) return false;
    try {
        return navigator.canShare({ files: [new File([''], ad.fileName, { type: ad.mime })] });
    } catch {
        return false;
    }
}

async function fileFor(ad: Ad): Promise<File> {
    const cached = files.get(ad.media);
    if (cached) return cached;
    const response = await fetch(ad.media);
    if (!response.ok) throw new Error('download failed');
    const file = new File([await response.blob()], ad.fileName, { type: ad.mime });
    files.set(ad.media, file);
    return file;
}

async function copyText(text: string): Promise<boolean> {
    try {
        await navigator.clipboard.writeText(text);
        return true;
    } catch {
        // Older in-app browsers and plain HTTP: fall back to a hidden textarea
        const area = Object.assign(document.createElement('textarea'), { value: text });
        area.style.cssText = 'position:fixed;opacity:0';
        document.body.append(area);
        area.select();
        const ok = document.execCommand('copy');
        area.remove();
        return ok;
    }
}

function download(ad: Ad) {
    const a = Object.assign(document.createElement('a'), { href: ad.media, download: ad.fileName });
    document.body.append(a);
    a.click();
    a.remove();
}

const openWindow = (url: string) => window.open(url, '_blank', 'noopener,width=620,height=640');

export default function AffiliateAds({ ads, stats, icons, urls }: Props) {
    const t = useTrans();
    const { csrfToken } = usePage().props;
    const [toast, setToast] = useState<string | null>(null);
    const [viewer, setViewer] = useState<number | null>(null);
    const toastTimer = useRef<ReturnType<typeof setTimeout>>(undefined);

    const say = useCallback((message: string) => {
        setToast(message);
        clearTimeout(toastTimer.current);
        toastTimer.current = setTimeout(() => setToast(null), 3600);
    }, []);

    /** Logged in the background; the share itself happens in the browser. */
    const log = useCallback(
        (ad: Ad, channel: Channel) =>
            fetch(ad.logUrl, {
                method: 'POST',
                keepalive: true,
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ channel }),
            }).catch(() => undefined),
        [csrfToken],
    );

    // Pictures are small: fetch the ones on screen early so the first tap shares at once.
    useEffect(() => {
        if (!('IntersectionObserver' in window)) return;
        const early = new IntersectionObserver(
            (entries) =>
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    early.unobserve(entry.target);
                    const ad = ads.data.find((a) => String(a.id) === (entry.target as HTMLElement).dataset.ad);
                    if (ad) fileFor(ad).catch(() => undefined);
                }),
            { rootMargin: '200px' },
        );
        document.querySelectorAll<HTMLElement>('.adc-card[data-ad]').forEach((card) => {
            const ad = ads.data.find((a) => String(a.id) === card.dataset.ad);
            if (ad && ad.kind === 'image' && canShareFile(ad)) early.observe(card);
        });
        return () => early.disconnect();
    }, [ads.data]);

    useEffect(() => () => clearTimeout(toastTimer.current), []);

    return (
        <div className="nc-wrap adc">
            <Head title={t('Ad Center')} />
            <br />
            <section className="adc-hero">
                <div className="adc-hero__text">
                    <span className="adc-hero__tag">
                        <i className="las la-bullhorn" /> {t('Ad Center')}
                    </span>
                    <h4 className="adc-hero__title">{t('Share ready-made ads, earn on every order')}</h4>
                    <p className="adc-hero__sub">{t('Each post carries your own link. Anyone who taps it and buys is counted as your sale.')}</p>
                </div>
                <Link href={urls.dashboard} className="adc-hero__btn">
                    <i className="las la-tachometer-alt" /> {t('Affiliate Dashboard')}
                </Link>
                <i className="las la-bullhorn adc-hero__art" aria-hidden="true" />
            </section>

            <div className="adc-stats">
                <Stat tone="green" icon="la-photo-video" value={stats.ads} label={t('Ads to share')} />
                <Stat tone="blue" icon="la-share-alt" value={stats.shared} label={t("Ads you've shared")} />
                <Stat tone="amber" icon="la-mouse-pointer" value={stats.visits} label={t('Visits from your ads')} />
                <Stat tone="rose" icon="la-shopping-bag" value={stats.orders} label={t('Paid orders from ads')} />
            </div>

            {ads.data.length === 0 ? (
                <div className="adc-empty">
                    <i className="las la-bullhorn" />
                    <h5>{t('No ads right now')}</h5>
                    <p>
                        {t('New posts appear here as soon as the team publishes them. Meanwhile, you can share your shop link from the')}{' '}
                        <Link href={urls.dashboard}>{t('Affiliate Dashboard')}</Link>.
                    </p>
                </div>
            ) : (
                <>
                    <div className="adc-list">
                        {ads.data.map((ad, i) => (
                            <AdCard key={ad.id} ad={ad} icons={icons} say={say} log={log} onZoom={() => setViewer(i)} />
                        ))}
                    </div>
                    <div className="mt-4">
                        <Pagination links={ads.links} />
                    </div>
                </>
            )}

            <p className="adc-foot">
                <i className="las la-info-circle" />
                <span>
                    <strong>{t('Share picture + link')}</strong>{' '}
                    {t(
                        'sends the actual picture or clip with your caption, so it shows in full on WhatsApp, Status, Facebook and Telegram. A link on its own shows a preview card with the picture. Instagram, TikTok and YouTube only take uploads: we save the file and copy your caption for you.',
                    )}
                </span>
            </p>

            <Viewer ads={ads.data} index={viewer} onIndex={setViewer} onClose={() => setViewer(null)} log={log} />
            <div className={`adc-toast${toast ? ' is-on' : ''}`} role="status" aria-live="polite">
                {toast}
            </div>
        </div>
    );
}

function Stat({ tone, icon, value, label }: { tone: string; icon: string; value: string; label: string }) {
    return (
        <div className={`adc-stat adc-stat--${tone}`}>
            <span className="adc-stat__icon">
                <i className={`las ${icon}`} />
            </span>
            <div>
                <strong>{value}</strong>
                <span>{label}</span>
            </div>
        </div>
    );
}

type Guide = { channel: UploadChannel; viaShareSheet: boolean };

function AdCard({ ad, icons, say, log, onZoom }: { ad: Ad; icons: Props['icons']; say: (m: string) => void; log: (ad: Ad, c: Channel) => void; onZoom: () => void }) {
    const t = useTrans();
    const [caption, setCaption] = useState(ad.caption);
    const [busy, setBusy] = useState(false);
    const [guide, setGuide] = useState<Guide | null>(null);
    const [shareable, setShareable] = useState(false);
    const what = ad.kind === 'video' ? t('video') : t('picture');

    // Known only in the browser: where files can't be shared, the main button saves instead.
    useEffect(() => setShareable(canShareFile(ad)), [ad]);

    const text = () => (caption.trim() ? `${caption.trim()}\n\n` : '') + ad.link;

    /** Opens the share sheet with the file. Resolves to 'shared', 'cancelled', 'retry' or 'failed'. */
    const shareFile = async (withText: boolean) => {
        let file = files.get(ad.media);
        if (!file) {
            setBusy(true);
            try {
                file = await fileFor(ad);
            } catch {
                setBusy(false);
                say(t('Couldn’t load the :what. Check your connection and try again.', { what }));
                return 'failed';
            }
            setBusy(false);
        }
        const data: ShareData = { files: [file] };
        if (withText) {
            data.text = text();
            data.title = ad.title;
        }
        try {
            await navigator.share(data);
            return 'shared';
        } catch (e) {
            const name = (e as DOMException).name;
            if (name === 'AbortError') return 'cancelled';
            if (name === 'NotAllowedError') {
                say(t('Ready. Tap again to share.'));
                return 'retry';
            }
            return 'failed';
        }
    };

    const handlers: Record<Exclude<Channel, 'download'>, () => void | Promise<void>> = {
        // Picture + caption + link in one go, through the phone's or computer's share sheet
        async native() {
            copyText(text()); // some apps keep the picture but drop the text
            if (!canShareFile(ad)) {
                download(ad);
                say(t('The :what is saving and your caption is copied. Attach it to your post, then paste.', { what }));
                log(ad, 'download');
                return;
            }
            const result = await shareFile(true);
            if (result === 'shared') {
                log(ad, 'native');
                say(t('Shared. Your caption and link are on the clipboard too, in case the app left them out.'));
            } else if (result === 'failed') {
                download(ad);
                say(t('Sharing isn’t available here, so the :what is saving and your caption is copied.', { what }));
                log(ad, 'download');
            }
        },
        facebook() {
            // Facebook ignores pre-filled text: the caption goes on the clipboard instead
            copyText(text());
            openWindow('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(ad.link));
            say(t('Caption copied. Paste it into your Facebook post.'));
            log(ad, 'facebook');
        },
        whatsapp() {
            window.open('https://wa.me/?text=' + encodeURIComponent(text()), '_blank', 'noopener');
            log(ad, 'whatsapp');
        },
        x() {
            const c = caption.trim();
            const short = c.length > 230 ? c.slice(0, 229).trimEnd() + '…' : c;
            openWindow('https://x.com/intent/tweet?text=' + encodeURIComponent(short) + '&url=' + encodeURIComponent(ad.link));
            log(ad, 'x');
        },
        telegram() {
            window.open('https://t.me/share/url?url=' + encodeURIComponent(ad.link) + '&text=' + encodeURIComponent(caption.trim()), '_blank', 'noopener');
            log(ad, 'telegram');
        },
        async copy() {
            if (await copyText(ad.link)) say(t('Link copied.'));
            log(ad, 'copy');
        },
        // Instagram, TikTok and YouTube: phones get the share sheet with the file,
        // computers get the file downloaded and the upload page one click away
        instagram: () => upload('instagram'),
        tiktok: () => upload('tiktok'),
        youtube: () => upload('youtube'),
    };

    async function upload(channel: UploadChannel) {
        copyText(text());
        if (isMobile && canShareFile(ad)) {
            const result = await shareFile(false);
            if (result === 'shared') {
                setGuide({ channel, viaShareSheet: true });
                log(ad, channel);
            }
            if (result !== 'failed') return;
        }
        download(ad);
        setGuide({ channel, viaShareSheet: false });
        log(ad, channel);
    }

    const plural = (n: number, one: string, many: string) => `${n.toLocaleString()} ${n === 1 ? t(one) : t(many)}`;

    return (
        <article className={`adc-card${ad.kind === 'video' ? ' adc-card--video' : ''}`} data-ad={ad.id}>
            <button type="button" className={`adc-thumb${ad.kind === 'video' ? ' adc-thumb--video' : ''}`} onClick={onZoom} aria-label={t('View :what full size: :title', { what, title: ad.title })}>
                {ad.thumb ? <img src={ad.thumb} alt="" width={200} height={200} loading="lazy" /> : <video src={`${ad.media}#t=0.1`} muted playsInline preload="metadata" tabIndex={-1} />}
                <span className="adc-thumb__badge">
                    <i className={`las la-${ad.kind === 'video' ? 'play' : 'search-plus'}`} />
                </span>
            </button>

            <header className="adc-head">
                <div className="adc-head__text">
                    <h5 className="adc-title">{ad.title}</h5>
                    <p className="adc-dest">
                        <span className="adc-type">
                            <i className={`las la-${ad.kind === 'video' ? 'video' : 'image'}`} /> {ad.kind === 'video' ? t('Video') : t('Picture')}
                        </span>{' '}
                        {t('opens :where', { where: ad.destination ?? t('the shop') })}
                    </p>
                </div>
                <div className="adc-mine" title={t('From your link on this ad')}>
                    <span className="adc-mine__pill adc-mine__pill--visits">
                        <i className="las la-mouse-pointer" /> <strong>{plural(ad.visits, 'visit', 'visits')}</strong>
                    </span>
                    <span className="adc-mine__pill adc-mine__pill--orders">
                        <i className="las la-shopping-bag" /> <strong>{plural(ad.orders, 'order', 'orders')}</strong>
                    </span>
                </div>
            </header>

            <div className="adc-body">
                <label className="adc-label" htmlFor={`caption${ad.id}`}>
                    <span>
                        <i className="las la-pen" /> {t('Caption')}
                    </span>{' '}
                    <small>{t('edit it to sound like you')}</small>
                </label>
                <textarea className="adc-caption" id={`caption${ad.id}`} rows={3} maxLength={1500} value={caption} onChange={(e) => setCaption(e.target.value)} />
                <div className="adc-link">
                    <i className="las la-link" />
                    <input type="text" value={ad.link} readOnly aria-label={t('Your link for this ad')} onFocus={(e) => e.target.select()} />
                    <button type="button" className="adc-link__copy" onClick={handlers.copy}>
                        {t('Copy link')}
                    </button>
                </div>
                <p className="adc-note">{t('Your link is added under the caption automatically.')}</p>
            </div>

            <div className="adc-share">
                <button type="button" className={`adc-primary${busy ? ' is-busy' : ''}`} disabled={busy} onClick={handlers.native}>
                    <span className="adc-primary__icon">
                        <i className={icons.native} />
                    </span>
                    <span className="adc-primary__text">
                        <strong>{shareable ? t('Share :what + link', { what }) : t('Save :what + copy caption', { what })}</strong>
                        <small>{shareable ? t('Sends the :what itself, with your caption and link', { what }) : t('Attach it to your post, then paste')}</small>
                    </span>
                </button>

                <p className="adc-group__label adc-group__label--link">
                    <span>
                        <i className="las la-link" /> {t('Send the link')}
                    </span>{' '}
                    <small>{t('preview shows the :what', { what: ad.kind === 'video' ? t('cover') : t('picture') })}</small>
                </p>
                <div className="adc-channels">
                    {LINK_CHANNELS.map(([key, label]) => (
                        <button key={key} type="button" className={`adc-ch adc-ch--${key}`} onClick={handlers[key as Exclude<Channel, 'download'>]}>
                            <span className="adc-ch__icon">
                                <i className={icons[key]} />
                            </span>
                            <span>{label}</span>
                        </button>
                    ))}
                </div>

                <p className="adc-group__label adc-group__label--upload">
                    <span>
                        <i className="las la-cloud-upload-alt" /> {t('Upload the :what', { what })}
                    </span>
                </p>
                <div className="adc-channels">
                    {FILE_CHANNELS.filter(([key]) => key !== 'youtube' || ad.kind === 'video').map(([key, label]) => (
                        <button key={key} type="button" className={`adc-ch adc-ch--${key}`} disabled={busy} onClick={handlers[key]}>
                            <span className="adc-ch__icon">
                                <i className={icons[key]} />
                            </span>
                            <span>{label}</span>
                        </button>
                    ))}
                    <a
                        href={ad.media}
                        download={ad.fileName}
                        className="adc-ch adc-ch--download"
                        onClick={() => {
                            say(t('Downloading…'));
                            log(ad, 'download');
                        }}
                    >
                        <span className="adc-ch__icon">
                            <i className={icons.download} />
                        </span>
                        <span>{t('Download')}</span>
                    </a>
                </div>
            </div>

            {guide && <UploadGuide guide={guide} what={what} onClose={() => setGuide(null)} />}
        </article>
    );
}

function UploadGuide({ guide, what, onClose }: { guide: Guide; what: string; onClose: () => void }) {
    const t = useTrans();
    const info = GUIDES[guide.channel];
    const steps = guide.viaShareSheet
        ? [t('Choose :name in the share list.', { name: info.name }), t('Your caption and link are copied: paste them in when you post.')]
        : [t('The :what is saving to your downloads.', { what }), t('Your caption and link are copied. Open :name, upload the :what, then paste.', { name: info.name, what })];

    return (
        <div className="adc-guide">
            <div className="adc-guide__head">
                <strong>{t('Posting on :name', { name: info.name })}</strong>
                <button type="button" aria-label={t('Close')} onClick={onClose}>
                    &times;
                </button>
            </div>
            <ol>
                {steps.map((step) => (
                    <li key={step}>{step}</li>
                ))}
            </ol>
            <p className="adc-guide__tip">{t(info.tip)}</p>
            {!guide.viaShareSheet && (
                <a className="adc-guide__go" href={info.url} target="_blank" rel="noopener noreferrer">
                    {t('Open :name', { name: info.name })}
                </a>
            )}
        </div>
    );
}

/** Full-size viewer: tap to zoom a picture, drag to pan, arrows to move between ads. */
function Viewer({ ads, index, onIndex, onClose, log }: { ads: Ad[]; index: number | null; onIndex: (i: number) => void; onClose: () => void; log: (ad: Ad, c: Channel) => void }) {
    const t = useTrans();
    const dialog = useRef<HTMLDialogElement>(null);
    const stage = useRef<HTMLDivElement>(null);
    const drag = useRef<{ x: number; y: number; left: number; top: number; moved: boolean } | null>(null);
    const [zoomed, setZoomed] = useState(false);
    const [imgWidth, setImgWidth] = useState<string | undefined>(undefined);
    const ad = index === null ? null : ads[index];
    const step = (by: number) => index !== null && onIndex((index + by + ads.length) % ads.length);

    useEffect(() => {
        const el = dialog.current;
        if (!el) return;
        if (index !== null && !el.open) {
            el.showModal();
            document.documentElement.classList.add('adc-noscroll');
        } else if (index === null && el.open) {
            el.close();
        }
    }, [index]);

    useEffect(() => {
        setZoomed(false);
        setImgWidth(undefined);
    }, [index]);

    useEffect(() => () => document.documentElement.classList.remove('adc-noscroll'), []);

    const toggleZoom = (img: HTMLImageElement, clientX: number, clientY: number) => {
        const box = img.getBoundingClientRect();
        const rx = (clientX - box.left) / box.width;
        const ry = (clientY - box.top) / box.height;
        if (!zoomed) {
            // At least twice the fitted size, and never less than the real size
            const width = Math.max(img.naturalWidth, box.width * 2);
            setImgWidth(`${width}px`);
            setZoomed(true);
            requestAnimationFrame(() => {
                const s = stage.current;
                if (!s) return;
                s.scrollLeft = rx * img.offsetWidth - s.clientWidth / 2;
                s.scrollTop = ry * img.offsetHeight - s.clientHeight / 2;
            });
        } else {
            setImgWidth(undefined);
            setZoomed(false);
        }
    };

    const onPointerDown = (e: ReactPointerEvent<HTMLDivElement>) => {
        if ((e.target as HTMLElement).tagName !== 'IMG' || !stage.current) return;
        drag.current = { x: e.clientX, y: e.clientY, left: stage.current.scrollLeft, top: stage.current.scrollTop, moved: false };
        if (zoomed) stage.current.setPointerCapture(e.pointerId);
    };
    const onPointerMove = (e: ReactPointerEvent<HTMLDivElement>) => {
        const d = drag.current;
        if (!d || !zoomed || !stage.current) return;
        const dx = e.clientX - d.x;
        const dy = e.clientY - d.y;
        if (Math.abs(dx) + Math.abs(dy) > 4) d.moved = true;
        stage.current.scrollLeft = d.left - dx;
        stage.current.scrollTop = d.top - dy;
    };
    const onPointerUp = (e: ReactPointerEvent<HTMLDivElement>) => {
        const img = stage.current?.querySelector('img');
        if (drag.current && !drag.current.moved && img) toggleZoom(img, e.clientX, e.clientY);
        drag.current = null;
    };

    return (
        <dialog
            ref={dialog}
            className={`adc-zoom${ads.length < 2 ? ' is-single' : ''}`}
            aria-labelledby="adcZoomTitle"
            onClose={() => {
                // Esc closes the dialog natively
                document.documentElement.classList.remove('adc-noscroll');
                onClose();
            }}
            onClick={(e) => {
                const outside = e.target === dialog.current || (e.target === stage.current && !zoomed);
                if (outside) dialog.current?.close();
            }}
            onKeyDown={(e) => {
                if (ads.length < 2) return;
                if (e.key === 'ArrowRight') step(1);
                if (e.key === 'ArrowLeft') step(-1);
            }}
        >
            {ad && (
                <>
                    <div className="adc-zoom__bar">
                        <strong className="adc-zoom__title" id="adcZoomTitle">
                            {ad.title}
                        </strong>
                        <span className="adc-zoom__count">{ads.length > 1 ? `${(index ?? 0) + 1} / ${ads.length}` : ''}</span>
                        <a className="adc-zoom__btn" href={ad.media} download={ad.fileName} onClick={() => log(ad, 'download')}>
                            <i className="las la-download" />
                            <span>{t('Download')}</span>
                        </a>
                        <button type="button" className="adc-zoom__btn adc-zoom__btn--close" aria-label={t('Close')} autoFocus onClick={() => dialog.current?.close()}>
                            <i className="las la-times" />
                        </button>
                    </div>
                    <div ref={stage} className={`adc-zoom__stage${zoomed ? ' is-zoomed' : ''}`} onPointerDown={onPointerDown} onPointerMove={onPointerMove} onPointerUp={onPointerUp}>
                        {ad.kind === 'video' ? (
                            // keyed so moving to another clip stops the playing one
                            <video key={ad.id} src={ad.media} controls autoPlay playsInline poster={ad.poster ?? undefined} />
                        ) : (
                            <img key={ad.id} src={ad.media} alt={ad.title} draggable={false} style={imgWidth ? { width: imgWidth } : undefined} />
                        )}
                    </div>
                    <button type="button" className="adc-zoom__nav adc-zoom__nav--prev" aria-label={t('Previous ad')} onClick={() => step(-1)}>
                        <i className="las la-angle-left" />
                    </button>
                    <button type="button" className="adc-zoom__nav adc-zoom__nav--next" aria-label={t('Next ad')} onClick={() => step(1)}>
                        <i className="las la-angle-right" />
                    </button>
                    <p className="adc-zoom__hint" hidden={ad.kind === 'video'}>
                        {zoomed ? t('Drag to look around. Tap again to fit') : t('Tap the picture to zoom in')}
                    </p>
                </>
            )}
        </dialog>
    );
}

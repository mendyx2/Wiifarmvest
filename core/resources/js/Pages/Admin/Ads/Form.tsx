import { Link, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState, type DragEvent, type FormEvent } from 'react';
import { FieldError } from '@/Components/Admin/Form';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';

type Props = {
    title: string;
    ad: {
        id: number | null;
        title: string;
        caption: string;
        product_id: string;
        sort_order: string;
        is_active: boolean;
        video: boolean;
        media: string | null;
        poster: string | null;
        preview: string | null;
    };
    products: { id: number; name: string }[];
    limits: { image: number; video: number; poster: number; server: number };
    extensions: { image: string[]; video: string[] };
    shopHost: string;
    urls: { save: string; index: string };
};

type Data = {
    title: string;
    caption: string;
    product_id: string;
    sort_order: string;
    is_active: boolean;
    media: File | null;
    poster: File | null;
    remove_poster: boolean;
};

const mb = (kb: number) => `${(kb / 1024).toFixed(kb % 1024 ? 1 : 0)} MB`;
const extOf = (file: File) => file.name.split('.').pop()?.toLowerCase() ?? '';

/** A frame about a second into a clip, as a JPEG (the first frame is often black). */
function captureFrame(src: string): Promise<Blob> {
    return new Promise((resolve, reject) => {
        const video = Object.assign(document.createElement('video'), { muted: true, playsInline: true, preload: 'auto', src });
        const fail = () => {
            clearTimeout(timer);
            reject(new Error('no frame'));
        };
        const timer = setTimeout(fail, 20000);
        video.addEventListener('error', fail, { once: true });
        video.addEventListener('loadedmetadata', () => (video.currentTime = Math.min(1, (video.duration || 4) / 4)), { once: true });
        video.addEventListener(
            'seeked',
            () => {
                clearTimeout(timer);
                const scale = Math.min(1, 1280 / (video.videoWidth || 1));
                const canvas = Object.assign(document.createElement('canvas'), { width: Math.round(video.videoWidth * scale), height: Math.round(video.videoHeight * scale) });
                if (!canvas.width || !canvas.height) return fail();
                canvas.getContext('2d')?.drawImage(video, 0, 0, canvas.width, canvas.height);
                canvas.toBlob((blob) => (blob ? resolve(blob) : fail()), 'image/jpeg', 0.86);
            },
            { once: true },
        );
    });
}

/** Affiliate Program → New / Edit Ad: the file, the post text, publishing, and a preview of the shared link. */
export default function AdForm({ title, ad, products, limits, extensions, shopHost, urls }: Props) {
    const t = useTrans();
    const form = useForm<Data>({ title: ad.title, caption: ad.caption, product_id: ad.product_id, sort_order: ad.sort_order, is_active: ad.is_active, media: null, poster: null, remove_poster: false });
    const { data, setData, errors } = form;
    const editing = ad.id !== null;
    const [isVideo, setIsVideo] = useState(ad.video);
    const [mediaName, setMediaName] = useState('');
    const [preview, setPreview] = useState<string | null>(ad.preview);
    const [posterThumb, setPosterThumb] = useState<string | null>(ad.poster);
    const [autoCover, setAutoCover] = useState(false);
    const [notice, setNotice] = useState('');
    const [over, setOver] = useState(false);
    const posterPicked = useRef(false);
    const mediaInput = useRef<HTMLInputElement>(null);
    const accept = [...extensions.image, ...extensions.video].map((e) => `.${e}`).join(',');
    const posterAccept = extensions.image.map((e) => `.${e}`).join(',');

    const tooBig = (file: File, kind: 'image' | 'video' | 'poster') => {
        if (file.size <= limits[kind] * 1024) return false;
        setNotice(t('That file is :size MB. The limit here is :limit.', { size: (file.size / 1048576).toFixed(1), limit: mb(limits[kind]) }));
        return true;
    };

    const makeCover = async (src: string) => {
        if (posterPicked.current) return;
        try {
            const blob = await captureFrame(src);
            if (posterPicked.current) return; // the admin chose a cover while we worked
            const file = new File([blob], 'cover.jpg', { type: 'image/jpeg' });
            setData('poster', file);
            const url = URL.createObjectURL(blob);
            setPosterThumb(url);
            setPreview(url);
            setAutoCover(true);
        } catch {
            // A codec this browser can't draw (some .mov files): the site logo is used instead
        }
    };

    // A saved clip without a cover gets one the next time it is opened here.
    useEffect(() => {
        if (ad.video && ad.media && !ad.poster) void makeCover(ad.media);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const pickMedia = (file: File | undefined) => {
        if (!file) return;
        const video = !extensions.image.includes(extOf(file));
        if (tooBig(file, video ? 'video' : 'image')) {
            if (mediaInput.current) mediaInput.current.value = '';
            return;
        }
        setNotice('');
        setMediaName(file.name);
        setIsVideo(video);
        const url = URL.createObjectURL(file);
        setData((d) => ({ ...d, media: file, poster: video ? d.poster : null }));
        if (video) void makeCover(url);
        else setPreview(url);
    };

    const pickPoster = (file: File | undefined) => {
        if (!file || tooBig(file, 'poster')) return;
        setNotice('');
        posterPicked.current = true;
        setAutoCover(false);
        setData('poster', file);
        const url = URL.createObjectURL(file);
        setPosterThumb(url);
        setPreview(url);
    };

    const drop = (e: DragEvent) => {
        e.preventDefault();
        setOver(false);
        pickMedia(e.dataTransfer.files[0]);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(urls.save, { forceFormData: true });
    };

    const caption = data.caption.trim();

    return (
        <>
            <PageHeader title={title}>
                <Link className="btn btn-sm btn-outline--dark" href={urls.index}>
                    <i className="las la-undo" /> {t('Back')}
                </Link>
            </PageHeader>
            <form onSubmit={submit} className="adm-ad-form">
                {notice && (
                    <div className="alert alert-danger" role="alert">
                        {notice}
                    </div>
                )}
                <div className="pf-grid">
                    <div className="pf-col-main">
                        <div className="pf-card">
                            <div className="pf-card-header">
                                <i className="las la-photo-video" /> {t('Picture or video')} {!editing && <span className="pf-req">*</span>}
                            </div>
                            <div className="pf-card-body">
                                <label
                                    className={`adm-drop ${data.media ? 'has-file' : ''} ${over ? 'is-over' : ''}`}
                                    htmlFor="ad-media"
                                    onDragEnter={(e) => (e.preventDefault(), setOver(true))}
                                    onDragOver={(e) => (e.preventDefault(), setOver(true))}
                                    onDragLeave={() => setOver(false)}
                                    onDrop={drop}
                                >
                                    <input ref={mediaInput} type="file" id="ad-media" accept={accept} required={!editing} onChange={(e) => pickMedia(e.target.files?.[0])} />
                                    <span className="adm-drop__icon">
                                        <i className="las la-cloud-upload-alt" />
                                    </span>
                                    <span className="adm-drop__text">
                                        <strong>{mediaName || (editing ? t('Replace the file') : t('Drop a file here, or browse'))}</strong>
                                        <small>{t('Pictures: JPG, PNG or WebP up to :i · Clips: MP4, WebM or MOV up to :v', { i: mb(limits.image), v: mb(limits.video) })}</small>
                                    </span>
                                </label>
                                <FieldError message={errors.media} />
                                {limits.server < 50 * 1024 && (
                                    <p className="adm-warn">
                                        <i className="las la-exclamation-triangle" />
                                        <span>
                                            {t('This server accepts uploads up to :size. For longer clips, raise upload_max_filesize and post_max_size to 64M in cPanel → MultiPHP INI Editor.', { size: mb(limits.server) })}
                                        </span>
                                    </p>
                                )}
                                <p className="pf-hint pf-hint--mt">{t('Square (1080×1080) or portrait (1080×1350) works on every network. For TikTok, Reels and Shorts, use a vertical clip (1080×1920) under 60 seconds.')}</p>
                                {isVideo && (
                                    <div className="adm-poster">
                                        <div className="adm-poster__thumb">{posterThumb ? <img src={posterThumb} alt="" /> : <i className="las la-image" />}</div>
                                        <div className="adm-poster__body">
                                            <label className="pf-label" htmlFor="ad-poster">
                                                {t('Cover picture')} <span className="pf-optional">{t('optional')}</span>
                                            </label>
                                            <p className="pf-hint">{t('Link previews on Facebook, WhatsApp and X show this picture. Leave it empty and a frame is taken from the clip for you.')}</p>
                                            {autoCover && (
                                                <p className="adm-auto">
                                                    <i className="las la-magic" /> {t('Cover taken from the clip. Choose a picture to use your own.')}
                                                </p>
                                            )}
                                            <div className="adm-poster__actions">
                                                <label className="adm-ad__btn" htmlFor="ad-poster">
                                                    <i className="las la-upload" /> {t('Choose picture')}
                                                </label>
                                                <input type="file" id="ad-poster" accept={posterAccept} hidden onChange={(e) => pickPoster(e.target.files?.[0])} />
                                                {ad.poster && (
                                                    <label className="adm-check">
                                                        <input type="checkbox" checked={data.remove_poster} onChange={(e) => setData('remove_poster', e.target.checked)} /> {t('Remove cover')}
                                                    </label>
                                                )}
                                            </div>
                                            <FieldError message={errors.poster} />
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="pf-card">
                            <div className="pf-card-header">
                                <i className="las la-pen-nib" /> {t('Post details')}
                            </div>
                            <div className="pf-card-body">
                                <div className="pf-field">
                                    <label className="pf-label" htmlFor="ad-title">
                                        {t('Title')} <span className="pf-req">*</span>
                                    </label>
                                    <input className="pf-input" id="ad-title" maxLength={120} required placeholder={t('e.g. Fresh parboiled rice, 50 kg bag')} value={data.title} onChange={(e) => setData('title', e.target.value)} />
                                    <p className="pf-hint">
                                        {t('Shown in bold on the link preview.')} <span className="adm-count">{data.title.length}/120</span>
                                    </p>
                                    <FieldError message={errors.title} />
                                </div>
                                <div className="pf-field mt-3">
                                    <label className="pf-label" htmlFor="ad-caption">
                                        {t('Caption')}
                                    </label>
                                    <textarea className="pf-input" id="ad-caption" rows={5} maxLength={1000} placeholder={t('Write it the way a member would post it. Their link is added at the end automatically.')} value={data.caption} onChange={(e) => setData('caption', e.target.value)} />
                                    <p className="pf-hint">
                                        {t('Members can edit it before posting.')} <span className="adm-count">{data.caption.length}/1000</span>
                                    </p>
                                    <FieldError message={errors.caption} />
                                </div>
                                <div className="pf-field mt-3">
                                    <label className="pf-label" htmlFor="ad-product">
                                        {t('Visitors land on')}
                                    </label>
                                    <select className="pf-input" id="ad-product" value={data.product_id} onChange={(e) => setData('product_id', e.target.value)}>
                                        <option value="">{t('Shop home page')}</option>
                                        {products.map((p) => (
                                            <option key={p.id} value={p.id}>
                                                {p.name}
                                            </option>
                                        ))}
                                    </select>
                                    <p className="pf-hint">{t('Pick a product when the ad is about one item, so buyers skip the search.')}</p>
                                    <FieldError message={errors.product_id} />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="pf-col-side">
                        <div className="pf-card">
                            <div className="pf-card-header">
                                <i className="las la-paper-plane" /> {t('Publish')}
                            </div>
                            <div className="pf-card-body">
                                <label className="adm-switch">
                                    <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} />
                                    <span className="adm-switch__track" />
                                    <span className="adm-switch__label">
                                        <strong>{t('Live')}</strong>
                                        <small>{t('Members can see and share it')}</small>
                                    </span>
                                </label>
                                <div className="pf-field mt-3">
                                    <label className="pf-label" htmlFor="ad-sort">
                                        {t('Sort order')}
                                    </label>
                                    <input className="pf-input" id="ad-sort" type="number" min={0} max={65535} value={data.sort_order} onChange={(e) => setData('sort_order', e.target.value)} />
                                    <p className="pf-hint">{t('Lower numbers show first. Ties show newest first.')}</p>
                                    <FieldError message={errors.sort_order} />
                                </div>
                                <button type="submit" className="adm-submit mt-3" disabled={form.processing}>
                                    <i className="las la-check" /> {editing ? t('Save changes') : t('Publish ad')}
                                </button>
                                {form.processing && (data.media || data.poster) && (
                                    <p className="adm-uploading" role="status">
                                        <i className="las la-spinner la-spin" /> {form.progress ? `${form.progress.percentage}% · ` : ''}
                                        {t('Uploading, keep this tab open…')}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="pf-card">
                            <div className="pf-card-header">
                                <i className="las la-comment" /> {t('Link preview')}
                            </div>
                            <div className="adm-post">
                                <div className="adm-post__bubble">
                                    <div className="adm-post__card">
                                        <div className="adm-post__media">
                                            {preview ? (
                                                <img src={preview} alt="" />
                                            ) : (
                                                <span className="adm-post__placeholder">
                                                    <i className="las la-image" />
                                                    <small>{editing ? t('The site logo shows until the clip has a cover') : t('Your picture shows here')}</small>
                                                </span>
                                            )}
                                        </div>
                                        <div className="adm-post__link">
                                            <strong>{data.title.trim() || t('Your ad title')}</strong>
                                            <span>{caption ? caption.split('\n')[0] : t('The first line of the caption.')}</span>
                                            <small>{shopHost}</small>
                                        </div>
                                    </div>
                                    <p className="adm-post__caption">
                                        <span>{caption || t("The member's caption appears here.")}</span>
                                        <span className="adm-post__url">https://{shopHost}/shop/ad/…?ref=MEMBER</span>
                                    </p>
                                </div>
                                <p className="adm-post__note">{t('On save the picture is fitted to 1200×630 on a matching background, the size Facebook, WhatsApp, X and Telegram show large.')}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </>
    );
}

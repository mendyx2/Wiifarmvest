@extends($activeTemplate . 'layouts.master')

@section('content')
@php
    $channelIcons = \App\Models\AdShare::CHANNEL_ICONS;
    $linkChannels = ['facebook' => 'Facebook', 'whatsapp' => 'WhatsApp', 'x' => 'X', 'telegram' => 'Telegram'];
    $fileChannels = ['instagram' => 'Instagram', 'tiktok' => 'TikTok', 'youtube' => 'YouTube'];
    $mimes        = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp',
                     'mp4' => 'video/mp4', 'webm' => 'video/webm', 'mov' => 'video/quicktime'];
@endphp
<div class="nc-wrap adc" id="adCenter" data-csrf="{{ csrf_token() }}">
    <br>
    <section class="adc-hero">
        <div class="adc-hero__text">
            <span class="adc-hero__tag"><i class="las la-bullhorn"></i> Ad Center</span>
            <h4 class="adc-hero__title">Share ready-made ads, earn on every order</h4>
            <p class="adc-hero__sub">Each post carries your own link. Anyone who taps it and buys is counted as your sale.</p>
        </div>
        <a href="{{ route('user.affiliate.dashboard') }}" class="adc-hero__btn">
            <i class="las la-tachometer-alt"></i> Affiliate Dashboard
        </a>
        <i class="las la-bullhorn adc-hero__art" aria-hidden="true"></i>
    </section>

    <div class="adc-stats">
        <div class="adc-stat adc-stat--green">
            <span class="adc-stat__icon"><i class="las la-photo-video"></i></span>
            <div><strong>{{ number_format($ads->total()) }}</strong><span>Ads to share</span></div>
        </div>
        <div class="adc-stat adc-stat--blue">
            <span class="adc-stat__icon"><i class="las la-share-alt"></i></span>
            <div><strong>{{ number_format($shared) }}</strong><span>Ads you've shared</span></div>
        </div>
        <div class="adc-stat adc-stat--amber">
            <span class="adc-stat__icon"><i class="las la-mouse-pointer"></i></span>
            <div><strong>{{ number_format($visits->sum()) }}</strong><span>Visits from your ads</span></div>
        </div>
        <div class="adc-stat adc-stat--rose">
            <span class="adc-stat__icon"><i class="las la-shopping-bag"></i></span>
            <div><strong>{{ number_format($orders->sum()) }}</strong><span>Paid orders from ads</span></div>
        </div>
    </div>

    @if ($ads->isEmpty())
        <div class="adc-empty">
            <i class="las la-bullhorn"></i>
            <h5>No ads right now</h5>
            <p>New posts appear here as soon as the team publishes them. Meanwhile, you can share your shop link from the
                <a href="{{ route('user.affiliate.dashboard') }}">Affiliate Dashboard</a>.</p>
        </div>
    @else
        <div class="adc-list">
            @foreach ($ads as $ad)
                @php
                    $link      = $ad->shareUrlFor($user);
                    $extension = strtolower(pathinfo($ad->media, PATHINFO_EXTENSION));
                    $fileName  = (\Illuminate\Support\Str::slug($ad->title) ?: 'ad-' . $ad->id) . '.' . $extension;
                    $what      = $ad->isVideo() ? 'video' : 'picture';
                    $thumb     = $ad->thumbUrl();
                    $adVisits  = (int) ($visits[$ad->id] ?? 0);
                    $adOrders  = (int) ($orders[$ad->id] ?? 0);
                @endphp
                <article @class(['adc-card', 'adc-card--video' => $ad->isVideo()]) data-ad
                    data-title="{{ $ad->title }}"
                    data-link="{{ $link }}"
                    data-media="{{ $ad->mediaUrl() }}"
                    data-mime="{{ $mimes[$extension] ?? 'application/octet-stream' }}"
                    data-poster="{{ $ad->posterUrl() }}"
                    data-file-name="{{ $fileName }}"
                    data-kind="{{ $ad->media_type }}"
                    data-log="{{ route('user.affiliate.ads.share', $ad->id) }}">

                    <button type="button" @class(['adc-thumb', 'adc-thumb--video' => $ad->isVideo()]) data-zoom aria-label="View {{ $what }} full size: {{ $ad->title }}">
                        @if ($thumb)
                            <img src="{{ $thumb }}" alt="" width="200" height="200" loading="lazy">
                        @else
                            <video src="{{ $ad->mediaUrl() }}#t=0.1" muted playsinline preload="metadata" tabindex="-1"></video>
                        @endif
                        <span class="adc-thumb__badge">
                            <i class="las la-{{ $ad->isVideo() ? 'play' : 'search-plus' }}"></i>
                        </span>
                    </button>

                    <header class="adc-head">
                        <div class="adc-head__text">
                            <h5 class="adc-title">{{ $ad->title }}</h5>
                            <p class="adc-dest">
                                <span class="adc-type"><i class="las la-{{ $ad->isVideo() ? 'video' : 'image' }}"></i> {{ ucfirst($what) }}</span>
                                opens {{ $ad->product ? $ad->product->name : 'the shop' }}
                            </p>
                        </div>
                        <div class="adc-mine" title="From your link on this ad">
                            <span class="adc-mine__pill adc-mine__pill--visits">
                                <i class="las la-mouse-pointer"></i> <strong>{{ number_format($adVisits) }}</strong> {{ \Illuminate\Support\Str::plural('visit', $adVisits) }}
                            </span>
                            <span class="adc-mine__pill adc-mine__pill--orders">
                                <i class="las la-shopping-bag"></i> <strong>{{ number_format($adOrders) }}</strong> {{ \Illuminate\Support\Str::plural('order', $adOrders) }}
                            </span>
                        </div>
                    </header>

                    <div class="adc-body">
                        <label class="adc-label" for="caption{{ $ad->id }}"><span><i class="las la-pen"></i> Caption</span> <small>edit it to sound like you</small></label>
                        <textarea class="adc-caption" id="caption{{ $ad->id }}" rows="3" maxlength="1500">{{ $ad->caption ?: $ad->title }}</textarea>

                        <div class="adc-link">
                            <i class="las la-link"></i>
                            <input type="text" value="{{ $link }}" readonly aria-label="Your link for this ad" onfocus="this.select()">
                            <button type="button" class="adc-link__copy" data-share="copy">Copy link</button>
                        </div>
                        <p class="adc-note">Your link is added under the caption automatically.</p>
                    </div>

                    <div class="adc-share">
                        <button type="button" class="adc-primary" data-share="native">
                            <span class="adc-primary__icon"><i class="{{ $channelIcons['native'] }}"></i></span>
                            <span class="adc-primary__text">
                                <strong data-label-share="Share {{ $what }} + link" data-label-save="Save {{ $what }} + copy caption">Share {{ $what }} + link</strong>
                                <small data-sub-share="Sends the {{ $what }} itself, with your caption and link"
                                       data-sub-save="Attach it to your post, then paste">Sends the {{ $what }} itself, with your caption and link</small>
                            </span>
                        </button>

                        <p class="adc-group__label adc-group__label--link"><span><i class="las la-link"></i> Send the link</span> <small>preview shows the {{ $ad->isVideo() ? 'cover' : 'picture' }}</small></p>
                        <div class="adc-channels">
                            @foreach ($linkChannels as $key => $label)
                                <button type="button" class="adc-ch adc-ch--{{ $key }}" data-share="{{ $key }}">
                                    <span class="adc-ch__icon"><i class="{{ $channelIcons[$key] }}"></i></span><span>{{ $label }}</span>
                                </button>
                            @endforeach
                        </div>

                        <p class="adc-group__label adc-group__label--upload"><span><i class="las la-cloud-upload-alt"></i> Upload the {{ $what }}</span></p>
                        <div class="adc-channels">
                            @foreach ($fileChannels as $key => $label)
                                @continue($key === 'youtube' && !$ad->isVideo())
                                <button type="button" class="adc-ch adc-ch--{{ $key }}" data-share="{{ $key }}">
                                    <span class="adc-ch__icon"><i class="{{ $channelIcons[$key] }}"></i></span><span>{{ $label }}</span>
                                </button>
                            @endforeach
                            <a href="{{ $ad->mediaUrl() }}" download="{{ $fileName }}" class="adc-ch adc-ch--download" data-share="download">
                                <span class="adc-ch__icon"><i class="{{ $channelIcons['download'] }}"></i></span><span>Download</span>
                            </a>
                        </div>
                    </div>

                    <div class="adc-guide" hidden></div>
                </article>
            @endforeach
        </div>

        @if ($ads->hasPages())
            <div class="mt-4">{{ $ads->links() }}</div>
        @endif
    @endif

    <p class="adc-foot"><i class="las la-info-circle"></i><span>
        <strong>Share picture + link</strong> sends the actual picture or clip with your caption, so it shows in full
        on WhatsApp, Status, Facebook and Telegram. A link on its own shows a preview card with the picture.
        Instagram, TikTok and YouTube only take uploads: we save the file and copy your caption for you.</span>
    </p>
</div>

<dialog class="adc-zoom" id="adcZoom" aria-labelledby="adcZoomTitle">
    <div class="adc-zoom__bar">
        <strong class="adc-zoom__title" id="adcZoomTitle"></strong>
        <span class="adc-zoom__count" id="adcZoomCount"></span>
        <a class="adc-zoom__btn" id="adcZoomDownload" href="#" download>
            <i class="las la-download"></i><span>Download</span>
        </a>
        <button type="button" class="adc-zoom__btn adc-zoom__btn--close" data-zoom-close aria-label="Close" autofocus>
            <i class="las la-times"></i>
        </button>
    </div>
    <div class="adc-zoom__stage" id="adcZoomStage"></div>
    <button type="button" class="adc-zoom__nav adc-zoom__nav--prev" data-zoom-step="-1" aria-label="Previous ad"><i class="las la-angle-left"></i></button>
    <button type="button" class="adc-zoom__nav adc-zoom__nav--next" data-zoom-step="1" aria-label="Next ad"><i class="las la-angle-right"></i></button>
    <p class="adc-zoom__hint" id="adcZoomHint">Tap the picture to zoom in</p>
</dialog>

<div class="adc-toast" id="adcToast" role="status" aria-live="polite"></div>

<script>
(function () {
    'use strict';

    const root  = document.getElementById('adCenter');
    const toast = document.getElementById('adcToast');
    if (!root) return;

    const csrf     = root.dataset.csrf;
    const cards    = [...root.querySelectorAll('[data-ad]')];
    const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent)
        || (navigator.maxTouchPoints > 1 && /Macintosh/.test(navigator.userAgent));
    const files    = new Map();   // media URL -> File, so a tap can share at once

    const GUIDES = {
        instagram: {
            name: 'Instagram', url: 'https://www.instagram.com/',
            tip: 'Links in Instagram captions can’t be tapped. Put your link in your bio, or add it with a Link sticker on a Story.',
        },
        tiktok: {
            name: 'TikTok', url: 'https://www.tiktok.com/upload',
            tip: 'TikTok captions can’t hold a tappable link either. Add your link to your bio and say “link in bio”.',
        },
        youtube: {
            name: 'YouTube', url: 'https://www.youtube.com/upload',
            tip: 'Upload it as a Short if it’s vertical and under a minute. Links in the description can be clicked.',
        },
    };

    /* ── Small helpers ─────────────────────────────────────────── */

    let toastTimer;
    function say(message) {
        toast.textContent = message;
        toast.classList.add('is-on');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.remove('is-on'), 3600);
    }

    function log(card, channel) {
        fetch(card.dataset.log, {
            method: 'POST',
            keepalive: true,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ channel }),
        }).catch(() => {});
    }

    const captionOf = (card) => card.querySelector('.adc-caption').value.trim();
    const textOf    = (card) => (captionOf(card) ? captionOf(card) + '\n\n' : '') + card.dataset.link;
    const what      = (card) => (card.dataset.kind === 'video' ? 'video' : 'picture');

    async function copy(text) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (e) {
            // Older in-app browsers: fall back to a hidden textarea
            const area = Object.assign(document.createElement('textarea'), { value: text });
            area.style.cssText = 'position:fixed;opacity:0';
            document.body.append(area);
            area.select();
            const ok = document.execCommand('copy');
            area.remove();
            return ok;
        }
    }

    function download(card) {
        const a = Object.assign(document.createElement('a'), { href: card.dataset.media, download: card.dataset.fileName });
        document.body.append(a);
        a.click();
        a.remove();
    }

    function openWindow(url) {
        window.open(url, '_blank', 'noopener,width=620,height=640');
    }

    /* ── Sharing the file itself ───────────────────────────────── */

    function canShareFile(card) {
        if (!navigator.canShare) return false;
        try {
            return navigator.canShare({ files: [new File([''], card.dataset.fileName, { type: card.dataset.mime })] });
        } catch (e) {
            return false;
        }
    }

    async function fileFor(card) {
        const url = card.dataset.media;
        if (files.has(url)) return files.get(url);

        const response = await fetch(url);
        if (!response.ok) throw new Error('download failed');
        const file = new File([await response.blob()], card.dataset.fileName, { type: card.dataset.mime });
        files.set(url, file);
        return file;
    }

    function busy(button, on) {
        button.disabled = on;
        button.classList.toggle('is-busy', on);
    }

    /**
     * Opens the share sheet with the file. A file already downloaded is shared
     * inside the tap itself: iPhones refuse a share that starts after a download.
     * Resolves to 'shared', 'cancelled', 'retry' or 'failed'.
     */
    async function shareFile(card, button, withText) {
        let file = files.get(card.dataset.media);

        if (!file) {
            busy(button, true);
            try {
                file = await fileFor(card);
            } catch (e) {
                busy(button, false);
                say('Couldn’t load the ' + what(card) + '. Check your connection and try again.');
                return 'failed';
            }
            busy(button, false);
        }

        const data = { files: [file] };
        if (withText) {
            data.text  = textOf(card);
            data.title = card.dataset.title;
        }

        try {
            await navigator.share(data);
            return 'shared';
        } catch (e) {
            if (e.name === 'AbortError') return 'cancelled';
            if (e.name === 'NotAllowedError') {
                say('Ready. Tap again to share.');
                return 'retry';
            }
            return 'failed';
        }
    }

    // Pictures are small: fetch the ones on screen early so the first tap shares at once
    if ('IntersectionObserver' in window) {
        const early = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                early.unobserve(entry.target);
                fileFor(entry.target).catch(() => {});
            });
        }, { rootMargin: '200px' });

        cards.filter((card) => card.dataset.kind === 'image' && canShareFile(card)).forEach((card) => early.observe(card));
    }

    /* ── Guide for upload-only networks ────────────────────────── */

    function showGuide(card, channel, viaShareSheet) {
        const guide = GUIDES[channel];
        const box   = card.querySelector('.adc-guide');
        const noun  = what(card);

        const steps = viaShareSheet
            ? [`Choose ${guide.name} in the share list.`, 'Your caption and link are copied: paste them in when you post.']
            : [`The ${noun} is saving to your downloads.`, `Your caption and link are copied. Open ${guide.name}, upload the ${noun}, then paste.`];

        box.replaceChildren();
        const head = document.createElement('div');
        head.className = 'adc-guide__head';
        head.innerHTML = '<strong></strong><button type="button" aria-label="Close">&times;</button>';
        head.querySelector('strong').textContent = `Posting on ${guide.name}`;
        head.querySelector('button').addEventListener('click', () => { box.hidden = true; });

        const list = document.createElement('ol');
        steps.forEach((step) => list.append(Object.assign(document.createElement('li'), { textContent: step })));

        const tip = Object.assign(document.createElement('p'), { className: 'adc-guide__tip', textContent: guide.tip });
        box.append(head, list, tip);

        if (!viaShareSheet) {
            box.append(Object.assign(document.createElement('a'), {
                className: 'adc-guide__go', href: guide.url, target: '_blank', rel: 'noopener', textContent: `Open ${guide.name}`,
            }));
        }
        box.hidden = false;
    }

    /* ── Buttons ───────────────────────────────────────────────── */

    const handlers = {
        // Picture + caption + link in one go, through the phone's or computer's share sheet
        async native(card, button) {
            copy(textOf(card));   // some apps keep the picture but drop the text

            if (!canShareFile(card)) {
                download(card);
                say(`The ${what(card)} is saving and your caption is copied. Attach it to your post, then paste.`);
                log(card, 'download');
                return;
            }

            const result = await shareFile(card, button, true);
            if (result === 'shared') {
                log(card, 'native');
                say('Shared. Your caption and link are on the clipboard too, in case the app left them out.');
            } else if (result === 'failed') {
                download(card);
                say(`Sharing isn’t available here, so the ${what(card)} is saving and your caption is copied.`);
                log(card, 'download');
            }
        },

        facebook(card) {
            // Facebook ignores pre-filled text: the caption goes on the clipboard instead
            copy(textOf(card));
            openWindow('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(card.dataset.link));
            say('Caption copied. Paste it into your Facebook post.');
            log(card, 'facebook');
        },

        whatsapp(card) {
            window.open('https://wa.me/?text=' + encodeURIComponent(textOf(card)), '_blank', 'noopener');
            log(card, 'whatsapp');
        },

        x(card) {
            const caption = captionOf(card);
            const text    = caption.length > 230 ? caption.slice(0, 229).trimEnd() + '…' : caption;
            openWindow('https://x.com/intent/tweet?text=' + encodeURIComponent(text) + '&url=' + encodeURIComponent(card.dataset.link));
            log(card, 'x');
        },

        telegram(card) {
            window.open('https://t.me/share/url?url=' + encodeURIComponent(card.dataset.link) + '&text=' + encodeURIComponent(captionOf(card)), '_blank', 'noopener');
            log(card, 'telegram');
        },

        async copy(card) {
            if (await copy(card.dataset.link)) say('Link copied.');
            log(card, 'copy');
        },

        download(card) {
            say('Downloading…');
            log(card, 'download');
        },
    };

    // Instagram, TikTok and YouTube: phones get the share sheet with the file,
    // computers get the file downloaded and the upload page one click away
    ['instagram', 'tiktok', 'youtube'].forEach((channel) => {
        handlers[channel] = async (card, button) => {
            copy(textOf(card));

            if (isMobile && canShareFile(card)) {
                const result = await shareFile(card, button, false);
                if (result === 'shared') {
                    showGuide(card, channel, true);
                    log(card, channel);
                }
                if (result !== 'failed') return;
            }

            download(card);
            showGuide(card, channel, false);
            log(card, channel);
        };
    });

    // Where files can't be shared, the main button saves the file and copies the caption instead
    cards.forEach((card) => {
        const button = card.querySelector('.adc-primary');
        const mode   = canShareFile(card) ? 'Share' : 'Save';
        const label  = button.querySelector('strong');
        const sub    = button.querySelector('small');
        label.textContent = label.dataset['label' + mode];
        sub.textContent   = sub.dataset['sub' + mode];
    });

    root.addEventListener('click', (event) => {
        const button = event.target.closest('[data-share]');
        if (!button || button.disabled) return;

        const card    = button.closest('[data-ad]');
        const channel = button.dataset.share;
        if (!card || !handlers[channel]) return;

        if (channel !== 'download') event.preventDefault();
        handlers[channel](card, button);
    });

    /* ── Full-size viewer ──────────────────────────────────────── */

    const zoom  = document.getElementById('adcZoom');
    const stage = document.getElementById('adcZoomStage');
    const hint  = document.getElementById('adcZoomHint');
    let current = -1;
    let drag    = null;

    function show(index) {
        current = (index + cards.length) % cards.length;
        const card = cards[current];

        stage.classList.remove('is-zoomed');
        stage.replaceChildren();
        document.getElementById('adcZoomTitle').textContent = card.dataset.title;
        document.getElementById('adcZoomCount').textContent = cards.length > 1 ? `${current + 1} / ${cards.length}` : '';

        const save    = document.getElementById('adcZoomDownload');
        save.href     = card.dataset.media;
        save.download = card.dataset.fileName;
        save.onclick  = () => log(card, 'download');

        if (card.dataset.kind === 'video') {
            const video = Object.assign(document.createElement('video'), {
                src: card.dataset.media, controls: true, autoplay: true, playsInline: true,
            });
            if (card.dataset.poster) video.poster = card.dataset.poster;
            stage.append(video);
            hint.hidden = true;
        } else {
            stage.append(Object.assign(document.createElement('img'), { src: card.dataset.media, alt: card.dataset.title, draggable: false }));
            hint.hidden      = false;
            hint.textContent = 'Tap the picture to zoom in';
        }
    }

    function open(card) {
        show(cards.indexOf(card));
        document.documentElement.classList.add('adc-noscroll');
        zoom.showModal();
    }

    function toggleZoom(img, clientX, clientY) {
        const box = img.getBoundingClientRect();
        const rx  = (clientX - box.left) / box.width;
        const ry  = (clientY - box.top) / box.height;

        if (stage.classList.toggle('is-zoomed')) {
            // At least twice the fitted size, and never less than the real size
            img.style.width  = Math.max(img.naturalWidth, box.width * 2) + 'px';
            stage.scrollLeft = rx * img.offsetWidth - stage.clientWidth / 2;
            stage.scrollTop  = ry * img.offsetHeight - stage.clientHeight / 2;
            hint.textContent = 'Drag to look around. Tap again to fit';
        } else {
            img.style.width  = '';
            hint.textContent = 'Tap the picture to zoom in';
        }
    }

    root.addEventListener('click', (event) => {
        const thumb = event.target.closest('[data-zoom]');
        if (thumb) open(thumb.closest('[data-ad]'));
    });

    function cleanUp() {
        stage.replaceChildren();   // stops a playing clip
        document.documentElement.classList.remove('adc-noscroll');
    }

    function closeViewer() {
        if (zoom.open) zoom.close();
        cleanUp();
    }

    zoom.addEventListener('close', cleanUp);   // Esc closes the dialog natively

    zoom.addEventListener('click', (event) => {
        const outside = event.target === zoom || (event.target === stage && !stage.classList.contains('is-zoomed'));
        if (outside || event.target.closest('[data-zoom-close]')) {
            closeViewer();
            return;
        }
        const step = event.target.closest('[data-zoom-step]');
        if (step) show(current + Number(step.dataset.zoomStep));
    });

    // Drag to pan a zoomed picture; a press that doesn't move is a tap that toggles zoom
    stage.addEventListener('pointerdown', (event) => {
        if (event.target.tagName !== 'IMG') return;
        drag = { x: event.clientX, y: event.clientY, left: stage.scrollLeft, top: stage.scrollTop, moved: false };
        if (stage.classList.contains('is-zoomed')) stage.setPointerCapture(event.pointerId);
    });
    stage.addEventListener('pointermove', (event) => {
        if (!drag || !stage.classList.contains('is-zoomed')) return;
        const dx = event.clientX - drag.x;
        const dy = event.clientY - drag.y;
        if (Math.abs(dx) + Math.abs(dy) > 4) drag.moved = true;
        stage.scrollLeft = drag.left - dx;
        stage.scrollTop  = drag.top - dy;
    });
    stage.addEventListener('pointerup', (event) => {
        const img = stage.querySelector('img');
        if (drag && !drag.moved && img) toggleZoom(img, event.clientX, event.clientY);
        drag = null;
    });

    zoom.addEventListener('keydown', (event) => {
        if (cards.length < 2) return;
        if (event.key === 'ArrowRight') show(current + 1);
        if (event.key === 'ArrowLeft') show(current - 1);
    });
    zoom.classList.toggle('is-single', cards.length < 2);
})();
</script>
@endsection

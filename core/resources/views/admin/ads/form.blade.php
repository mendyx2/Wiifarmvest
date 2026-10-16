@extends('admin.layouts.app')

@section('panel')
@php
    $isEdit   = $ad->exists;
    $mediaAccept  = collect(\App\Models\Ad::IMAGE_EXTENSIONS)->merge(\App\Models\Ad::VIDEO_EXTENSIONS)->map(fn ($e) => '.' . $e)->implode(',');
    $posterAccept = collect(\App\Models\Ad::IMAGE_EXTENSIONS)->map(fn ($e) => '.' . $e)->implode(',');
    $mb = fn ($kb) => rtrim(rtrim(number_format($kb / 1024, 1), '0'), '.') . ' MB';
    $shopHost = parse_url(route('shop.index'), PHP_URL_HOST);
@endphp
<form action="{{ $isEdit ? route('admin.ads.update', $ad->id) : route('admin.ads.store') }}" method="POST" enctype="multipart/form-data"
    class="adm-ad-form" id="adForm"
    data-limit-image="{{ $limits['image'] }}" data-limit-video="{{ $limits['video'] }}" data-limit-poster="{{ $limits['poster'] }}">
    @csrf
    <div class="pf-grid">

        <div class="pf-col-main">

            {{-- Media --}}
            <div class="pf-card">
                <div class="pf-card-header">
                    <i class="las la-photo-video"></i> Picture or video @unless ($isEdit)<span class="pf-req">*</span>@endunless
                </div>
                <div class="pf-card-body">
                    <label class="adm-drop" id="mediaDrop" for="mediaInput">
                        <input type="file" name="media" id="mediaInput" accept="{{ $mediaAccept }}" @required(!$isEdit)>
                        <span class="adm-drop__icon"><i class="las la-cloud-upload-alt"></i></span>
                        <span class="adm-drop__text">
                            <strong id="mediaName">{{ $isEdit ? 'Replace the file' : 'Drop a file here, or browse' }}</strong>
                            <small>
                                Pictures: JPG, PNG or WebP up to {{ $mb($limits['image']) }}
                                &middot; Clips: MP4, WebM or MOV up to {{ $mb($limits['video']) }}
                            </small>
                        </span>
                    </label>
                    @if ($limits['server'] < 50 * 1024)
                        <p class="adm-warn">
                            <i class="las la-exclamation-triangle"></i>
                            <span>This server accepts uploads up to {{ $mb($limits['server']) }}. For longer clips, raise
                            <code>upload_max_filesize</code> and <code>post_max_size</code> to 64M in cPanel &rarr; MultiPHP INI Editor.</span>
                        </p>
                    @endif
                    <p class="pf-hint pf-hint--mt">
                        Square (1080&times;1080) or portrait (1080&times;1350) works on every network.
                        For TikTok, Reels and Shorts, use a vertical clip (1080&times;1920) under 60 seconds.
                    </p>

                    <div class="adm-poster" id="posterField" @if (!$ad->isVideo()) hidden @endif>
                        <div class="adm-poster__thumb" id="posterThumb">
                            @if ($ad->poster)
                                <img src="{{ $ad->posterUrl() }}" alt="">
                            @else
                                <i class="las la-image"></i>
                            @endif
                        </div>
                        <div class="adm-poster__body">
                            <label class="pf-label" for="posterInput">Cover picture <span class="pf-optional">optional</span></label>
                            <p class="pf-hint">
                                Link previews on Facebook, WhatsApp and X show this picture. Leave it empty and a
                                frame is taken from the clip for you.
                            </p>
                            <p class="adm-auto" id="posterAuto" hidden>
                                <i class="las la-magic"></i> Cover taken from the clip. Choose a picture to use your own.
                            </p>
                            <div class="adm-poster__actions">
                                <label class="adm-ad__btn" for="posterInput"><i class="las la-upload"></i> Choose picture</label>
                                <input type="file" name="poster" id="posterInput" accept="{{ $posterAccept }}" hidden>
                                @if ($ad->poster)
                                    <label class="adm-check">
                                        <input type="checkbox" name="remove_poster" value="1"> Remove cover
                                    </label>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Details --}}
            <div class="pf-card">
                <div class="pf-card-header">
                    <i class="las la-pen-nib"></i> Post details
                </div>
                <div class="pf-card-body">
                    <div class="pf-field">
                        <label class="pf-label" for="titleInput">Title <span class="pf-req">*</span></label>
                        <input class="pf-input" id="titleInput" name="title" type="text" maxlength="120" required
                            value="{{ old('title', $ad->title) }}" placeholder="e.g. Fresh parboiled rice, 50 kg bag">
                        <p class="pf-hint">Shown in bold on the link preview. <span class="adm-count" data-for="titleInput"></span></p>
                    </div>

                    <div class="pf-field mt-3">
                        <label class="pf-label" for="captionInput">Caption</label>
                        <textarea class="pf-input" id="captionInput" name="caption" rows="5" maxlength="1000"
                            placeholder="Write it the way a member would post it. Their link is added at the end automatically.">{{ old('caption', $ad->caption) }}</textarea>
                        <p class="pf-hint">Members can edit it before posting. <span class="adm-count" data-for="captionInput"></span></p>
                    </div>

                    <div class="pf-field mt-3">
                        <label class="pf-label" for="productInput">Visitors land on</label>
                        <select class="pf-input select2" id="productInput" name="product_id" data-minimum-results-for-search="8">
                            <option value="">Shop home page</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" @selected(old('product_id', $ad->product_id) == $product->id)>{{ $product->name }}</option>
                            @endforeach
                        </select>
                        <p class="pf-hint">Pick a product when the ad is about one item, so buyers skip the search.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="pf-col-side">

            {{-- Publish --}}
            <div class="pf-card">
                <div class="pf-card-header">
                    <i class="las la-paper-plane"></i> Publish
                </div>
                <div class="pf-card-body">
                    <label class="adm-switch">
                        {{-- After a failed submit, an unticked box is absent from old(): don't fall back to the saved value --}}
                        <input type="checkbox" name="is_active" value="1" @checked(old('_token') ? old('is_active') : $ad->is_active)>
                        <span class="adm-switch__track"></span>
                        <span class="adm-switch__label">
                            <strong>Live</strong>
                            <small>Members can see and share it</small>
                        </span>
                    </label>

                    <div class="pf-field mt-3">
                        <label class="pf-label" for="sortInput">Sort order</label>
                        <input class="pf-input" id="sortInput" name="sort_order" type="number" min="0" max="65535"
                            value="{{ old('sort_order', $ad->sort_order ?? 0) }}">
                        <p class="pf-hint">Lower numbers show first. Ties show newest first.</p>
                    </div>

                    <button type="submit" class="adm-submit mt-3" id="adSubmit">
                        <i class="las la-check"></i> {{ $isEdit ? 'Save changes' : 'Publish ad' }}
                    </button>
                    <p class="adm-uploading" id="adUploading" hidden><i class="las la-spinner la-spin"></i> Uploading, keep this tab open&hellip;</p>
                </div>
            </div>

            {{-- Link preview: what a chat or feed shows when a member sends the link --}}
            <div class="pf-card">
                <div class="pf-card-header">
                    <i class="las la-comment"></i> Link preview
                </div>
                <div class="adm-post">
                    <div class="adm-post__bubble">
                        <div class="adm-post__card">
                            <div class="adm-post__media" id="pvMedia">
                                @if ($isEdit && $ad->pictureSource())
                                    <img src="{{ $ad->previewImageUrl() }}" alt="">
                                @else
                                    <span class="adm-post__placeholder" id="pvPlaceholder">
                                        <i class="las la-image"></i>
                                        <small>{{ $isEdit ? 'The site logo shows until the clip has a cover' : 'Your picture shows here' }}</small>
                                    </span>
                                @endif
                            </div>
                            <div class="adm-post__link">
                                <strong id="pvTitle"></strong>
                                <span id="pvDesc"></span>
                                <small>{{ $shopHost }}</small>
                            </div>
                        </div>
                        <p class="adm-post__caption"><span id="pvCaption"></span><span class="adm-post__url">https://{{ $shopHost }}/shop/ad/&hellip;?ref=MEMBER</span></p>
                    </div>
                    <p class="adm-post__note">
                        On save the picture is fitted to 1200&times;630 on a matching background, the size
                        Facebook, WhatsApp, X and Telegram show large.
                    </p>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('breadcrumb-plugins')
    <a class="btn btn-sm btn-outline--dark" href="{{ route('admin.ads.index') }}">
        <i class="las la-undo"></i> Back
    </a>
@endpush

@push('script')
<script>
"use strict";
(function ($) {
    const form        = document.getElementById('adForm');
    const mediaInput  = document.getElementById('mediaInput');
    const posterInput = document.getElementById('posterInput');
    const drop        = document.getElementById('mediaDrop');
    const pvMedia     = document.getElementById('pvMedia');
    const posterAuto  = document.getElementById('posterAuto');
    const imageExt    = @json(\App\Models\Ad::IMAGE_EXTENSIONS);
    const limitKb     = { image: +form.dataset.limitImage, video: +form.dataset.limitVideo, poster: +form.dataset.limitPoster };
    const savedClip   = @json($ad->isVideo() ? $ad->mediaUrl() : null);
    const hasPoster   = @json((bool) $ad->poster);
    let posterPicked  = false;   // the admin chose their own cover in this form

    const extOf  = (file) => file.name.split('.').pop().toLowerCase();
    const sizeMb = (kb) => (kb / 1024).toFixed(kb % 1024 ? 1 : 0) + ' MB';

    function tooBig(file, kind) {
        if (file.size <= limitKb[kind] * 1024) return false;
        notify('error', `That file is ${(file.size / 1048576).toFixed(1)} MB. The limit here is ${sizeMb(limitKb[kind])}.`);
        return true;
    }

    function showPreview(url) {
        pvMedia.replaceChildren(Object.assign(document.createElement('img'), { src: url, alt: '' }));
    }

    function showPoster(url) {
        document.getElementById('posterThumb').replaceChildren(Object.assign(document.createElement('img'), { src: url, alt: '' }));
        showPreview(url);
    }

    // A frame from about a second in: the first frame of a clip is often black
    function captureFrame(src) {
        return new Promise((resolve, reject) => {
            const video = Object.assign(document.createElement('video'), { muted: true, playsInline: true, preload: 'auto', src });
            const fail  = () => { clearTimeout(timer); reject(new Error('no frame')); };
            const timer = setTimeout(fail, 20000);

            video.addEventListener('error', fail, { once: true });
            video.addEventListener('loadedmetadata', () => {
                video.currentTime = Math.min(1, (video.duration || 4) / 4);
            }, { once: true });
            video.addEventListener('seeked', () => {
                clearTimeout(timer);
                const scale  = Math.min(1, 1280 / (video.videoWidth || 1));
                const canvas = Object.assign(document.createElement('canvas'), {
                    width: Math.round(video.videoWidth * scale), height: Math.round(video.videoHeight * scale),
                });
                if (!canvas.width || !canvas.height) return fail();
                canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
                canvas.toBlob((blob) => (blob ? resolve(blob) : fail()), 'image/jpeg', 0.86);
            }, { once: true });
        });
    }

    async function autoCover(src) {
        if (posterPicked || typeof DataTransfer === 'undefined') return;
        try {
            const blob = await captureFrame(src);
            if (posterPicked) return;   // the admin chose a cover while we worked

            const transfer = new DataTransfer();
            transfer.items.add(new File([blob], 'cover.jpg', { type: 'image/jpeg' }));
            posterInput.files = transfer.files;
            posterAuto.hidden = false;
            showPoster(URL.createObjectURL(blob));
        } catch (e) {
            // A codec this browser can't draw (some .mov files): the site logo is used instead
        }
    }

    mediaInput.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        const video = !imageExt.includes(extOf(file));
        if (tooBig(file, video ? 'video' : 'image')) {
            this.value = '';
            return;
        }

        document.getElementById('mediaName').textContent = file.name;
        document.getElementById('posterField').hidden = !video;
        drop.classList.add('has-file');

        const url = URL.createObjectURL(file);
        if (video) {
            autoCover(url);
        } else {
            showPreview(url);
        }
    });

    posterInput.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        if (tooBig(file, 'poster')) {
            this.value = '';
            return;
        }

        posterPicked      = true;
        posterAuto.hidden = true;
        showPoster(URL.createObjectURL(file));
    });

    ['dragenter', 'dragover'].forEach((type) => drop.addEventListener(type, (e) => {
        e.preventDefault();
        drop.classList.add('is-over');
    }));
    ['dragleave', 'drop'].forEach((type) => drop.addEventListener(type, () => drop.classList.remove('is-over')));
    drop.addEventListener('drop', (e) => {
        e.preventDefault();
        if (!e.dataTransfer.files.length) return;
        mediaInput.files = e.dataTransfer.files;
        mediaInput.dispatchEvent(new Event('change'));
    });

    // A saved clip without a cover gets one the next time it is opened here
    if (savedClip && !hasPoster) autoCover(savedClip);

    // Live preview + character counters
    const title   = document.getElementById('titleInput');
    const caption = document.getElementById('captionInput');

    function syncText() {
        const text = caption.value.trim();
        document.getElementById('pvTitle').textContent   = title.value.trim() || 'Your ad title';
        document.getElementById('pvDesc').textContent    = text ? text.split('\n')[0] : 'The first line of the caption.';
        document.getElementById('pvCaption').textContent = text || 'The member\'s caption appears here.';
        document.querySelectorAll('.adm-count').forEach((el) => {
            const input = document.getElementById(el.dataset.for);
            el.textContent = `${input.value.length}/${input.maxLength}`;
        });
    }
    title.addEventListener('input', syncText);
    caption.addEventListener('input', syncText);
    syncText();

    form.addEventListener('submit', function () {
        document.getElementById('adSubmit').disabled = true;
        document.getElementById('adUploading').hidden = !mediaInput.files.length && !posterInput.files.length;
    });
})(jQuery);
</script>
@endpush

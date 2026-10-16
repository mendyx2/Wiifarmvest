@extends('admin.layouts.app')
@section('panel')
@php
    $channelLabels = \App\Models\AdShare::CHANNELS;
    $channelIcons  = \App\Models\AdShare::CHANNEL_ICONS;
    $topChannel    = $channels->max() ?: 1;
@endphp
<div class="db-wrap adm-ads">
    <div class="db-greeting">
        <div class="db-greeting__left">
            <p class="db-greeting__tag">Marketing</p>
            <h2 class="db-greeting__name">Ad Manager</h2>
            <p class="db-greeting__sub">Pictures and clips members post with their own affiliate link. Every visit and order is traced back to the ad.</p>
        </div>
        <div class="db-greeting__right">
            <a href="{{ route('admin.ads.create') }}" class="adm-ads-new"><i class="las la-plus"></i> New ad</a>
        </div>
    </div>

    <div class="row gy-4 fr-boxes">
        <div class="col-xl-3 col-sm-6">
            <x-widget value="{{ number_format($counts['live']) }}" title="Live ads" subheading="{{ number_format($counts['paused']) }} paused" style="6" icon="las la-bullhorn" bg="success" outline=false :viewMoreIcon="false" />
        </div>
        <div class="col-xl-3 col-sm-6">
            <x-widget value="{{ number_format($totals['shares']) }}" title="Shares" subheading="Posts by members" style="6" icon="las la-share" bg="info" outline=false :viewMoreIcon="false" />
        </div>
        <div class="col-xl-3 col-sm-6">
            <x-widget value="{{ number_format($totals['visits']) }}" title="Visits" subheading="Taps on shared ads" style="6" icon="las la-mouse-pointer" bg="warning" outline=false :viewMoreIcon="false" />
        </div>
        <div class="col-xl-3 col-sm-6">
            <x-widget value="{{ showAmount($totals['revenue']) }}" title="Sales from ads" subheading="{{ number_format($totals['orders']) }} paid {{ \Illuminate\Support\Str::plural('order', $totals['orders']) }}" style="6" icon="las la-coins" bg="1" outline=false :viewMoreIcon="false" />
        </div>
    </div>

    <div class="row gy-4 mt-1">
        <div class="col-xl-8">
            <div class="adm-ads-bar">
                <nav class="adm-ads-tabs" aria-label="Filter ads">
                    @foreach (['all' => 'All', 'live' => 'Live', 'paused' => 'Paused'] as $key => $label)
                        <a href="{{ route('admin.ads.index', $key === 'all' ? [] : ['status' => $key]) }}" @class(['adm-ads-tab', 'is-active' => $filter === $key])>
                            {{ $label }} <span>{{ $counts[$key] }}</span>
                        </a>
                    @endforeach
                </nav>
                <span class="adm-ads-bar__hint">Lower sort number shows first on the member page</span>
            </div>

            @if ($ads->isEmpty())
                <div class="adm-ads-empty">
                    <i class="las la-photo-video"></i>
                    @if ($filter === 'all')
                        <h6>No ads yet</h6>
                        <p>Upload a product photo or a short clip. Members will see it in their Ad Center with share buttons for WhatsApp, Facebook, Instagram, TikTok and more.</p>
                        <a href="{{ route('admin.ads.create') }}" class="btn btn--primary btn-sm"><i class="las la-plus"></i> Create the first ad</a>
                    @else
                        <h6>No {{ $filter }} ads</h6>
                        <p><a href="{{ route('admin.ads.index') }}">Show all ads</a></p>
                    @endif
                </div>
            @else
                <div class="adm-ads-grid">
                    @foreach ($ads as $ad)
                        @php $sales = $salesByAd->get($ad->id); @endphp
                        <article @class(['adm-ad', 'is-paused' => !$ad->is_active])>
                            <a href="{{ $ad->mediaUrl() }}" target="_blank" rel="noopener" class="adm-ad__media" title="Open the file">
                                @if ($thumb = $ad->thumbUrl())
                                    <img src="{{ $thumb }}" alt="{{ $ad->title }}" loading="lazy">
                                @else
                                    {{-- A clip without a cover: its first frame --}}
                                    <video src="{{ $ad->mediaUrl() }}#t=0.5" preload="metadata" muted playsinline></video>
                                @endif
                                @if ($ad->isVideo())
                                    <span class="adm-ad__type"><i class="las la-play"></i> Video</span>
                                @else
                                    <span class="adm-ad__type"><i class="las la-image"></i> Picture</span>
                                @endif
                            </a>

                            <div class="adm-ad__body">
                                <div class="adm-ad__status">
                                    <span class="adm-ad__dot"></span>{{ $ad->is_active ? 'Live' : 'Paused' }}
                                    <span class="adm-ad__order">#{{ $ad->sort_order }}</span>
                                </div>
                                <h6 class="adm-ad__title">{{ $ad->title }}</h6>
                                <p class="adm-ad__dest">
                                    <i class="las la-level-up-alt"></i>
                                    {{ $ad->product ? $ad->product->name : 'Shop home' }}
                                </p>

                                <dl class="adm-ad__stats">
                                    <div><dt>Shares</dt><dd>{{ number_format($ad->shares_count) }}</dd></div>
                                    <div><dt>Visits</dt><dd>{{ number_format($ad->clicks_count) }}</dd></div>
                                    <div><dt>Orders</dt><dd>{{ number_format($sales->orders ?? 0) }}</dd></div>
                                </dl>
                                <p @class(['adm-ad__sales', 'is-zero' => !($sales->revenue ?? 0)])>
                                    <span>Sales</span> {{ showAmount($sales->revenue ?? 0) }}
                                </p>
                            </div>

                            <div class="adm-ad__foot">
                                <a href="{{ route('admin.ads.edit', $ad->id) }}" class="adm-ad__btn"><i class="las la-pen"></i> Edit</a>
                                <form action="{{ route('admin.ads.status', $ad->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="adm-ad__btn">
                                        @if ($ad->is_active)
                                            <i class="las la-pause"></i> Pause
                                        @else
                                            <i class="las la-play"></i> Go live
                                        @endif
                                    </button>
                                </form>
                                <button type="button" class="adm-ad__btn adm-ad__btn--danger confirmationBtn ms-auto"
                                    data-question="Delete “{{ $ad->title }}”? Its file is removed and links already shared will open the shop instead."
                                    data-action="{{ route('admin.ads.delete', $ad->id) }}" title="Delete">
                                    <i class="las la-trash"></i>
                                </button>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($ads->hasPages())
                    <div class="mt-4">{{ paginateLinks($ads) }}</div>
                @endif
            @endif
        </div>

        <div class="col-xl-4">
            <section class="adm-ads-panel">
                <header class="adm-ads-panel__head">
                    <h6>Where members share</h6>
                    <span>{{ number_format($totals['shares']) }} total</span>
                </header>
                @forelse ($channels as $channel => $total)
                    <div class="adm-ch adm-ch--{{ $channel }}">
                        <span class="adm-ch__icon"><i class="{{ $channelIcons[$channel] ?? 'las la-share' }}"></i></span>
                        <div class="adm-ch__main">
                            <div class="adm-ch__row">
                                <span>{{ $channelLabels[$channel] ?? ucfirst($channel) }}</span>
                                <strong>{{ number_format($total) }}</strong>
                            </div>
                            <div class="adm-ch__track"><span style="width: {{ max(3, round($total / $topChannel * 100)) }}%"></span></div>
                        </div>
                    </div>
                @empty
                    <p class="adm-ads-panel__empty">Nothing shared yet. Channels appear here as members post ads.</p>
                @endforelse
            </section>

            <section class="adm-ads-panel">
                <header class="adm-ads-panel__head">
                    <h6>Top promoters</h6>
                    <span>By visits from ads</span>
                </header>
                @forelse ($promoters as $i => $row)
                    <div class="adm-promoter">
                        <span class="adm-promoter__rank">{{ $i + 1 }}</span>
                        <div class="adm-promoter__who">
                            <a href="{{ route('admin.users.detail', $row->user->id) }}">{{ $row->user->fullname }}</a>
                            <small>{{ '@' . $row->user->username }}</small>
                        </div>
                        <div class="adm-promoter__nums">
                            <strong>{{ number_format($row->visits) }}</strong> visits
                            <small>{{ number_format($row->orders) }} {{ \Illuminate\Support\Str::plural('order', $row->orders) }}</small>
                        </div>
                    </div>
                @empty
                    <p class="adm-ads-panel__empty">No visits from ads yet.</p>
                @endforelse
            </section>

            <!-- <p class="adm-ads-note">
                <i class="las la-info-circle"></i>
                Facebook, WhatsApp, X and Telegram show the ad as a link preview. Instagram, TikTok and YouTube don't take links
                from websites, so members download the file and upload it there, with their link in the caption or bio.
            </p> -->
        </div>
    </div>
</div>

<x-confirmation-modal />
@endsection

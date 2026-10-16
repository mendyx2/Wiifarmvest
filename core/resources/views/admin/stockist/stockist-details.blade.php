@extends('admin.layouts.app')
@section('panel')
@php
    $user     = $stockist->user;
    $isMega   = $stockist->isMega();
    $active   = (int) $stockist->status === \App\Constants\Status::ACTIVE && $stockist->is_active;
    $initials = collect(explode(' ', (string) $stockist->business_name))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('') ?: 'S';
    $sent     = $transferStats['sent'];
    $received = $transferStats['received'];
@endphp
<div class="db-wrap asd">
    {{-- Header --}}
    <section class="asd-hero">
        <div class="asd-hero__id">
            <span class="asd-hero__avatar">{{ mb_strtoupper($initials) }}</span>
            <div>
                <p class="asd-hero__tags">
                    <span @class(['asd-tier', 'asd-tier--mega' => $isMega])>{{ $isMega ? 'Mega stockist' : 'Mini stockist' }}</span>
                    <span @class(['asd-state', 'is-off' => !$active])><i class="las la-circle"></i> {{ $active ? 'Active' : 'Inactive' }}</span>
                </p>
                <h2 class="asd-hero__name">{{ $stockist->business_name ?: 'Stockist #' . $stockist->id }}</h2>
                <p class="asd-hero__meta">
                    <span><i class="las la-map-marker"></i> {{ $stockist->state?->name ?? 'No state' }}</span>
                    @if ($user)
                        <span><i class="las la-user"></i> <a href="{{ route('admin.users.detail', $user->id) }}">{{ $user->fullname }}</a> {{ '@' . $user->username }}</span>
                    @endif
                    <span><i class="las la-calendar"></i> Stockist since {{ showDateTime($stockist->created_at, 'M Y') }}</span>
                </p>
            </div>
        </div>
        <div class="asd-hero__actions">
            <a href="{{ route('admin.stockist.wallet-page', $stockist) }}" class="asd-btn asd-btn--primary"><i class="las la-wallet"></i> Top up wallet</a>
            <a href="{{ route('admin.stockist.transfers.index', ['search' => $user?->username]) }}" class="asd-btn"><i class="las la-exchange-alt"></i> All transfers</a>
        </div>
    </section>

    {{-- Key numbers --}}
    <div class="row gy-4 fr-boxes">
        <div class="col-xl-3 col-sm-6">
            <x-widget value="{{ showAmount($stockist->wallet) }}" title="Stockist wallet" subheading="Used to buy stock" style="6" icon="las la-wallet" bg="success" outline=false :viewMoreIcon="false" />
        </div>
        <div class="col-xl-3 col-sm-6">
            <x-widget value="{{ number_format($unitsOnHand) }}" title="Units on hand" subheading="{{ $inventory->count() }} products · {{ $lowStock }} low · {{ $outOfStock }} out" style="6" icon="las la-boxes" bg="info" outline=false :viewMoreIcon="false" />
        </div>
        <div class="col-xl-3 col-sm-6">
            <x-widget value="{{ showAmount($inventoryValue) }}" title="Stock value" subheading="At {{ $stockist->state?->name ?? 'state' }} stockist prices" style="6" icon="las la-coins" bg="warning" outline=false :viewMoreIcon="false" />
        </div>
        <div class="col-xl-3 col-sm-6">
            @if ($isMega)
                <x-widget value="{{ showAmount($sent->total) }}" title="Sold to Mini stockists" subheading="{{ $sent->n }} {{ \Illuminate\Support\Str::plural('transfer', $sent->n) }} · {{ showAmount($sent->rebate) }} rebate" style="6" icon="las la-share-square" bg="1" outline=false :viewMoreIcon="false" />
            @else
                <x-widget value="{{ showAmount($received->total) }}" title="Bought from Mega stockists" subheading="{{ $received->n }} {{ \Illuminate\Support\Str::plural('transfer', $received->n) }} · {{ number_format($received->units) }} units" style="6" icon="las la-truck-loading" bg="1" outline=false :viewMoreIcon="false" />
            @endif
        </div>
    </div>

    {{-- Tabs --}}
    <nav class="asd-tabs" role="tablist" aria-label="Stockist sections">
        <button type="button" role="tab" class="asd-tab" data-tab="overview"><i class="las la-id-card"></i> Overview</button>
        <button type="button" role="tab" class="asd-tab" data-tab="inventory"><i class="las la-boxes"></i> Inventory <span>{{ $inventory->count() }}</span></button>
        <button type="button" role="tab" class="asd-tab" data-tab="transfers">
            <i class="las la-exchange-alt"></i> Stock transfers <span>{{ $transferStats['all'] }}</span>
            @if ($transferStats['pending'])<em title="Pending">{{ $transferStats['pending'] }} pending</em>@endif
        </button>
        <button type="button" role="tab" class="asd-tab" data-tab="activity"><i class="las la-receipt"></i> Wallet activity</button>
    </nav>

    {{-- Overview --}}
    <section class="asd-panel" id="tab-overview" role="tabpanel">
        <div class="row gy-4">
            <div class="col-lg-4">
                <div class="asd-card h-100">
                    <h6 class="asd-card__title"><i class="las la-store"></i> Business</h6>
                    <dl class="asd-facts">
                        <div><dt>Name</dt><dd>{{ $stockist->business_name ?: '—' }}</dd></div>
                        <div><dt>Store type</dt><dd>{{ $stockist->store_type_name }}</dd></div>
                        <div><dt>State</dt><dd>{{ $stockist->state?->name ?? '—' }}</dd></div>
                        <div><dt>Phone</dt><dd>@if ($stockist->business_phone)<a href="tel:{{ $stockist->business_phone }}">{{ $stockist->business_phone }}</a>@else — @endif</dd></div>
                        <div><dt>Email</dt><dd>@if ($stockist->business_email)<a href="mailto:{{ $stockist->business_email }}">{{ $stockist->business_email }}</a>@else — @endif</dd></div>
                        <div><dt>Registration no.</dt><dd>{{ $stockist->business_registration_number ?: 'Not provided' }}</dd></div>
                        <div><dt>Verified</dt><dd>{{ $stockist->is_verified ? 'Yes' . ($stockist->verified_at ? ', ' . showDateTime($stockist->verified_at, 'd M Y') : '') : 'No' }}</dd></div>
                    </dl>
                    @if ($stockist->business_description)
                        <p class="asd-desc">{{ $stockist->business_description }}</p>
                    @endif
                </div>
            </div>
            <div class="col-lg-4">
                <div class="asd-card h-100">
                    <h6 class="asd-card__title"><i class="las la-user-circle"></i> Owner</h6>
                    @if ($user)
                        <dl class="asd-facts">
                            <div><dt>Name</dt><dd><a href="{{ route('admin.users.detail', $user->id) }}">{{ $user->fullname }}</a></dd></div>
                            <div><dt>Username</dt><dd>{{ '@' . $user->username }}</dd></div>
                            <div><dt>Email</dt><dd>{{ $user->email ?: '—' }}</dd></div>
                            <div><dt>Mobile</dt><dd>{{ $user->mobile ?: '—' }}</dd></div>
                            <div><dt>Member since</dt><dd>{{ showDateTime($user->created_at, 'd M Y') }}</dd></div>
                        </dl>
                        <div class="asd-balances">
                            <div><span>Money Box</span><strong>{{ showAmount($user->balance) }}</strong></div>
                            <div><span>Stockist rebate</span><strong>{{ showAmount($user->stockist_rebate) }}</strong></div>
                        </div>
                    @else
                        <p class="text-muted mb-0">No member account linked.</p>
                    @endif
                </div>
            </div>
            <div class="col-lg-4">
                <div class="asd-card h-100">
                    <h6 class="asd-card__title"><i class="las la-chart-line"></i> Activity</h6>
                    <ul class="asd-kpis">
                        <li>
                            <span class="asd-kpis__icon asd-kpis__icon--green"><i class="las la-hand-holding-heart"></i></span>
                            <div><strong>{{ showAmount($redemptions->total) }}</strong><small>{{ number_format($redemptions->n) }} redemptions & POS sales · {{ number_format($redemptions->units) }} units</small></div>
                        </li>
                        <li>
                            <span class="asd-kpis__icon asd-kpis__icon--blue"><i class="las la-share-square"></i></span>
                            <div><strong>{{ showAmount($sent->total) }}</strong><small>Sold to Mini stockists · {{ $sent->n }} {{ \Illuminate\Support\Str::plural('transfer', $sent->n) }} · {{ number_format($sent->units) }} units</small></div>
                        </li>
                        <li>
                            <span class="asd-kpis__icon asd-kpis__icon--amber"><i class="las la-truck-loading"></i></span>
                            <div><strong>{{ showAmount($received->total) }}</strong><small>Bought from Mega stockists · {{ $received->n }} {{ \Illuminate\Support\Str::plural('transfer', $received->n) }} · {{ number_format($received->units) }} units</small></div>
                        </li>
                        <li>
                            <span class="asd-kpis__icon asd-kpis__icon--rose"><i class="las la-hourglass-half"></i></span>
                            <div><strong>{{ $transferStats['pending'] }}</strong><small>Transfers waiting for a Mega stockist</small></div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- Inventory --}}
    <section class="asd-panel" id="tab-inventory" role="tabpanel" hidden>
        <div class="asd-card p-0">
            <div class="table-responsive--md table-responsive">
                <table class="table table--light style--two mb-0 asd-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>On hand</th>
                            <th>Min level</th>
                            <th>State price</th>
                            <th>Value</th>
                            <th>Last restocked</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($inventory as $row)
                            @php
                                $price = (float) ($prices[$row->product_id] ?? 0);
                                $level = $row->quantity <= 0 ? 'out' : ($row->quantity < ($row->min_stock_level ?: 10) ? 'low' : 'ok');
                            @endphp
                            <tr>
                                <td class="text-start">
                                    <div class="asd-product">
                                        <img src="{{ getImage(getFilePath('products') . '/' . $row->product?->thumbnail, getFileSize('products')) }}" alt="" loading="lazy">
                                        <span><strong>{{ $row->product?->name ?? 'Product #' . $row->product_id }}</strong><small>SKU {{ $row->product?->sku ?? '—' }}</small></span>
                                    </div>
                                </td>
                                <td><span class="asd-qty asd-qty--{{ $level }}">{{ number_format($row->quantity) }}</span></td>
                                <td>{{ number_format($row->min_stock_level) }}</td>
                                <td>{{ $price ? showAmount($price) : '—' }}</td>
                                <td><strong>{{ $price ? showAmount($row->quantity * $price) : '—' }}</strong></td>
                                <td>{{ $row->last_restocked_at ? showDateTime($row->last_restocked_at, 'd M Y') : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5"><i class="las la-box-open d-block fs-1 mb-2"></i>No stock yet.</td></tr>
                        @endforelse
                    </tbody>
                    @if ($inventory->isNotEmpty())
                        <tfoot>
                            <tr>
                                <th class="text-start">Total</th>
                                <th>{{ number_format($unitsOnHand) }}</th>
                                <th colspan="2"></th>
                                <th>{{ showAmount($inventoryValue) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </section>

    {{-- Stock transfers --}}
    <section class="asd-panel" id="tab-transfers" role="tabpanel" hidden>
        <div class="asd-subbar">
            <nav class="atx-tabs" aria-label="Direction">
                <a href="{{ route('admin.stockist.details', $stockist) }}?direction=#transfers" @class(['atx-tab', 'is-active' => !$direction])>All <span>{{ $transferStats['all'] }}</span></a>
                <a href="{{ route('admin.stockist.details', ['stockist' => $stockist, 'direction' => 'sent']) }}#transfers" @class(['atx-tab', 'is-active' => $direction === 'sent'])>Sent to Minis <span>{{ $transferStats['sentAll'] }}</span></a>
                <a href="{{ route('admin.stockist.details', ['stockist' => $stockist, 'direction' => 'received']) }}#transfers" @class(['atx-tab', 'is-active' => $direction === 'received'])>Received from Megas <span>{{ $transferStats['recvAll'] }}</span></a>
            </nav>
            <span class="asd-subbar__hint">{{ $isMega ? 'A Mega stockist sends stock to the Mini stockists in its state.' : 'A Mini stockist buys from a Mega stockist in its state.' }}</span>
        </div>
        <div class="asd-card p-0">
            <div class="table-responsive--md table-responsive">
                <table class="table table--light style--two mb-0 asd-table">
                    <thead>
                        <tr>
                            <th>Transfer</th>
                            <th>Direction</th>
                            <th>Stock</th>
                            <th>Amount</th>
                            <th>Rebate</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transfers as $transfer)
                            @php $out = (int) $transfer->seller_stockist_id === (int) $stockist->id; @endphp
                            <tr>
                                <td>
                                    <span class="atx-code">{{ $transfer->code }}</span>
                                    <small class="d-block text-muted">{{ showDateTime($transfer->created_at, 'd M Y, h:i A') }}</small>
                                </td>
                                <td class="text-start">
                                    <span @class(['asd-dir', 'asd-dir--out' => $out, 'asd-dir--in' => !$out])>
                                        <i class="las la-{{ $out ? 'arrow-up' : 'arrow-down' }}"></i> {{ $out ? 'Sent' : 'Received' }}
                                    </span>
                                    <span class="asd-dir__who">
                                        {{ $out ? 'to' : 'from' }} <strong>{{ ($out ? $transfer->buyer : $transfer->seller)?->business_name ?? '—' }}</strong>
                                        <small>{{ '@' . ($out ? $transfer->buyer : $transfer->seller)?->user?->username }}</small>
                                    </span>
                                </td>
                                <td>{{ number_format($transfer->total_quantity) }} {{ \Illuminate\Support\Str::plural('unit', $transfer->total_quantity) }}<small class="d-block text-muted">{{ $transfer->items_count }} {{ \Illuminate\Support\Str::plural('product', $transfer->items_count) }}</small></td>
                                <td><strong @class(['atx-plus' => $out && $transfer->status === 'completed'])>{{ showAmount($transfer->total_amount) }}</strong></td>
                                <td>{{ $out && $transfer->status === 'completed' ? showAmount($transfer->rebate_amount) : '—' }}</td>
                                <td><span class="badge {{ $transfer->adminBadge() }}" title="{{ $transfer->statusLabel() }}">{{ ucfirst($transfer->status) }}</span></td>
                                <td>
                                    <a href="{{ route('admin.stockist.transfers.show', $transfer->id) }}" class="btn btn-sm btn-outline--primary"><i class="las la-eye"></i> View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="las la-exchange-alt d-block fs-1 mb-2"></i>
                                    {{ $direction ? 'Nothing ' . $direction . ' yet.' : 'No stock transfers yet.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($transfers->hasPages())
                <div class="asd-card__foot">{{ $transfers->fragment('transfers')->links() }}</div>
            @endif
        </div>
    </section>

    {{-- Wallet activity --}}
    <section class="asd-panel" id="tab-activity" role="tabpanel" hidden>
        <div class="asd-card p-0">
            <div class="table-responsive--md table-responsive">
                <table class="table table--light style--two mb-0 asd-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Activity</th>
                            <th>Reference</th>
                            <th>Amount</th>
                            <th>Balance after</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activity as $row)
                            <tr>
                                <td>{{ showDateTime($row->at, 'd M Y, h:i A') }}</td>
                                <td class="text-start">{{ $row->label }}<small class="d-block"><span class="asd-source">{{ $row->source }}</span></small></td>
                                <td><code>{{ $row->ref }}</code></td>
                                <td><strong @class(['atx-plus' => $row->sign === '+', 'atx-minus' => $row->sign !== '+'])>{{ $row->sign }}{{ showAmount($row->amount) }}</strong></td>
                                <td>{{ showAmount($row->balance) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-5"><i class="las la-receipt d-block fs-1 mb-2"></i>No wallet activity yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="asd-card__foot asd-card__note">Latest 15 entries: admin top-ups, POS sales, redemptions and stock transfers.</p>
        </div>
    </section>
</div>
@endsection

@push('breadcrumb-plugins')
    <a class="btn btn-sm btn-outline--dark" href="{{ route('admin.stockist.dashboard') }}"><i class="las la-undo"></i> Back</a>
@endpush

@push('script')
<script>
(function () {
    'use strict';
    const tabs   = document.querySelectorAll('.asd-tab');
    const panels = document.querySelectorAll('.asd-panel');

    function open(name) {
        if (!document.getElementById('tab-' + name)) name = 'overview';
        tabs.forEach((tab) => {
            const on = tab.dataset.tab === name;
            tab.classList.toggle('is-active', on);
            tab.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        panels.forEach((panel) => { panel.hidden = panel.id !== 'tab-' + name; });
    }

    tabs.forEach((tab) => tab.addEventListener('click', () => {
        open(tab.dataset.tab);
        history.replaceState(null, '', '#' + tab.dataset.tab);
    }));

    open(location.hash.slice(1) || @json($tab));
})();
</script>
@endpush

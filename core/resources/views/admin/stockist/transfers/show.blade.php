@extends('admin.layouts.app')
@section('panel')
@php
    $buyer     = $transfer->buyer;
    $seller    = $transfer->seller;
    $completed = $transfer->status === \App\Models\StockTransfer::STATUS_COMPLETED;
    $pending   = $transfer->isPending();
    $parties   = [
        ['role' => 'Mega stockist · sold', 'tone' => 'mega', 'stockist' => $seller],
        ['role' => 'Mini stockist · bought', 'tone' => 'mini', 'stockist' => $buyer],
    ];
@endphp
<div class="db-wrap atx">
    {{-- Summary --}}
    <div class="atx-head">
        <div>
            <p class="atx-head__tag">Stock transfer</p>
            <h3 class="atx-head__code">{{ $transfer->code }} <span class="badge {{ $transfer->adminBadge() }}">{{ $transfer->statusLabel() }}</span></h3>
            <p class="atx-head__sub">
                {{ $seller?->business_name }} <i class="las la-long-arrow-alt-right"></i> {{ $buyer?->business_name }}
                &middot; {{ $transfer->state?->name }} &middot; requested {{ showDateTime($transfer->created_at, 'd M Y, h:i A') }}
            </p>
        </div>
        <dl class="atx-head__nums">
            <div><dt>Amount</dt><dd>{{ showAmount($transfer->total_amount) }}</dd></div>
            <div><dt>Units</dt><dd>{{ number_format($transfer->total_quantity) }}</dd></div>
            <div><dt>{{ $completed ? 'Rebate paid' : 'Rebate if confirmed' }}</dt><dd>{{ $completed ? showAmount($transfer->rebate_amount) : ($pending ? '≈ ' . showAmount($rebatePreview) : '—') }}</dd></div>
        </dl>
    </div>

    <div class="row gy-4">
        {{-- People involved --}}
        @foreach ($parties as $party)
            @php $s = $party['stockist']; @endphp
            <div class="col-lg-6">
                <section class="atx-party atx-party--{{ $party['tone'] }}">
                    <p class="atx-party__role">{{ $party['role'] }}</p>
                    @if ($s)
                        <h5 class="atx-party__name">{{ $s->business_name }}</h5>
                        <dl class="atx-party__facts">
                            <div><dt>Owner</dt><dd>
                                @if ($s->user)
                                    <a href="{{ route('admin.users.detail', $s->user->id) }}">{{ $s->user->fullname }}</a>
                                    <small>{{ '@' . $s->user->username }}</small>
                                @else — @endif
                            </dd></div>
                            <div><dt>Phone</dt><dd>{{ $s->business_phone ?: ($s->user?->mobile ?? '—') }}</dd></div>
                            <div><dt>Email</dt><dd>{{ $s->business_email ?: ($s->user?->email ?? '—') }}</dd></div>
                            <div><dt>State</dt><dd>{{ $s->state?->name ?? '—' }} &middot; {{ $s->store_type_name }}</dd></div>
                            <div><dt>Stockist wallet now</dt><dd><strong>{{ showAmount($s->wallet) }}</strong></dd></div>
                        </dl>
                        <a href="{{ route('admin.stockist.details', $s->id) }}" class="atx-party__link">Stockist profile <i class="las la-arrow-right"></i></a>
                    @else
                        <p class="text-muted mb-0">Stockist record not found.</p>
                    @endif
                </section>
            </div>
        @endforeach

        {{-- Stock breakdown --}}
        <div class="col-12">
            <div class="card atx-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0"><i class="las la-boxes"></i> Stock breakdown</h6>
                    <small class="text-muted">{{ $transfer->items->count() }} {{ \Illuminate\Support\Str::plural('product', $transfer->items->count()) }} · {{ number_format($transfer->total_quantity) }} units</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive--md table-responsive">
                        <table class="table table--light style--two mb-0 atx-items">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th>Unit price</th>
                                    <th>Line total</th>
                                    <th>Rebate</th>
                                    <th>Mega stock</th>
                                    <th>Mini stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transfer->items as $item)
                                    <tr>
                                        <td class="text-start"><strong>{{ $item->product_name }}</strong><small class="d-block text-muted">#{{ $item->product_id }}</small></td>
                                        <td>{{ number_format($item->quantity) }}</td>
                                        <td>{{ showAmount($item->unit_price) }}</td>
                                        <td><strong>{{ showAmount($item->line_total) }}</strong></td>
                                        <td>
                                            @if ($completed)
                                                {{ showAmount($item->rebate_amount) }}
                                                @if ($item->rebate_percent !== null)<small class="d-block text-muted">{{ $item->rebate_percent + 0 }}% of PV × ₦{{ rDollar() }}</small>@endif
                                            @else — @endif
                                        </td>
                                        <td>
                                            @if ($item->seller_stock_before !== null)
                                                <span class="atx-move">{{ number_format($item->seller_stock_before) }} <i class="las la-long-arrow-alt-right"></i> <b>{{ number_format($item->seller_stock_after) }}</b></span>
                                                <small class="d-block atx-minus">&minus;{{ number_format($item->quantity) }}</small>
                                            @else — @endif
                                        </td>
                                        <td>
                                            @if ($item->buyer_stock_before !== null)
                                                <span class="atx-move">{{ number_format($item->buyer_stock_before) }} <i class="las la-long-arrow-alt-right"></i> <b>{{ number_format($item->buyer_stock_after) }}</b></span>
                                                <small class="d-block atx-plus">+{{ number_format($item->quantity) }}</small>
                                            @else — @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th class="text-start">Total</th>
                                    <th>{{ number_format($transfer->total_quantity) }}</th>
                                    <th></th>
                                    <th>{{ showAmount($transfer->total_amount) }}</th>
                                    <th>{{ $completed ? showAmount($transfer->rebate_amount) : '—' }}</th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Money --}}
        <div class="col-lg-7">
            <div class="card atx-card h-100">
                <div class="card-header"><h6 class="card-title mb-0"><i class="las la-coins"></i> Money</h6></div>
                <div class="card-body">
                    <ol class="atx-money">
                        <li>
                            <span class="atx-money__dot atx-money__dot--out"></span>
                            <div>
                                <strong>{{ $buyer?->business_name }} paid {{ showAmount($transfer->total_amount) }}</strong>
                                <small>From the Mini's stockist wallet, held on the transfer · wallet after {{ showAmount($transfer->buyer_balance_after) }} · trx {{ $transfer->trx }}</small>
                            </div>
                        </li>
                        @if ($completed)
                            <li>
                                <span class="atx-money__dot atx-money__dot--in"></span>
                                <div>
                                    <strong>{{ $seller?->business_name }} received {{ showAmount($transfer->total_amount) }}</strong>
                                    <small>Product money to the Mega's stockist wallet · wallet after {{ showAmount($transfer->seller_balance_after) }} · trx {{ $transfer->settle_trx }}</small>
                                </div>
                            </li>
                            <li>
                                <span class="atx-money__dot atx-money__dot--cost"></span>
                                <div>
                                    <strong>Company paid {{ showAmount($transfer->rebate_amount) }} stockist rebate</strong>
                                    <small>To the Mega's stockist_rebate, at the Mega % for {{ $transfer->state?->name }}</small>
                                </div>
                            </li>
                        @elseif ($pending)
                            <li>
                                <span class="atx-money__dot atx-money__dot--hold"></span>
                                <div>
                                    <strong>Held, waiting for {{ $seller?->business_name }}</strong>
                                    <small>Paid to the Mega on confirmation, or refunded to the Mini if declined or cancelled</small>
                                </div>
                            </li>
                        @else
                            <li>
                                <span class="atx-money__dot atx-money__dot--in"></span>
                                <div>
                                    <strong>Refunded {{ showAmount($transfer->total_amount) }} to {{ $buyer?->business_name }}</strong>
                                    <small>{{ $transfer->statusLabel() }} · trx {{ $transfer->settle_trx }}</small>
                                </div>
                            </li>
                        @endif
                    </ol>

                    @if ($ledger->isNotEmpty() || $rebates->isNotEmpty())
                        <p class="atx-sub">Ledger rows</p>
                        <div class="table-responsive">
                            <table class="table table-sm atx-ledger mb-0">
                                <thead><tr><th>Wallet</th><th>Member</th><th>Details</th><th class="text-end">Amount</th><th class="text-end">Balance after</th></tr></thead>
                                <tbody>
                                    @foreach ($ledger as $row)
                                        <tr>
                                            <td>Stockist wallet</td>
                                            <td>{{ '@' . $row->user?->username }}</td>
                                            <td>{{ $row->details }}</td>
                                            <td @class(['text-end', 'atx-plus' => $row->trx_type === '+', 'atx-minus' => $row->trx_type !== '+'])>{{ $row->trx_type }}{{ showAmount($row->amount) }}</td>
                                            <td class="text-end">{{ showAmount($row->post_balance) }}</td>
                                        </tr>
                                    @endforeach
                                    @foreach ($rebates as $row)
                                        <tr>
                                            <td>Stockist rebate</td>
                                            <td>{{ '@' . $row->user?->username }}</td>
                                            <td>{{ $row->details }}</td>
                                            <td class="text-end atx-plus">+{{ showAmount($row->amount) }}</td>
                                            <td class="text-end">{{ showAmount($row->post_balance) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Timeline + actions --}}
        <div class="col-lg-5">
            <div class="card atx-card mb-4">
                <div class="card-header"><h6 class="card-title mb-0"><i class="las la-stream"></i> Timeline</h6></div>
                <div class="card-body">
                    <ul class="atx-timeline">
                        <li><b>Requested &amp; paid</b> by {{ $buyer?->business_name }}<small>{{ showDateTime($transfer->created_at, 'd M Y, h:i:s A') }}</small></li>
                        @if ($transfer->buyer_note)
                            <li class="atx-timeline__note">“{{ $transfer->buyer_note }}”</li>
                        @endif
                        @if ($completed)
                            <li class="is-done"><b>Confirmed</b> by {{ $seller?->business_name }}; stock moved and paid<small>{{ showDateTime($transfer->completed_at, 'd M Y, h:i:s A') }}</small></li>
                        @elseif ($pending)
                            <li class="is-wait"><b>Waiting</b> for {{ $seller?->business_name }} to confirm</li>
                        @else
                            <li class="is-stop">
                                <b>{{ $transfer->statusLabel() }}</b>
                                {{ match ($transfer->closed_by) { 'seller' => 'by the Mega stockist', 'buyer' => 'by the Mini stockist', 'admin' => 'by ' . ($transfer->admin?->name ?? 'admin'), default => '' } }}
                                <small>{{ showDateTime($transfer->closedAt(), 'd M Y, h:i:s A') }}</small>
                            </li>
                            @if ($transfer->decision_note)
                                <li class="atx-timeline__note">“{{ $transfer->decision_note }}”</li>
                            @endif
                        @endif
                    </ul>
                </div>
            </div>

            @if ($canCancel)
                <div class="card atx-card atx-danger">
                    <div class="card-header"><h6 class="card-title mb-0"><i class="las la-undo-alt"></i> Cancel &amp; refund</h6></div>
                    <div class="card-body">
                        <p class="atx-sub mb-2">For a request the Mega stockist can't or won't act on. {{ showAmount($transfer->total_amount) }} goes back to {{ $buyer?->business_name }}'s stockist wallet and both stockists are told why.</p>
                        <form action="{{ route('admin.stockist.transfers.cancel', $transfer->id) }}" method="POST"
                              onsubmit="return confirm('Cancel {{ $transfer->code }} and refund {{ showAmount($transfer->total_amount) }}?');">
                            @csrf
                            <textarea name="reason" class="form-control mb-2" rows="2" maxlength="450" required placeholder="Reason, sent to both stockists"></textarea>
                            <button type="submit" class="btn btn--danger w-100"><i class="las la-undo-alt"></i> Cancel request &amp; refund</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('breadcrumb-plugins')
    <a class="btn btn-sm btn-outline--dark" href="{{ route('admin.stockist.transfers.index') }}"><i class="las la-undo"></i> Back</a>
@endpush

@extends($activeTemplate . 'layouts.master')

@section('content')
@php
    $pending   = $transfer->isPending();
    $completed = $transfer->status === \App\Models\StockTransfer::STATUS_COMPLETED;
    $buyer     = $transfer->buyer;
    $seller    = $transfer->seller;
    $short     = $isSeller && $pending
        ? $transfer->items->filter(fn ($item) => (int) ($onHand[$item->product_id] ?? 0) < $item->quantity)
        : collect();
@endphp
<div class="nc-wrap stx" id="ncWrap">
    <br>
    <div class="sl-page-header d-flex flex-wrap align-items-center gap-3 mb-4">
        <a href="{{ route('user.stockist.transfers.index') }}" class="sl-back-btn" aria-label="Back to stock requests"><i class="las la-arrow-left"></i></a>
        <div class="flex-grow-1">
            <h4 class="sl-page-title mb-1">
                Stock request <span class="stx-code">{{ $transfer->code }}</span>
                <span class="sl-status {{ $transfer->statusClass() }} ms-1 align-middle">{{ $transfer->statusLabel() }}</span>
            </h4>
            <p class="sl-page-subtitle mb-0">
                {{ $isSeller ? 'From ' . $buyer->business_name : 'To ' . $seller->business_name }}
                &middot; {{ showDateTime($transfer->created_at, 'd M Y, h:i A') }}
            </p>
        </div>
    </div>

    @if ($isSeller && $pending)
        <div @class(['stx-callout', 'stx-callout--warn' => $short->isNotEmpty()])>
            <i class="las la-{{ $short->isNotEmpty() ? 'exclamation-triangle' : 'hand-holding-usd' }}"></i>
            <div>
                @if ($short->isNotEmpty())
                    <strong>You don't have enough of {{ $short->pluck('product_name')->implode(', ') }}.</strong>
                    Restock first, or decline so {{ $buyer->business_name }} gets their money back.
                @else
                    <strong>{{ showAmount($transfer->total_amount) }} is already paid and held for you.</strong>
                    Confirm to move the stock to {{ $buyer->business_name }}; the money goes to your stockist wallet
                    and you earn about {{ showAmount($rebate) }} in stockist rebate.
                @endif
            </div>
        </div>
    @elseif (!$isSeller && $pending)
        <div class="stx-callout">
            <i class="las la-hourglass-half"></i>
            <div>
                <strong>{{ showAmount($transfer->total_amount) }} is held from your stockist wallet.</strong>
                Waiting for {{ $seller->business_name }} to confirm. You can cancel before they do and get it back at once.
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="sl-card mb-4">
                <div class="sl-card-header"><i class="las la-boxes"></i> Products <span class="sl-badge-count ms-auto">{{ number_format($transfer->total_quantity) }} units</span></div>
                <div class="table-responsive">
                    <table class="sl-table" aria-label="Products in this request">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                @if ($isSeller && $pending)<th>You have</th>@endif
                                @if ($completed)<th>Your stock</th>@endif
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transfer->items as $item)
                                <tr>
                                    <td data-label="Product">
                                        <div class="sl-table-name">
                                            @if ($item->product)
                                                <img class="stx-thumb" alt="" loading="lazy" src="{{ getImage(getFilePath('products') . '/' . $item->product->thumbnail, getFileSize('products')) }}">
                                            @endif
                                            <span>{{ $item->product_name }}<br><x-product.sku :value="$item->product?->sku" /></span>
                                        </div>
                                    </td>
                                    <td data-label="Quantity"><strong>{{ number_format($item->quantity) }}</strong></td>
                                    <td data-label="Price">{{ showAmount($item->unit_price) }}</td>
                                    @if ($isSeller && $pending)
                                        @php $have = (int) ($onHand[$item->product_id] ?? 0); @endphp
                                        <td data-label="You have">
                                            <span class="sl-status {{ $have >= $item->quantity ? 'sl-status-in-stock' : 'sl-status-out-of-stock' }}">{{ number_format($have) }}</span>
                                        </td>
                                    @endif
                                    @if ($completed)
                                        <td data-label="Your stock">
                                            @if ($isSeller)
                                                {{ number_format($item->seller_stock_before) }} <i class="las la-long-arrow-alt-right"></i> <strong>{{ number_format($item->seller_stock_after) }}</strong>
                                            @else
                                                {{ number_format($item->buyer_stock_before) }} <i class="las la-long-arrow-alt-right"></i> <strong>{{ number_format($item->buyer_stock_after) }}</strong>
                                            @endif
                                        </td>
                                    @endif
                                    <td data-label="Subtotal" class="text-end"><strong>{{ showAmount($item->line_total) }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="stx-total-row">
                                <td colspan="{{ 3 + ($isSeller && $pending ? 1 : 0) + ($completed ? 1 : 0) }}">Total</td>
                                <td class="text-end">{{ showAmount($transfer->total_amount) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            @if ($transfer->buyer_note || $transfer->decision_note)
                <div class="sl-card mb-4">
                    <div class="sl-card-header"><i class="las la-comment-alt"></i> Notes</div>
                    <div class="sl-card-body stx-notes">
                        @if ($transfer->buyer_note)
                            <p><strong>{{ $buyer->business_name }}:</strong> {{ $transfer->buyer_note }}</p>
                        @endif
                        @if ($transfer->decision_note)
                            <p><strong>{{ ucfirst($transfer->statusLabel()) }}:</strong> {{ $transfer->decision_note }}</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="sl-card mb-4">
                <div class="sl-card-header"><i class="las la-people-carry"></i> Stockists</div>
                <div class="sl-card-body">
                    <div class="stx-party">
                        <span class="stx-party__tag stx-party__tag--mega">Mega · sells</span>
                        <strong>{{ $seller->business_name }}</strong>
                        <small>{{ $seller->user?->fullname }} &middot; {{ $seller->business_phone ?: $seller->user?->mobile }}</small>
                    </div>
                    <div class="stx-party__arrow"><i class="las la-arrow-down"></i> {{ number_format($transfer->total_quantity) }} units</div>
                    <div class="stx-party">
                        <span class="stx-party__tag">Mini · buys</span>
                        <strong>{{ $buyer->business_name }}</strong>
                        <small>{{ $buyer->user?->fullname }} &middot; {{ $buyer->business_phone ?: $buyer->user?->mobile }}</small>
                    </div>
                </div>
            </div>

            <div class="sl-card mb-4">
                <div class="sl-card-header"><i class="las la-coins"></i> Money</div>
                <div class="sl-card-body">
                    <dl class="stx-totals">
                        @if ($isSeller)
                            <div><dt>Stock price</dt><dd>{{ showAmount($transfer->total_amount) }}</dd></div>
                            @if ($completed)
                                <div><dt>Paid to your stockist wallet</dt><dd class="text-success">+{{ showAmount($transfer->total_amount) }}</dd></div>
                                <div><dt>Stockist rebate earned</dt><dd class="text-success">+{{ showAmount($transfer->rebate_amount) }}</dd></div>
                                <div><dt>Wallet after</dt><dd>{{ showAmount($transfer->seller_balance_after) }}</dd></div>
                            @elseif ($pending)
                                <div><dt>Rebate if you confirm</dt><dd>≈ {{ showAmount($rebate) }}</dd></div>
                            @endif
                        @else
                            <div><dt>Paid from your stockist wallet</dt><dd>&minus;{{ showAmount($transfer->total_amount) }}</dd></div>
                            <div><dt>Wallet after paying</dt><dd>{{ showAmount($transfer->buyer_balance_after) }}</dd></div>
                            @if (!$pending && !$completed)
                                <div><dt>Refunded</dt><dd class="text-success">+{{ showAmount($transfer->total_amount) }}</dd></div>
                            @endif
                        @endif
                        <div class="stx-totals__ref"><dt>Reference</dt><dd>{{ $transfer->trx }}</dd></div>
                    </dl>
                </div>
            </div>

            @if ($pending)
                <div class="sl-card mb-4">
                    <div class="sl-card-header"><i class="las la-tasks"></i> What next</div>
                    <div class="sl-card-body">
                        @if ($isSeller)
                            <form action="{{ route('user.stockist.transfers.confirm', $transfer->id) }}" method="POST" class="stx-confirm"
                                  data-confirm="Move {{ number_format($transfer->total_quantity) }} units to {{ $buyer->business_name }} now? This can't be undone.">
                                @csrf
                                <button type="submit" class="sl-btn sl-btn-primary w-100 justify-content-center" @disabled($short->isNotEmpty())>
                                    <i class="las la-check-circle"></i> Confirm &amp; transfer stock
                                </button>
                            </form>
                            <details class="stx-decline mt-3">
                                <summary>Decline this request</summary>
                                <form action="{{ route('user.stockist.transfers.decline', $transfer->id) }}" method="POST" class="mt-2">
                                    @csrf
                                    <label class="sl-label" for="declineReason">Reason (sent to {{ $buyer->business_name }})</label>
                                    <textarea class="sl-input" id="declineReason" name="reason" rows="2" maxlength="500" required placeholder="e.g. Out of stock until next week"></textarea>
                                    <button type="submit" class="sl-btn sl-btn-outline stx-btn-danger w-100 justify-content-center mt-2">
                                        <i class="las la-times-circle"></i> Decline &amp; refund
                                    </button>
                                </form>
                            </details>
                        @else
                            <form action="{{ route('user.stockist.transfers.cancel', $transfer->id) }}" method="POST" class="stx-confirm"
                                  data-confirm="Cancel {{ $transfer->code }}? {{ showAmount($transfer->total_amount) }} goes back to your stockist wallet.">
                                @csrf
                                <button type="submit" class="sl-btn sl-btn-outline stx-btn-danger w-100 justify-content-center">
                                    <i class="las la-undo-alt"></i> Cancel request &amp; get refund
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            <div class="sl-card">
                <div class="sl-card-header"><i class="las la-stream"></i> Timeline</div>
                <div class="sl-card-body">
                    <div class="sl-timeline-item">
                        <div class="sl-timeline-icon stx-tl--blue"><i class="las la-paper-plane"></i></div>
                        <div>
                            <strong>Requested &amp; paid</strong>
                            <small class="d-block text-muted">{{ showDateTime($transfer->created_at, 'd M Y, h:i A') }}</small>
                        </div>
                    </div>
                    @if ($pending)
                        <div class="sl-timeline-item">
                            <div class="sl-timeline-icon stx-tl--amber"><i class="las la-hourglass-half"></i></div>
                            <div><strong>Waiting for {{ $seller->business_name }}</strong></div>
                        </div>
                    @else
                        <div class="sl-timeline-item">
                            <div @class(['sl-timeline-icon', 'stx-tl--green' => $completed, 'stx-tl--red' => !$completed])>
                                <i class="las la-{{ $completed ? 'check' : 'times' }}"></i>
                            </div>
                            <div>
                                <strong>{{ $transfer->statusLabel() }}{{ $transfer->closed_by === 'admin' ? ' by admin' : '' }}</strong>
                                <small class="d-block text-muted">{{ showDateTime($transfer->closedAt(), 'd M Y, h:i A') }}</small>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.stx-confirm').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        if (!window.confirm(form.dataset.confirm)) { event.preventDefault(); return; }
        form.querySelector('button[type="submit"]').disabled = true;
    });
});
</script>
@endsection

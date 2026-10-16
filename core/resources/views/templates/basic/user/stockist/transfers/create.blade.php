@extends($activeTemplate . 'layouts.master')

@section('content')
<div class="nc-wrap stx" id="ncWrap">
    <br>
    <div class="sl-page-header d-flex flex-wrap align-items-center gap-3 mb-4">
        <a href="{{ route('user.stockist.transfers.index') }}" class="sl-back-btn" aria-label="Back to stock requests"><i class="las la-arrow-left"></i></a>
        <div>
            <h4 class="sl-page-title mb-1">Buy stock from a Mega stockist</h4>
            <p class="sl-page-subtitle mb-0">Pay from your stockist wallet. The money is held until the Mega stockist confirms; if they decline, it comes straight back.</p>
        </div>
    </div>

    @if ($sellers->isEmpty())
        <div class="sl-card">
            <div class="sl-empty-state">
                <div class="sl-empty-icon"><i class="las la-store-alt"></i></div>
                <p class="sl-empty-title">No Mega stockist in your state yet</p>
                <p class="sl-empty-sub">Mini stockists restock only through a Mega stockist in their state. Ask admin to activate a Mega stockist in {{ $stockist->state_name }}; you can order as soon as there is one.</p>
            </div>
        </div>
    @else
        {{-- Step 1: who to buy from --}}
        <p class="stx-step"><span>1</span> Choose a Mega stockist in {{ $stockist->state_name }}</p>
        <div class="stx-sellers mb-4">
            @foreach ($sellers as $option)
                <a href="{{ route('user.stockist.transfers.create', ['seller' => $option->id]) }}"
                   @class(['stx-seller', 'is-selected' => $seller && $seller->id === $option->id])>
                    <span class="stx-seller__icon"><i class="las la-warehouse"></i></span>
                    <span class="stx-seller__body">
                        <strong>{{ $option->business_name ?: 'Mega stockist #' . $option->id }}</strong>
                        <small>{{ $option->user?->fullname }} @if ($option->business_phone ?: $option->user?->mobile) &middot; {{ $option->business_phone ?: $option->user?->mobile }} @endif</small>
                        <em>{{ number_format($option->products_in_stock) }} {{ \Illuminate\Support\Str::plural('product', $option->products_in_stock) }} in stock</em>
                    </span>
                    <i class="las la-check-circle stx-seller__tick"></i>
                </a>
            @endforeach
        </div>

        @if ($seller)
            {{-- Step 2: what to buy --}}
            <p class="stx-step"><span>2</span> Choose quantities from {{ $seller->business_name }}</p>

            @if ($catalogue->isEmpty())
                <div class="sl-card">
                    <div class="sl-empty-state">
                        <div class="sl-empty-icon"><i class="las la-box-open"></i></div>
                        <p class="sl-empty-title">Nothing to buy right now</p>
                        <p class="sl-empty-sub">{{ $seller->business_name }} has no stock with a price for your state. Try another Mega stockist or check back later.</p>
                    </div>
                </div>
            @else
                <form action="{{ route('user.stockist.transfers.store') }}" method="POST" id="stxForm" class="stx-buy"
                      data-wallet="{{ (float) $stockist->wallet }}">
                    @csrf
                    <input type="hidden" name="seller_id" value="{{ $seller->id }}">

                    <div class="sl-card stx-buy__list">
                        <div class="table-responsive">
                            <table class="sl-table" aria-label="Products">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>In stock</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($catalogue as $row)
                                        @php $old = (int) old('quantities.' . $row->product->id, 0); @endphp
                                        <tr class="stx-line" data-price="{{ $row->unit_price }}">
                                            <td data-label="Product">
                                                <div class="sl-table-name">
                                                    <img class="stx-thumb" alt="" loading="lazy"
                                                         src="{{ getImage(getFilePath('products') . '/' . $row->product->thumbnail, getFileSize('products')) }}">
                                                    <span>{{ $row->product->name }}<br><x-product.sku :value="$row->product->sku" /></span>
                                                </div>
                                            </td>
                                            <td data-label="In stock"><span class="sl-status sl-status-in-stock">{{ number_format($row->available) }}</span></td>
                                            <td data-label="Price"><x-product.price :amount="$row->unit_price" /></td>
                                            <td data-label="Quantity">
                                                <div class="sl-qty-stepper stx-qty">
                                                    <button type="button" class="sl-qty-btn" data-step="-1" aria-label="Less">&minus;</button>
                                                    <input type="number" class="sl-qty-input" name="quantities[{{ $row->product->id }}]"
                                                           value="{{ $old }}" min="0" max="{{ $row->available }}" inputmode="numeric"
                                                           aria-label="Quantity of {{ $row->product->name }}">
                                                    <button type="button" class="sl-qty-btn" data-step="1" aria-label="More">+</button>
                                                </div>
                                            </td>
                                            <td data-label="Subtotal" class="text-end"><strong class="stx-subtotal">{{ showAmount(0) }}</strong></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <aside class="sl-card stx-summary">
                        <div class="sl-card-header"><i class="las la-receipt"></i> Summary</div>
                        <div class="sl-card-body">
                            <dl class="stx-totals">
                                <div><dt>Products</dt><dd id="stxLines">0</dd></div>
                                <div><dt>Units</dt><dd id="stxUnits">0</dd></div>
                                <div class="stx-totals__grand"><dt>Total</dt><dd id="stxTotal">{{ showAmount(0) }}</dd></div>
                                <div><dt>Stockist wallet</dt><dd>{{ showAmount($stockist->wallet) }}</dd></div>
                                <div><dt>After this request</dt><dd id="stxAfter">{{ showAmount($stockist->wallet) }}</dd></div>
                            </dl>
                            <p class="sl-alert-warning stx-short" id="stxShort" hidden>
                                <i class="las la-exclamation-triangle"></i> Your wallet doesn't cover this. Lower the quantities or ask admin to top up your stockist wallet.
                            </p>

                            <label class="sl-label" for="stxNote">Note to {{ $seller->business_name }} <small class="text-muted">optional</small></label>
                            <textarea class="sl-input stx-note" id="stxNote" name="note" rows="2" maxlength="500" placeholder="e.g. I'll pick up on Friday morning">{{ old('note') }}</textarea>

                            <button type="submit" class="sl-btn sl-btn-primary w-100 justify-content-center mt-3" id="stxSubmit" disabled>
                                <i class="las la-paper-plane"></i> Pay &amp; send request
                            </button>
                            <p class="stx-fineprint">Prices are the {{ $stockist->state_name }} stockist prices. You can cancel any time before the Mega stockist confirms.</p>
                        </div>
                    </aside>
                </form>
            @endif
        @endif
    @endif
</div>

@if ($seller && $catalogue->isNotEmpty())
<script>
(function () {
    'use strict';
    const form   = document.getElementById('stxForm');
    const wallet = parseFloat(form.dataset.wallet) || 0;
    const naira  = new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', minimumFractionDigits: 2 });
    const money  = (n) => naira.format(n).replace('NGN', '₦');

    function clamp(input) {
        const max = parseInt(input.max, 10) || 0;
        let value = parseInt(input.value, 10);
        if (isNaN(value) || value < 0) value = 0;
        if (value > max) value = max;
        input.value = value;
        return value;
    }

    // Display only: the server prices the request again from the state price
    function update() {
        let lines = 0, units = 0, total = 0;
        form.querySelectorAll('.stx-line').forEach((row) => {
            const qty = clamp(row.querySelector('.sl-qty-input'));
            const sub = qty * parseFloat(row.dataset.price);
            row.querySelector('.stx-subtotal').textContent = money(sub);
            row.classList.toggle('is-picked', qty > 0);
            if (qty > 0) { lines++; units += qty; total += sub; }
        });

        document.getElementById('stxLines').textContent = lines;
        document.getElementById('stxUnits').textContent = units.toLocaleString();
        document.getElementById('stxTotal').textContent = money(total);
        document.getElementById('stxAfter').textContent = money(wallet - total);

        const short = total > wallet + 0.004;
        document.getElementById('stxShort').hidden = !short;
        document.getElementById('stxAfter').classList.toggle('is-negative', short);
        document.getElementById('stxSubmit').disabled = units === 0 || short;
    }

    form.addEventListener('click', (event) => {
        const button = event.target.closest('[data-step]');
        if (!button) return;
        const input = button.parentElement.querySelector('.sl-qty-input');
        input.value = (parseInt(input.value, 10) || 0) + parseInt(button.dataset.step, 10);
        update();
    });
    form.addEventListener('input', update);
    form.addEventListener('submit', () => { document.getElementById('stxSubmit').disabled = true; });
    update();
})();
</script>
@endif
@endsection

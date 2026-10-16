@extends($activeTemplate . 'layouts.master')
@section('content')

<div class="pos-wrap" id="posWrap">

    {{-- ══════════════════════════════════════════
         LEFT — Product Catalogue
    ══════════════════════════════════════════ --}}
    <div class="pos-left" id="posLeft">

        {{-- Toolbar --}}
        <div class="pos-toolbar">
            <div class="pos-toolbar-brand">
                <div class="pos-toolbar-icon"><i class="las la-cash-register"></i></div>
                <div>
                    <p class="pos-toolbar-title">Point of Sale</p>
                    <p class="pos-toolbar-sub">{{ $stockist->business_name ?? auth()->user()->fullname }}</p>
                </div>
            </div>
            <div class="pos-search-wrap">
                <i class="las la-search pos-search-icon"></i>
                <input type="text" id="posSearch" class="pos-search-input" placeholder="Search products…">
            </div>
            <div class="pos-toolbar-meta">
                <span class="pos-meta-pill"><i class="las la-boxes"></i> <span id="productCount">{{ $inventory->count() }}</span> items</span>
                <span class="pos-meta-pill green"><i class="las la-wallet"></i> {{ showAmount($stockist->wallet) }}</span>
            </div>
        </div>

        {{-- Product Grid --}}
        <div class="pos-grid-wrap" id="posGridWrap">
            @forelse($inventory as $item)
            <div class="pos-card" data-id="{{ $item->product_id }}"
                 data-name="{{ $item->product->name }}"
                 data-sku="{{ $item->product->sku }}"
                 data-price="{{ $item->unit_price }}"
                 data-stock="{{ $item->quantity }}"
                 data-search="{{ strtolower($item->product->name) }}">

                {{-- Stock ribbon --}}
                <div class="pos-card-stock {{ $item->quantity <= 5 ? 'pos-card-stock--low' : '' }}">
                    {{ $item->quantity }} left
                </div>

                {{-- Product visual --}}
                <div class="pos-card-img-wrap">
                    @if($item->product->thumbnail)
                        <img src="{{ getImage(getFilePath('products') . '/' . $item->product->thumbnail, getFilePath('products')) }}"
                             alt="{{ $item->product->name }}" class="pos-card-img">
                    @else
                        <div class="pos-card-img-placeholder">
                            <i class="las la-box-open"></i>
                        </div>
                    @endif
                </div>

                {{-- Info --}}
                <div class="pos-card-body">
                    <p class="pos-card-name">{{ $item->product->name }}</p>
                    <div class="pp-meta my-1"><x-product.sku :value="$item->product->sku" /></div>
                    <p class="pos-card-price"><x-product.price :amount="$item->unit_price" /></p>
                </div>

                {{-- Add button --}}
                <button type="button" class="pos-card-add" onclick="POS.add({{ $item->product_id }})">
                    <i class="las la-plus"></i>
                </button>
            </div>
            @empty
            <div class="pos-empty-state">
                <div class="pos-empty-icon"><i class="las la-box-open"></i></div>
                <p class="pos-empty-title">No inventory available</p>
                @if ($stockist->isMega())
                    <p class="pos-empty-sub">Your stock is empty. Order products from the company to begin selling.</p>
                @else
                    <p class="pos-empty-sub">Your stock is empty. <a href="{{ route('user.stockist.transfers.create') }}">Buy from a Mega stockist</a> to begin selling.</p>
                @endif
            </div>
            @endforelse
        </div>

    </div>

    {{-- ══════════════════════════════════════════
         RIGHT — Cart & Checkout
    ══════════════════════════════════════════ --}}
    <div class="pos-right" id="posRight">

        {{-- Cart Header --}}
        <div class="pos-cart-header">
            <div class="pos-cart-header-left">
                <span class="pos-cart-header-icon"><i class="las la-shopping-basket"></i></span>
                <span class="pos-cart-header-label">Current Order</span>
            </div>
            <button type="button" class="pos-cart-clear-btn" id="clearCartBtn" title="Clear order">
                <i class="las la-trash-alt"></i>
            </button>
        </div>

        {{-- Cart Items (scrollable) --}}
        <div class="pos-cart-list" id="cartList">
            <div class="pos-cart-empty" id="cartEmpty">
                <div class="pos-cart-empty-icon"><i class="las la-shopping-basket"></i></div>
                <p>Add products from the left panel to begin an order</p>
            </div>
        </div>

        {{-- Cart Footer --}}
        <div class="pos-cart-footer">

            {{-- Totals --}}
            <div class="pos-totals">
                <div class="pos-totals-row">
                    <span>Items</span>
                    <span id="totalQty">0</span>
                </div>
                <div class="pos-totals-row pos-totals-row--total">
                    <span>Total</span>
                    <span id="totalAmount">{{ showAmount(0) }}</span>
                </div>
            </div>

            {{-- Payment tabs --}}
            <div class="pos-pay-tabs">
                <button type="button" class="pos-pay-tab active" id="tabCash" onclick="POS.switchTab('cash')">
                    <i class="las la-money-bill-wave"></i> Cash
                </button>
                <button type="button" class="pos-pay-tab" id="tabCode" onclick="POS.switchTab('welcome_pack')">
                    <i class="las la-gift"></i> Welcome Code
                </button>
            </div>

            {{-- Cash panel --}}
            <div class="pos-pay-panel" id="panelCash">
                <button type="button" class="pos-checkout-btn pos-checkout-btn--cash" id="cashCheckoutBtn" disabled>
                    <i class="las la-check-circle"></i>
                    <span id="cashBtnText">Select products to checkout</span>
                </button>
                <p class="pos-pay-note"><i class="las la-info-circle"></i> Collect cash from customer and press to confirm</p>
            </div>

            {{-- Welcome Code panel --}}
            <div class="pos-pay-panel d-none" id="panelCode">
                <div class="pos-code-entry" id="codeEntrySection">
                    <div class="pos-code-field-wrap">
                        <input type="text" id="welcomeCodeInput" class="pos-code-input"
                               maxlength="10" placeholder="Enter 10-char code"
                               autocomplete="off" spellcheck="false" inputmode="text">
                        <button type="button" class="pos-code-verify-btn" id="verifyCodeBtn">
                            <i class="las la-search-plus"></i>
                        </button>
                    </div>
                    <p class="pos-pay-note"><i class="las la-info-circle"></i> Ask customer for their welcome package code</p>
                </div>

                {{-- Verified package info (hidden until verified) --}}
                <div class="pos-code-verified d-none" id="codeVerifiedSection">
                    <div class="pos-verified-badge">
                        <i class="las la-check-circle"></i>
                        <div>
                            <p class="pos-verified-name" id="verifiedCustomerName">—</p>
                            <p class="pos-verified-meta" id="verifiedPackageValue">—</p>
                        </div>
                        <button type="button" class="pos-verified-clear" id="clearVerifiedBtn" title="Use different code">
                            <i class="las la-times"></i>
                        </button>
                    </div>
                    <button type="button" class="pos-checkout-btn pos-checkout-btn--code" id="codeCheckoutBtn" disabled>
                        <i class="las la-gift"></i>
                        <span id="codeBtnText">Confirm & Charge Package</span>
                    </button>
                </div>
            </div>

        </div>
    </div>

</div>

{{-- ══════════════════════════════════════════
     SUCCESS RECEIPT OVERLAY
══════════════════════════════════════════ --}}
<div class="pos-receipt-overlay d-none" id="receiptOverlay">
    <div class="pos-receipt-card">
        <div class="pos-receipt-icon-wrap">
            <div class="pos-receipt-icon"><i class="las la-check-double"></i></div>
            <div class="pos-receipt-rings">
                <div class="pos-receipt-ring pos-receipt-ring-1"></div>
                <div class="pos-receipt-ring pos-receipt-ring-2"></div>
            </div>
        </div>
        <h4 class="pos-receipt-title">Sale Complete!</h4>
        <p class="pos-receipt-trx" id="receiptTrx">TRX: —</p>
        <div class="pos-receipt-detail-grid" id="receiptGrid"></div>
        <button type="button" class="pos-receipt-close-btn" id="receiptCloseBtn">
            <i class="las la-plus-circle"></i> New Order
        </button>
    </div>
</div>

@endsection

@push('script')
<script>
/* ══════════════════════════════════════════════════════════════════
   POS ENGINE  (jQuery)
══════════════════════════════════════════════════════════════════ */
const POS = (function ($) {

    /* ── State ── */
    let cart         = [];    // [{productId, name, price, qty, stock}]
    let activeTab    = 'cash';
    let verifiedCode = null;
    let verifiedPkg  = null;

    /* ── Cached jQuery DOM refs (populated in init) ── */
    let $cartList, $cartEmpty, $totalQty, $totalAmount;
    let $cashCheckoutBtn, $cashBtnText;
    let $codeCheckoutBtn, $codeBtnText;
    let $welcomeInput, $verifyCodeBtn;
    let $codeEntrySection, $codeVerifiedSection;
    let $verifiedName, $verifiedValue;
    let $receiptOverlay, $receiptTrx, $receiptGrid;

    /* ── Currency formatter ── */
    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function fmt(n) {
        return '₦' + Number(n).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    /* ── Cart aggregates ── */
    function totalQty()    { return cart.reduce(function (s, i) { return s + i.qty; }, 0); }
    function totalAmount() { return cart.reduce(function (s, i) { return s + (i.price * i.qty); }, 0); }

    /* ── Render the cart list ── */
    function renderCart() {
        var isEmpty = cart.length === 0;
        $cartEmpty.toggleClass('d-none', !isEmpty);

        /* Remove stale item rows */
        $cartList.find('.pos-cart-item').remove();

        $.each(cart, function (_, item) {
            var $row = $('<div>')
                .addClass('pos-cart-item')
                .attr('data-id', item.productId)
                .html(
                    '<div class="pos-ci-info">' +
                        '<p class="pos-ci-name">'  + esc(item.name) + '</p>' +
                        '<p class="pos-ci-price">' + (item.sku ? '<span class="pp-sku"><span class="pp-sku__label">SKU</span>' + esc(item.sku) + '</span> ' : '') + fmt(item.price) + ' each</p>' +
                    '</div>' +
                    '<div class="pos-ci-controls">' +
                        '<button type="button" class="pos-ci-btn" onclick="POS.decrement(' + item.productId + ')"><i class="las la-minus"></i></button>' +
                        '<span class="pos-ci-qty">' + item.qty + '</span>' +
                        '<button type="button" class="pos-ci-btn" onclick="POS.increment(' + item.productId + ')"><i class="las la-plus"></i></button>' +
                    '</div>' +
                    '<div class="pos-ci-right">' +
                        '<p class="pos-ci-subtotal">' + fmt(item.price * item.qty) + '</p>' +
                        '<button type="button" class="pos-ci-remove" onclick="POS.remove(' + item.productId + ')"><i class="las la-times"></i></button>' +
                    '</div>'
                );
            $row.insertBefore($cartEmpty);
        });

        /* Totals */
        $totalQty.text(totalQty());
        $totalAmount.text(fmt(totalAmount()));

        updateCheckoutState();
    }

    /* ── Sync button enabled/label states ── */
    function updateCheckoutState() {
        var hasItems = cart.length > 0;
        var amt      = totalAmount();

        $cashCheckoutBtn.prop('disabled', !hasItems);
        $cashBtnText.text(hasItems ? 'Process Cash Sale — ' + fmt(amt) : 'Select products to checkout');

        var codeReady = hasItems && verifiedCode !== null;
        $codeCheckoutBtn.prop('disabled', !codeReady);
        $codeBtnText.text(
            codeReady   ? 'Charge Package — ' + fmt(amt) :
            verifiedCode ? 'Confirm & Charge Package'     : 'Verify a code first'
        );
    }

    /* ── Public: add a product to the cart ── */
    function add(productId) {
        var $card    = $('.pos-card[data-id="' + productId + '"]');
        var stock    = parseInt($card.attr('data-stock'), 10);
        var existing = null;
        $.each(cart, function (_, i) { if (i.productId === productId) { existing = i; return false; } });

        if (existing) {
            if (existing.qty >= stock) { pulseCard($card, 'error'); return; }
            existing.qty++;
        } else {
            if (stock <= 0) { pulseCard($card, 'error'); return; }
            cart.push({
                productId : productId,
                name      : $card.attr('data-name'),
                sku       : $card.attr('data-sku'),
                price     : parseFloat($card.attr('data-price')),
                qty       : 1,
                stock     : stock
            });
        }
        pulseCard($card, 'success');
        renderCart();
    }

    /* ── Public: increase qty of an item already in cart ── */
    function increment(productId) {
        $.each(cart, function (_, item) {
            if (item.productId === productId) {
                if (item.qty < item.stock) { item.qty++; }
                return false;
            }
        });
        renderCart();
    }

    /* ── Public: decrease qty; removes item when it hits zero ── */
    function decrement(productId) {
        $.each(cart, function (_, item) {
            if (item.productId === productId) {
                item.qty--;
                return false;
            }
        });
        cart = $.grep(cart, function (i) { return i.qty > 0; });
        renderCart();
    }

    /* ── Public: remove a product entirely from the cart ── */
    function remove(productId) {
        cart = $.grep(cart, function (i) { return i.productId !== productId; });
        renderCart();
    }

    /* ── Public: wipe the entire cart ── */
    function clear() {
        cart = [];
        resetVerification();
        renderCart();
    }

    /* ── CSS-class pulse on product card ── */
    function pulseCard($card, type) {
        $card.addClass('pos-card--' + type);
        setTimeout(function () { $card.removeClass('pos-card--' + type); }, 400);
    }

    /* ── Public: switch the payment tab ── */
    function switchTab(tab) {
        activeTab = tab;
        $('#tabCash').toggleClass('active', tab === 'cash');
        $('#tabCode').toggleClass('active', tab === 'welcome_pack');
        $('#panelCash').toggleClass('d-none', tab !== 'cash');
        $('#panelCode').toggleClass('d-none', tab !== 'welcome_pack');
    }

    /* ── Reset the welcome-code verification state ── */
    function resetVerification() {
        verifiedCode = null;
        verifiedPkg  = null;
        $welcomeInput.val('').prop('disabled', false);
        $verifyCodeBtn.prop('disabled', false);
        $codeEntrySection.removeClass('d-none');
        $codeVerifiedSection.addClass('d-none');
    }

    /* ── Call /welcome-pack/verify to validate a code ── */
    function verifyCode() {
        var code = $welcomeInput.val().replace(/[^A-Za-z0-9]/g, '').toUpperCase();
        if (code.length !== 10) { shake($welcomeInput); return; }

        $verifyCodeBtn.prop('disabled', true).html('<i class="las la-spinner la-spin"></i>');

        $.ajax({
            url    : "{{ route('user.stockist.welcome-pack.verify') }}",
            method : 'POST',
            data   : { code: code, _token: "{{ csrf_token() }}" },
            success: function (res) {
                $verifyCodeBtn.prop('disabled', false).html('<i class="las la-search-plus"></i>');

                if (res.success) {
                    verifiedCode = code;
                    verifiedPkg  = res.package;
                    $verifiedName.text((res.package.fullname || '—') + '  (@' + res.package.username + ')');
                    $verifiedValue.text('Package value: ' + res.package.amount);
                    $codeEntrySection.addClass('d-none');
                    $codeVerifiedSection.removeClass('d-none');
                    updateCheckoutState();
                } else {
                    shake($welcomeInput);
                    flashError(res.message || 'Invalid or already redeemed code.');
                }
            },
            error: function () {
                $verifyCodeBtn.prop('disabled', false).html('<i class="las la-search-plus"></i>');
                flashError('Verification failed. Check your connection and try again.');
            }
        });
    }

    /* ── POST checkout to the server ── */
    function checkout(method) {
        if (cart.length === 0) return;
        if (method === 'welcome_pack' && !verifiedCode) return;

        var $btn  = method === 'cash' ? $cashCheckoutBtn : $codeCheckoutBtn;
        var $txt  = method === 'cash' ? $cashBtnText     : $codeBtnText;
        var orig  = $txt.text();

        $btn.prop('disabled', true);
        $txt.text('Processing…');

        var payload = {
            payment_method : method,
            _token         : "{{ csrf_token() }}"
        };
        $.each(cart, function (idx, item) {
            payload['items[' + idx + '][product_id]'] = item.productId;
            payload['items[' + idx + '][quantity]']   = item.qty;
        });
        if (method === 'welcome_pack') { payload.welcome_code = verifiedCode; }

        $.ajax({
            url    : "{{ route('user.stockist.pos.checkout') }}",
            method : 'POST',
            data   : payload,
            success: function (res) {
                $btn.prop('disabled', false);
                $txt.text(orig);
                res.success ? showReceipt(res, method) : flashError(res.message);
            },
            error: function (xhr) {
                $btn.prop('disabled', false);
                $txt.text(orig);
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'An error occurred. Please try again.';
                flashError(msg);
            }
        });
    }

    /* ── Render the success receipt overlay ── */
    function showReceipt(res, method) {
        var payLabel   = method === 'cash' ? 'Cash Payment' : 'Welcome Package';
        var creditNote = res.wallet_credited
            ? '<div class="pos-receipt-credit"><i class="las la-wallet"></i> ₦' + res.wallet_credited + ' credited to stockist rebate</div>'
            : '';

        $receiptTrx.text('TRX: ' + res.trx);
        $receiptGrid.html(
            '<div class="pos-rd-row"><span>Items Sold</span><strong>' + res.items_count      + '</strong></div>' +
            '<div class="pos-rd-row"><span>Sale Total</span><strong>₦' + res.total_formatted + '</strong></div>' +
            '<div class="pos-rd-row"><span>Payment</span><strong>'    + payLabel             + '</strong></div>' +
            creditNote
        );
        $receiptOverlay.removeClass('d-none');

        /* Decrement stock counters on the visible product cards */
        $.each(cart, function (_, item) {
            var $card = $('.pos-card[data-id="' + item.productId + '"]');
            if ($card.length) {
                var newStock = parseInt($card.attr('data-stock'), 10) - item.qty;
                newStock = Math.max(0, newStock);
                $card.attr('data-stock', newStock);
                $card.find('.pos-card-stock')
                    .text(newStock + ' left')
                    .toggleClass('pos-card-stock--low', newStock <= 5);
                if (newStock <= 0) { $card.addClass('pos-card--out'); }
            }
        });

        /* Reset for next order */
        cart = [];
        resetVerification();
        renderCart();
    }

    /* ── Shake animation (expects a jQuery object) ── */
    function shake($el) {
        $el.addClass('pos-shake');
        setTimeout(function () { $el.removeClass('pos-shake'); }, 500);
    }

    /* ── Slide-in error toast ── */
    function flashError(msg) {
        var $toast = $('<div>')
            .addClass('pos-toast pos-toast--error')
            .html('<i class="las la-exclamation-circle"></i> ' + msg);
        $('body').append($toast);
        setTimeout(function () { $toast.addClass('pos-toast--show'); }, 10);
        setTimeout(function () {
            $toast.removeClass('pos-toast--show');
            setTimeout(function () { $toast.remove(); }, 350);
        }, 4000);
    }

    /* ── Wire up all DOM events ── */
    function init() {
        $cartList            = $('#cartList');
        $cartEmpty           = $('#cartEmpty');
        $totalQty            = $('#totalQty');
        $totalAmount         = $('#totalAmount');
        $cashCheckoutBtn     = $('#cashCheckoutBtn');
        $cashBtnText         = $('#cashBtnText');
        $codeCheckoutBtn     = $('#codeCheckoutBtn');
        $codeBtnText         = $('#codeBtnText');
        $welcomeInput        = $('#welcomeCodeInput');
        $verifyCodeBtn       = $('#verifyCodeBtn');
        $codeEntrySection    = $('#codeEntrySection');
        $codeVerifiedSection = $('#codeVerifiedSection');
        $verifiedName        = $('#verifiedCustomerName');
        $verifiedValue       = $('#verifiedPackageValue');
        $receiptOverlay      = $('#receiptOverlay');
        $receiptTrx          = $('#receiptTrx');
        $receiptGrid         = $('#receiptGrid');

        /* Verify button & Enter key */
        $verifyCodeBtn.on('click', verifyCode);
        $welcomeInput.on('keydown', function (e) {
            if (e.key === 'Enter') { verifyCode(); }
        });

        /* Force uppercase, strip non-alphanumeric */
        $welcomeInput.on('input', function () {
            $(this).val($(this).val().replace(/[^A-Za-z0-9]/g, '').toUpperCase());
        });

        /* Clear cart */
        $('#clearCartBtn').on('click', function () {
            if (cart.length) { clear(); }
        });

        /* Checkout actions */
        $cashCheckoutBtn.on('click', function () {
            if (!$(this).prop('disabled')) { checkout('cash'); }
        });
        $codeCheckoutBtn.on('click', function () {
            if (!$(this).prop('disabled')) { checkout('welcome_pack'); }
        });

        /* Reset verified code */
        $('#clearVerifiedBtn').on('click', resetVerification);

        /* Close receipt */
        $('#receiptCloseBtn').on('click', function () {
            $receiptOverlay.addClass('d-none');
        });

        /* Product search */
        $('#posSearch').on('input', function () {
            var q       = $(this).val().toLowerCase();
            var visible = 0;
            $('.pos-card').each(function () {
                var match = $(this).attr('data-search').indexOf(q) !== -1;
                $(this).css('display', match ? '' : 'none');
                if (match) { visible++; }
            });
            $('#productCount').text(visible);
        });

        renderCart();
    }

    /* ── Public API ── */
    return { init: init, add: add, increment: increment, decrement: decrement, remove: remove, clear: clear, switchTab: switchTab };

}(jQuery));

$(document).ready(function () { POS.init(); });
</script>
@endpush

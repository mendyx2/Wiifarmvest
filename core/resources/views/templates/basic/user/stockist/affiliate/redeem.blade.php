@extends($activeTemplate . 'layouts.master')

@section('content')
<div class="nc-wrap" id="ncWrap">
    <br>
    <div class="sl-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="sl-page-title mb-1">Redeem Affiliate Products</h4>
            <p class="sl-page-subtitle mb-0">Enter a buyer's redemption code to hand over their /shop order.</p>
        </div>
    </div>
 
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="aff-stat-card">
                <label class="shop-form-label">Redemption Code</label>
                <div class="input-group mb-3">
                    <input type="text" id="orderCodeInput" class="form-control text-uppercase" placeholder="AF-XXXXXXXX">
                    <button class="btn btn-success" type="button" id="verifyBtn">Verify</button>
                </div>
                <div id="verifyAlert"></div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="aff-stat-card" id="orderPanel" style="display:none;">
                <h6 class="fw-bold mb-3">Order Details</h6>
                <table class="table table-sm">
                    <tbody>
                        <tr><td class="text-muted">Code</td><td id="oCode" class="fw-bold"></td></tr>
                        <tr><td class="text-muted">Buyer</td><td id="oBuyer"></td></tr>
                        <tr><td class="text-muted">Payment</td><td id="oPayment"></td></tr>
                        <tr><td class="text-muted">Total</td><td id="oTotal" class="fw-bold"></td></tr>
                    </tbody>
                </table>
                <table class="table table-sm" id="oItemsTable">
                    <thead><tr><th>Product</th><th>Qty</th><th>Line Total</th></tr></thead>
                    <tbody id="oItemsBody"></tbody>
                </table>
                <button type="button" class="shop-buy-btn" id="confirmBtn">
                    <i class="las la-check"></i> Confirm Pickup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';
    var CSRF_TOKEN = "{{ csrf_token() }}";
    var currentCode = null;

    function post(url, data) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            body: JSON.stringify(data)
        }).then(function (r) { return r.json(); });
    }

    function alertBox(message, type) {
        document.getElementById('verifyAlert').innerHTML =
            '<div class="alert alert-' + type + ' py-2 px-3 small mb-0">' + message + '</div>';
    }

    document.getElementById('verifyBtn').addEventListener('click', function () {
        var code = document.getElementById('orderCodeInput').value.trim();
        if (!code) return;

        post("{{ route('user.stockist.affiliate.verify') }}", { order_code: code }).then(function (res) {
            if (!res.success) {
                alertBox(res.message, 'danger');
                document.getElementById('orderPanel').style.display = 'none';
                return;
            }

            currentCode = res.order.order_code;
            document.getElementById('verifyAlert').innerHTML = '';
            document.getElementById('oCode').textContent = res.order.order_code;
            document.getElementById('oBuyer').textContent = res.order.buyer_name;
            document.getElementById('oPayment').textContent = res.order.payment_method === 'paystack' ? 'Paid Online' : 'Cash on Pickup';
            document.getElementById('oTotal').textContent = res.order.total_display || res.order.total_amount;

            var body = document.getElementById('oItemsBody');
            body.innerHTML = '';
            res.order.items.forEach(function (item) {
                // Built from text nodes: names and SKUs come from the order, never as HTML
                var tr = document.createElement('tr');
                var name = document.createElement('td');
                name.textContent = item.product_name;
                if (item.sku) {
                    var sku = document.createElement('span');
                    sku.className = 'pp-sku ms-1';
                    sku.innerHTML = '<span class="pp-sku__label">SKU</span>';
                    sku.appendChild(document.createTextNode(item.sku));
                    name.appendChild(sku);
                }
                var qty = document.createElement('td');
                qty.textContent = item.quantity;
                var total = document.createElement('td');
                total.textContent = item.line_display || item.line_total;
                tr.append(name, qty, total);
                body.appendChild(tr);
            });

            document.getElementById('orderPanel').style.display = 'block';
        });
    });

    document.getElementById('confirmBtn').addEventListener('click', function () {
        if (!currentCode) return;
        var btn = this;
        btn.disabled = true;

        post("{{ route('user.stockist.affiliate.confirm') }}", { order_code: currentCode }).then(function (res) {
            btn.disabled = false;
            if (res.success) {
                alertBox(res.message, 'success');
                document.getElementById('orderPanel').style.display = 'none';
                document.getElementById('orderCodeInput').value = '';
                currentCode = null;
            } else {
                alertBox(res.message, 'danger');
            }
        });
    });
})();
</script>
@endsection

<script>
(function () {
    'use strict';
    var CSRF_TOKEN = "{{ csrf_token() }}";

    function toast(message, isError) {
        var el = document.createElement('div');
        el.textContent = message;
        el.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:99999;padding:12px 20px;border-radius:10px;' +
            'color:#fff;font-size:.85rem;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,.18);' +
            'background:' + (isError ? '#DC2626' : '#0D5C2E') + ';';
        document.body.appendChild(el);
        setTimeout(function () { el.remove(); }, 2600);
    }

    function post(url, data) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            body: JSON.stringify(data)
        }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); });
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-add-to-cart]');
        if (!btn || btn.disabled) return;

        btn.disabled = true;
        post("{{ route('shop.cart.add') }}", {
            product_id: btn.getAttribute('data-product-id'),
            quantity: btn.getAttribute('data-quantity') || 1
        }).then(function (res) {
            btn.disabled = false;
            if (res.body.success) {
                toast(res.body.message);
                var badge = document.getElementById('shopHeaderCartCount');
                if (badge) badge.textContent = res.body.count;
            } else {
                toast(res.body.message || 'Could not add to cart.', true);
            }
        }).catch(function () {
            btn.disabled = false;
            toast('Something went wrong. Please try again.', true);
        });
    });
})();
</script>

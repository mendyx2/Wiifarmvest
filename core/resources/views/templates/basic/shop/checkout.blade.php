@extends($activeTemplate . 'layouts.shop')
@section('content')

<div class="shop-wrap">
    <div class="shop-container">

        <a href="{{ route('shop.cart') }}" class="shop-back-link"><i class="las la-arrow-left"></i> Back to Cart</a>
        <h2 class="shop-related-heading mt-0">Checkout</h2>

        @if(session('error'))
            <div class="shop-alert shop-alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="shop-alert shop-alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="shop-checkout-grid">
            <form action="{{ route('shop.checkout.store') }}" method="POST" class="shop-form-card">
                @csrf

                {{-- Honeypot: hidden from real users, bots tend to fill every field --}}
                <div class="shop-honeypot" aria-hidden="true">
                    <label for="website">Website</label>
                    <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="shop-field">
                    <label class="shop-form-label">Full Name</label>
                    <input type="text" name="name" class="shop-input" value="{{ old('name') }}" required>
                </div>

                <div class="shop-form-row">
                    <div class="shop-field">
                        <label class="shop-form-label">Email Address</label>
                        <input type="email" name="email" class="shop-input" value="{{ old('email') }}" required>
                    </div>
                    <div class="shop-field">
                        <label class="shop-form-label">Phone Number (optional)</label>
                        <input type="text" name="phone" class="shop-input" value="{{ old('phone') }}">
                    </div>
                </div>

                <div class="shop-field">
                    <label class="shop-form-label">Delivery / Pickup State</label>
                    <select name="state_id" class="shop-input" required>
                        <option value="">Select your state</option>
                        @foreach($states as $state)
                            <option value="{{ $state->id }}" {{ old('state_id') == $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
                        @endforeach
                    </select>
                </div>

                <label class="shop-form-label">Payment Method</label>
                <div class="shop-pay-options">
                    <label class="shop-pay-option">
                        <input type="radio" name="payment_method" value="paystack" checked>
                        <i class="las la-credit-card"></i>
                        <div>
                            <div class="fw-bold small">Pay Online</div>
                            <div class="text-muted" style="font-size:.72rem;">Card, bank, USSD via Paystack</div>
                        </div>
                    </label>
                    <label class="shop-pay-option {{ $settings->cash_on_pickup_enabled ? '' : 'disabled' }}">
                        <input type="radio" name="payment_method" value="cash_on_pickup" {{ $settings->cash_on_pickup_enabled ? '' : 'disabled' }}>
                        <i class="las la-hand-holding-usd"></i>
                        <div>
                            <div class="fw-bold small">Cash on Pickup</div>
                            <div class="text-muted" style="font-size:.72rem;">
                                {{ $settings->cash_on_pickup_enabled ? 'Pay when you collect your order' : 'Currently unavailable' }}
                            </div>
                        </div>
                    </label>
                </div>

                <button type="submit" class="shop-buy-btn w-100 mt-3">
                    <i class="las la-lock"></i> Place Order
                </button>
            </form>

            <div class="shop-summary">
                <p class="shop-summary-heading">Order Summary</p>
                @foreach($resolved['lines'] as $line)
                    <div class="shop-summary-row">
                        <span>{{ $line['product']->name }} &times; {{ $line['quantity'] }} <x-product.sku :value="$line['product']->sku" class="ms-1" /></span>
                        <span>{{ showAmount($line['line_total']) }}</span>
                    </div>
                @endforeach
                <div class="shop-summary-total"><span>Estimated Total</span><span>{{ showAmount($resolved['subtotal']) }} <small class="shop-cur">{{ gs('cur_text') }}</small></span></div>
                <p class="text-muted small mt-2 mb-0">Final total is calculated for your selected state at checkout.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@php
    $shopFooterCategories = \App\Models\Category::active()->hasActiveProduct()->limit(6)->get();
    $shopFooterSocials    = getContent('social_icon.element');
@endphp

<footer class="shopft">
    <div class="shopft-main">
        <div class="shophd-container shopft-grid">

            <div class="shopft-col shopft-brand-col">
                <a href="{{ route('shop.index') }}" class="shopft-logo">
                    <img src="{{ siteLogo('dark') }}" alt="{{ gs('site_name') }}">
                </a>
                <p class="shopft-tagline">Quality products, delivered or ready for pickup near you.</p>
                @if($shopFooterSocials->count())
                    <div class="shopft-socials">
                        @foreach($shopFooterSocials as $social)
                            <a href="{{ @$social->data_values->url }}" title="{{ @$social->data_values->title }}" target="_blank" rel="noopener noreferrer" class="shopft-social-btn">
                                @php echo @$social->data_values->social_icon; @endphp
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="shopft-col">
                <h6 class="shopft-heading">Shop</h6>
                <a href="{{ route('shop.index') }}">All Products</a>
                @foreach($shopFooterCategories as $cat)
                    <a href="{{ route('shop.index', ['category' => $cat->id]) }}">{{ $cat->name }}</a>
                @endforeach
                <a href="{{ route('shop.cart') }}">My Cart</a>
            </div>

            <div class="shopft-col">
                <h6 class="shopft-heading">Customer Service</h6>
                <a href="mailto:{{ gs('email_from') }}">Contact Us</a>
                <a href="{{ route('cookie.policy') }}">Cookie Policy</a>
                <a href="{{ route('agreement') }}">Terms &amp; Conditions</a>
            </div>

            <div class="shopft-col">
                <h6 class="shopft-heading">Make Money With Us</h6>
                <p class="shopft-text">Become an affiliate — share your link and earn a bonus on every order.</p>
                @if(auth()->check())
                    <a href="{{ route('user.affiliate.dashboard') }}" class="shopft-cta">Go to Affiliate Dashboard</a>
                @else
                    <a href="{{ route('user.login') }}" class="shopft-cta">Login to Get Your Link</a>
                @endif
            </div>

        </div>
    </div>

    <div class="shopft-bottom">
        <div class="shophd-container shopft-bottom-inner">
            <p class="shopft-copy">&copy; {{ now()->year }} {{ __(gs('site_name')) }}. All rights reserved.</p>
            <div class="shopft-badges">
                <span class="shopft-badge"><i class="las la-lock"></i> Secure Checkout</span>
                <span class="shopft-badge"><i class="las la-credit-card"></i> Paystack</span>
                <span class="shopft-badge"><i class="las la-hand-holding-usd"></i> Cash on Pickup</span>
            </div>
        </div>
    </div>
</footer>

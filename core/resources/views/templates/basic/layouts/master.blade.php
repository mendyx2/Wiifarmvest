@extends($activeTemplate . 'layouts.app')

@section('panel')
    {{-- Flashcards are included only on the dashboard page --}}

    @if(request()->routeIs('user.affiliate.*'))
        @include($activeTemplate.'partials.dashboard_affiliate')

    @elseif($inStockistSection = request()->routeIs('user.stockist.*'))
    
        @include($activeTemplate.'partials.dashboard_stockist_main')
    @else
        @include($activeTemplate.'partials.dashboard')

    @endif
    

    @include($activeTemplate . 'partials.footer')
@endsection 

@push('style')
    {{-- The dashboard CSS lives in resources/css/dashboard.css (notes Step 78). --}}
    @vite('resources/css/dashboard.css')
@endpush

@push('script')
    <script>
        (function($) {
            "use strict";
            window.addEventListener('scroll', function(){
              var header = document.querySelector('header');
              if (header) header.classList.toggle('sticky', window.scrollY > 0);
            });   
        })(jQuery);
    </script>
@endpush

@extends($activeTemplate . 'layouts.app')

@section('panel')
    {{-- Only Blade pages still use this layout (the payment gateway pages, and the
         old copies of migrated pages kept for rollback). The affiliate and stockist
         areas are React, so their sidebar partials are gone (notes Step 85). --}}
    @php $inStockistSection = false; @endphp
    @include($activeTemplate.'partials.dashboard')
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

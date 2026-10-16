@extends($activeTemplate . 'layouts.app')
@section('panel')
    @include($activeTemplate . 'partials.shop_header')

    @yield('content')

    @include($activeTemplate . 'partials.shop_footer')
@endsection

@push('script')
    <script>
        (function($) {
            "use strict";
            window.addEventListener('scroll', function(){
              var header = document.querySelector('.shophd');
              if (header) header.classList.toggle('shophd--stuck', window.scrollY > 4);
            });
        })(jQuery);
    </script>
@endpush

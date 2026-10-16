@extends($activeTemplate . 'layouts.app')
@section('panel')
   {{-- @include($activeTemplate.'layouts.breadcrumb1')--}}

    @yield('content')

   {{--  @include($activeTemplate . 'partials.footer') --}}
@endsection

@push('script')
    <script>
        (function($) {
            "use strict";
            window.addEventListener('scroll', function(){
              var header = document.querySelector('header');
              header.classList.toggle('sticky', window.scrollY > 0);
            });   
        })(jQuery);
    </script>
@endpush

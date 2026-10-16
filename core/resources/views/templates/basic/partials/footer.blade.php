@php
    $socials     = getContent('social_icon.element');
    $footer      = getContent('footer_section.content', true);
    $policyPages = getContent('policy_pages.element', false, orderById: true);
@endphp

<footer class="ft-root">

    {{-- Top bar: brand + social --}}
    <div class="ft-top">
        <div class="ft-brand">
            <span class="ft-brand-logo">{{ gs('site_name') }}</span>
            <p class="ft-brand-tagline">Empowering financial freedom, one step at a time.</p>
        </div>

        @if($socials->count())
        <div class="ft-socials">
            @foreach($socials as $social)
            <a href="{{ @$social->data_values->url }}"
               title="{{ @$social->data_values->title }}"
               target="_blank"
               rel="noopener noreferrer"
               class="ft-social-btn">
                @php echo @$social->data_values->social_icon; @endphp
            </a>
            @endforeach
        </div>
        @endif
    </div>

    {{-- 

    <!--  Divider -->
    <div class="ft-divider"></div>

    --}}
   {{--  Policy links  -->
    @if($policyPages->count())
    <div class="ft-policy-row">
        @foreach($policyPages as $page)
        <a href="{{ route('policy.pages', [$page->slug]) }}" class="ft-policy-link">
            {{ __($page->data_values->title) }}
        </a>
        @endforeach
    </div>
    @endif

    <!--  Bottom bar  -->
    <div class="ft-bottom">
        <p class="ft-copy">
            &copy; {{ now()->year }} <a href="{{ route('home') }}">{{ __(gs('site_name')) }}</a>.
            @lang('All rights reserved.')
        </p>
        <div class="ft-badges">
            <span class="ft-badge"><i class="las la-lock"></i> SSL Secured</span>
            <span class="ft-badge"><i class="las la-shield-alt"></i> PCI Compliant</span>
        </div>
    </div>
    --}}
</footer>

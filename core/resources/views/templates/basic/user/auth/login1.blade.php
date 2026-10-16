@extends($activeTemplate . 'layouts.app')

@php
    $loginContent = getContent('login.content', true);
@endphp
@section('panel')
    <section class="account-section">
        <div class="row g-0 flex-wrap-reverse">
            <div class="col-md-6 col-xl-5 col-lg-6">
                <div class="account-form-wrapper">
                    <div class="d-flex align-items-center justify-content-center mb-3">
                        <img src="{{ asset($activeTemplateTrue . 'images/logo/Wordmark.png') }}" style="width:100px !important">
                                
                    </div>

                    <form class="account-form verify-gcaptcha" method="POST" action="{{ route('user.login') }}">
                        @csrf

                        <div class="form--group">
                            <label class="form--label">@lang('Username')</label>
                            <input class="form-control form--control" name="username" type="text" value="{{ old('username') }}"
                                placeholder="@lang('Enter Username')" required>
                        </div>
                        <div class="form--group">
                            <label class="form--label">@lang('Password')</label>
                            <input class="form-control form--control" id="password" name="password" type="password" placeholder="@lang('Enter Password')"
                                required>
                        </div>

                        @php
                            $custom = true;
                        @endphp
                        <x-captcha :custom="$custom" />

                        <div class="form--group custom--checkbox">
                            <input class="form--control" id="remember" name="remember" type="checkbox" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form--label" for="remember">
                                @lang('Remember Me')
                            </label>
                        </div>

                        <div class="form--group button-wrapper">
                            <button class="account--btn" type="submit">@lang('Sign In')</button>
                           
                            <a class="custom--btn" href="{{ route('user.register') }}"><span>@lang('Create Account')</span></a>
                            
                        </div>
                    </form>
                    <p class="text--dark">@lang('Forgot your login credentials') ? <a class="text--base ms-2" href="{{ route('user.password.request') }}">@lang('Reset Password')</a>
                    </p>
                </div>
            </div>
            <div class="col-md-6 col-xl-7 col-lg-6">
                <div class="account-thumb">
                    {{--<img src="{{ frontendImage('login', @$loginContent->data_values->login_image, '1100x750') }}" alt="thumb">
                    --}}
                    <div class="account-thumb-content">
                       <h3 class="title">{{ __('Welcome To WiiFarmVest') }}</h3>
                    </div>
                </div>
            </div>
        </div>
        
    </section>
@endsection

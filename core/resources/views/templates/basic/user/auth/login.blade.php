@extends($activeTemplate . 'layouts.app1')

@php
    $loginContent = getContent('login.content', true);
@endphp
@section('panel')
    <div class="login-container">
        <div class="login-card">
            <!-- Card Header -->
            <div class="card-header">
                <div class="brand-logo">
                   <!--  <i class="fas fa-store"></i> -->

                    <img src="{{ siteLogo() }}" alt="logo">

                </div>
                <h1>Welcome Back</h1>
                <p>Sign in to your account to continue</p>
            </div>
            
            <!-- Card Body -->
            <div class="card-body">
                <!-- Error/Success Messages -->
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        {{ $errors->first() }}
                    </div>
                @endif 
                
                @if (session('status'))
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('status') }}
                    </div>
                @endif
                
                <!-- Login Form -->
                <form method="POST" action="{{ route('user.login') }}" id="loginForm">
                    @csrf
                    
                    <!-- Username Field -->
                    <div class="form-group">
                        <label for="username" class="form-label">
                            <i class="fas fa-user me-2"></i>Username
                        </label>
                        <div class="input-group">
                            <input type="text" 
                                   class="form-control @error('username') is-invalid @enderror" 
                                   id="username" 
                                   name="username" 
                                   value="{{ old('username') }}" 
                                   placeholder="Enter your username" 
                                   required 
                                   autocomplete="username" 
                                   autofocus>
                        </div>
                        @error('username')
                            <div class="invalid-feedback d-block">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    
                    <!-- Password Field -->
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock me-2"></i>Password
                        </label>
                        <div class="input-group">
                            <input type="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Enter your password" 
                                   required 
                                   autocomplete="current-password">
                            <button type="button" class="password-toggle" id="passwordToggle">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback d-block">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    
                    <!-- Remember Me & Forgot Password -->
                    <div class="form-options">
                        <div class="custom-checkbox">
                            
                             <input class="form--control" id="remember" name="remember" type="checkbox" {{ old('remember') ? 'checked' : '' }}>
                            <span class="checkmark"></span>
                            <label for="remember" class="form-check-label">Remember me</label>
                        </div>
                        
                        @if (Route::has('password.request'))
                            <a href="{{ route('user.password.request') }}" class="forgot-password">
                                Forgot Password?
                            </a>
                        @endif
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-login" id="loginButton">
                        <i class="fas fa-sign-in-alt me-2"></i>Sign In
                    </button>
                </form>
                
                <!-- Footer Links -->
                <div class="login-footer">
                    <p class="text-dark">
                        Don't have an account? 
                           @if (Route::has('user.register'))
                                <a href="{{ route('user.register') }}" class="signup-link">Sign up now</a>
                            @endif
                        
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
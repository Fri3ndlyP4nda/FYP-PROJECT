@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
    <div class="auth-main" style="min-height: 100vh; background: radial-gradient(circle at center, #fdfbfb 0%, #f7eff2 100%);">
        <div class="auth-form-card">
            <!-- Header -->
            <div class="auth-header">
                <h2>Forgot Password</h2>
                <p class="muted">Enter your registered email address and we will send you a password reset link.</p>
            </div>

            <!-- Success Alert -->
            @if (session('success'))
                <div class="auth-alert auth-alert-success">
                    <div style="font-size: 16px;">✨</div>
                    <div>{{ session('success') }}</div>
                </div>
            @endif

            <!-- Errors Alert -->
            @if ($errors->any())
                <div class="auth-alert auth-alert-error">
                    <div style="font-size: 16px;">⚠️</div>
                    <div>
                        <ul style="padding-left: 12px; margin: 0;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Form -->
            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <!-- Email Input -->
                <div class="form-input-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <input id="email" type="email" name="email" class="input-field"
                            value="{{ old('email') }}" placeholder="name@domain.com" required autofocus>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="auth-submit-btn">
                    <span>Send Reset Link</span>
                    <span style="font-size: 16px;">→</span>
                </button>
            </form>

            <!-- Footer links -->
            <div class="auth-footer-links">
                <p>Remember your password? <a href="{{ route('login') }}">Back to Login</a></p>
            </div>
        </div>
    </div>
@endsection

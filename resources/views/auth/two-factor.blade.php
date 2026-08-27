@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
    <div class="auth-grid">
        <!-- Left Sidebar: System Info and Branding -->
        <div class="auth-sidebar">
            <div class="decor-orb orb-1"></div>
            <div class="decor-orb orb-2"></div>

            <!-- Brand Header -->
            <div class="auth-brand">
                <div class="auth-brand-logo">🎓</div>
                <span class="auth-brand-title">APEL Portal</span>
                <p class="auth-brand-subtitle">Management & Evaluation System</p>
            </div>

            <!-- Promotional Features Section -->
            <div class="auth-promo-content">
                <h1 class="auth-promo-heading">Secure Account Access</h1>
                <p class="auth-promo-text">
                    We take security seriously. Two-factor authentication adds an extra layer of protection to keep your personal data, credentials, and portfolios safe.
                </p>

                <!-- Feature Widgets Stack -->
                <div class="auth-features-stack">
                    <div class="feature-widget">
                        <div class="feature-icon-wrapper">🔒</div>
                        <div class="feature-widget-content">
                            <h4>Identity Verification</h4>
                            <p>Verify your identity by entering the code sent to your email.</p>
                        </div>
                    </div>

                    <div class="feature-widget">
                        <div class="feature-icon-wrapper">⏱️</div>
                        <div class="feature-widget-content">
                            <h4>Time-Sensitive Codes</h4>
                            <p>Codes are only active for a limited time to ensure authentication integrity.</p>
                        </div>
                    </div>

                    <div class="feature-widget">
                        <div class="feature-icon-wrapper">🛡️</div>
                        <div class="feature-widget-content">
                            <h4>Data Protection</h4>
                            <p>Keeps your evaluations and certificates protected against unauthorized access.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Details -->
            <div class="auth-sidebar-footer">
                <span>© {{ date('Y') }} APEL Management System</span>
                <span>Version 2.0.0</span>
            </div>
        </div>

        <!-- Right Content Section: Form -->
        <div class="auth-main">
            <div class="auth-form-card">
                <!-- Header -->
                <div class="auth-header">
                    <h2>Two-Factor Verification</h2>
                    <p class="muted">We have sent a 6-digit verification code to your email.</p>
                </div>

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

                <!-- 2FA Form -->
                <form method="POST" action="{{ route('2fa.verify') }}">
                    @csrf

                    <!-- Code Input -->
                    <div class="form-input-group">
                        <label for="two_factor_code">Verification Code</label>
                        <div class="input-wrapper">
                            <input id="two_factor_code" type="text" name="two_factor_code" class="input-field" 
                                maxlength="6" placeholder="000000" style="text-align: center; letter-spacing: 6px; font-size: 20px; font-weight: 700;" required autofocus>
                            <x-field-error name="two_factor_code" />
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="auth-submit-btn">
                        <span>Verify Code</span>
                        <span style="font-size: 16px;">→</span>
                    </button>
                </form>

                <!-- Footer link to go back to login -->
                <div class="auth-footer-links">
                    <p>Didn't receive the code? <a href="{{ route('login') }}">Go back to login</a></p>
                </div>
            </div>
        </div>
    </div>
@endsection

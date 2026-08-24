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
                <h1 class="auth-promo-heading">Recognizing Your Prior Experience</h1>
                <p class="auth-promo-text">
                    APEL enables individuals with work experience to obtain academic qualifications by demonstrating their learning outcomes. Join thousands of students who fast-tracked their degree programs.
                </p>

                <!-- Feature Widgets Stack -->
                <div class="auth-features-stack">
                    <div class="feature-widget">
                        <div class="feature-icon-wrapper">🚀</div>
                        <div class="feature-widget-content">
                            <h4>Accelerated Learning Path</h4>
                            <p>Fast-track your educational goals by converting work experience into academic credits.</p>
                        </div>
                    </div>

                    <div class="feature-widget">
                        <div class="feature-icon-wrapper">🛡️</div>
                        <div class="feature-widget-content">
                            <h4>Official Accreditation</h4>
                            <p>Recognized by MQA and partner academic institutions for credit transfer guidelines.</p>
                        </div>
                    </div>

                    <div class="feature-widget">
                        <div class="feature-icon-wrapper">💻</div>
                        <div class="feature-widget-content">
                            <h4>Seamless Evaluation</h4>
                            <p>Submit portfolios, take assessment papers, and review advisor feedback entirely online.</p>
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
                    <h2>Welcome Back</h2>
                    <p class="muted">Sign in to manage your APEL profile and applications.</p>
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

                <!-- Login Form -->
                <form method="POST" action="{{ route('login.submit') }}">
                    @csrf

                    <!-- Email Input -->
                    <div class="form-input-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrapper">
                            <input id="email" type="email" name="email" class="input-field" 
                                placeholder="name@domain.com" required autofocus autocomplete="off">
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div class="form-input-group">
                        <label for="login-password">Password</label>
                        <div class="input-wrapper">
                            <input id="login-password" type="password" name="password" class="input-field" 
                                placeholder="••••••••" required style="padding-right: 45px;" autocomplete="off">
                            
                            <button type="button" onclick="togglePassword('login-password', this)" class="eye-toggle-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon-open"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon-closed" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-10-7-10-7a18.39 18.39 0 0 1 2.18-3.03M8.9 8.9a3.5 3.5 0 0 1 4.9 4.9M1 1l22 22"></path><path d="M12 5c7 0 10 7 10 7a18.4 18.4 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Security Verification Captcha -->
                    <div class="form-input-group">
                        <label>Security Check</label>
                        <div class="captcha-box">
                            <div class="captcha-question-tag">
                                <span class="verify-label">Verify</span>
                                <span>{{ session('captcha_question') }}</span>
                            </div>
                            <input type="text" name="captcha_answer" class="input-field captcha-input" 
                                placeholder="Answer" required autocomplete="off">
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="auth-submit-btn">
                        <span>Sign In</span>
                        <span style="font-size: 16px;">→</span>
                    </button>
                </form>

                <!-- Register and recovery footer -->
                <div class="auth-footer-links">
                    <p>Don't have an account? <a href="{{ route('register') }}">Register here</a></p>
                    <div class="auth-footer-secondary">
                        <a href="{{ route('password.request') }}">Forgot password?</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const openIcon = button.querySelector('.eye-icon-open');
            const closedIcon = button.querySelector('.eye-icon-closed');
            
            if (input.type === 'password') {
                input.type = 'text';
                openIcon.style.display = 'none';
                closedIcon.style.display = 'inline-block';
            } else {
                input.type = 'password';
                openIcon.style.display = 'inline-block';
                closedIcon.style.display = 'none';
            }
        }

        // Clear all inputs on reload/load to avoid autocomplete/pasting issues
        window.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('login-password');
            const captchaInput = document.querySelector('input[name="captcha_answer"]');
            
            if (emailInput) emailInput.value = '';
            if (passwordInput) passwordInput.value = '';
            if (captchaInput) captchaInput.value = '';
        });
    </script>
@endpush

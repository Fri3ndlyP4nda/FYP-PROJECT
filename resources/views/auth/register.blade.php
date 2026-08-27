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
                <h1 class="auth-promo-heading">Begin Your APEL Journey</h1>
                <p class="auth-promo-text">
                    Create an account as a student to evaluate your prior experiential learning. Unlock new academic achievements and complete your degree pathways faster.
                </p>

                <!-- Feature Widgets Stack -->
                <div class="auth-features-stack">
                    <div class="feature-widget">
                        <div class="feature-icon-wrapper">📝</div>
                        <div class="feature-widget-content">
                            <h4>Easy Registration</h4>
                            <p>Sign up in seconds to access the student application dashboard.</p>
                        </div>
                    </div>

                    <div class="feature-widget">
                        <div class="feature-icon-wrapper">📁</div>
                        <div class="feature-widget-content">
                            <h4>Portfolio Management</h4>
                            <p>Build and update your digital portfolio with evidence of your work experience.</p>
                        </div>
                    </div>

                    <div class="feature-widget">
                        <div class="feature-icon-wrapper">🔔</div>
                        <div class="feature-widget-content">
                            <h4>Real-Time Progress</h4>
                            <p>Receive notifications at each stage of your application review and evaluation.</p>
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
                    <h2>Create Account</h2>
                    <p class="muted">Register as a student to start your APEL application.</p>
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

                <!-- Register Form -->
                <form method="POST" action="{{ route('register.submit') }}">
                    @csrf

                    <!-- Name Input -->
                    <div class="form-input-group">
                        <label for="name">Full Name</label>
                        <div class="input-wrapper">
                            <input id="name" type="text" name="name" class="input-field" 
                                value="{{ old('name') }}" placeholder="John Doe" required autofocus>
                            <x-field-error name="name" />
                        </div>
                    </div>

                    <!-- Email Input -->
                    <div class="form-input-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrapper">
                            <input id="email" type="email" name="email" class="input-field" 
                                value="{{ old('email') }}" placeholder="name@domain.com" required>
                            <x-field-error name="email" />
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div class="form-input-group">
                        <label for="register-password">Password</label>
                        <div class="input-wrapper">
                            <input id="register-password" type="password" name="password" class="input-field" 
                                placeholder="••••••••" required style="padding-right: 45px;">
                            <x-field-error name="password" />
                            
                            <button type="button" onclick="togglePassword('register-password', this)" class="eye-toggle-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon-open"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon-closed" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-10-7-10-7a18.39 18.39 0 0 1 2.18-3.03M8.9 8.9a3.5 3.5 0 0 1 4.9 4.9M1 1l22 22"></path><path d="M12 5c7 0 10 7 10 7a18.4 18.4 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Confirm Password Input -->
                    <div class="form-input-group">
                        <label for="register-password-confirmation">Confirm Password</label>
                        <div class="input-wrapper">
                            <input id="register-password-confirmation" type="password" name="password_confirmation" class="input-field" 
                                placeholder="••••••••" required style="padding-right: 45px;">
                            <x-field-error name="password_confirmation" />
                            
                            <button type="button" onclick="togglePassword('register-password-confirmation', this)" class="eye-toggle-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon-open"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon-closed" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-10-7-10-7a18.39 18.39 0 0 1 2.18-3.03M8.9 8.9a3.5 3.5 0 0 1 4.9 4.9M1 1l22 22"></path><path d="M12 5c7 0 10 7 10 7a18.4 18.4 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Security Verification Captcha -->
                    <div class="form-input-group">
                        <label for="f-captcha-answer">Security Check</label>
                        <div class="captcha-box">
                            <div class="captcha-question-tag">
                                <span class="verify-label">Verify</span>
                                <span>{{ session('captcha_question') }}</span>
                            </div>
                            <input type="text" name="captcha_answer" class="input-field captcha-input" 
                                placeholder="Answer" required autocomplete="off" id="f-captcha-answer">
                            <x-field-error name="captcha_answer" />
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="auth-submit-btn">
                        <span>Register</span>
                        <span style="font-size: 16px;">→</span>
                    </button>
                </form>

                <!-- Login and recovery footer -->
                <div class="auth-footer-links">
                    <p>Already have an account? <a href="{{ route('login') }}">Login here</a></p>
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
    </script>
@endpush

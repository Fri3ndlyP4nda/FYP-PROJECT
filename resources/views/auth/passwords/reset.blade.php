@extends('layouts.app')


@section('content')
    <div class="auth-main" style="min-height: 100vh; background: radial-gradient(circle at center, var(--surface-sunk) 0%, #f7eff2 100%);">
        <div class="auth-form-card">
            <!-- Header -->
            <div class="auth-header">
                <h2>Reset Password</h2>
                <p class="muted">Enter your new password below to complete the password reset process.</p>
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

            <!-- Form -->
            <form method="POST" action="{{ route('password.update') }}">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                <!-- Email Input (Read-only) -->
                <div class="form-input-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <input id="email" type="email" name="email" class="input-field"
                            value="{{ old('email', $email) }}" required readonly style="background-color: #f7f5f6; color: #718096; cursor: not-allowed;">
                        <x-field-error name="email" />
                    </div>
                </div>

                <!-- Password Input -->
                <div class="form-input-group">
                    <label for="password">New Password</label>
                    <div class="input-wrapper">
                        <input id="password" type="password" name="password" class="input-field" 
                            placeholder="••••••••" required style="padding-right: 45px;" autofocus>
                        <x-field-error name="password" />
                        
                        <button type="button" onclick="togglePassword('password', this)" class="eye-toggle-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon-open"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon-closed" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-10-7-10-7a18.39 18.39 0 0 1 2.18-3.03M8.9 8.9a3.5 3.5 0 0 1 4.9 4.9M1 1l22 22"></path><path d="M12 5c7 0 10 7 10 7a18.4 18.4 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path></svg>
                        </button>
                    </div>
                </div>

                <!-- Confirm Password Input -->
                <div class="form-input-group">
                    <label for="password_confirmation">Confirm New Password</label>
                    <div class="input-wrapper">
                        <input id="password_confirmation" type="password" name="password_confirmation" class="input-field" 
                            placeholder="••••••••" required style="padding-right: 45px;">
                        <x-field-error name="password_confirmation" />
                        
                        <button type="button" onclick="togglePassword('password_confirmation', this)" class="eye-toggle-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon-open"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon-closed" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-10-7-10-7a18.39 18.39 0 0 1 2.18-3.03M8.9 8.9a3.5 3.5 0 0 1 4.9 4.9M1 1l22 22"></path><path d="M12 5c7 0 10 7 10 7a18.4 18.4 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path></svg>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="auth-submit-btn">
                    <span>Reset Password</span>
                    <span style="font-size: 16px;">→</span>
                </button>
            </form>

            <!-- Footer links -->
            <div class="auth-footer-links">
                <p><a href="{{ route('login') }}">Back to Login</a></p>
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

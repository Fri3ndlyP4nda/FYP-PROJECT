@extends('layouts.app')

@section('content')
    {{--
        Setting a new password from an emailed link.

        The rules are stated before the reader types, not after the server
        rejects them - AuthController and PasswordResetController both require
        eight characters with mixed case and a number, and a person choosing a
        password should not have to discover that by failing.
    --}}
    <div class="gate">
        <a class="gate-mark" href="{{ route('login') }}">
            <span class="door-mark-glyph" aria-hidden="true">AP</span>
            <span class="door-mark-name">APEL</span>
        </a>

        <section class="door-panel" aria-labelledby="rp-head">
            <h2 id="rp-head">Choose a new password</h2>
            <p class="door-panel-sub">This link works once, and only for a short time.</p>

            @if ($errors->any())
                <div class="notice notice--bad" role="alert">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token ?? request()->route('token') }}">

                <div class="field">
                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" required
                           autocomplete="username"
                           value="{{ old('email', request()->query('email')) }}">
                    <x-field-error name="email" />
                </div>

                <div class="field">
                    <label for="password">New password</label>
                    <div class="field-peek">
                        <input id="password" name="password" type="password" required autofocus
                               autocomplete="new-password">
                        <button type="button" class="peek-btn" data-peek="password"
                                aria-label="Show password" aria-pressed="false">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round" aria-hidden="true">
                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                        </button>
                    </div>
                    <p class="field-hint">
                        At least 8 characters, with an upper and a lower case letter and a number.
                    </p>
                    <x-field-error name="password" />
                </div>

                <div class="field">
                    <label for="password_confirmation">Confirm new password</label>
                    <input id="password_confirmation" name="password_confirmation"
                           type="password" required autocomplete="new-password">
                    <x-field-error name="password_confirmation" />
                </div>

                <button type="submit" class="go">
                    Save the new password
                    <span class="go-arrow" aria-hidden="true">&rarr;</span>
                </button>
            </form>

            <div class="door-foot">
                <a href="{{ route('login') }}">Back to sign in</a>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-peek]');
            if (!btn) return;

            const input = document.getElementById(btn.dataset.peek);
            if (!input) return;

            const shown = input.type === 'text';
            input.type = shown ? 'password' : 'text';
            btn.setAttribute('aria-pressed', String(!shown));
            btn.setAttribute('aria-label', shown ? 'Show password' : 'Hide password');
        });
    </script>
@endpush

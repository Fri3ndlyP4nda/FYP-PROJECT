@extends('layouts.app')

@section('content')
    {{--
        Asking for a reset link.

        A single-purpose screen, so it gets a single centred panel rather than
        the two-column door the sign-in and registration pages use - the shape
        should say how much is being asked before the reader parses a word.

        The reply is deliberately the same whether or not the address is on
        file: PasswordResetController does not confirm which emails exist, and
        this page must not either.
    --}}
    <div class="gate">
        <a class="gate-mark" href="{{ route('login') }}">
            <span class="door-mark-glyph" aria-hidden="true">AP</span>
            <span class="door-mark-name">APEL</span>
        </a>

        <section class="door-panel" aria-labelledby="fp-head">
            <h2 id="fp-head">Reset your password</h2>
            <p class="door-panel-sub">
                Enter the address you sign in with and we will send a link to it.
            </p>

            @if (session('success'))
                <p class="notice notice--good" role="status">{{ session('success') }}</p>
            @endif
            @if (session('status'))
                <p class="notice notice--good" role="status">{{ session('status') }}</p>
            @endif

            @if ($errors->any())
                <div class="notice notice--bad" role="alert">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="field">
                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" required autofocus
                           autocomplete="username" placeholder="name@example.com"
                           value="{{ old('email') }}">
                    <p class="field-hint">
                        If an account exists for it, a link arrives within a few minutes. Check
                        your spam folder before trying again.
                    </p>
                    <x-field-error name="email" />
                </div>

                <button type="submit" class="go">
                    Send the link
                    <span class="go-arrow" aria-hidden="true">&rarr;</span>
                </button>
            </form>

            <div class="door-foot">
                <a href="{{ route('login') }}">Back to sign in</a>
            </div>
        </section>
    </div>
@endsection

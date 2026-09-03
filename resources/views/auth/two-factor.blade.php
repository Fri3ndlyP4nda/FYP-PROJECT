@extends('layouts.app')

@section('content')
    {{--
        Entering the emailed one-time code.

        Someone is here mid-sign-in with a code in another window, so the page
        says only what they need: where the code went, how long it lasts, and
        one field to put it in. The old version wrapped it in a promotional
        column headed "Secure Account Access", which is the last thing anyone
        reads while copying six digits across.

        The lifetime comes from config/apel.php rather than being written in
        prose, so the page cannot promise ten minutes while the code expires in
        five.
    --}}
    @php $lifetime = (int) config('apel.two_factor.code_lifetime_minutes', 10); @endphp

    <div class="gate">
        <a class="gate-mark" href="{{ route('login') }}">
            <span class="door-mark-glyph" aria-hidden="true">AP</span>
            <span class="door-mark-name">APEL</span>
        </a>

        <section class="door-panel" aria-labelledby="tf-head">
            <h2 id="tf-head">Check your email</h2>
            <p class="door-panel-sub">
                We sent a six-digit code. It works once, and expires in
                {{ $lifetime }} {{ Str::plural('minute', $lifetime) }}.
            </p>

            @if (session('success'))
                <p class="notice notice--good" role="status">{{ session('success') }}</p>
            @endif

            @if ($errors->any())
                <div class="notice notice--bad" role="alert">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('2fa.verify') }}">
                @csrf

                <div class="field">
                    <label for="two_factor_code">Verification code</label>
                    <input id="two_factor_code" name="two_factor_code" type="text" required autofocus
                           inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                           autocomplete="one-time-code" class="code-input"
                           placeholder="000000">
                    <p class="field-hint">Six digits, from the email we just sent.</p>
                    <x-field-error name="two_factor_code" />
                </div>

                <button type="submit" class="go">
                    Verify
                    <span class="go-arrow" aria-hidden="true">&rarr;</span>
                </button>
            </form>

            <div class="door-foot">
                <span>Code not arrived? <a href="{{ route('login') }}">Start again</a></span>
            </div>
        </section>
    </div>
@endsection

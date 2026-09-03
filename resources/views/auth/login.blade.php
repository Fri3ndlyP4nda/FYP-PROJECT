@extends('layouts.app')

@section('content')
    {{--
        The front door.

        Everyone who arrives here was told to come here — a candidate following
        a faculty email, a lecturer between classes, the registry officer
        starting their day. None of them need selling to, so the marketing
        column that used to sit here (three feature cards, rocket and shield
        emoji, "join thousands of students") is gone.

        What a first-time candidate genuinely arrives wanting to know is what
        they are about to be put through and roughly how long it takes. So the
        space is spent on the real journey, drawn with the same spine that
        structures the rest of the product. It is information, not decoration,
        and it is honest: these are the actual stages from ApelStage.

        The layout is a single auto-fit grid, so it cannot strand space the way
        a fixed 1fr / 420px split did — at 1900px that left roughly 980px of
        dead screen between the two columns.
    --}}
    <div class="wrap">
        <div class="door">

            <section class="door-tell">
                <div class="door-mark">
                    <span class="door-mark-glyph" aria-hidden="true">AP</span>
                    <span>
                        <span class="door-mark-name">APEL</span>
                        <span class="door-mark-sub">Universiti Teknologi Malaysia</span>
                    </span>
                </div>

                <h1 class="door-head">Your working life, <em>assessed as credit</em>.</h1>

                <p class="door-lede">
                    Ten years on the job is not nothing. APEL puts that experience in front of
                    academics who decide what it is worth — as entry to a programme, or as credit
                    against a course you would otherwise sit.
                </p>

                <h2 class="sr-only">What happens after you apply</h2>

                {{-- The real stages, not a marketing summary. --}}
                <ol class="spine" style="--spine-done: 0%">
                    <li class="spine-node" data-state="todo">
                        <span class="spine-label">You submit your evidence</span>
                        <span class="spine-note">Work history, certificates, and what you learned doing it.</span>
                    </li>
                    <li class="spine-node" data-state="todo">
                        <span class="spine-label">An advisor reads it</span>
                        <span class="spine-note">They recommend whether to proceed, and how you should be assessed.</span>
                    </li>
                    <li class="spine-node" data-state="todo">
                        <span class="spine-label">You pay the processing fee</span>
                        <span class="spine-note">Only once someone has said your case is worth assessing.</span>
                    </li>
                    <li class="spine-node" data-state="todo">
                        <span class="spine-label">One or two evaluators assess you</span>
                        <span class="spine-note">A written paper, or a portfolio of what you have actually built.</span>
                    </li>
                    <li class="spine-node" data-state="todo">
                        <span class="spine-label">The faculty decides</span>
                        <span class="spine-note">Admission, or credit hours awarded against the course.</span>
                    </li>
                </ol>

                <p class="door-colophon">
                    APEL A — admission &nbsp;·&nbsp; APEL C — credit transfer
                </p>
            </section>

            <section class="door-panel" aria-labelledby="signin-head">
                <h2 id="signin-head">Sign in</h2>
                <p class="door-panel-sub">Candidates, evaluators and faculty staff.</p>

                @if (session('success'))
                    <p class="notice notice--good" role="status">{{ session('success') }}</p>
                @endif

                @if ($errors->any())
                    <div class="notice notice--bad" role="alert">
                        <div>
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.submit') }}">
                    @csrf

                    <div class="field">
                        <label for="email">Email address</label>
                        <input id="email" name="email" type="email" required autofocus
                               autocomplete="username" placeholder="name@utm.my"
                               value="{{ old('email') }}">
                        <x-field-error name="email" />
                    </div>

                    <div class="field">
                        <label for="login-password">Password</label>
                        <div class="field-peek">
                            <input id="login-password" name="password" type="password" required
                                   autocomplete="current-password" placeholder="Your password">
                            <button type="button" class="peek-btn" data-peek="login-password"
                                    aria-label="Show password" aria-pressed="false">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round" aria-hidden="true">
                                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>
                        <x-field-error name="password" />
                    </div>

                    <x-human-check />

                    <button type="submit" class="go">
                        Sign in
                        <span class="go-arrow" aria-hidden="true">&rarr;</span>
                    </button>
                </form>

                <div class="door-foot">
                    <span>No account yet? <a href="{{ route('register') }}">Register</a></span>
                    <a href="{{ route('password.request') }}">Forgot password</a>
                </div>
            </section>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Reveal the password. aria-pressed carries the state, so the control
        // reports what it did rather than only looking different.
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

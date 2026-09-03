@extends('layouts.app')

@section('content')
    {{--
        Creating a candidate account.

        The panel on the left used to be a promotional block - "Begin Your APEL
        Journey" over three benefit lines. Someone on the registration form has
        already decided; what they need now is to know what they are signing up
        to do, so the space carries the same five stages the landing page shows,
        and the password rules are stated before they are broken rather than
        after.

        Only candidates register here. Staff accounts are created by the
        registry, which is why nothing on this page offers a role.
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

                <h1 class="door-head">Start your <em>application</em>.</h1>

                <p class="door-lede">
                    An account lets you build your application over several sittings, upload your
                    evidence, and follow where it has got to. Nothing is sent to the faculty until
                    you submit it.
                </p>

                <h2 class="sr-only">What happens after you apply</h2>

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
            </section>

            <section class="door-panel" aria-labelledby="reg-head">
                <h2 id="reg-head">Create an account</h2>
                <p class="door-panel-sub">For candidates. Staff accounts are set up by the registry.</p>

                @if ($errors->any())
                    <div class="notice notice--bad" role="alert">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('register.submit') }}">
                    @csrf

                    <div class="field">
                        <label for="name">Full name</label>
                        <input id="name" name="name" type="text" required autofocus
                               maxlength="255" autocomplete="name" value="{{ old('name') }}">
                        <x-field-error name="name" />
                    </div>

                    <div class="field">
                        <label for="email">Email address</label>
                        <input id="email" name="email" type="email" required
                               maxlength="255" autocomplete="username"
                               placeholder="name@example.com" value="{{ old('email') }}">
                        <p class="field-hint">You sign in with this, and we send your updates here.</p>
                        <x-field-error name="email" />
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <div class="field-peek">
                            <input id="password" name="password" type="password" required
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
                        {{-- The exact rule AuthController enforces, stated up front. --}}
                        <p class="field-hint">
                            At least 8 characters, with an upper and a lower case letter and a number.
                        </p>
                        <x-field-error name="password" />
                    </div>

                    <div class="field">
                        <label for="password_confirmation">Confirm password</label>
                        <input id="password_confirmation" name="password_confirmation"
                               type="password" required autocomplete="new-password">
                        <x-field-error name="password_confirmation" />
                    </div>

                    <x-human-check />

                    <button type="submit" class="go">
                        Create account
                        <span class="go-arrow" aria-hidden="true">&rarr;</span>
                    </button>
                </form>

                <div class="door-foot">
                    <span>Already registered? <a href="{{ route('login') }}">Sign in</a></span>
                </div>
            </section>
        </div>
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

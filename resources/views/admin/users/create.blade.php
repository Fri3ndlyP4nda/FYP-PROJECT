@extends('layouts.app')

@section('content')
    {{--
        Adding a staff account.

        This view did not exist. The route, the controller and the validation
        were all in place, so GET /admin/users/create resolved and then died on
        a missing view - an administrator could not add an evaluator through
        the interface at all.

        Only evaluators and administrators can be created here: students arrive
        through registration, and store() rejects that role with a message
        saying so. The form offers only what the server accepts, so the rule is
        visible before it is broken rather than after.
    --}}
    <div class="deck deck--narrow">
        <header class="deck-head">
            <div>
                <p class="deck-eyebrow">Staff accounts</p>
                <h1 class="deck-title">Add someone</h1>
            </div>
            <div class="deck-acts">
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">All accounts</a>
            </div>
        </header>

        @if ($errors->any())
            <div class="notice notice--bad" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <section class="panel" aria-labelledby="new-head">
            <h2 class="panel-head" id="new-head">The account</h2>

            <form method="POST" action="{{ route('admin.users.store') }}" class="stack-form">
                @csrf

                <div class="field">
                    <label for="f-name">Full name</label>
                    <input type="text" id="f-name" name="name" required maxlength="255"
                           value="{{ old('name') }}" autocomplete="off">
                    <x-field-error name="name" />
                </div>

                <div class="field">
                    <label for="f-email">Email</label>
                    <input type="email" id="f-email" name="email" required maxlength="255"
                           value="{{ old('email') }}" autocomplete="off" placeholder="name@utm.my">
                    <p class="field-hint">They sign in with this, and it must not already be in use.</p>
                    <x-field-error name="email" />
                </div>

                <div class="field">
                    <label for="f-role">Role</label>
                    <select id="f-role" name="role" required>
                        <option value="" selected disabled>Choose one</option>
                        <option value="evaluator" {{ old('role') === 'evaluator' ? 'selected' : '' }}>
                            Evaluator &mdash; reviews applications and marks assessments
                        </option>
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>
                            Administrator &mdash; runs the registry queue and finalises decisions
                        </option>
                    </select>
                    {{-- store() rejects 'student' with exactly this reason. --}}
                    <p class="field-hint">
                        Candidates are not created here &mdash; they register themselves.
                    </p>
                    <x-field-error name="role" />
                </div>

                <p class="note">
                    A temporary password is generated when you save. It is shown to you once, on the
                    next screen &mdash; hand it over directly and ask them to reset it.
                </p>

                <button type="submit" class="btn btn-primary">Create the account</button>
            </form>
        </section>
    </div>
@endsection

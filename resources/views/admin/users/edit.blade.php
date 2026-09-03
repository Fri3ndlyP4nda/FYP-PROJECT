@extends('layouts.app')

@section('content')
    {{--
        Editing one account.

        Two rules in update() can refuse a change, and both are surprising if
        you meet them only after pressing save: an administrator cannot change
        their own role, and an evaluator holding live applications cannot be
        moved off the role. Both are stated here, on the control they govern,
        before the change is attempted - the server still enforces them, this
        just stops the reader walking into them.
    --}}
    @php
        $id = (string) ($user->_id ?? $user->id);
        $isSelf = $id === (string) auth()->id();
        $isBusyEvaluator = $user->role === 'evaluator' && $activeAssignments > 0;
    @endphp

    <div class="deck deck--narrow">
        <header class="deck-head">
            <div>
                <p class="deck-eyebrow">Account</p>
                <h1 class="deck-title">{{ $user->name }}</h1>
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

        <section class="panel" aria-labelledby="who-head">
            <h2 class="panel-head" id="who-head">On record</h2>
            <dl class="kv">
                <div><dt>Email</dt><dd>{{ $user->email }}</dd></div>
                <div><dt>Role</dt><dd>{{ ucfirst($user->role) }}</dd></div>
                @if ($user->role === 'evaluator')
                    <div>
                        <dt>Live assignments</dt>
                        <dd>{{ $activeAssignments }}</dd>
                    </div>
                @endif
                @if ($user->created_at)
                    <div>
                        <dt>Added</dt>
                        <dd>{{ \Carbon\Carbon::parse($user->created_at)->format('j M Y') }}</dd>
                    </div>
                @endif
            </dl>
        </section>

        <section class="panel" aria-labelledby="edit-head">
            <h2 class="panel-head" id="edit-head">Change this account</h2>

            <form method="POST" action="{{ route('admin.users.update', $id) }}" class="stack-form">
                @csrf
                @method('PUT')

                <div class="field">
                    <label for="f-name">Full name</label>
                    <input type="text" id="f-name" name="name" required maxlength="255"
                           value="{{ old('name', $user->name) }}">
                    <x-field-error name="name" />
                </div>

                <div class="field">
                    <label for="f-role">Role</label>
                    <select id="f-role" name="role" required @disabled($isSelf)>
                        @foreach (['student' => 'Candidate', 'evaluator' => 'Evaluator', 'admin' => 'Administrator'] as $value => $label)
                            <option value="{{ $value }}" {{ old('role', $user->role) === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>

                    @if ($isSelf)
                        {{-- update() refuses this outright: with one admin account,
                             a self-demotion locks the institution out for good. --}}
                        <input type="hidden" name="role" value="{{ $user->role }}">
                        <p class="field-hint">
                            You cannot change your own role. Ask another administrator to do it.
                        </p>
                    @elseif ($isBusyEvaluator)
                        <p class="field-hint">
                            {{ $user->name }} is assigned to {{ $activeAssignments }}
                            live {{ Str::plural('application', $activeAssignments) }}. Moving them off
                            the evaluator role will be refused until those are reassigned &mdash;
                            otherwise the applications would be left with an evaluator who can no
                            longer open them.
                        </p>
                    @endif

                    <x-field-error name="role" />
                </div>

                <button type="submit" class="btn btn-primary">Save changes</button>
            </form>
        </section>

        @if (! $isSelf && Route::has('admin.users.destroy'))
            <section class="panel panel--edge" aria-labelledby="danger-head">
                <h2 class="panel-head" id="danger-head">Remove this account</h2>
                <p>
                    This cannot be undone.
                    @if ($isBusyEvaluator)
                        {{ $user->name }} is holding {{ $activeAssignments }} live
                        {{ Str::plural('application', $activeAssignments) }} &mdash; reassign those first.
                    @endif
                </p>
                <form method="POST" action="{{ route('admin.users.destroy', $id) }}"
                      onsubmit="return confirm('Remove {{ addslashes($user->name) }}? This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Remove {{ $user->name }}</button>
                </form>
            </section>
        @endif
    </div>
@endsection

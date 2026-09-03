@extends('layouts.app')

@section('content')
    {{--
        Staff and candidate accounts.

        Grouped by role rather than listed alphabetically in one table, because
        the three roles are not interchangeable and an administrator is almost
        always looking for one kind. Evaluators carry their live workload, so
        the consequence of demoting or removing someone is visible on the row
        rather than discovered afterwards.
    --}}
    @php
        $groups = [
            'evaluator' => ['title' => 'Evaluators', 'note' => 'Review applications and mark assessments.'],
            'admin' => ['title' => 'Administrators', 'note' => 'Run the queue and finalise decisions.'],
            'student' => ['title' => 'Candidates', 'note' => 'Registered themselves. Listed for reference.'],
        ];
    @endphp

    <div class="deck">
        <header class="deck-head">
            <div>
                <p class="deck-eyebrow">Accounts</p>
                <h1 class="deck-title">
                    {{ $counts['evaluator'] }} {{ Str::plural('evaluator', $counts['evaluator']) }},
                    {{ $counts['admin'] }} {{ Str::plural('admin', $counts['admin']) }}
                </h1>
            </div>
            <div class="deck-acts">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Dashboard</a>
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Add someone</a>
            </div>
        </header>

        @if (session('success'))
            <p class="notice notice--good" role="status">{{ session('success') }}</p>
        @endif
        @if (session('error'))
            <p class="notice notice--bad" role="alert">{{ session('error') }}</p>
        @endif

        <form method="GET" action="{{ route('admin.users.index') }}" class="sift" role="search">
            <div class="field">
                <label for="q" class="sr-only">Search by name or email</label>
                <input type="search" id="q" name="q" value="{{ $search }}"
                       placeholder="Search by name or email">
            </div>

            <div class="field">
                <label for="role-filter" class="sr-only">Filter by role</label>
                <select id="role-filter" name="role">
                    <option value="">Every role</option>
                    @foreach ($groups as $key => $meta)
                        <option value="{{ $key }}" {{ $role === $key ? 'selected' : '' }}>
                            {{ $meta['title'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn btn-secondary">Search</button>

            @if ($search || $role)
                <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">Clear</a>
            @endif
        </form>

        @if ($users->isEmpty())
            <section class="blank">
                <h2>Nothing matches that.</h2>
                <p>
                    @if ($search || $role)
                        Try a different name, email or role.
                    @else
                        There are no accounts yet.
                    @endif
                </p>
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Show everyone</a>
            </section>
        @else
            @foreach ($groups as $key => $meta)
                @php $set = $users->where('role', $key); @endphp
                @continue($set->isEmpty())

                <section class="stack" aria-labelledby="ug-{{ $key }}">
                    <div class="stack-head">
                        <h2 id="ug-{{ $key }}">
                            {{ $meta['title'] }}
                            <span class="stack-count">{{ $set->count() }}</span>
                        </h2>
                        <p>{{ $meta['note'] }}</p>
                    </div>

                    <div class="stack-body stack-body--rows">
                        @foreach ($set as $user)
                            @php
                                $id = (string) ($user->_id ?? $user->id);
                                $load = $workload[$id] ?? null;
                                $isSelf = $id === (string) auth()->id();
                            @endphp

                            <article class="row-case">
                                <div class="row-case-tell">
                                    <div class="case-top">
                                        <span class="badge badge--{{ $key === 'admin' ? 'appeal' : ($key === 'evaluator' ? 'progress' : 'neutral') }}">
                                            {{ ucfirst($user->role) }}
                                        </span>
                                        @if ($isSelf)
                                            <span class="badge badge--good">You</span>
                                        @endif
                                    </div>

                                    <h3 class="row-case-title">{{ $user->name }}</h3>
                                    <p class="row-case-meta">{{ $user->email }}</p>

                                    @if ($key === 'evaluator')
                                        {{--
                                            Live workload, so the cost of demoting or
                                            deleting this person is on the row rather
                                            than discovered after the fact.
                                        --}}
                                        <p class="{{ $load > 0 ? 'row-case-act' : 'row-case-wait' }}">
                                            {{ $load > 0
                                                ? $load.' '.Str::plural('application', $load).' assigned right now'
                                                : 'Nothing assigned' }}
                                        </p>
                                    @endif
                                </div>

                                <div class="row-acts">
                                    <a class="btn btn-ghost btn--sm" href="{{ route('admin.users.edit', $id) }}">
                                        Edit
                                    </a>

                                    @if (! $isSelf && Route::has('admin.users.destroy'))
                                        <form method="POST" action="{{ route('admin.users.destroy', $id) }}"
                                              onsubmit="return confirm('Remove {{ addslashes($user->name) }}? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn--sm">Remove</button>
                                        </form>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        @endif
    </div>
@endsection

@extends('layouts.app')

@section('content')
    {{--
        The APEL A slice of the registry queue.

        The table this replaces had six columns, three of which - Status, Stage
        and Final Decision - are all derived from the single stage field and so
        could only restate one another. Grouped by whose move it is, the top of
        the page is the work.
    --}}
    <div class="deck">
        <header class="deck-head">
            <div>
                <p class="deck-eyebrow">APEL A &mdash; admission</p>
                <h1 class="deck-title">
                    {{ $cases->count() }} {{ Str::plural('application', $cases->count()) }}
                </h1>
            </div>
            <div class="deck-acts">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Dashboard</a>
                <a href="{{ route('admin.applications.index') }}" class="btn btn-secondary">Both tracks</a>
            </div>
        </header>

        @if (session('success'))
            <p class="notice notice--good" role="status">{{ session('success') }}</p>
        @endif

        @if ($cases->isEmpty())
            <section class="blank">
                <h2>No APEL A applications yet.</h2>
                <p>They appear here once a candidate submits one.</p>
            </section>
        @else
            @php
                $groups = [
                    ['key' => 'you', 'title' => 'Needs the registry', 'note' => 'Nothing moves until the office acts.', 'set' => $needsYou],
                    ['key' => 'else', 'title' => 'With someone else', 'note' => 'Waiting on the candidate or an evaluator.', 'set' => $elsewhere],
                    ['key' => 'done', 'title' => 'Closed', 'note' => 'Decided.', 'set' => $closed],
                ];
            @endphp

            @foreach ($groups as $group)
                @continue($group['set']->isEmpty())
                <section class="stack" aria-labelledby="aa-{{ $group['key'] }}">
                    <div class="stack-head">
                        <h2 id="aa-{{ $group['key'] }}">
                            {{ $group['title'] }}
                            <span class="stack-count">{{ $group['set']->count() }}</span>
                        </h2>
                        <p>{{ $group['note'] }}</p>
                    </div>

                    <div class="stack-body stack-body--rows">
                        @foreach ($group['set'] as $case)
                            @php
                                $application = $case['application'];
                                $stage = $case['stage'];
                                $action = $case['action'];
                                $who = $names[(string) $application->user_id] ?? null;
                            @endphp

                            <article class="row-case">
                                <div class="row-case-tell">
                                    <div class="case-top">
                                        <span class="badge badge--type">APEL A</span>
                                        @if ($stage)
                                            <span class="badge badge--{{ $stage->tone() }}">
                                                {{ $stage->label('APEL A') }}
                                            </span>
                                        @endif
                                        <span class="case-ref">
                                            {{ strtoupper(substr((string) $application->_id, -6)) }}
                                        </span>
                                    </div>

                                    <h3 class="row-case-title">{{ $who?->name ?? 'Candidate no longer on file' }}</h3>

                                    <p class="row-case-meta">
                                        {{ $application->program_applied ?: 'Programme not stated' }}
                                        @if ($application->submission_date)
                                            &nbsp;·&nbsp;
                                            submitted {{ \Carbon\Carbon::parse($application->submission_date)->format('j M Y') }}
                                        @endif
                                    </p>

                                    @if ($action)
                                        <p class="row-case-act">{{ $action['title'] }}</p>
                                    @elseif ($stage && ! $stage->isTerminal())
                                        <p class="row-case-wait">
                                            Waiting on {{ $stage->awaitsStudent() ? 'the candidate' : 'an evaluator' }}.
                                        </p>
                                    @endif
                                </div>

                                @if (Route::has('admin.applications.assign'))
                                    <a class="btn {{ $action ? 'btn-primary' : 'btn-ghost' }} btn--sm"
                                       href="{{ route('admin.applications.assign', $application->_id) }}">
                                        Open
                                    </a>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        @endif
    </div>
@endsection

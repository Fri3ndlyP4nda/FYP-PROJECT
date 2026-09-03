@extends('layouts.app')

@section('content')
    {{--
        The candidate's own state.

        What stood here was three cards reading "Manage Applications", "Track
        Review Status" and "Stay Informed" - a description of the product, on
        the one screen where the reader already knows what the product is and
        wants to know what has happened to their case. Nothing on it changed
        when their application changed.

        Someone signing in has one question: is anything waiting on me, and if
        not, who is it with? So that is the order - what you owe, then what is
        moving, then what is finished - and the rail inside each case is the
        real ApelStage pipeline, so it cannot drift from the workflow.
    --}}
    <div class="deck">
        <header class="deck-head">
            <div>
                <p class="deck-eyebrow">Your applications</p>
                <h1 class="deck-title">{{ auth()->user()->name }}</h1>
            </div>
            <a href="{{ route('student.applications.create') }}" class="btn btn-primary">Start a new application</a>
        </header>

        @if ($cases->isEmpty())
            <section class="blank">
                <h2>You have not applied yet.</h2>
                <p>
                    APEL assesses work you have already done and turns it into university standing
                    &mdash; entry to a programme, or credit against a course you would otherwise sit.
                    You can save an application as a draft and finish it later.
                </p>
                <a href="{{ route('student.applications.create') }}" class="btn btn-primary">Start your first application</a>
            </section>
        @else
            @php
                $groups = [
                    [
                        'key' => 'you',
                        'title' => $yourMove->count() === 1 ? 'Needs you' : 'Need you',
                        'note' => 'Nothing moves until you do this.',
                        'set' => $yourMove,
                    ],
                    [
                        'key' => 'moving',
                        'title' => 'Moving',
                        'note' => 'With the faculty. Nothing for you to do.',
                        'set' => $inProgress,
                    ],
                    [
                        'key' => 'closed',
                        'title' => 'Closed',
                        'note' => 'Decided.',
                        'set' => $closed,
                    ],
                ];
            @endphp

            @foreach ($groups as $group)
                @continue($group['set']->isEmpty())
                <section class="stack" aria-labelledby="grp-{{ $group['key'] }}">
                    <div class="stack-head">
                        <h2 id="grp-{{ $group['key'] }}">
                            {{ $group['title'] }}
                            <span class="stack-count">{{ $group['set']->count() }}</span>
                        </h2>
                        <p>{{ $group['note'] }}</p>
                    </div>

                    <div class="stack-body">
                        @foreach ($group['set'] as $case)
                            @include('student.partials._case', ['case' => $case])
                        @endforeach
                    </div>
                </section>
            @endforeach
        @endif
    </div>
@endsection

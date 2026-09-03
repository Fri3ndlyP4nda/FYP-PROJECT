@extends('layouts.app')

@section('content')
    {{--
        The evaluator's queue.

        This was four stat tiles - total claims, graded, pending, average score.
        A count cannot be worked. An evaluator between classes wants the list of
        things blocked on them, in an order they can start at the top of and
        work down; the numbers are context for that, not the point of the page.

        The counts were also wrong. Every query filtered to APEL C and matched
        evaluator_id alone, so an evaluator assigned an APEL A case, or named as
        the second evaluator on any case, saw a dashboard of zeroes. Fixed in
        AuthController::evaluatorDashboard.
    --}}
    <div class="deck">
        <header class="deck-head">
            <div>
                <p class="deck-eyebrow">Assigned to you</p>
                <h1 class="deck-title">{{ auth()->user()->name }}</h1>
            </div>

            <dl class="tally">
                <div>
                    <dt>Assigned</dt>
                    <dd>{{ $assignedCount }}</dd>
                </div>
                <div>
                    <dt>Awaiting grading</dt>
                    <dd>{{ $awaitingGrading }}</dd>
                </div>
                <div>
                    <dt>Graded</dt>
                    <dd>{{ $gradedCount }}</dd>
                </div>
            </dl>
        </header>

        @if ($assignedCount === 0)
            <section class="blank">
                <h2>Nothing is assigned to you.</h2>
                <p>
                    Applications appear here once the registry assigns you to them. You will be
                    notified when that happens.
                </p>
                @if (Route::has('evaluator.assessment.papers.index'))
                    <a href="{{ route('evaluator.assessment.papers.index') }}" class="btn btn-secondary">
                        Your assessment papers
                    </a>
                @endif
            </section>
        @else
            @php
                $groups = [
                    [
                        'key' => 'mine',
                        'title' => 'Waiting on you',
                        'note' => 'These do not move until you report.',
                        'set' => $waitingOnMe,
                    ],
                    [
                        'key' => 'others',
                        'title' => 'With someone else',
                        'note' => 'Assigned to you, but the next step is not yours.',
                        'set' => $withOthers,
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
                <section class="stack" aria-labelledby="egrp-{{ $group['key'] }}">
                    <div class="stack-head">
                        <h2 id="egrp-{{ $group['key'] }}">
                            {{ $group['title'] }}
                            <span class="stack-count">{{ $group['set']->count() }}</span>
                        </h2>
                        <p>{{ $group['note'] }}</p>
                    </div>

                    <div class="stack-body stack-body--rows">
                        @foreach ($group['set'] as $case)
                            @include('evaluator.partials._row', ['case' => $case])
                        @endforeach
                    </div>
                </section>
            @endforeach
        @endif
    </div>
@endsection

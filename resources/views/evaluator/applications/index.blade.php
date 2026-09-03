@extends('layouts.app')

@section('content')
    {{--
        The full queue. Same grouping and the same row as the evaluator's
        dashboard, because it is the same question with a longer list.

        Sorted by date, an evaluator has to read every row to find the ones they
        can act on. Grouped by whose move it is, the first group is the work and
        everything below it is context they can ignore.
    --}}
    <div class="deck">
        <header class="deck-head">
            <div>
                <p class="deck-eyebrow">Assigned to you</p>
                <h1 class="deck-title">{{ $cases->count() }} {{ Str::plural('application', $cases->count()) }}</h1>
            </div>
            <div class="deck-acts">
                <a href="{{ route('evaluator.dashboard') }}" class="btn btn-secondary">Dashboard</a>
                @if (Route::has('evaluator.assessment.grading.index'))
                    <a href="{{ route('evaluator.assessment.grading.index') }}" class="btn btn-secondary">Grading</a>
                @endif
            </div>
        </header>

        @if (session('success'))
            <p class="notice notice--good" role="status">{{ session('success') }}</p>
        @endif

        @if ($cases->isEmpty())
            <section class="blank">
                <h2>Nothing is assigned to you.</h2>
                <p>
                    Applications appear here once the registry assigns you to them. You will be
                    notified when that happens.
                </p>
            </section>
        @else
            @php
                $groups = [
                    ['key' => 'mine', 'title' => 'Waiting on you', 'note' => 'These do not move until you report.', 'set' => $waitingOnMe],
                    ['key' => 'others', 'title' => 'With someone else', 'note' => 'Assigned to you, but the next step is not yours.', 'set' => $withOthers],
                    ['key' => 'closed', 'title' => 'Closed', 'note' => 'Decided.', 'set' => $closed],
                ];
            @endphp

            @foreach ($groups as $group)
                @continue($group['set']->isEmpty())
                <section class="stack" aria-labelledby="qgrp-{{ $group['key'] }}">
                    <div class="stack-head">
                        <h2 id="qgrp-{{ $group['key'] }}">
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

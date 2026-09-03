@extends('layouts.app')

@section('content')
    {{--
        The APEL A slice of the same queue.

        Grouped and drawn exactly like evaluator/applications/index, using the
        same row, because it is the same work filtered to one track. A five
        column table sorted by date - programme, status, stage, decision,
        action - made the evaluator read every row to find the ones they could
        act on, and printed "status" and "stage" side by side when one is
        derived from the other.
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
                <a href="{{ route('evaluator.dashboard') }}" class="btn btn-secondary">Dashboard</a>
                <a href="{{ route('evaluator.applications.index') }}" class="btn btn-secondary">Both tracks</a>
            </div>
        </header>

        @if (session('success'))
            <p class="notice notice--good" role="status">{{ session('success') }}</p>
        @endif

        @if ($cases->isEmpty())
            <section class="blank">
                <h2>No APEL A applications are assigned to you.</h2>
                <p>They appear here once the registry assigns you to one.</p>
                <a href="{{ route('evaluator.applications.index') }}" class="btn btn-secondary">See both tracks</a>
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
                <section class="stack" aria-labelledby="ag-{{ $group['key'] }}">
                    <div class="stack-head">
                        <h2 id="ag-{{ $group['key'] }}">
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

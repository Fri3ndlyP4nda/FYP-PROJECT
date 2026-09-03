@extends('layouts.app')

@section('content')
    {{--
        Every application the candidate has, in the same shape as their
        dashboard - because it is the same question asked with a longer list,
        and two different answers to it would just be two things to learn.

        The counts that used to head this page were computed here in Blade by
        substring-matching the legacy status string, and were wrong for three of
        the four outcomes; see the note in Student\ApplicationController::index.
        Grouping by stage is both correct and more useful than a tally, so the
        tally is gone rather than repaired.
    --}}
    <div class="deck">
        <header class="deck-head">
            <div>
                <p class="deck-eyebrow">All your applications</p>
                <h1 class="deck-title">{{ $cases->count() }} {{ Str::plural('application', $cases->count()) }}</h1>
            </div>
            <div class="deck-acts">
                <a href="{{ route('student.dashboard') }}" class="btn btn-secondary">Dashboard</a>
                <a href="{{ route('student.applications.create') }}" class="btn btn-primary">Start a new application</a>
            </div>
        </header>

        @if (session('success'))
            <p class="notice notice--good" role="status">{{ session('success') }}</p>
        @endif

        @if ($cases->isEmpty())
            <section class="blank">
                <h2>Nothing here yet.</h2>
                <p>
                    Your applications will appear here once you start one. You can save a draft and
                    come back to it.
                </p>
                <a href="{{ route('student.applications.create') }}" class="btn btn-primary">Start your first application</a>
            </section>
        @else
            @php
                $groups = [
                    ['key' => 'you', 'title' => 'Needs you', 'note' => 'Nothing moves until you do this.', 'set' => $yourMove],
                    ['key' => 'moving', 'title' => 'Moving', 'note' => 'With the faculty.', 'set' => $inProgress],
                    ['key' => 'closed', 'title' => 'Closed', 'note' => 'Decided.', 'set' => $closed],
                ];
            @endphp

            @foreach ($groups as $group)
                @continue($group['set']->isEmpty())
                <section class="stack" aria-labelledby="sgrp-{{ $group['key'] }}">
                    <div class="stack-head">
                        <h2 id="sgrp-{{ $group['key'] }}">
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

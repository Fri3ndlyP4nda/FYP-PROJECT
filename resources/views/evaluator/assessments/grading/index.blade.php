@extends('layouts.app')

@section('content')
    {{--
        The grading queue.

        What stood here was three stat tiles above a six-column table -
        submission id, application id, student id, answer file, score, result.
        Half of it was identifiers, which is what a database shows you, not what
        an evaluator needs: whose work this is, whether it is waiting on them,
        and a way in.

        Grouped the same way as everything else the evaluator sees, so the top
        of the page is the work.
    --}}
    <div class="deck">
        <header class="deck-head">
            <div>
                <p class="deck-eyebrow">APEL C grading</p>
                <h1 class="deck-title">
                    {{ $awaiting->count() }} {{ Str::plural('submission', $awaiting->count()) }} to mark
                </h1>
            </div>
            <div class="deck-acts">
                <a href="{{ route('evaluator.dashboard') }}" class="btn btn-secondary">Dashboard</a>
                <a href="{{ route('evaluator.assessment.papers.index') }}" class="btn btn-secondary">Papers</a>
            </div>
        </header>

        @if (session('success'))
            <p class="notice notice--good" role="status">{{ session('success') }}</p>
        @endif

        @if ($submissions->isEmpty())
            <section class="blank">
                <h2>No submissions yet.</h2>
                <p>
                    Answer scripts appear here once a candidate submits one against a paper you set
                    or an application you are assigned to.
                </p>
                <a href="{{ route('evaluator.assessment.papers.index') }}" class="btn btn-secondary">Your papers</a>
            </section>
        @else
            @php
                $groups = [
                    ['key' => 'todo', 'title' => 'Waiting on you', 'note' => 'Nothing is decided until these are marked.', 'set' => $awaiting],
                    ['key' => 'done', 'title' => 'Marked', 'note' => 'Already graded.', 'set' => $graded],
                ];
            @endphp

            @foreach ($groups as $group)
                @continue($group['set']->isEmpty())
                <section class="stack" aria-labelledby="gg-{{ $group['key'] }}">
                    <div class="stack-head">
                        <h2 id="gg-{{ $group['key'] }}">
                            {{ $group['title'] }}
                            <span class="stack-count">{{ $group['set']->count() }}</span>
                        </h2>
                        <p>{{ $group['note'] }}</p>
                    </div>

                    <div class="stack-body stack-body--rows">
                        @foreach ($group['set'] as $submission)
                            @php
                                $application = $applications[(string) $submission->application_id] ?? null;
                                $student = $students[(string) $submission->student_id] ?? null;
                                $isGraded = $submission->graded_at !== null;
                            @endphp

                            <article class="row-case">
                                <div class="row-case-tell">
                                    <div class="case-top">
                                        <span class="badge badge--type">APEL C</span>
                                        @if ($isGraded)
                                            <span class="badge badge--{{ $submission->result === 'pass' ? 'good' : 'bad' }}">
                                                {{ $submission->result === 'pass' ? 'Passed' : 'Not passed' }}
                                                @if (filled($submission->score))
                                                    &nbsp;{{ $submission->score }}%
                                                @endif
                                            </span>
                                        @else
                                            <span class="badge badge--attention">To mark</span>
                                        @endif
                                        <span class="case-ref">
                                            {{ strtoupper(substr((string) $submission->_id, -6)) }}
                                        </span>
                                    </div>

                                    <h3 class="row-case-title">
                                        {{ $student?->name ?? 'Candidate no longer on file' }}
                                    </h3>

                                    <p class="row-case-meta">
                                        {{ $application?->credit_course_name
                                            ?: ($application?->credit_course_code
                                                ?: ($application?->program_applied ?: 'Course not stated')) }}
                                        @if ($submission->submitted_at)
                                            &nbsp;·&nbsp;
                                            submitted {{ \Carbon\Carbon::parse($submission->submitted_at)->format('j M Y') }}
                                        @endif
                                    </p>

                                    @if ($submission->answer_file)
                                        <p class="row-case-meta">
                                            <a href="{{ route('files.submission', $submission->_id) }}"
                                               target="_blank" rel="noopener">Open the answer script</a>
                                        </p>
                                    @endif
                                </div>

                                <a class="btn {{ $isGraded ? 'btn-ghost' : 'btn-primary' }} btn--sm"
                                   href="{{ route('evaluator.assessment.grading.show', $submission->_id) }}">
                                    {{ $isGraded ? 'Review marking' : 'Mark this' }}
                                </a>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        @endif
    </div>
@endsection

@extends('layouts.app')

@section('content')
    {{--
        Sitting the assessment.

        The candidate has one job here and a deadline attached to it, so the
        deadline is stated in the open rather than buried in the paper's
        metadata, and the upload states the rules the server actually enforces
        (PDF or Word, 10MB, one submission only) before they choose a file
        rather than after it is rejected.
    --}}
    @php
        use Carbon\Carbon;

        $deadline = $paper->submission_deadline ? Carbon::parse($paper->submission_deadline) : null;
        $isPast = $deadline?->isPast() ?? false;
        $hasSubmitted = $submission && filled($submission->answer_file);
        $isGraded = $submission && filled($submission->result);
    @endphp

    <div class="deck deck--narrow">
        <header class="deck-head">
            <div>
                <p class="deck-eyebrow">Your assessment</p>
                <h1 class="deck-title">{{ $paper->title ?: 'Assessment' }}</h1>
            </div>
            <div class="deck-acts">
                <a href="{{ route('student.applications.index') }}" class="btn btn-secondary">All applications</a>
            </div>
        </header>

        @if (session('success'))
            <p class="notice notice--good" role="status">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <div class="notice notice--bad" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{-- Where they stand, before the paper itself. --}}
        @php
            [$tone, $head, $body] = match (true) {
                $isGraded => [
                    $submission->result === 'pass' ? 'good' : 'bad',
                    $submission->result === 'pass' ? 'You passed' : 'You did not pass',
                    'Your evaluator has marked this. The full result is on your application.',
                ],
                $hasSubmitted => [
                    'progress',
                    'Submitted',
                    'Your answer is with the evaluator. Nothing further is needed from you.',
                ],
                $isPast => [
                    'bad',
                    'The deadline has passed',
                    'This assessment can no longer be submitted. Contact the faculty office if you believe this is wrong.',
                ],
                default => [
                    'attention',
                    'Waiting on you',
                    'Read the paper, then upload your answer below.',
                ],
            };
        @endphp

        <section class="lede-card lede-card--{{ $tone }}" aria-labelledby="state-head">
            <p class="lede-kicker">Right now</p>
            <h2 class="lede-head" id="state-head">{{ $head }}</h2>
            <p class="lede-body">{{ $body }}</p>
            @if ($deadline && ! $hasSubmitted && ! $isPast)
                <div class="lede-foot">
                    <span class="lede-due">
                        Due {{ $deadline->format('j M Y, H:i') }} &mdash; {{ $deadline->diffForHumans() }}
                    </span>
                </div>
            @endif
        </section>

        <section class="panel" aria-labelledby="paper-head">
            <h2 class="panel-head" id="paper-head">The paper</h2>

            @if (filled($paper->instructions))
                <div class="said">
                    <h3>Instructions from your evaluator</h3>
                    <p>{{ $paper->instructions }}</p>
                </div>
            @endif

            @if ($paper->question_file)
                <p class="note">
                    <a href="{{ route('files.paper', $paper->_id) }}" target="_blank" rel="noopener">
                        Open the question paper
                    </a>
                </p>
            @else
                <p class="muted">Your evaluator has not attached a question file to this paper.</p>
            @endif
        </section>

        <section class="panel" aria-labelledby="answer-head">
            <h2 class="panel-head" id="answer-head">Your answer</h2>

            @if ($hasSubmitted)
                <dl class="kv">
                    <div>
                        <dt>Submitted</dt>
                        <dd>
                            {{ $submission->submitted_at
                                ? Carbon::parse($submission->submitted_at)->format('j M Y, H:i')
                                : 'Recorded' }}
                        </dd>
                    </div>
                </dl>

                <p class="note">
                    <a href="{{ route('files.submission', $submission->_id) }}" target="_blank" rel="noopener">
                        Open what you submitted
                    </a>
                </p>

                @if (filled($submission->grader_feedback))
                    <div class="said">
                        <h3>Evaluator feedback</h3>
                        <p>{{ $submission->grader_feedback }}</p>
                    </div>
                @endif

                <p class="muted">
                    An assessment can only be submitted once. If something is wrong with what you
                    sent, contact the faculty office.
                </p>
            @elseif ($isPast)
                <p class="muted">The deadline has passed, so no answer can be uploaded.</p>
            @else
                <form method="POST" action="{{ route('student.assessment.submit', $application->_id) }}"
                      enctype="multipart/form-data" class="stack-form">
                    @csrf

                    <div class="field">
                        <label for="f-answer">Your answer file</label>
                        <input type="file" name="answer_file" id="f-answer"
                               accept=".pdf,.doc,.docx" required>
                        {{-- The rules the server enforces, stated before the choice. --}}
                        <p class="field-hint">
                            PDF or Word, up to 10MB. You can submit once, so check it before you send.
                        </p>
                        <x-field-error name="answer_file" />
                    </div>

                    <button type="submit" class="btn btn-primary">Submit my answer</button>
                </form>
            @endif
        </section>
    </div>
@endsection

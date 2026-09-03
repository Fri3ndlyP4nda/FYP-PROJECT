@extends('layouts.app')

@section('content')
    {{--
        Reviewing one application.

        An evaluator opens this to do one thing: read the evidence and record a
        recommendation. So the page is the evidence, then the form - not a
        record dump with the form buried in a sidebar three screens down.

        The candidate's submitted form runs to hundreds of fields and is
        reference material, so it is folded away rather than placed between the
        reviewer and their task.
    --}}
    @php
        use App\Domain\Apel\ApelStage;

        $stage = $case['stage'];
        $action = $case['action'];
        $isC = $case['type'] === 'APEL C';
        $me = (string) auth()->id();

        $seat = (string) ($application->evaluator_id ?? '') === $me ? 1
            : ((string) ($application->evaluator_2_id ?? '') === $me ? 2 : null);

        $mine = fn (string $field) => $seat ? $application->{"evaluator_{$seat}_{$field}"} : null;
        $iHaveReviewed = $seat !== null && filled($mine('reviewed_at'));

        // The other seat, so a reviewer can see whether the case is waiting on
        // them alone or on both of them.
        $otherSeat = $seat === 1 ? 2 : ($seat === 2 ? 1 : null);
        $otherHasReviewed = $otherSeat !== null
            && filled($application->{"evaluator_{$otherSeat}_id"})
            && filled($application->{"evaluator_{$otherSeat}_reviewed_at"});
        $otherExists = $otherSeat !== null && filled($application->{"evaluator_{$otherSeat}_id"});

        $decisionLabel = [
            'recommended' => 'Recommended',
            'not_recommended' => 'Not recommended',
            'pending' => 'Not yet decided',
        ];
    @endphp

    <div class="deck deck--narrow">
        <header class="deck-head">
            <div>
                <p class="deck-eyebrow">
                    {{ $case['type'] }} &nbsp;·&nbsp; {{ strtoupper(substr((string) $application->_id, -6)) }}
                </p>
                <h1 class="deck-title">{{ $candidate?->name ?? 'Candidate no longer on file' }}</h1>
            </div>
            <div class="deck-acts">
                <a href="{{ route('evaluator.applications.index') }}" class="btn btn-secondary">All assigned</a>
                @if ($isC && Route::has('evaluator.assessment.grading.index'))
                    <a href="{{ route('evaluator.assessment.grading.index') }}" class="btn btn-secondary">Grading</a>
                @endif
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

        <section class="lede-card lede-card--{{ $stage?->tone() ?? 'progress' }}" aria-labelledby="now-head">
            <p class="lede-kicker">Your move</p>
            <h2 class="lede-head" id="now-head">
                {{ $action['title'] ?? ($stage?->label($case['type']) ?? 'Nothing outstanding') }}
            </h2>
            <p class="lede-body">
                @if ($action)
                    {{ $action['body'] }}
                @elseif ($stage?->isTerminal())
                    This application is closed. Your review is on the record.
                @else
                    Nothing is waiting on you. The next step belongs to
                    {{ $stage?->awaitsStudent() ? 'the candidate' : 'the registry' }}.
                @endif
            </p>
        </section>

        <div class="split">
            <section class="panel" aria-labelledby="rail-head">
                <h2 class="panel-head" id="rail-head">Where this sits</h2>
                @if (!empty($case['rail']))
                    <ol class="spine" style="--spine-done: {{ (int) $case['progress'] }}%">
                        @foreach ($case['rail'] as $node)
                            @php
                                $state = $node['state'];
                                if ($state !== 'upcoming' && in_array($node['tone'], ['bad', 'no'], true)) {
                                    $state = 'failed';
                                }
                            @endphp
                            <li class="spine-node" data-state="{{ $state }}">
                                <span class="spine-label">{{ $node['label'] }}</span>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="muted">This application has no recorded stage.</p>
                @endif
            </section>

            <section class="panel" aria-labelledby="who-head">
                <h2 class="panel-head" id="who-head">The candidate</h2>
                <dl class="kv">
                    <div>
                        <dt>{{ $isC ? 'Course' : 'Programme' }}</dt>
                        <dd>
                            {{ $isC
                                ? ($application->credit_course_name ?: ($application->credit_course_code ?: 'Not stated'))
                                : ($application->program_applied ?: 'Not stated') }}
                        </dd>
                    </div>
                    @if ($application->company_name)
                        <div><dt>Employer</dt><dd>{{ $application->company_name }}</dd></div>
                    @endif
                    @if ($application->university_name)
                        <div><dt>Institution</dt><dd>{{ $application->university_name }}</dd></div>
                    @endif
                    @if ($application->age)
                        <div><dt>Age</dt><dd>{{ $application->age }}</dd></div>
                    @endif
                    <div>
                        <dt>Submitted</dt>
                        <dd>
                            {{ $application->submission_date
                                ? \Carbon\Carbon::parse($application->submission_date)->format('j M Y')
                                : 'Not recorded' }}
                        </dd>
                    </div>
                    @if ($otherExists)
                        <div>
                            <dt>Second reviewer</dt>
                            <dd>{{ $otherHasReviewed ? 'Has reported' : 'Has not reported' }}</dd>
                        </div>
                    @endif
                </dl>
            </section>
        </div>

        {{-- The evidence. This is what the reviewer is here to read. --}}
        <section class="panel" aria-labelledby="ev-head">
            <h2 class="panel-head" id="ev-head">The evidence</h2>

            @php
                $fileGroups = ['evidence_file' => 'Evidence', 'portfolio_file' => 'Portfolio', 'supporting_docs' => 'Supporting'];
                $anyFiles = collect($fileGroups)->keys()->contains(fn ($f) => filled($application->{$f}));
            @endphp

            @if ($anyFiles)
                <ul class="files">
                    @foreach ($fileGroups as $field => $label)
                        @php $files = $application->{$field}; @endphp
                        @continue(blank($files))
                        @foreach ((array) $files as $file)
                            @php
                                $path = is_array($file) ? ($file['path'] ?? '') : (string) $file;
                                $name = is_array($file) ? ($file['name'] ?? basename($path)) : basename($path);
                            @endphp
                            @continue($path === '')
                            <li>
                                <span class="files-kind">{{ $label }}</span>
                                <a href="{{ route('files.application', ['application' => $application->_id, 'path' => $path]) }}"
                                   target="_blank" rel="noopener">{{ $name }}</a>
                            </li>
                        @endforeach
                    @endforeach
                </ul>
            @else
                <p class="muted">The candidate has not uploaded any files.</p>
            @endif

            @if ($isC && $submission && $submission->answer_file)
                <p class="note">
                    <a href="{{ route('files.submission', $submission->_id) }}" target="_blank" rel="noopener">
                        Open the submitted answer script
                    </a>
                    @if (Route::has('evaluator.assessment.grading.show'))
                        &nbsp;·&nbsp;
                        <a href="{{ route('evaluator.assessment.grading.show', $submission->_id) }}">Mark it</a>
                    @endif
                </p>
            @endif

            @if ($isC && $paper)
                <p class="note">
                    Assessment set: <strong>{{ $paper->title }}</strong>
                    @if ($paper->question_file)
                        &nbsp;·&nbsp;
                        <a href="{{ route('files.paper', $paper->_id) }}" target="_blank" rel="noopener">Open the paper</a>
                    @endif
                </p>
            @endif
        </section>

        @if (filled($application->working_experience_details) || filled($application->reason_applying))
            <details class="fold">
                <summary>What the candidate wrote</summary>
                @if (filled($application->working_experience_details))
                    <div class="said">
                        <h3>Working experience</h3>
                        <p>{{ $application->working_experience_details }}</p>
                    </div>
                @endif
                @if (filled($application->reason_applying))
                    <div class="said">
                        <h3>Why they are applying</h3>
                        <p>{{ $application->reason_applying }}</p>
                    </div>
                @endif
            </details>
        @endif

        @if (filled($application->pre_app_data))
            <details class="fold">
                <summary>The full submitted form</summary>
                @include('student.apel_c._submitted', ['data' => $application->pre_app_data])
            </details>
        @endif

        {{-- The reviewer's own report. --}}
        <section class="panel" aria-labelledby="rev-head">
            <h2 class="panel-head" id="rev-head">Your recommendation</h2>

            @if ($seat === null)
                <p class="muted">You are not one of the assigned reviewers on this application.</p>
            @elseif ($iHaveReviewed)
                @php $d = $mine('decision') ?: 'pending'; @endphp
                <p class="outcome outcome--{{ $d === 'recommended' ? 'good' : ($d === 'not_recommended' ? 'bad' : 'progress') }}">
                    {{ $decisionLabel[$d] ?? ucfirst((string) $d) }}
                </p>

                @if (filled($mine('feedback')))
                    <div class="said">
                        <h3>What you wrote</h3>
                        <p>{{ $mine('feedback') }}</p>
                    </div>
                @endif

                <dl class="kv">
                    <div>
                        <dt>Reported</dt>
                        <dd>{{ \Carbon\Carbon::parse($mine('reviewed_at'))->format('j M Y, H:i') }}</dd>
                    </div>
                    @if ($otherExists)
                        <div>
                            <dt>Second reviewer</dt>
                            <dd>{{ $otherHasReviewed ? 'Has reported' : 'Still to report' }}</dd>
                        </div>
                    @endif
                </dl>

                {{--
                    Keyed off the stage. This used to test
                    status against 'Final Approved' / 'Final Rejected', strings
                    the application stopped writing when the stage machine
                    landed - APEL A now records "Admission approved" and APEL C
                    "Credit awarded" - so the panel never appeared.
                --}}
                @if ($stage?->isTerminal())
                    <p class="note note--good">
                        Decided: <strong>{{ $stage->label($case['type']) }}</strong>.
                    </p>
                @endif
            @else
                <form method="POST" action="{{ route('evaluator.applications.update', $application->_id) }}"
                      class="stack-form">
                    @csrf

                    {{--
                        No "pending" option. update() validates this as
                        `in:recommended,not_recommended`, so the old form's
                        Pending choice - which was also the one preselected -
                        could only ever come back as a validation error. This is
                        a submission, not a draft: leaving it unanswered means
                        not submitting.
                    --}}
                    <div class="field">
                        <label for="f-admission-decision">Decision</label>
                        <select name="admission_decision" id="f-admission-decision" required>
                            <option value="" selected disabled>Choose one</option>
                            <option value="recommended">Recommend</option>
                            <option value="not_recommended">Do not recommend</option>
                        </select>
                        <x-field-error name="admission_decision" />
                    </div>

                    <div class="field">
                        <label for="f-evaluator-feedback">Your reasons</label>
                        <textarea name="evaluator_feedback" id="f-evaluator-feedback" rows="8" required
                                  maxlength="1000"
                                  placeholder="What the evidence shows, and where it falls short."></textarea>
                        <p class="field-hint">
                            Required, up to 1000 characters. The candidate sees this with their outcome.
                        </p>
                        <x-field-error name="evaluator_feedback" />
                    </div>

                    @if ($otherExists && ! $otherHasReviewed)
                        <p class="note">
                            A second reviewer is assigned and has not reported. The application will
                            not advance until both of you have.
                        </p>
                    @endif

                    <button type="submit" class="btn btn-primary">Submit your recommendation</button>
                </form>
            @endif
        </section>
    </div>
@endsection

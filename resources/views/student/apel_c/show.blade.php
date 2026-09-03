@extends('layouts.app')

@section('content')
    {{--
        One APEL C application, as the candidate reads it.

        The progress tracker that stood here was hard-coded in Blade with its
        own step list and a match() on $application->status. StageMachine writes
        status as $stage->label($type), and none of the labels matched the arms,
        which still expected the pre-stage-machine spellings - so all 19 stages
        fell to `default => 0` and every candidate saw step 1 of 8 regardless of
        where they actually were. It is now ApelStage::rail().

        The panels below are ordered by when the candidate needs them: what to
        do now, where it sits, the result, then the paperwork.
    --}}
    @php
        use App\Domain\Apel\ApelStage;

        $stage = $case['stage'];
        $action = $case['action'];
        $paymentStatus = $application->payment_status ?? 'pending';

        $evaluatorName = $application->evaluator_id
            ? (\App\Models\User::where('_id', $application->evaluator_id)->value('name') ?: 'Unknown')
            : null;

        $assessmentPaper = \App\Models\AssessmentPaper::where('application_id', (string) $application->_id)
            ->where('status', 'active')
            ->first();

        $submission = \App\Models\AssessmentSubmission::where('application_id', (string) $application->_id)->first();

        $isPortfolio = ($application->assessment_type ?? '') === 'portfolio';

        // Gate the appeal on the stage. The old test also accepted
        // status === 'Final Rejected', a string the application stopped writing
        // when the stage machine landed - APEL C now records "Credit not
        // awarded" - so that arm was dead and the panel depended entirely on
        // credit_decision having been set.
        $isRejected = $stage === ApelStage::REJECTED
            || ($application->credit_decision ?? '') === 'rejected';
    @endphp

    <div class="deck deck--narrow">
        <header class="deck-head">
            <div>
                <p class="deck-eyebrow">
                    APEL C &nbsp;·&nbsp; {{ strtoupper(substr((string) $application->_id, -6)) }}
                </p>
                <h1 class="deck-title">
                    {{ $application->credit_course_name ?: ($application->program_applied ?: 'Course not yet stated') }}
                </h1>
            </div>
            <div class="deck-acts">
                <a href="{{ route('student.applications.index') }}" class="btn btn-secondary">All applications</a>
                <a href="{{ route('student.applications.print', $application->_id) }}"
                   target="_blank" rel="noopener" class="btn btn-secondary">Print portfolio</a>
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
            <p class="lede-kicker">Right now</p>
            <h2 class="lede-head" id="now-head">
                {{ $action['title'] ?? ($stage?->label('APEL C') ?? 'Not started') }}
            </h2>
            <p class="lede-body">{{ $action['body'] ?? $case['explanation'] }}</p>

            @if ($action)
                <div class="lede-foot">
                    @if (!empty($action['cta']['route']) && Route::has($action['cta']['route']))
                        <a class="btn btn-primary" href="{{ route($action['cta']['route'], $action['cta']['params']) }}">
                            {{ $action['cta']['label'] }}
                        </a>
                    @endif
                    @if (!empty($action['deadline']))
                        <span class="lede-due">Due {{ $action['deadline']->format('j M Y') }}</span>
                    @endif
                </div>
            @endif
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

            <section class="panel" aria-labelledby="record-head">
                <h2 class="panel-head" id="record-head">The record</h2>
                <dl class="kv">
                    <div>
                        <dt>Submitted</dt>
                        <dd>
                            @if ($stage === ApelStage::DRAFT)
                                Not submitted yet
                            @elseif ($application->submission_date)
                                {{ \Carbon\Carbon::parse($application->submission_date)->format('j M Y') }}
                            @else
                                Not submitted yet
                            @endif
                        </dd>
                    </div>
                    <div><dt>Stage</dt><dd>{{ $stage?->label('APEL C') ?? 'Unknown' }}</dd></div>
                    @if ($application->credit_course_code)
                        <div><dt>Course</dt><dd>{{ $application->credit_course_code }}</dd></div>
                    @endif
                    <div>
                        <dt>Assessment</dt>
                        <dd>{{ $isPortfolio ? 'Portfolio' : 'Written paper' }}</dd>
                    </div>
                    <div><dt>Evaluator</dt><dd>{{ $evaluatorName ?? 'Not assigned yet' }}</dd></div>
                </dl>
            </section>
        </div>

        {{-- The result, only once there is one. --}}
        @if ($stage?->isTerminal() || filled($application->credit_remarks) || filled($application->evaluator_feedback))
            <section class="panel" aria-labelledby="outcome-head">
                <h2 class="panel-head" id="outcome-head">The decision</h2>

                @if ($stage?->isTerminal())
                    <p class="outcome outcome--{{ $stage->tone() }}">{{ $stage->label('APEL C') }}</p>
                @endif

                @if (filled($application->credit_hours_approved))
                    <dl class="kv">
                        <div>
                            <dt>Credit hours awarded</dt>
                            <dd>{{ $application->credit_hours_approved }}</dd>
                        </div>
                    </dl>
                @endif

                @if (filled($application->credit_remarks))
                    <div class="said">
                        <h3>Faculty remarks</h3>
                        <p>{{ $application->credit_remarks }}</p>
                    </div>
                @endif

                @if (filled($application->evaluator_feedback))
                    <div class="said">
                        <h3>Evaluator feedback</h3>
                        <p>{{ $application->evaluator_feedback }}</p>
                    </div>
                @endif
            </section>
        @endif

        {{-- Assessment: the graded result, or the way in to sitting it. --}}
        <section class="panel" aria-labelledby="assess-head">
            <h2 class="panel-head" id="assess-head">Assessment</h2>

            @if ($submission && $submission->graded_at)
                <p class="outcome outcome--{{ $submission->result === 'pass' ? 'good' : 'bad' }}">
                    {{ $submission->result === 'pass' ? 'Passed' : 'Not passed' }}
                    @if (filled($submission->score))
                        &nbsp;·&nbsp; {{ $submission->score }}%
                    @endif
                </p>

                @if (isset($submission->evaluator_1_clo1) || isset($submission->evaluator_2_clo1))
                    {{--
                        The rule is stated, not just the numbers. A candidate who
                        scored well overall and still failed has no way to work
                        out why from four marks alone, and this is the single
                        question they will ask: you must reach 5 of 10 on every
                        outcome, so a strong showing in three cannot carry a
                        weak fourth (AssessmentGradingController:116).
                    --}}
                    <p class="muted clo-rule">
                        Each outcome is marked out of 10. A pass needs at least 5 on
                        <strong>every</strong> one of them &mdash; a high total cannot make up for a
                        single outcome below 5.
                    </p>

                    @foreach ([1, 2] as $n)
                        @continue(! isset($submission->{"evaluator_{$n}_clo1"}))
                        <div class="clo-set">
                            <h3>Evaluator {{ $n }}</h3>
                            <ul class="clo-list">
                                @foreach ([1, 2, 3, 4] as $i)
                                    @php $mark = (int) $submission->{"evaluator_{$n}_clo{$i}"}; @endphp
                                    <li class="clo {{ $mark >= 5 ? 'is-pass' : 'is-fail' }}">
                                        <span class="clo-name">CLO{{ $i }}</span>
                                        <span class="clo-mark">{{ $mark }}<span>/10</span></span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                @endif
            @elseif ($isPortfolio)
                <p>
                    Your advisor recommended assessment by <strong>portfolio</strong>. The evaluator is
                    reviewing what you uploaded. Nothing is needed from you.
                </p>
            @elseif ($assessmentPaper)
                <p>The evaluator has set your assessment paper.</p>
                <dl class="kv">
                    <div><dt>Title</dt><dd>{{ $assessmentPaper->title }}</dd></div>
                </dl>
                <a href="{{ route('student.assessment.show', $application->_id) }}" class="btn btn-primary">
                    Open the assessment
                </a>
            @else
                <p class="muted">
                    No assessment has been set yet. The evaluator will publish one once your payment
                    is verified.
                </p>
            @endif
        </section>

        {{-- Payment. The form only where paying is actually the next step. --}}
        <section class="panel" aria-labelledby="pay-head">
            <h2 class="panel-head" id="pay-head">Payment</h2>

            <dl class="kv">
                <div>
                    <dt>Fee</dt>
                    <dd>{{ $application->payment_type ?? 'APEL C processing fee' }}</dd>
                </div>
                <div><dt>Status</dt><dd>{{ ucfirst($paymentStatus) }}</dd></div>
                @if ($application->payment_receipt)
                    <div>
                        <dt>Your receipt</dt>
                        <dd>
                            <a href="{{ route('files.application', ['application' => $application->_id, 'path' => $application->payment_receipt]) }}"
                               target="_blank" rel="noopener">View receipt</a>
                        </dd>
                    </div>
                @endif
            </dl>

            @if (filled($application->payment_remarks) && in_array($paymentStatus, ['submitted', 'verified'], true))
                <div class="said">
                    <h3>Remarks</h3>
                    <p>{{ $application->payment_remarks }}</p>
                </div>
            @endif

            @if ($paymentStatus === 'verified')
                <p class="note note--good">Your payment has been verified by the faculty office.</p>
            @elseif ($paymentStatus === 'submitted')
                <p class="note">Your receipt is with the faculty office. Nothing further is needed from you.</p>
            @elseif ($stage === ApelStage::PAYMENT_DUE || $stage === ApelStage::PAYMENT_REJECTED)
                <form method="POST" action="{{ route('student.applications.payment', $application->_id) }}"
                      enctype="multipart/form-data" class="stack-form">
                    @csrf

                    <div class="field">
                        <label for="f-payment-receipt">Payment receipt</label>
                        <input type="file" name="payment_receipt" id="f-payment-receipt"
                               accept=".pdf,.jpg,.jpeg,.png" required>
                        <p class="field-hint">PDF, JPG or PNG, up to 5MB.</p>
                        <x-field-error name="payment_receipt" />
                    </div>

                    <div class="field">
                        <label for="f-payment-remarks">Anything the office should know</label>
                        <textarea name="payment_remarks" id="f-payment-remarks" rows="3"
                                  placeholder="For example: paid by online transfer on 3 September.">{{ old('payment_remarks', $application->payment_remarks) }}</textarea>
                        <x-field-error name="payment_remarks" />
                    </div>

                    <button type="submit" class="btn btn-primary">Send receipt</button>
                </form>
            @else
                <p class="muted">No fee is payable at this stage.</p>
            @endif
        </section>

        {{-- Appeal, only on a refusal. --}}
        @if ($isRejected)
            <section class="panel panel--edge" aria-labelledby="appeal-head">
                <h2 class="panel-head" id="appeal-head">Appeal</h2>

                @if (($application->appeal_status ?? null) === 'submitted')
                    <p class="note note--good">Your appeal is with the faculty office and is being re-examined.</p>
                    <dl class="kv">
                        <div>
                            <dt>Submitted</dt>
                            <dd>
                                {{ $application->appeal_submitted_at
                                    ? \Carbon\Carbon::parse($application->appeal_submitted_at)->format('j M Y, H:i')
                                    : 'Recorded' }}
                            </dd>
                        </div>
                    </dl>
                    @if (filled($application->appeal_remarks))
                        <div class="said">
                            <h3>What you said</h3>
                            <p>{{ $application->appeal_remarks }}</p>
                        </div>
                    @endif
                @else
                    <p>
                        Credit was not awarded. Under UTM APEL C regulations you may appeal by setting
                        out how your prior learning meets the course outcomes.
                    </p>

                    <form method="POST" action="{{ route('student.applications.appeal', $application->_id) }}"
                          class="stack-form">
                        @csrf
                        <div class="field">
                            <label for="f-appeal-remarks">Your grounds for appeal</label>
                            <textarea name="appeal_remarks" id="f-appeal-remarks" rows="6" required
                                      placeholder="Set out which outcomes you believe your experience already meets, and what evidence supports that."></textarea>
                            <x-field-error name="appeal_remarks" />
                        </div>
                        <button type="submit" class="btn btn-primary">Submit appeal</button>
                    </form>
                @endif
            </section>
        @endif

        {{-- The paperwork, folded away: it is a reference, not something to read. --}}
        @if (filled($application->evidence_file) || filled($application->portfolio_file))
            <details class="fold">
                <summary>Files you uploaded</summary>
                <ul class="files">
                    {{--
                        An entry is either a bare path or ['path' =>, 'name' =>]
                        depending on when it was uploaded, so both shapes are
                        unwrapped rather than assuming the newer one.
                    --}}
                    @foreach (['evidence_file' => 'Evidence', 'portfolio_file' => 'Portfolio'] as $field => $label)
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
            </details>
        @endif

        @if (filled($application->pre_app_data))
            <details class="fold">
                <summary>Your submitted application</summary>
                @include('student.apel_c._submitted', ['data' => $application->pre_app_data])
            </details>
        @endif
    </div>
@endsection

@extends('layouts.app')

@section('content')
    {{--
        The registry's working screen for one application.

        Six forms live here - advisor recommendation, payment verification,
        evaluator assignment, the two finalisations, and a manual stage
        override - and the old page rendered all of them at once, so an officer
        looking at a draft was shown a "Final Credit Decision" form for a case
        that had not been assessed. Every one of them is now gated on the stage,
        which is the same thing the controller checks, so the page can only
        offer moves the workflow will actually accept.

        That also fixes a gate that could never open. The advisor form was shown
        only when status was 'Pre-Application Submitted' or 'Under Advisor
        Review'; StageMachine writes status as $stage->label($type), which for
        those two stages is "Pre-application submitted" and "Advisor review".
        Neither string matched, so the form never rendered - and an APEL C
        application reaching advisor review had no way for the registry to
        record the decision. It was invisible only because no application was
        sitting at that stage.
    --}}
    @php
        use App\Domain\Apel\ApelStage;
        use App\Domain\Apel\StageMachine;
        use App\Support\ApplicationCase;

        $stage = ApplicationCase::stageOf($application);
        $type = (string) $application->application_type;
        $isC = $type === 'APEL C';

        $student = \App\Models\User::where('_id', (string) $application->user_id)->first();

        // Every gate below reads the stage, never a status string.
        $canAdvise = $isC && in_array($stage, [ApelStage::SUBMITTED, ApelStage::ADVISOR_REVIEW], true);
        $canTakePayment = in_array($stage, [ApelStage::PAYMENT_SUBMITTED], true);
        $canAssign = in_array($stage, [ApelStage::PAYMENT_VERIFIED, ApelStage::EVALUATOR_ASSIGNED], true);
        $canFinalise = $stage === ApelStage::AWAITING_DECISION;

        $nextStages = $stage ? StageMachine::nextStages($application) : [];

        $assigned = collect([$application->evaluator_id, $application->evaluator_2_id])
            ->filter()
            ->map(fn ($id) => \App\Models\User::where('_id', (string) $id)->value('name') ?: 'Unknown')
            ->values();
    @endphp

    <div class="deck deck--narrow">
        <header class="deck-head">
            <div>
                <p class="deck-eyebrow">
                    {{ $type }} &nbsp;·&nbsp; {{ strtoupper(substr((string) $application->_id, -6)) }}
                </p>
                <h1 class="deck-title">{{ $student?->name ?? 'Candidate no longer on file' }}</h1>
            </div>
            <div class="deck-acts">
                <a href="{{ route('admin.applications.index') }}" class="btn btn-secondary">The queue</a>
                @if (! $isC && Route::has('admin.applications.brief'))
                    <a href="{{ route('admin.applications.brief', $application->_id) }}" class="btn btn-secondary">
                        Evaluator brief
                    </a>
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
            <p class="lede-kicker">Where this is</p>
            <h2 class="lede-head" id="now-head">{{ $stage?->label($type) ?? 'No recorded stage' }}</h2>
            <p class="lede-body">
                @if ($canAdvise)
                    Waiting for the advisor's recommendation to be recorded.
                @elseif ($canTakePayment)
                    A receipt is in and needs checking against the faculty record.
                @elseif ($canAssign)
                    Payment is verified. This needs an evaluator.
                @elseif ($canFinalise)
                    Every review is in. This is the registry's decision to make.
                @elseif ($stage?->isTerminal())
                    Decided. Nothing further will happen to this application.
                @else
                    Nothing is waiting on the registry. The next step belongs to
                    {{ $stage?->awaitsStudent() ? 'the candidate' : 'an evaluator' }}.
                @endif
            </p>
        </section>

        <div class="split">
            <section class="panel" aria-labelledby="case-head">
                <h2 class="panel-head" id="case-head">The case</h2>
                <dl class="kv">
                    <div>
                        <dt>{{ $isC ? 'Course' : 'Programme' }}</dt>
                        <dd>
                            {{ $isC
                                ? ($application->credit_course_name ?: ($application->credit_course_code ?: 'Not stated'))
                                : ($application->program_applied ?: 'Not stated') }}
                        </dd>
                    </div>
                    @if ($student)
                        <div><dt>Email</dt><dd>{{ $student->email }}</dd></div>
                    @endif
                    @if ($application->company_name)
                        <div><dt>Employer</dt><dd>{{ $application->company_name }}</dd></div>
                    @endif
                    <div>
                        <dt>Submitted</dt>
                        <dd>
                            {{ $application->submission_date
                                ? \Carbon\Carbon::parse($application->submission_date)->format('j M Y')
                                : 'Not recorded' }}
                        </dd>
                    </div>
                    <div>
                        <dt>Evaluators</dt>
                        <dd>{{ $assigned->isEmpty() ? 'None assigned' : $assigned->join(', ') }}</dd>
                    </div>
                </dl>
            </section>

            <section class="panel" aria-labelledby="ev-head">
                <h2 class="panel-head" id="ev-head">What was uploaded</h2>

                @php
                    $groups = ['evidence_file' => 'Evidence', 'portfolio_file' => 'Portfolio', 'supporting_docs' => 'Supporting', 'payment_receipt' => 'Receipt'];
                    $any = collect($groups)->keys()->contains(fn ($f) => filled($application->{$f}));
                @endphp

                @if ($any)
                    <ul class="files">
                        @foreach ($groups as $field => $label)
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
                    <p class="muted">Nothing has been uploaded.</p>
                @endif
            </section>
        </div>

        {{-- ADVISOR — APEL C only, and only while it is genuinely awaited. --}}
        @if ($canAdvise)
            <section class="panel" aria-labelledby="adv-head">
                <h2 class="panel-head" id="adv-head">Record the advisor's recommendation</h2>

                <form method="POST" action="{{ route('admin.applications.advisor_approve', $application->_id) }}"
                      class="stack-form">
                    @csrf

                    <div class="field">
                        <label for="f-advisor-name">Advisor</label>
                        <select name="advisor_name" id="f-advisor-name" required>
                            <option value="" selected disabled>Choose one</option>
                            <option value="Ts Dr. Maheyzah Md Siraj">Ts Dr. Maheyzah Md Siraj</option>
                            <option value="Dr. Hajar">Dr. Hajar</option>
                        </select>
                        <x-field-error name="advisor_name" />
                    </div>

                    <fieldset class="marks">
                        <legend class="sr-only">Course learning outcome attainment</legend>
                        @foreach (['clo1', 'clo2', 'clo3', 'clo4'] as $i => $key)
                            <div class="mark">
                                <label for="f-adv-{{ $key }}">Outcome {{ $i + 1 }}</label>
                                <div class="mark-row">
                                    <input type="number" id="f-adv-{{ $key }}"
                                           name="advisor_evaluation[{{ $key }}]"
                                           min="1" max="4" step="1" required inputmode="numeric"
                                           value="{{ old('advisor_evaluation.'.$key) }}">
                                    <span class="mark-of">/ 4</span>
                                </div>
                            </div>
                        @endforeach
                    </fieldset>
                    <p class="field-hint">The advisor's judgement of competence per outcome, 1 to 4.</p>
                    <x-field-error name="advisor_evaluation" />

                    <div class="field">
                        <label for="recommendation_status">Recommendation</label>
                        <select name="recommendation_status" id="recommendation_status" required>
                            <option value="" selected disabled>Choose one</option>
                            <option value="Recommended">Recommended &mdash; proceed to assessment</option>
                            <option value="NOT recommended">Not recommended &mdash; stop here, no fee is taken</option>
                        </select>
                        <x-field-error name="recommendation_status" />
                    </div>

                    <div class="field">
                        <label for="f-mode">Mode of assessment</label>
                        <select name="mode_of_assessment" id="f-mode" required>
                            <option value="" selected disabled>Choose one</option>
                            <option value="portfolio">Portfolio</option>
                            <option value="test">Written paper</option>
                        </select>
                        <x-field-error name="mode_of_assessment" />
                    </div>

                    <div class="field">
                        <label for="f-advisor-remarks">Advisor remarks</label>
                        <textarea name="advisor_remarks" id="f-advisor-remarks" rows="4"
                                  maxlength="1000">{{ old('advisor_remarks') }}</textarea>
                        <x-field-error name="advisor_remarks" />
                    </div>

                    <button type="submit" class="btn btn-primary">Save the recommendation</button>
                </form>
            </section>
        @endif

        {{-- PAYMENT --}}
        @if ($canTakePayment)
            <section class="panel" aria-labelledby="pay-head">
                <h2 class="panel-head" id="pay-head">Check the payment</h2>

                @if ($application->payment_receipt)
                    <p class="note">
                        <a href="{{ route('files.application', ['application' => $application->_id, 'path' => $application->payment_receipt]) }}"
                           target="_blank" rel="noopener">Open the receipt the candidate uploaded</a>
                    </p>
                @endif
                @if (filled($application->payment_remarks))
                    <div class="said">
                        <h3>What the candidate said</h3>
                        <p>{{ $application->payment_remarks }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.applications.update_payment', $application->_id) }}"
                      class="stack-form">
                    @csrf

                    <div class="field">
                        <label for="f-pay-status">Decision</label>
                        <select name="payment_status" id="f-pay-status" required>
                            <option value="" selected disabled>Choose one</option>
                            <option value="verified">Verified &mdash; the fee is paid</option>
                            <option value="rejected">Not accepted &mdash; send it back</option>
                        </select>
                        <x-field-error name="payment_status" />
                    </div>

                    <div class="field">
                        <label for="f-pay-ref">Faculty receipt reference</label>
                        <input type="text" name="payment_reference" id="f-pay-ref" maxlength="255"
                               value="{{ old('payment_reference') }}">
                        {{-- required_if verified: the controller says so, so the label does too. --}}
                        <p class="field-hint">Required when verifying &mdash; what you checked it against.</p>
                        <x-field-error name="payment_reference" />
                    </div>

                    <div class="field">
                        <label for="f-pay-remarks">Reason, if not accepted</label>
                        <textarea name="payment_remarks" id="f-pay-remarks" rows="3"
                                  maxlength="1000">{{ old('payment_remarks') }}</textarea>
                        <p class="field-hint">Required when rejecting &mdash; the candidate sees this and must be able to fix it.</p>
                        <x-field-error name="payment_remarks" />
                    </div>

                    <button type="submit" class="btn btn-primary">Record the decision</button>
                </form>
            </section>
        @endif

        {{-- ASSIGNMENT --}}
        @if ($canAssign)
            <section class="panel" aria-labelledby="asg-head">
                <h2 class="panel-head" id="asg-head">Assign evaluators</h2>

                @if (!empty($evaluatorRecommendations) && count($evaluatorRecommendations) > 0)
                    <p class="clo-rule">
                        Ranked by current load and past turnaround. A second evaluator is optional;
                        when there are two, the application waits for both.
                    </p>

                    <ul class="flags">
                        @foreach ($evaluatorRecommendations as $rec)
                            <li class="flag">
                                <strong>{{ $rec['name'] }}</strong>
                                <span>{{ $rec['recommendation_reason'] ?? '' }}</span>
                                <small>
                                    {{ $rec['active_assignments'] }} active ·
                                    {{ $rec['pending_submissions'] }} to mark ·
                                    {{ $rec['completed_reviews'] }} done
                                    @if (($rec['average_completion_days'] ?? 0) > 0)
                                        · {{ $rec['average_completion_days'] }} days average
                                    @endif
                                </small>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <form method="POST" action="{{ route('admin.applications.assign', $application->_id) }}"
                      class="stack-form">
                    @csrf

                    <div class="field">
                        <label for="f-eval-1">Evaluator</label>
                        <select name="evaluator_id" id="f-eval-1" required>
                            <option value="" selected disabled>Choose one</option>
                            @foreach ($evaluators as $evaluator)
                                <option value="{{ $evaluator->_id }}"
                                        {{ (string) $application->evaluator_id === (string) $evaluator->_id ? 'selected' : '' }}>
                                    {{ $evaluator->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-field-error name="evaluator_id" />
                    </div>

                    <div class="field">
                        <label for="f-eval-2">Second evaluator</label>
                        <select name="evaluator_2_id" id="f-eval-2">
                            <option value="">None</option>
                            @foreach ($evaluators as $evaluator)
                                <option value="{{ $evaluator->_id }}"
                                        {{ (string) $application->evaluator_2_id === (string) $evaluator->_id ? 'selected' : '' }}>
                                    {{ $evaluator->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="field-hint">Optional, and must be someone different.</p>
                        <x-field-error name="evaluator_2_id" />
                    </div>

                    <button type="submit" class="btn btn-primary">Assign</button>
                </form>
            </section>
        @endif

        {{-- FINAL DECISION --}}
        @if ($canFinalise)
            <section class="panel" aria-labelledby="fin-head">
                <h2 class="panel-head" id="fin-head">Make the final decision</h2>

                @if (filled($application->evaluator_feedback))
                    <div class="said">
                        <h3>Evaluator feedback</h3>
                        <p>{{ $application->evaluator_feedback }}</p>
                    </div>
                @endif

                <p class="note">This cannot be changed once saved.</p>

                @if ($isC)
                    <form method="POST" action="{{ route('admin.applications.finalize_apel_c', $application->_id) }}"
                          class="stack-form">
                        @csrf

                        <div class="field">
                            <label for="f-credit">Credit decision</label>
                            <select name="credit_decision" id="f-credit" required>
                                <option value="" selected disabled>Choose one</option>
                                <option value="approved">Award the credit</option>
                                <option value="rejected">Do not award</option>
                            </select>
                            <x-field-error name="credit_decision" />
                        </div>

                        <div class="field">
                            <label for="f-course-code">Course code</label>
                            <input type="text" name="credit_course_code" id="f-course-code" maxlength="100"
                                   value="{{ old('credit_course_code', $application->credit_course_code) }}">
                            <x-field-error name="credit_course_code" />
                        </div>

                        <div class="field">
                            <label for="f-course-name">Course name</label>
                            <input type="text" name="credit_course_name" id="f-course-name" maxlength="255"
                                   value="{{ old('credit_course_name', $application->credit_course_name) }}">
                            <x-field-error name="credit_course_name" />
                        </div>

                        <div class="field">
                            <label for="f-credit-remarks">Remarks</label>
                            <textarea name="credit_remarks" id="f-credit-remarks" rows="4"
                                      maxlength="1000">{{ old('credit_remarks') }}</textarea>
                            <p class="field-hint">The candidate sees this with their outcome.</p>
                            <x-field-error name="credit_remarks" />
                        </div>

                        <button type="submit" class="btn btn-primary">Save the decision</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.applications.finalize_apel_a', $application->_id) }}"
                          class="stack-form">
                        @csrf

                        <div class="field">
                            <label for="f-final">Admission decision</label>
                            <select name="final_decision" id="f-final" required>
                                <option value="" selected disabled>Choose one</option>
                                <option value="approved">Admit</option>
                                <option value="rejected">Do not admit</option>
                            </select>
                            <x-field-error name="final_decision" />
                        </div>

                        <div class="field">
                            <label for="f-final-remarks">Remarks</label>
                            <textarea name="final_decision_remarks" id="f-final-remarks" rows="4"
                                      maxlength="1000">{{ old('final_decision_remarks') }}</textarea>
                            <p class="field-hint">The candidate sees this with their outcome.</p>
                            <x-field-error name="final_decision_remarks" />
                        </div>

                        <button type="submit" class="btn btn-primary">Save the decision</button>
                    </form>
                @endif
            </section>
        @endif

        {{-- APEL A support, kept as reference where it exists. --}}
        @if (! $isC && !empty($apelAEligibility['criteria']))
            <details class="fold">
                <summary>Entry scorecard</summary>
                <ul class="checks">
                    @foreach ($apelAEligibility['criteria'] as $criterion)
                        @php
                            $status = strtolower((string) ($criterion['status'] ?? ''));
                            [$mark, $state] = match ($status) {
                                'pass' => ['✓', 'is-met'],
                                'fail' => ['✕', 'is-unmet'],
                                default => ['!', 'is-warned'],
                            };
                        @endphp
                        <li class="check {{ $state }}">
                            <span class="check-mark" aria-hidden="true">{{ $mark }}</span>
                            <span>
                                <strong>{{ $criterion['name'] ?? 'Criterion' }}</strong>
                                @if (!empty($criterion['message']))
                                    <span class="check-msg">{{ $criterion['message'] }}</span>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </details>
        @endif

        @if (filled($application->pre_app_data))
            <details class="fold">
                <summary>The submitted application</summary>
                @include('student.apel_c._submitted', ['data' => $application->pre_app_data])
            </details>
        @endif

        {{--
            The manual override. Last, folded, and built from
            StageMachine::nextStages() - so it can only ever offer moves the
            machine will accept, and a reason is required because it becomes
            part of the audit trail.
        --}}
        @if (!empty($nextStages))
            <details class="fold">
                <summary>Move this by hand</summary>

                <p class="muted">
                    Only for correcting a mistake. Every move is recorded against your name.
                </p>

                <form method="POST" action="{{ route('admin.applications.update_status', $application->_id) }}"
                      class="stack-form">
                    @csrf

                    <div class="field">
                        <label for="f-stage">Move to</label>
                        <select name="stage" id="f-stage" required>
                            <option value="" selected disabled>Choose one</option>
                            @foreach ($nextStages as $next)
                                <option value="{{ $next->value }}">{{ $next->label($type) }}</option>
                            @endforeach
                        </select>
                        <x-field-error name="stage" />
                    </div>

                    <div class="field">
                        <label for="f-reason">Why</label>
                        <textarea name="reason" id="f-reason" rows="3" required maxlength="500"
                                  placeholder="What went wrong, and what you are correcting."></textarea>
                        <x-field-error name="reason" />
                    </div>

                    <button type="submit" class="btn btn-secondary">Move it</button>
                </form>
            </details>
        @endif
    </div>
@endsection

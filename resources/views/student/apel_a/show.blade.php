@extends('layouts.app')

@section('content')
    {{--
        One APEL A application, as the candidate reads it.

        The progress tracker that stood here was hard-coded in Blade and keyed
        on status strings the application stopped writing when the stage machine
        landed, so all 19 stages fell through to step 0 and every candidate saw
        "just submitted" no matter how far along they were. It is now
        ApelStage::rail(), which is the same source the workflow itself uses.

        Order follows what the reader wants: what is happening now, then where
        that sits in the whole process, then the decision if there is one, then
        the record. The derived legacy fields - review_stage, payment_status -
        are gone from the face of the page: they are computed from the stage and
        could only ever repeat it back in different words.
    --}}
    @php
        $stage = $case['stage'];
        $action = $case['action'];
        $evaluatorName = $application->evaluator_id
            ? (\App\Models\User::where('_id', $application->evaluator_id)->value('name') ?: 'Unknown')
            : null;
    @endphp

    <div class="deck deck--narrow">
        <header class="deck-head">
            <div>
                <p class="deck-eyebrow">
                    APEL A &nbsp;·&nbsp; {{ strtoupper(substr((string) $application->_id, -6)) }}
                </p>
                <h1 class="deck-title">{{ $application->program_applied ?: 'Programme not yet stated' }}</h1>
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

        {{-- What is happening now. First, because it is the reason they opened the page. --}}
        <section class="lede-card lede-card--{{ $stage?->tone() ?? 'progress' }}" aria-labelledby="now-head">
            <p class="lede-kicker">Right now</p>
            <h2 class="lede-head" id="now-head">
                {{ $action['title'] ?? ($stage?->label('APEL A') ?? 'Not started') }}
            </h2>
            <p class="lede-body">
                {{ $action['body'] ?? $case['explanation'] }}
            </p>

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
                            @if ($stage === \App\Domain\Apel\ApelStage::DRAFT)
                                Not submitted yet
                            @elseif ($application->submission_date)
                                {{ \Carbon\Carbon::parse($application->submission_date)->format('j M Y') }}
                            @else
                                Not submitted yet
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt>Stage</dt>
                        <dd>{{ $stage?->label('APEL A') ?? 'Unknown' }}</dd>
                    </div>
                    <div>
                        <dt>Reviewer</dt>
                        <dd>{{ $evaluatorName ?? 'Not assigned yet' }}</dd>
                    </div>
                    @if ($application->university_name)
                        <div>
                            <dt>Institution</dt>
                            <dd>{{ $application->university_name }}</dd>
                        </div>
                    @endif
                    @if ($application->company_name)
                        <div>
                            <dt>Employer</dt>
                            <dd>{{ $application->company_name }}</dd>
                        </div>
                    @endif
                </dl>
            </section>
        </div>

        {{--
            Feedback is shown only once it exists. An empty panel reading "No
            evaluator feedback available yet" on a case that has not reached a
            reviewer tells the candidate nothing and reads as something missing.
        --}}
        @php
            $hasDecision = $stage?->isTerminal()
                || filled($application->evaluator_feedback)
                || filled($application->final_decision_remarks);
        @endphp

        @if ($hasDecision)
            <section class="panel" aria-labelledby="outcome-head">
                <h2 class="panel-head" id="outcome-head">The decision</h2>

                @if ($stage?->isTerminal())
                    <p class="outcome outcome--{{ $stage->tone() }}">{{ $stage->label('APEL A') }}</p>
                @endif

                @if (filled($application->final_decision_remarks))
                    <div class="said">
                        <h3>Faculty remarks</h3>
                        <p>{{ $application->final_decision_remarks }}</p>
                    </div>
                @endif

                @if (filled($application->evaluator_feedback))
                    <div class="said">
                        <h3>Reviewer feedback</h3>
                        <p>{{ $application->evaluator_feedback }}</p>
                    </div>
                @endif
            </section>
        @endif
    </div>
@endsection

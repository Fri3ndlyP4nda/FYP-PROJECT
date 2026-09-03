@extends('layouts.app')

@section('content')
    {{--
        The pre-assignment brief for one APEL A application.

        This is decision support, not a decision. Everything on it is generated
        by ApelDecisionSupportService from what the candidate submitted, and it
        exists so the officer choosing an evaluator knows how much work the case
        is and where the evidence is thin - before anyone's time is committed.

        It is labelled as generated throughout, because the one way a screen
        like this does harm is by being mistaken for a ruling.
    --}}
    @php
        $class = $brief['classification'] ?? [];
        $elig = $brief['eligibility'] ?? [];
        $gaps = collect($brief['evidence_gaps'] ?? []);
        $critical = collect($brief['critical_failures'] ?? []);
        $focus = collect($brief['focus_areas'] ?? []);

        // The service speaks in low/medium/high; the page speaks in tone.
        $level = strtolower((string) ($class['level'] ?? 'medium'));
        $tone = match ($level) {
            'high' => 'bad',
            'low' => 'good',
            default => 'attention',
        };

        $severityTone = fn (string $s) => match (strtolower($s)) {
            'critical', 'high' => 'bad',
            'low' => 'progress',
            default => 'attention',
        };
    @endphp

    <div class="deck deck--narrow">
        <header class="deck-head">
            <div>
                <p class="deck-eyebrow">
                    Evaluator brief &nbsp;·&nbsp; {{ strtoupper(substr((string) $application->_id, -6)) }}
                </p>
                <h1 class="deck-title">{{ $student?->name ?? 'Candidate no longer on file' }}</h1>
            </div>
            <div class="deck-acts">
                <a href="{{ route('admin.applications.assign.form', $application->_id) }}" class="btn btn-primary">
                    Assign an evaluator
                </a>
                <a href="{{ route('admin.applications.index') }}" class="btn btn-secondary">The queue</a>
            </div>
        </header>

        <p class="notice" role="note">
            Generated from the submitted application
            @if (!empty($brief['generated_at']))
                on {{ \Carbon\Carbon::parse($brief['generated_at'])->format('j M Y, H:i') }}
            @endif
            &mdash; a summary to plan the review, not an assessment of it.
        </p>

        <section class="lede-card lede-card--{{ $tone }}" aria-labelledby="class-head">
            <p class="lede-kicker">Expected effort</p>
            <h2 class="lede-head" id="class-head">{{ $class['label'] ?? 'Not classified' }}</h2>
            @if (!empty($class['reason']))
                <p class="lede-body">{{ $class['reason'] }}</p>
            @endif
        </section>

        <div class="split">
            <section class="panel" aria-labelledby="score-head">
                <h2 class="panel-head" id="score-head">Entry scorecard</h2>

                @if (!empty($elig['summary']))
                    <p class="lede-body">{{ $elig['summary'] }}</p>
                @endif

                <dl class="kv">
                    <div><dt>Score</dt><dd>{{ $elig['score'] ?? 0 }}</dd></div>
                    <div><dt>Reads as</dt><dd>{{ $elig['recommendation'] ?? 'Not assessed' }}</dd></div>
                </dl>

                @if (!empty($elig['criteria']))
                    {{--
                        The scorecard reports status as 'pass' | 'warning' |
                        'fail', and marks the rules that are absolute with
                        `critical`. Reading anything else - a `met` or `passed`
                        key - renders every row as a failure, which is exactly
                        what an earlier version of this file did: a criterion
                        whose own message read "Candidate meets the minimum age
                        requirement" was drawn with a red cross beside it.
                    --}}
                    <ul class="checks">
                        @foreach ($elig['criteria'] as $criterion)
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
                                    <strong>
                                        {{ $criterion['name'] ?? 'Criterion' }}
                                        @if (!empty($criterion['critical']))
                                            <em class="check-must">must pass</em>
                                        @endif
                                    </strong>
                                    @if (!empty($criterion['message']))
                                        <span class="check-msg">{{ $criterion['message'] }}</span>
                                    @endif
                                    @if (!empty($criterion['value']))
                                        <small>{{ $criterion['value'] }}</small>
                                    @endif
                                </span>
                                @if (isset($criterion['points'], $criterion['max_points']))
                                    <span class="check-pts">
                                        {{ $criterion['points'] }}<span>/{{ $criterion['max_points'] }}</span>
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="panel" aria-labelledby="case-head">
                <h2 class="panel-head" id="case-head">The case</h2>
                <dl class="kv">
                    <div>
                        <dt>Programme</dt>
                        <dd>{{ $application->program_applied ?: 'Not stated' }}</dd>
                    </div>
                    @if ($application->age)
                        <div><dt>Age</dt><dd>{{ $application->age }}</dd></div>
                    @endif
                    @if ($application->highest_qualification)
                        <div><dt>Qualification</dt><dd>{{ $application->highest_qualification }}</dd></div>
                    @endif
                    @if ($application->company_name)
                        <div><dt>Employer</dt><dd>{{ $application->company_name }}</dd></div>
                    @endif
                    @if ($application->working_experience_years)
                        <div>
                            <dt>Experience</dt>
                            <dd>{{ $application->working_experience_years }} years</dd>
                        </div>
                    @endif
                </dl>
            </section>
        </div>

        @if ($critical->isNotEmpty())
            <section class="panel panel--edge" aria-labelledby="crit-head">
                <h2 class="panel-head" id="crit-head">Blocking problems</h2>
                <ul class="flags">
                    @foreach ($critical as $item)
                        <li class="flag flag--bad">
                            {{ is_array($item) ? ($item['message'] ?? $item['title'] ?? '') : $item }}
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($gaps->isNotEmpty())
            <section class="panel" aria-labelledby="gap-head">
                <h2 class="panel-head" id="gap-head">
                    Where the evidence is thin
                    <span class="stack-count">{{ $gaps->count() }}</span>
                </h2>
                <ul class="flags">
                    @foreach ($gaps as $gap)
                        <li class="flag flag--{{ $severityTone((string) ($gap['severity'] ?? '')) }}">
                            <strong>{{ $gap['area'] ?? 'Unspecified' }}</strong>
                            <span>{{ $gap['message'] ?? '' }}</span>
                            @if (!empty($gap['value']))
                                <small>Recorded: {{ is_scalar($gap['value']) ? $gap['value'] : json_encode($gap['value']) }}</small>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($focus->isNotEmpty())
            <section class="panel" aria-labelledby="focus-head">
                <h2 class="panel-head" id="focus-head">What the evaluator should look at</h2>
                @foreach ($focus as $item)
                    <div class="said">
                        <h3>{{ $item['title'] ?? 'Focus area' }}</h3>
                        <p>{{ $item['detail'] ?? '' }}</p>
                    </div>
                @endforeach
            </section>
        @endif

        <div class="split">
            @if (!empty($brief['next_actions']))
                <section class="panel" aria-labelledby="next-head">
                    <h2 class="panel-head" id="next-head">Suggested next steps</h2>
                    <ol class="steps">
                        @foreach ($brief['next_actions'] as $step)
                            <li>{{ is_array($step) ? ($step['label'] ?? '') : $step }}</li>
                        @endforeach
                    </ol>
                </section>
            @endif

            @if (!empty($brief['efficiency_notes']))
                <section class="panel" aria-labelledby="eff-head">
                    <h2 class="panel-head" id="eff-head">Notes on effort</h2>
                    <ul class="flags">
                        @foreach ($brief['efficiency_notes'] as $note)
                            <li class="flag">{{ is_array($note) ? ($note['message'] ?? '') : $note }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </div>
@endsection

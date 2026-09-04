@extends('layouts.app')

@section('content')
    {{--
        The registry's overview.

        What stood here was an inventory: total applications, how many of each
        type, how many approved, and a chart. None of that is work. A registry
        officer opening this at nine in the morning is asking what is stuck and
        what is waiting on them, and the old page could answer neither - the
        headline number went up whether the office was on top of things or
        drowning.

        So the counts that carry an action lead, each one linking into the
        queue filtered to exactly those cases, and the inventory is a footnote.
    --}}
    @php
        $m = $workflowMetrics;

        // Only the counts an officer can act on. A number with nothing behind
        // it is a decoration.
        $todo = [
            [
                'n' => $m['unassigned_ready_count'] ?? 0,
                'label' => 'Paid, waiting for an evaluator',
                'note' => 'Payment is verified and nobody is assigned.',
                'tone' => 'attention',
            ],
            [
                'n' => $m['pending_payment_count'] ?? 0,
                'label' => 'Payment to check',
                'note' => 'A receipt is in, or a fee is owed.',
                'tone' => 'progress',
            ],
            [
                'n' => $m['delayed_count'] ?? 0,
                'label' => 'Sitting over a week',
                'note' => 'No movement in seven days.',
                'tone' => ($m['delayed_count'] ?? 0) > 0 ? 'bad' : 'neutral',
            ],
        ];

        $active = $m['active_count'] ?? 0;
        $delayed = $m['delayed_count'] ?? 0;
    @endphp

    <div class="deck">
        <header class="deck-head">
            <div>
                <p class="deck-eyebrow">Registry</p>
                <h1 class="deck-title">{{ auth()->user()->name }}</h1>
            </div>
            <div class="deck-acts">
                <a href="{{ route('admin.applications.index') }}" class="btn btn-primary">Open the queue</a>
                @if (Route::has('admin.reports.apel_a'))
                    <a href="{{ route('admin.reports.apel_a') }}" class="btn btn-secondary">Reports</a>
                @endif
            </div>
        </header>

        @if (session('success'))
            <p class="notice notice--good" role="status">{{ session('success') }}</p>
        @endif

        {{--
            The one sentence worth reading. Stated as a proportion, because
            "4 delayed" means nothing without knowing whether that is four out
            of five or four out of four hundred.
        --}}
        <section class="lede-card lede-card--{{ $active === 0 ? 'neutral' : ($delayed > 0 ? 'attention' : 'good') }}"
                 aria-labelledby="state-head">
            <p class="lede-kicker">Right now</p>
            <h2 class="lede-head" id="state-head">
                @if ($active === 0)
                    Nothing is in progress
                @elseif ($delayed === 0)
                    {{ $active }} {{ Str::plural('application', $active) }} moving, none stalled
                @else
                    {{ $delayed }} of {{ $active }} stalled over a week
                @endif
            </h2>
            <p class="lede-body">
                @if ($active === 0)
                    No application is currently between submission and decision.
                @elseif ($delayed === 0)
                    Everything in progress has moved within the last seven days.
                @else
                    These have had no recorded movement in seven days. They are listed below.
                @endif
            </p>
        </section>

        {{--
            Only shown when something is wrong. A healthy queue needs no
            reporting, and a permanent green tick trains people to stop reading.
        --}}
        @if (($queue['failed'] ?? 0) > 0 || ($queue['pending'] ?? 0) > 0)
            <section class="panel panel--edge" aria-labelledby="queue-head">
                <h2 class="panel-head" id="queue-head">Notifications are not going out</h2>

                <dl class="kv">
                    @if (($queue['pending'] ?? 0) > 0)
                        <div>
                            <dt>Waiting to send</dt>
                            <dd>{{ $queue['pending'] }}</dd>
                        </div>
                    @endif
                    @if (($queue['failed'] ?? 0) > 0)
                        <div>
                            <dt>Failed</dt>
                            <dd>{{ $queue['failed'] }}</dd>
                        </div>
                    @endif
                </dl>

                <p class="note">
                    Email is queued, so it only sends while a worker is running. Until one is,
                    candidates are not being told when their application moves. Start it with
                    <code>php artisan queue:work</code>, and
                    <code>php artisan queue:retry all</code> for anything that already failed.
                </p>
            </section>
        @endif

        <section class="stack" aria-labelledby="todo-head">
            <div class="stack-head">
                <h2 id="todo-head">What needs the office</h2>
                <p>Each of these is a queue you can work down.</p>
            </div>

            <div class="tiles">
                @foreach ($todo as $item)
                    <article class="tile tile--{{ $item['n'] > 0 ? $item['tone'] : 'neutral' }}">
                        <p class="tile-n">{{ $item['n'] }}</p>
                        <h3 class="tile-label">{{ $item['label'] }}</h3>
                        <p class="tile-note">{{ $item['note'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        @if (!empty($m['delayed_applications']) && count($m['delayed_applications']) > 0)
            <section class="stack" aria-labelledby="stalled-head">
                <div class="stack-head">
                    <h2 id="stalled-head">
                        Stalled longest
                        <span class="stack-count">{{ count($m['delayed_applications']) }}</span>
                    </h2>
                    <p>Start here.</p>
                </div>

                <div class="stack-body stack-body--rows">
                    @foreach ($m['delayed_applications'] as $application)
                        @php
                            $stage = \App\Support\ApplicationCase::stageOf($application);
                            $since = $application->status_updated_at ?? $application->submission_date;
                        @endphp
                        <article class="row-case">
                            <div class="row-case-tell">
                                <div class="case-top">
                                    <span class="badge badge--type">{{ $application->application_type }}</span>
                                    @if ($stage)
                                        <span class="badge badge--{{ $stage->tone() }}">
                                            {{ $stage->label((string) $application->application_type) }}
                                        </span>
                                    @endif
                                    <span class="case-ref">
                                        {{ strtoupper(substr((string) $application->_id, -6)) }}
                                    </span>
                                </div>

                                <h3 class="row-case-title">
                                    {{ $application->program_applied
                                        ?: ($application->credit_course_name ?: 'Not stated') }}
                                </h3>

                                <p class="row-case-wait">
                                    @if ($since)
                                        No movement since
                                        {{ \Carbon\Carbon::parse($since)->format('j M Y') }}
                                        &mdash; {{ \Carbon\Carbon::parse($since)->diffForHumans() }}
                                    @else
                                        No movement recorded.
                                    @endif
                                </p>
                            </div>

                            <a class="btn btn-primary btn--sm"
                               href="{{ route('admin.applications.index') }}#{{ $application->_id }}">
                                Open
                            </a>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @if (!empty($m['bottlenecks']) && count($m['bottlenecks']) > 0)
            <section class="stack" aria-labelledby="block-head">
                <div class="stack-head">
                    <h2 id="block-head">Where they pile up</h2>
                    <p>The stages holding the most work right now.</p>
                </div>

                <dl class="kv">
                    @foreach ($m['bottlenecks'] as $row)
                        <div>
                            <dt>{{ $row['stage'] }}</dt>
                            <dd>{{ $row['count'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endif

        <div class="split">
            <section class="panel" aria-labelledby="log-head">
                <h2 class="panel-head" id="log-head">Recent activity</h2>

                @if ($activityLogs->isEmpty())
                    <p class="muted">Nothing has been recorded yet.</p>
                @else
                    <ol class="log">
                        @foreach ($activityLogs as $log)
                            <li>
                                <p class="log-what">{{ $log->action ?? $log->description ?? 'Activity' }}</p>
                                <p class="log-when">
                                    {{ $log->created_at
                                        ? \Carbon\Carbon::parse($log->created_at)->diffForHumans()
                                        : '' }}
                                </p>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>

            {{-- The inventory. Kept, but it is background, not the job. --}}
            <section class="panel" aria-labelledby="totals-head">
                <h2 class="panel-head" id="totals-head">On the books</h2>
                <dl class="kv">
                    <div><dt>Applications, all time</dt><dd>{{ $totalApplications }}</dd></div>
                    <div><dt>APEL A</dt><dd>{{ $apelACount }}</dd></div>
                    <div><dt>APEL C</dt><dd>{{ $apelCCount }}</dd></div>
                    <div><dt>Admitted through APEL A</dt><dd>{{ $apelAApproved }}</dd></div>
                    <div><dt>Credit awarded</dt><dd>{{ $apelCApproved }}</dd></div>
                    <div>
                        <dt>Average time to decide</dt>
                        <dd>
                            {{ ($m['average_processing_days'] ?? 0) > 0
                                ? $m['average_processing_days'].' days'
                                : 'No decisions yet' }}
                        </dd>
                    </div>
                </dl>
            </section>
        </div>
    </div>
@endsection

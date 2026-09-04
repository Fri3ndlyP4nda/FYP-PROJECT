@extends('layouts.app')

@section('content')
    {{--
        The triage console.

        This screen used to be a table of every application sorted by target
        year — a shape that answers a question nobody actually has. An APEL
        application is a turn-based object: at any moment it is blocked on
        exactly one party. So the queue is grouped by WHO IS BLOCKING, the group
        that needs the administrator sits at the top, and selecting a row opens
        it beside the list rather than navigating away and back.
    --}}
    <div class="console">

        {{-- Where work is piling up. Sourced from workflowMetrics(). --}}
        <section class="console-bar" aria-label="Queue health">
            <div class="console-bar-lead">
                <span class="section-pill">Queue</span>
                <h1>Applications</h1>
            </div>

            <dl class="pressure">
                <div class="pressure-item {{ $needsYou->count() ? 'is-live' : '' }}">
                    <dt>Needs you</dt>
                    <dd>{{ $needsYou->count() }}</dd>
                </div>
                <div class="pressure-item {{ ($metrics['delayed_count'] ?? 0) ? 'is-warn' : '' }}">
                    <dt>Stalled &gt; 7 days</dt>
                    <dd>{{ $metrics['delayed_count'] ?? 0 }}</dd>
                </div>
                <div class="pressure-item">
                    <dt>Awaiting payment</dt>
                    <dd>{{ $metrics['pending_payment_count'] ?? 0 }}</dd>
                </div>
                <div class="pressure-item">
                    <dt>Median days to decide</dt>
                    <dd>{{ $metrics['average_processing_days'] ?? 0 }}</dd>
                </div>
            </dl>
        </section>

        <div class="console-split">

            {{-- ------------------------------------------------------------
                 Queue — grouped by who is blocking, not by date
            ------------------------------------------------------------- --}}
            <aside class="queue" aria-label="Application queue">
                <div class="queue-search">
                    <input type="search" id="queue-filter" placeholder="Filter by name or programme…"
                           aria-label="Filter the queue" autocomplete="off">
                </div>

                <div class="queue-scroll">
                    <section class="queue-group" data-group="you">
                        <h2 class="queue-group-head">
                            <span>Needs you</span>
                            <span class="queue-count is-live">{{ $needsYou->count() }}</span>
                        </h2>

                        @forelse ($needsYou as $row)
                            @include('admin.applications._queue-row', ['row' => $row, 'selected' => $selected])
                        @empty
                            <p class="queue-clear">Nothing is waiting on you.</p>
                        @endforelse
                    </section>

                    @foreach ($elsewhere as $who => $group)
                        <section class="queue-group" data-group="{{ $who }}">
                            <h2 class="queue-group-head">
                                <span>With {{ $who }}</span>
                                <span class="queue-count">{{ $group->count() }}</span>
                            </h2>
                            @foreach ($group as $row)
                                @include('admin.applications._queue-row', ['row' => $row, 'selected' => $selected])
                            @endforeach
                        </section>
                    @endforeach

                    @if ($closed->count())
                        <section class="queue-group" data-group="closed">
                            <h2 class="queue-group-head">
                                <span>Closed</span>
                                {{-- The true total, not the number on screen. Closed
                                     applications accumulate forever, so the queue loads a
                                     recent slice - the count must still be honest about
                                     how many there are. --}}
                                <span class="queue-count">{{ $closedTotal }}</span>
                            </h2>
                            @foreach ($closed as $row)
                                @include('admin.applications._queue-row', ['row' => $row, 'selected' => $selected])
                            @endforeach

                            @if ($closedTotal > $closed->count())
                                <p class="queue-more">
                                    Showing the {{ $closed->count() }} most recently decided
                                    of {{ $closedTotal }}.
                                    <a href="{{ route('admin.reports.apel_a') }}">See the full report</a>.
                                </p>
                            @endif
                        </section>
                    @endif
                </div>
            </aside>

            {{-- ------------------------------------------------------------
                 Detail — the case file, opened beside the queue
            ------------------------------------------------------------- --}}
            <main class="case" aria-live="polite">
                @if (! $selected)
                    <div class="empty">
                        <div class="empty-mark" aria-hidden="true">—</div>
                        <p class="empty-title">No applications yet</p>
                        <p class="empty-body">Submitted applications appear here for triage.</p>
                    </div>
                @else
                    @php
                        $app = $selected['model'];
                        $action = $selected['action'];
                    @endphp

                    <header class="case-head">
                        <div>
                            <span class="section-pill">{{ $app->type() }}</span>
                            <h2>{{ $selected['student'] }}</h2>
                            <p class="muted">{{ $app->program_applied ?: 'No programme recorded' }}</p>
                        </div>
                        <span class="badge badge--{{ $selected['stage']->tone() }}">
                            {{ $selected['stage']->label($app->type()) }}
                        </span>
                    </header>

                    {{-- The answer to "what do I do about this?" leads. --}}
                    @if ($action)
                        <section class="act act--{{ $action['tone'] }}">
                            <div class="act-body">
                                <p class="act-title">{{ $action['title'] }}</p>
                                <p class="act-detail">{{ $action['body'] }}</p>
                                @if (! empty($action['deadline']))
                                    <p class="act-deadline">
                                        Due {{ \Carbon\Carbon::parse($action['deadline'])->format('j M Y, H:i') }}
                                    </p>
                                @endif
                            </div>
                            @if (! empty($action['cta']) && Route::has($action['cta']['route']))
                                <a class="btn" href="{{ route($action['cta']['route'], $action['cta']['params']) }}">
                                    {{ $action['cta']['label'] }}
                                </a>
                            @endif
                        </section>
                    @endif

                    {{-- Where it has reached. Ticks and crosses, not colour alone. --}}
                    <section class="case-block">
                        <h3 class="section-title">Progress</h3>
                        <ol class="rail">
                            @foreach ($app->rail() as $node)
                                <li class="rail-node rail-node--{{ $node['state'] ?? 'todo' }}">
                                    <span class="rail-mark" aria-hidden="true">
                                        @if (($node['state'] ?? '') === 'done') &check;
                                        @elseif (($node['state'] ?? '') === 'failed') &times;
                                        @else {{ $loop->iteration }}
                                        @endif
                                    </span>
                                    <span class="rail-label">{{ $node['label'] ?? '' }}</span>
                                    @if (($node['state'] ?? '') === 'current')
                                        <span class="sr-only">(current step)</span>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </section>

                    <section class="case-block">
                        <h3 class="section-title">Record</h3>
                        <dl class="facts">
                            <div><dt>Submitted</dt>
                                <dd>{{ $app->submission_date ? \Carbon\Carbon::parse($app->submission_date)->format('j M Y') : '—' }}</dd></div>
                            <div><dt>Last movement</dt>
                                <dd>{{ $selected['since'] ? \Carbon\Carbon::parse($selected['since'])->diffForHumans() : '—' }}</dd></div>
                            <div><dt>Payment</dt>
                                <dd>{{ ucfirst($app->payment_status ?? 'pending') }}</dd></div>
                            <div><dt>Blocked on</dt>
                                <dd>{{ $selected['blocked_on'] === 'you' ? 'You' : ucfirst($selected['blocked_on']) }}</dd></div>
                        </dl>
                    </section>

                    <footer class="case-foot">
                        <a class="btn btn-secondary"
                           href="{{ route('admin.applications.assign.form', $selected['id']) }}">
                            Open full record
                        </a>
                        @if (Route::has('student.applications.print'))
                            <a class="btn btn-secondary" target="_blank" rel="noopener noreferrer"
                               href="{{ route('student.applications.print', $selected['id']) }}">
                                Print portfolio
                            </a>
                        @endif
                    </footer>
                @endif
            </main>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Filter the queue in place. Groups whose rows are all hidden collapse
        // too, so the counts on screen never contradict what is visible.
        document.addEventListener('DOMContentLoaded', function () {
            const box = document.getElementById('queue-filter');
            if (!box) return;

            box.addEventListener('input', function () {
                const q = box.value.trim().toLowerCase();

                document.querySelectorAll('.queue-group').forEach(function (group) {
                    let shown = 0;

                    group.querySelectorAll('.queue-row').forEach(function (row) {
                        const hit = !q || (row.dataset.search || '').includes(q);
                        row.hidden = !hit;
                        if (hit) shown++;
                    });

                    group.hidden = shown === 0;
                });
            });
        });
    </script>
@endpush

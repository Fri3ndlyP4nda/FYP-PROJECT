{{--
    One application, as the candidate needs to read it.

    Order is deliberate: what it is, where it has got to, then what happens
    next. The rail comes from ApelStage::rail(), so a stage added to the
    workflow appears here without this file changing, and a rejected case shows
    the outcome it actually reached rather than always promising approval.

    Expects: $case = ['application','stage','type','action','rail','progress','explanation']
--}}
@php
    $application = $case['application'];
    $stage = $case['stage'];
    $action = $case['action'];
    $isC = $case['type'] === 'APEL C';

    $detailRoute = $isC ? 'student.apel_c.show' : 'student.apel_a.show';
    $reference = strtoupper(substr((string) $application->_id, -6));
@endphp

<article class="case-card">
    <div class="case-top">
        <span class="badge badge--type">{{ $case['type'] }}</span>
        @if ($stage)
            <span class="badge badge--{{ $stage->tone() }}">{{ $stage->label($case['type']) }}</span>
        @endif
        <span class="case-ref">{{ $reference }}</span>
    </div>

    <h3 class="case-title">
        @if (Route::has($detailRoute))
            <a href="{{ route($detailRoute, $application->_id) }}">
                {{ $application->program_applied ?: 'Programme not yet stated' }}
            </a>
        @else
            {{ $application->program_applied ?: 'Programme not yet stated' }}
        @endif
    </h3>

    {{--
        Keyed off the stage, not off submission_date being present. A draft
        carries a submission_date from the moment the row is created, so
        testing the date alone told the candidate their draft was "Submitted
        20 Jul 2026" while the badge beside it read Draft.
    --}}
    <p class="case-meta">
        @if ($stage === \App\Domain\Apel\ApelStage::DRAFT)
            Not submitted yet
            @if ($application->created_at)
                &nbsp;·&nbsp; started {{ \Carbon\Carbon::parse($application->created_at)->format('j M Y') }}
            @endif
        @elseif ($application->submission_date)
            Submitted {{ \Carbon\Carbon::parse($application->submission_date)->format('j M Y') }}
        @else
            Not submitted yet
        @endif
    </p>

    @if (!empty($case['rail']))
        <ol class="spine spine--tight" style="--spine-done: {{ (int) $case['progress'] }}%">
            @foreach ($case['rail'] as $node)
                @php
                    // The rail reports 'done' | 'current' | 'upcoming'; a node
                    // that records a bad outcome is drawn as failed so the
                    // colour does not congratulate the reader on a rejection.
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
    @endif

    @if ($action)
        <div class="case-act case-act--{{ $action['tone'] ?? 'progress' }}">
            <div>
                <p class="case-act-what">{{ $action['title'] }}</p>
                @if (!empty($action['body']))
                    <p class="case-act-why">{{ $action['body'] }}</p>
                @endif
                @if (!empty($action['deadline']))
                    <p class="case-act-when">Due {{ $action['deadline']->format('j M Y') }}</p>
                @endif
            </div>

            @if (!empty($action['cta']['route']) && Route::has($action['cta']['route']))
                <a class="btn btn-primary btn--sm"
                   href="{{ route($action['cta']['route'], $action['cta']['params']) }}">
                    {{ $action['cta']['label'] }}
                </a>
            @endif
        </div>
    @elseif ($stage && ! $stage->isTerminal())
        <p class="case-wait">
            {{ $case['explanation'] ?: 'With the faculty. You will be notified when this moves.' }}
        </p>
    @endif
</article>

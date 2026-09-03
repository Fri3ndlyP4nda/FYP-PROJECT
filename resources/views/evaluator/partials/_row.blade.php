{{--
    One assigned application, as a queue row.

    Shared by the evaluator's dashboard and their full queue: the same question
    in both places, so the same answer. A row leads with what it is and what
    state it is in, and ends with the one control that advances it - the action
    from NextAction when this is the evaluator's move, and a plain way in when
    it is not.

    Expects: $case = ['application','stage','type','action', ...]
--}}
@php
    $application = $case['application'];
    $stage = $case['stage'];
    $action = $case['action'];
@endphp

<article class="row-case">
    <div class="row-case-tell">
        <div class="case-top">
            <span class="badge badge--type">{{ $case['type'] }}</span>
            @if ($stage)
                <span class="badge badge--{{ $stage->tone() }}">{{ $stage->label($case['type']) }}</span>
            @endif
            <span class="case-ref">{{ strtoupper(substr((string) $application->_id, -6)) }}</span>
        </div>

        <h3 class="row-case-title">
            @if (Route::has('evaluator.applications.show'))
                <a href="{{ route('evaluator.applications.show', $application->_id) }}">
                    {{ $application->program_applied ?: 'Programme not stated' }}
                </a>
            @else
                {{ $application->program_applied ?: 'Programme not stated' }}
            @endif
        </h3>

        <p class="row-case-meta">
            {{ $application->company_name ?: 'Employer not stated' }}
            @if ($application->submission_date)
                &nbsp;·&nbsp; submitted {{ \Carbon\Carbon::parse($application->submission_date)->format('j M Y') }}
            @endif
        </p>

        @if ($action)
            <p class="row-case-act">{{ $action['title'] }}</p>
        @elseif ($stage && ! $stage->isTerminal())
            {{--
                NextAction is null here by definition - that is what puts a case
                in "with someone else" - so who is holding it comes from the
                stage, not from the action.
            --}}
            <p class="row-case-wait">
                Waiting on {{ $stage->awaitsStudent() ? 'the candidate' : 'the registry' }}.
            </p>
        @endif
    </div>

    @if ($action && ! empty($action['cta']['route']) && Route::has($action['cta']['route']))
        <a class="btn btn-primary btn--sm" href="{{ route($action['cta']['route'], $action['cta']['params']) }}">
            {{ $action['cta']['label'] }}
        </a>
    @elseif (Route::has('evaluator.applications.show'))
        <a class="btn btn-ghost btn--sm" href="{{ route('evaluator.applications.show', $application->_id) }}">
            Open
        </a>
    @endif
</article>

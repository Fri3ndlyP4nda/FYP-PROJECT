{{--
    One row in the triage queue.

    data-search carries everything the filter should match, lowercased once here
    rather than recomputed in JavaScript on every keystroke.
--}}
@php
    $isOpen = $selected && $selected['id'] === $row['id'];
    $stale = $row['since'] && \Carbon\Carbon::parse($row['since'])->lt(now()->subDays(7));
@endphp

<a class="queue-row {{ $isOpen ? 'is-open' : '' }}"
   href="{{ route('admin.applications.index', ['open' => $row['id']]) }}"
   @if ($isOpen) aria-current="true" @endif
   data-search="{{ strtolower($row['student'] . ' ' . $row['model']->program_applied . ' ' . $row['stage']->label($row['model']->type())) }}">

    <span class="queue-row-main">
        <span class="queue-row-name">{{ $row['student'] }}</span>
        <span class="queue-row-sub">{{ $row['model']->program_applied ?: 'No programme recorded' }}</span>
    </span>

    <span class="queue-row-meta">
        <span class="badge badge--type">{{ $row['model']->type() }}</span>
        <span class="queue-row-age {{ $stale ? 'is-stale' : '' }}">
            {{ $row['since'] ? \Carbon\Carbon::parse($row['since'])->diffForHumans(null, true) : '—' }}
            @if ($stale)
                <span class="sr-only">(stalled more than seven days)</span>
            @endif
        </span>
    </span>
</a>

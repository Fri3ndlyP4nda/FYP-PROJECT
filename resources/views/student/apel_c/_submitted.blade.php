{{--
    The APEL C pre-application, played back.

    A reference, not something anyone reads top to bottom - it lives behind a
    <details> on the detail page. It is generated from the stored shape rather
    than a hand-written field list, so a question added to the form appears here
    without this file changing, and no answer the candidate gave can silently
    stop being shown.

    Expects: $data = $application->pre_app_data
--}}
@php
    $data = (array) $data;

    // Sections the candidate filled in, in the order the form asks them.
    $order = [
        'personal_particulars' => 'Personal particulars',
        'formal_learning' => 'Formal learning',
        'experiential_learning' => 'Employment history',
        'training_activities' => 'Training',
        'other_learning_skills' => 'Other learning',
        'language_skills' => 'Languages',
        'referees' => 'Referees',
        'self_declaration' => 'Declaration',
    ];

    $humanise = static fn (string $key): string => ucfirst(str_replace('_', ' ', $key));

    $render = static function ($value): string {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        if (is_array($value)) {
            return implode(', ', array_filter(array_map('strval', $value), 'strlen'));
        }

        return trim((string) $value);
    };

    // Anything stored but not named above is still shown, at the end.
    $sections = $order + array_combine(array_keys($data), array_map($humanise, array_keys($data)));
@endphp

<div class="submitted">
    @foreach ($sections as $key => $title)
        @php $block = $data[$key] ?? null; @endphp
        @continue(blank($block))

        <section class="submitted-part">
            <h3>{{ $title }}</h3>

            @php
                $rows = array_values((array) $block);
                $isTable = ! empty($rows) && is_array($rows[0]);
            @endphp

            @if ($isTable)
                @foreach ($rows as $i => $row)
                    <dl class="kv">
                        @foreach ((array) $row as $field => $value)
                            @php $shown = $render($value); @endphp
                            @continue($shown === '')
                            <div>
                                <dt>{{ $humanise($field) }}</dt>
                                <dd>{{ $shown }}</dd>
                            </div>
                        @endforeach
                    </dl>
                    @if (! $loop->last)
                        <hr class="submitted-rule">
                    @endif
                @endforeach
            @else
                <dl class="kv">
                    @foreach ((array) $block as $field => $value)
                        @php $shown = $render($value); @endphp
                        @continue($shown === '')
                        <div>
                            <dt>{{ $humanise($field) }}</dt>
                            <dd>{{ $shown }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </section>
    @endforeach
</div>

{{--
    Field-level validation message.

    The application previously had zero @error directives in 13,000 lines of
    Blade, so every message appeared only in a summary at the top of the page
    while the field it referred to sat inside a collapsed tab several screens
    down. There was no path from the message to the control.

    Usage:  <x-field-error name="ic_no" />
            <x-field-error name="pre_app_data.referees.0.referee_email" />
--}}
@props(['name'])

@error($name)
    <span class="field-error" role="alert" aria-live="polite">
        {{ $message }}
    </span>
@enderror

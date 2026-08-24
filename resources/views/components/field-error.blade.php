@props(['name'])

{{--
    Field-level validation message.

    This application previously had no field-level validation output at all, so
    every message appeared only in a summary at the top of the page while the
    field it referred to sat inside a collapsed tab several screens down. There
    was no path from the message to the control.

    Usage: pass the field's validation key as the name prop, e.g. "ic_no" or a
    nested key such as "pre_app_data.referees.0.referee_email".

    Component tags are deliberately not written literally in this comment:
    Blade compiles x- tags anywhere in a file, so a tag inside a comment or a
    style block still renders, and one without a name prop throws.
--}}
@error($name)
    <span class="field-error" role="alert" aria-live="polite">
        {{ $message }}
    </span>
@enderror

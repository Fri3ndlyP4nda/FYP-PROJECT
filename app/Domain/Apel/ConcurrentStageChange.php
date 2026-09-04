<?php

namespace App\Domain\Apel;

use RuntimeException;

/**
 * Someone else moved this application while you were looking at it.
 *
 * Distinct from IllegalStageTransition, which means the move was never allowed.
 * This one means the move was allowed when the page was drawn and is not any
 * more, because the application is no longer where it was. The distinction
 * matters to the person on the other end: one is "you cannot do that", the
 * other is "reload and look again".
 */
class ConcurrentStageChange extends RuntimeException
{
    public function __construct(
        public readonly ApelStage $expected,
        public readonly ?ApelStage $found,
        public readonly ApelStage $attempted,
        public readonly string $type,
    ) {
        parent::__construct(sprintf(
            'Expected %s but found %s while moving to %s.',
            $expected->value,
            $found?->value ?? 'no stage',
            $attempted->value,
        ));
    }

    /** What to show the person who lost the race. */
    public function forHumans(): string
    {
        $found = $this->found?->label($this->type);

        return $found === null
            ? 'Someone else changed this application while this page was open. Reload it and try again.'
            : sprintf(
                'Someone else moved this application to "%s" while this page was open. '
                .'Reload it to see where it is now.',
                $found,
            );
    }
}

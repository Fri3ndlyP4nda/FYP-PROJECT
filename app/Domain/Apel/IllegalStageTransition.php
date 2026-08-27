<?php

namespace App\Domain\Apel;

use RuntimeException;

/**
 * Raised when code attempts a stage change the process does not allow — for
 * example appealing an application that was never decided, or verifying a
 * payment for an application the advisor already turned down.
 *
 * The old controllers wrote status strings directly, so these moves happened
 * silently and left applications stranded in states nothing could act on.
 */
class IllegalStageTransition extends RuntimeException
{
    public function __construct(
        public readonly ApelStage $from,
        public readonly ApelStage $to,
        public readonly string $type,
    ) {
        parent::__construct(sprintf(
            'A %s application cannot move from "%s" to "%s".',
            $type,
            $from->label($type),
            $to->label($type),
        ));
    }

    /** The sentence shown to the member of staff who attempted the move. */
    public function forHumans(): string
    {
        return sprintf(
            'This application is at "%s", so it cannot be moved to "%s". %s',
            $this->from->label($this->type),
            $this->to->label($this->type),
            $this->from->isTerminal()
                ? 'The application has already been completed.'
                : 'Complete the current step first.',
        );
    }
}

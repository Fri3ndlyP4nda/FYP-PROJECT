<?php

namespace App\Support;

use App\Domain\Apel\ApelStage;
use App\Domain\Apel\NextAction;
use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * One application, resolved into everything a screen needs to render it.
 *
 * The dashboards and the two index screens all ask the same three questions of
 * an application - where is it, whose move is it, and what does the rail look
 * like - and each was answering them itself. Resolving it once here keeps the
 * workflow out of Blade, so a view stays a presentation of state rather than a
 * second place the rules live and can drift.
 */
class ApplicationCase
{
    /**
     * @return array{
     *     application: Application,
     *     stage: ?ApelStage,
     *     type: string,
     *     action: ?array,
     *     rail: array,
     *     progress: int,
     *     explanation: string
     * }
     */
    public static function for(Application $application, ?User $viewer): array
    {
        $stage = self::stageOf($application);
        $type = (string) $application->application_type;

        return [
            'application' => $application,
            'stage' => $stage,
            'type' => $type,
            'action' => NextAction::for($application, $viewer),
            'rail' => $stage?->rail($type) ?? [],
            'progress' => $stage?->progress($type) ?? 0,
            'explanation' => $stage?->studentExplanation($type) ?? '',
        ];
    }

    /** @param iterable<Application> $applications */
    public static function collect(iterable $applications, ?User $viewer): Collection
    {
        return (new Collection($applications))
            ->map(fn (Application $application) => self::for($application, $viewer));
    }

    /**
     * Read the stage without going through an accessor.
     *
     * mongodb/laravel-mongodb resolves a method whose name matches a field as
     * an embedded relation before it checks the attributes, so Application has
     * no usable stage() accessor - calling it throws rather than returning the
     * field.
     */
    public static function stageOf(Application $application): ?ApelStage
    {
        $raw = $application->getAttributes()['stage'] ?? null;

        return $raw ? ApelStage::tryFrom((string) $raw) : null;
    }

    /** Cases the viewer must act on: a stage that is live and reports an action. */
    public static function awaitingViewer(Collection $cases): Collection
    {
        return $cases->filter(
            fn (array $c) => $c['stage'] && ! $c['stage']->isTerminal() && $c['action'] !== null
        )->values();
    }

    /** Live, but the next step belongs to someone else. */
    public static function elsewhere(Collection $cases): Collection
    {
        return $cases->filter(
            fn (array $c) => $c['stage'] && ! $c['stage']->isTerminal() && $c['action'] === null
        )->values();
    }

    public static function closed(Collection $cases): Collection
    {
        return $cases->filter(fn (array $c) => $c['stage']?->isTerminal())->values();
    }
}

<?php

namespace App\Exceptions;

use RuntimeException;

/** Thrown when something tries to mark a project Completed while its Financial Overview is incomplete. */
class ProjectCompletionBlockedException extends RuntimeException
{
    /** @var string[] what is still missing, in plain words */
    public array $missing;

    public function __construct(string $projectName, array $missing)
    {
        $this->missing = $missing;

        parent::__construct(
            "\"{$projectName}\" can't be marked as Completed yet because its Financial Overview is incomplete. Still missing: "
            . implode('; ', $missing) . '.'
        );
    }
}

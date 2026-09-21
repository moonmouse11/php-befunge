<?php

declare(strict_types=1);

namespace Pots\PhpBefunge\Exception;

use RuntimeException;

/**
 * Thrown when a program runs past the configured instruction step limit,
 * which almost always means the program contains an infinite loop.
 */
final class ExecutionLimitExceededException extends RuntimeException
{
    public function __construct(int $maxSteps)
    {
        parent::__construct(
            sprintf('The program exceeded the execution limit of %d steps.', $maxSteps),
        );
    }
}

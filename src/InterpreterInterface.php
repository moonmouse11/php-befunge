<?php

declare(strict_types=1);

namespace Pots\PhpBefunge;

use Pots\PhpBefunge\Exception\ExecutionLimitExceededException;

interface InterpreterInterface
{
    /**
     * Executes a Befunge-93 program and returns everything it printed.
     *
     * @param string $code The Befunge-93 source code.
     *
     * @return string The accumulated program output.
     *
     * @throws ExecutionLimitExceededException When the program does not
     *                                         terminate within the configured
     *                                         step limit.
     */
    public function interpret(string $code): string;
}

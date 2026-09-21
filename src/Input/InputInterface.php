<?php

declare(strict_types=1);

namespace Pots\PhpBefunge\Input;

use Pots\PhpBefunge\Exception\InputExhaustedException;

/**
 * Source of user input for the Befunge-93 `&` (read integer) and
 * `~` (read character) instructions.
 *
 * Injecting input through this interface keeps the interpreter free of
 * global state (STDIN) and makes it usable inside any application.
 */
interface InputInterface
{
    /**
     * Reads a whitespace-prefixed signed decimal integer.
     *
     * @throws InputExhaustedException When no more input is available.
     */
    public function readInt(): int;

    /**
     * Reads a single character.
     *
     * @throws InputExhaustedException When no more input is available.
     */
    public function readChar(): string;
}

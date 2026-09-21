<?php

declare(strict_types=1);

namespace Pots\PhpBefunge\Exception;

use RuntimeException;

/**
 * Thrown when a Befunge-93 input instruction (& or ~) requests more
 * data than the injected InputInterface can provide.
 */
final class InputExhaustedException extends RuntimeException
{
}

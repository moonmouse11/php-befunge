<?php

declare(strict_types=1);

namespace Pots\PhpBefunge\Exception;

use LogicException;

/**
 * Thrown when a program executes an input instruction (& or ~) but no
 * input source was injected into the interpreter.
 */
final class InputRequiredException extends LogicException
{
}

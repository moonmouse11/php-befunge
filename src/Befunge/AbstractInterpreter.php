<?php

declare(strict_types=1);

namespace Pots\PhpBefunge\Befunge;

abstract class AbstractInterpreter
{
    abstract public function interpret(string $code): string;
}

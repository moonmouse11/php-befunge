<?php

declare(strict_types=1);

namespace Pots\PhpBefunge\Input;

use Pots\PhpBefunge\Exception\InputExhaustedException;

/**
 * Feeds program input from an in-memory string.
 */
final class StringInput implements InputInterface
{
    private int $offset = 0;

    public function __construct(
        private readonly string $input,
    ) {
    }

    public function readInt(): int
    {
        $this->skipWhitespace();

        $sign = 1;
        if ($this->peek() === '-') {
            $sign = -1;
            $this->offset++;
        } elseif ($this->peek() === '+') {
            $this->offset++;
        }

        $digits = '';
        while (($char = $this->peek()) !== null && ctype_digit($char)) {
            $digits .= $char;
            $this->offset++;
        }

        if ($digits === '') {
            throw new InputExhaustedException('Expected an integer in the input stream.');
        }

        return $sign * (int) $digits;
    }

    public function readChar(): string
    {
        $char = $this->peek();

        if ($char === null) {
            throw new InputExhaustedException('No more characters in the input stream.');
        }

        $this->offset++;

        return $char;
    }

    private function skipWhitespace(): void
    {
        while (($char = $this->peek()) !== null && ctype_space($char)) {
            $this->offset++;
        }
    }

    private function peek(): ?string
    {
        return $this->offset < strlen($this->input) ? $this->input[$this->offset] : null;
    }
}

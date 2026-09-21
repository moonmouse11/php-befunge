<?php

declare(strict_types=1);

namespace Pots\PhpBefunge\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Pots\PhpBefunge\Exception\InputExhaustedException;
use Pots\PhpBefunge\Input\StringInput;

#[CoversClass(StringInput::class)]
final class StringInputTest extends TestCase
{
    #[Test]
    public function it_reads_signed_integers(): void
    {
        $input = new StringInput('  42 -7');

        self::assertSame(42, $input->readInt());
        self::assertSame(-7, $input->readInt());
    }

    #[Test]
    public function it_reads_characters(): void
    {
        $input = new StringInput('a1b');

        self::assertSame('a', $input->readChar());
        self::assertSame('1', $input->readChar());
        self::assertSame('b', $input->readChar());
    }

    #[Test]
    public function it_throws_when_exhausted(): void
    {
        $input = new StringInput('');

        $this->expectException(InputExhaustedException::class);
        $input->readChar();
    }

    #[Test]
    public function it_throws_when_no_integer_is_available(): void
    {
        $input = new StringInput('abc');

        $this->expectException(InputExhaustedException::class);
        $input->readInt();
    }
}

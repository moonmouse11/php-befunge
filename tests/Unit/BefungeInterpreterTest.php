<?php

declare(strict_types=1);

namespace Pots\PhpBefunge\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Pots\PhpBefunge\BefungeInterpreter;
use Pots\PhpBefunge\Exception\ExecutionLimitExceededException;
use Pots\PhpBefunge\Exception\InputRequiredException;
use Pots\PhpBefunge\Input\StringInput;

#[CoversClass(BefungeInterpreter::class)]
final class BefungeInterpreterTest extends TestCase
{
    private BefungeInterpreter $interpreter;

    protected function setUp(): void
    {
        $this->interpreter = new BefungeInterpreter();
    }

    #[Test]
    public function it_prints_hello_world(): void
    {
        // The program pushes 6+4=10 ('\n') first, so the newline
        // is printed after the text.
        self::assertSame(
            "Hello, World!\n",
            $this->interpreter->interpret('64+"!dlroW ,olleH">:#,_@'),
        );
    }

    #[Test]
    public function it_evaluates_arithmetic(): void
    {
        self::assertSame('7 ', $this->interpreter->interpret('52+.@'));   // 5 + 2
        self::assertSame('6 ', $this->interpreter->interpret('93-.@'));   // 9 - 3
        self::assertSame('8 ', $this->interpreter->interpret('42*.@'));   // 4 * 2
        self::assertSame('4 ', $this->interpreter->interpret('92/.@'));   // 9 / 2, truncated
        self::assertSame('1 ', $this->interpreter->interpret('92%.@'));   // 9 % 2
        self::assertSame('0 ', $this->interpreter->interpret('00/.@'));   // division by zero
        self::assertSame('0 ', $this->interpreter->interpret('00%.@'));   // modulo by zero
    }

    #[Test]
    public function it_compares_values(): void
    {
        self::assertSame('1 ', $this->interpreter->interpret('52`.@'));   // 5 > 2
        self::assertSame('0 ', $this->interpreter->interpret('25`.@'));   // 2 > 5
        self::assertSame('1 ', $this->interpreter->interpret('0!.@'));    // !0
        self::assertSame('0 ', $this->interpreter->interpret('5!.@'));    // !5
    }

    #[Test]
    public function it_handles_string_mode(): void
    {
        self::assertSame('A', $this->interpreter->interpret('"A",@'));
        self::assertSame('AB', $this->interpreter->interpret('"B""A",,@'));
    }

    #[Test]
    public function it_manipulates_the_stack(): void
    {
        self::assertSame('4 ', $this->interpreter->interpret('12:+.@'));  // dup: 2+2
        self::assertSame('1 ', $this->interpreter->interpret('12\\-.@')); // swap then subtract: 2-1
        self::assertSame('1 ', $this->interpreter->interpret('12$.@'));   // drop 2, print 1
        self::assertSame('0 ', $this->interpreter->interpret('.@'));      // pop on empty stack yields 0
    }

    #[Test]
    public function it_goes_right_on_zero_horizontal_conditional(): void
    {
        self::assertSame('1 ', $this->interpreter->interpret('0_1.@'));
    }

    #[Test]
    public function it_goes_left_on_non_zero_horizontal_conditional(): void
    {
        // 'v' drops onto '5', '_' pops 5 (non-zero) and sends the pointer
        // left onto '.', which prints 0 (the stack is empty after the pop),
        // then wraps onto '@'. Had '_' sent the pointer right, '@' at (3,2)
        // would have terminated the program without any output.
        self::assertSame('0 ', $this->interpreter->interpret("  v \n  5 \n ._@"));
    }

    #[Test]
    public function it_skips_cells_with_the_trampoline(): void
    {
        self::assertSame('4 ', $this->interpreter->interpret('1#23+.@')); // 3 is skipped: 1 + 3 = 4
    }

    #[Test]
    public function it_wraps_the_instruction_pointer(): void
    {
        // '<' sends the pointer left; it must wrap onto the '@' at the end.
        self::assertSame('', $this->interpreter->interpret('<@'));
    }

    #[Test]
    public function it_self_modifies_with_put_and_get(): void
    {
        // Write 'A' (65) at (5,0) with p, read it back with g, print it.
        self::assertSame('A', $this->interpreter->interpret('88*1+50p50g,@'));
    }

    #[Test]
    public function it_reads_input_when_provided(): void
    {
        $interpreter = new BefungeInterpreter(new StringInput('7'));

        self::assertSame('7 ', $interpreter->interpret('&.@'));

        $echo = new BefungeInterpreter(new StringInput('Q'));
        self::assertSame('Q', $echo->interpret('~,@'));
    }

    #[Test]
    public function it_throws_when_input_is_required_but_missing(): void
    {
        $this->expectException(InputRequiredException::class);
        $this->interpreter->interpret('&.@');
    }

    #[Test]
    public function it_throws_when_the_step_limit_is_exceeded(): void
    {
        $interpreter = new BefungeInterpreter(maxSteps: 100);

        $this->expectException(ExecutionLimitExceededException::class);
        $interpreter->interpret('>'); // infinite loop: keeps bouncing on the torus
    }

    #[Test]
    public function it_rejects_a_non_positive_step_limit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new BefungeInterpreter(maxSteps: 0);
    }

    #[Test]
    public function it_is_reusable_between_runs(): void
    {
        self::assertSame('3 ', $this->interpreter->interpret('12+.@'));
        self::assertSame('7 ', $this->interpreter->interpret('34+.@'));
        self::assertSame('3 ', $this->interpreter->interpret('12+.@'));
    }
}

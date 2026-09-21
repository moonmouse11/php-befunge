<?php

declare(strict_types=1);

namespace Pots\PhpBefunge;

use Pots\PhpBefunge\Exception\ExecutionLimitExceededException;
use Pots\PhpBefunge\Exception\InputRequiredException;
use Pots\PhpBefunge\Grid\Grid;
use Pots\PhpBefunge\Input\InputInterface;

/**
 * Interpreter for the Befunge-93 programming language.
 *
 * The interpreter is reusable: every call to interpret() starts from a
 * clean state. All behaviour that depends on the environment (program
 * input) is injected, so the class is safe to use inside any application.
 *
 * @see https://en.wikipedia.org/wiki/Befunge
 */
final class BefungeInterpreter implements InterpreterInterface
{
    private const DEFAULT_MAX_STEPS = 1_000_000;

    private Grid $grid;

    /** @var list<int> */
    private array $stack;

    private string $output;
    private bool $stringMode;

    // Instruction pointer position and direction.
    private int $x;
    private int $y;
    private int $dx;
    private int $dy;

    public function __construct(
        private readonly ?InputInterface $input = null,
        private readonly int $maxSteps = self::DEFAULT_MAX_STEPS,
    ) {
        if ($maxSteps < 1) {
            throw new \InvalidArgumentException('The step limit must be a positive integer.');
        }
    }

    public function interpret(string $code): string
    {
        $this->grid = Grid::fromString($code);
        $this->stack = [];
        $this->output = '';
        $this->stringMode = false;
        $this->x = 0;
        $this->y = 0;
        $this->dx = 1;
        $this->dy = 0;

        for ($step = 0; ; $step++) {
            if ($step >= $this->maxSteps) {
                throw new ExecutionLimitExceededException($this->maxSteps);
            }

            $instruction = $this->grid->getChar($this->x, $this->y);

            // The @ command terminates the program.
            if ($instruction === '@') {
                break;
            }

            $this->executeInstruction($instruction);
            $this->movePointer();
        }

        return $this->output;
    }

    private function executeInstruction(string $instruction): void
    {
        // In string mode every character is pushed as its ASCII value,
        // except the closing quote.
        if ($this->stringMode && $instruction !== '"') {
            $this->push(ord($instruction));
            return;
        }

        if (ctype_digit($instruction)) {
            $this->push((int) $instruction);
            return;
        }

        // The command groups are split into separate handlers to keep
        // the dispatch and each group easy to follow.
        match ($instruction) {
            '+', '-', '*', '/', '%', '!', '`' => $this->executeArithmetic($instruction),
            '>', '<', '^', 'v', '?', '_', '|' => $this->executeDirection($instruction),
            ':', '\\', '$' => $this->executeStackOperation($instruction),
            '.', ',' => $this->executeOutput($instruction),
            '#', 'p', 'g' => $this->executeGridOperation($instruction),
            '&', '~' => $this->executeInput($instruction),
            '"' => $this->stringMode = !$this->stringMode,
            default => null, // Spaces and unknown instructions are no-ops.
        };
    }

    /**
     * Arithmetic and logic: pop the operands, push the result.
     * Note the order: the top of the stack is the right operand.
     */
    private function executeArithmetic(string $instruction): void
    {
        switch ($instruction) {
            case '+':
                $a = $this->pop();
                $b = $this->pop();
                $this->push($b + $a);
                break;
            case '-':
                $a = $this->pop();
                $b = $this->pop();
                $this->push($b - $a);
                break;
            case '*':
                $a = $this->pop();
                $b = $this->pop();
                $this->push($b * $a);
                break;
            case '/':
                $a = $this->pop();
                $b = $this->pop();
                // Division by zero is defined to yield 0.
                $this->push($a === 0 ? 0 : intdiv($b, $a));
                break;
            case '%':
                $a = $this->pop();
                $b = $this->pop();
                // Modulo by zero is defined to yield 0; PHP's % keeps
                // the C-style truncation semantics of Befunge-93.
                $this->push($a === 0 ? 0 : $b % $a);
                break;
            case '!':
                $this->push($this->pop() === 0 ? 1 : 0);
                break;
            case '`':
                $a = $this->pop();
                $b = $this->pop();
                $this->push($b > $a ? 1 : 0);
                break;
            default:
                break; // Unreachable: the dispatch only forwards known instructions.
        }
    }

    private function executeDirection(string $instruction): void
    {
        switch ($instruction) {
            case '>':
                $this->dx = 1;
                $this->dy = 0;
                break;
            case '<':
                $this->dx = -1;
                $this->dy = 0;
                break;
            case '^':
                $this->dx = 0;
                $this->dy = -1;
                break;
            case 'v':
                $this->dx = 0;
                $this->dy = 1;
                break;
            case '?':
                $directions = [[1, 0], [-1, 0], [0, -1], [0, 1]];
                [$this->dx, $this->dy] = $directions[array_rand($directions)];
                break;
            case '_':
                $this->dy = 0;
                $this->dx = $this->pop() === 0 ? 1 : -1;
                break;
            case '|':
                $this->dx = 0;
                $this->dy = $this->pop() === 0 ? 1 : -1;
                break;
            default:
                break; // Unreachable: the dispatch only forwards known instructions.
        }
    }

    private function executeStackOperation(string $instruction): void
    {
        switch ($instruction) {
            case ':':
                // Duplicating an empty stack pushes 0.
                $this->push($this->stack === [] ? 0 : $this->stack[count($this->stack) - 1]);
                break;
            case '\\':
                $a = $this->pop();
                $b = $this->pop();
                $this->push($a);
                $this->push($b);
                break;
            case '$':
                $this->pop();
                break;
            default:
                break; // Unreachable: the dispatch only forwards known instructions.
        }
    }

    private function executeOutput(string $instruction): void
    {
        if ($instruction === '.') {
            // Integer output is followed by a space, per the spec.
            $this->output .= $this->pop() . ' ';
        } else {
            $this->output .= chr($this->pop() & 0xFF);
        }
    }

    private function executeGridOperation(string $instruction): void
    {
        if ($instruction === '#') {
            // Trampoline: execute an extra move to skip the next cell.
            $this->movePointer();
            return;
        }

        $y = $this->pop();
        $x = $this->pop();

        if ($instruction === 'p') {
            // Self-modification: write a character into the program grid.
            $this->grid->putChar($x, $y, chr($this->pop() & 0xFF));
        } else {
            // Read a character from the program grid.
            $this->push(ord($this->grid->getChar($x, $y)));
        }
    }

    private function executeInput(string $instruction): void
    {
        if ($this->input === null) {
            throw new InputRequiredException(sprintf(
                'Program requested %s, but no input source was provided.',
                $instruction === '&' ? 'an integer (&)' : 'a character (~)',
            ));
        }

        if ($instruction === '&') {
            $this->push($this->input->readInt());
        } else {
            $this->push(ord($this->input->readChar()));
        }
    }

    private function movePointer(): void
    {
        ['x' => $this->x, 'y' => $this->y] = $this->grid->wrap(
            $this->x + $this->dx,
            $this->y + $this->dy,
        );
    }

    private function push(int $value): void
    {
        $this->stack[] = $value;
    }

    /**
     * Pops a value off the stack. Per the Befunge-93 specification,
     * popping an empty stack yields 0.
     */
    private function pop(): int
    {
        if ($this->stack === []) {
            return 0;
        }

        return array_pop($this->stack);
    }
}

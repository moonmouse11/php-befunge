<?php

declare(strict_types=1);

namespace Pots\PhpBefunge\Befunge;

use Pots\PhpBefunge\Befunge\Grid\Grid;

final class BefungeInterpreter extends AbstractInterpreter
{
    private Grid $grid; // Our 2D array representing the program space
    private int $width; // Width of our program grid
    private int $height; // Height of our program grid
    private int $x; // Current X position of instruction pointer
    private int $y; // Current Y position of instruction pointer
    private int $dx; // Direction vector for X movement (1=right, -1=left, 0=no movement)
    private int $dy; // Direction vector for Y movement (1=down, -1=up, 0=no movement)
    private array $stack; // Our computation stack - PHP arrays work perfectly for this
    private string $output; // String to accumulate our program's outpu
    private bool $stringMode; // Boolean flag to track whether we're in string collection mode

    public function interpret(string $code): string
    {
        $this->grid = (new Grid())->setupGrid(code: $code); // Start at top-left corner (standard for Befunge)
        $this->y = 0;
        $this->dx = 1; // Initially moving right (default direction)
        $this->dy = 0;
        $this->stack = []; // PHP arrays serve as excellent stacks
        $this->output = ""; // PHP string concatenation is very efficient
        $this->stringMode = false; // We start in normal instruction mode

        // Keep executing until we hit the @ termination command
        // We include a safety limit to prevent infinite loops in malformed code
        $stepLimit = 1000000;
        $steps = 0;

        while ($steps < $stepLimit) {
            $currentChar = $this->getCurrentChar();

            // Check for program termination - the @ symbol ends execution
            if ($currentChar === "@") {
                break;
            }

            // Execute the current instruction
            $this->executeInstruction($currentChar);

            // Move to next position (with wrapping around edges)
            $this->movePointer();

            $steps++;
        }

        return $this->output;
    }

    /**
     * Convert the input string into a 2D grid that we can navigate
     * This transforms the linear text representation into our spatial program format
     *
     * Imagine taking a piece of paper with code written on it and creating a coordinate
     * system where we can point to any character by its (x,y) position
     */
    private function setupGrid(string $code): void
    {
        // Split the code into individual lines - PHP's explode is perfect for this
        $lines = explode("\n", $code);
        $this->height = count($lines);
        $this->width = 0;

        // Find the maximum width to create a rectangular grid
        // This ensures we can navigate consistently even if lines have different lengths
        foreach ($lines as $line) {
            $this->width = max($this->width, strlen($line));
        }

        // Create our 2D grid initialized with spaces (no-op characters)
        // PHP's array handling makes this quite straightforward
        $this->grid = [];
        for ($i = 0; $i < $this->height; $i++) {
            $this->grid[$i] = [];
            for ($j = 0; $j < $this->width; $j++) {
                $this->grid[$i][$j] = " "; // Default to space (no-op instruction)
            }
        }

        // Copy the actual program code into our grid structure
        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            for ($j = 0; $j < strlen($line); $j++) {
                $this->grid[$i][$j] = $line[$j];
            }
        }
    }

    /**
     * Get the character at current position, handling boundary conditions gracefully
     * This is our interface between the instruction pointer and the program grid
     */
    private function getCurrentChar(): string
    {
        // Boundary checking - return space for out-of-bounds positions
        // This provides safe behavior even if our pointer logic has bugs
        if (
            $this->x < 0 ||
            $this->x >= $this->width ||
            $this->y < 0 ||
            $this->y >= $this->height
        ) {
            return " ";
        }
        return $this->grid[$this->y][$this->x];
    }

    /**
     * Execute a single Befunge instruction
     * This is the heart of our interpreter - where each command gets processed
     *
     * Think of this as the CPU of our virtual machine, decoding and executing
     * each instruction according to the Befunge-93 specification
     */
    private function executeInstruction(string $instruction): void
    {
        // String mode has special handling - we collect characters instead of executing them
        // This allows Befunge programs to work with text data alongside numbers
        if ($this->stringMode && $instruction !== '"') {
            // In string mode, push the ASCII value of each character
            array_push($this->stack, ord($instruction));
            return;
        }

        // Process each instruction type according to Befunge-93 specification
        switch ($instruction) {
            // Digit instructions: push the numeric value onto our stack
            case "0":
            case "1":
            case "2":
            case "3":
            case "4":
            case "5":
            case "6":
            case "7":
            case "8":
            case "9":
                array_push($this->stack, intval($instruction));
                break;

            // Arithmetic operations - these all follow the pattern of popping two values,
            // performing the operation, and pushing the result back
            case "+":
                $a = $this->pop();
                $b = $this->pop();
                array_push($this->stack, $b + $a);
                break;
            case "-":
                $a = $this->pop();
                $b = $this->pop();
                array_push($this->stack, $b - $a); // Note the order: b - a, not a - b
                break;
            case "*":
                $a = $this->pop();
                $b = $this->pop();
                array_push($this->stack, $b * $a);
                break;
            case "/":
                $a = $this->pop();
                $b = $this->pop();
                // Handle division by zero as specified - return 0 instead of error
                $result = $a == 0 ? 0 : intval($b / $a);
                array_push($this->stack, $result);
                break;
            case "%":
                $a = $this->pop();
                $b = $this->pop();
                // Handle modulo by zero as specified
                $result = $a == 0 ? 0 : $b % $a;
                array_push($this->stack, $result);
                break;

            // Logic operations
            case "!":
                // Logical NOT - 0 becomes 1, anything else becomes 0
                $val = $this->pop();
                array_push($this->stack, $val == 0 ? 1 : 0);
                break;
            case "`": // Greater than comparison (backtick character)
                $a = $this->pop();
                $b = $this->pop();
                array_push($this->stack, $b > $a ? 1 : 0);
                break;

            // Direction change instructions - these modify our movement vector
            case ">":
                $this->dx = 1;
                $this->dy = 0;
                break; // Move right
            case "<":
                $this->dx = -1;
                $this->dy = 0;
                break; // Move left
            case "^":
                $this->dx = 0;
                $this->dy = -1;
                break; // Move up
            case "v":
                $this->dx = 0;
                $this->dy = 1;
                break; // Move down
            case "?": // Random direction - adds non-deterministic behavior
                $directions = [
                    [1, 0], // Right
                    [-1, 0], // Left
                    [0, -1], // Up
                    [0, 1], // Down
                ];
                $randomDir = $directions[array_rand($directions)];
                $this->dx = $randomDir[0];
                $this->dy = $randomDir[1];
                break;

            // Conditional movement instructions - these create branching logic
            case "_": // Horizontal conditional
                $val = $this->pop();
                $this->dx = $val == 0 ? 1 : -1; // Right if 0, left otherwise
                $this->dy = 0;
                break;
            case "|": // Vertical conditional
                $val = $this->pop();
                $this->dy = $val == 0 ? 1 : -1; // Down if 0, up otherwise
                $this->dx = 0;
                break;

            // String mode toggle - this switches between instruction and data collection modes
            case '"':
                $this->stringMode = !$this->stringMode;
                break;

            // Stack manipulation instructions
            case ":": // Duplicate top stack value
                $topVal = empty($this->stack) ? 0 : end($this->stack);
                array_push($this->stack, $topVal);
                break;
            case "\\": // Swap top two stack values
                $a = $this->pop();
                $b = $this->pop();
                array_push($this->stack, $a);
                array_push($this->stack, $b);
                break;
            case '$': // Discard top stack value
                $this->pop();
                break;

            // Output instructions - these generate the program's results
            case ".": // Output as integer
                $this->output .= $this->pop();
                break;
            case ",": // Output as ASCII character
                $this->output .= chr($this->pop());
                break;

            // Special control flow instructions
            case "#": // Trampoline - skip the next cell
                $this->movePointer(); // Move once extra to skip next instruction
                break;

            // Self-modification instructions - these allow programs to change themselves
            case "p": // Put - modify the program grid
                $y = $this->pop();
                $x = $this->pop();
                $val = $this->pop();
                // Bounds checking to prevent crashes
                if (
                    $x >= 0 &&
                    $x < $this->width &&
                    $y >= 0 &&
                    $y < $this->height
                ) {
                    $this->grid[$y][$x] = chr($val);
                }
                break;
            case "g": // Get - read from the program grid
                $y = $this->pop();
                $x = $this->pop();
                if (
                    $x >= 0 &&
                    $x < $this->width &&
                    $y >= 0 &&
                    $y < $this->height
                ) {
                    array_push($this->stack, ord($this->grid[$y][$x]));
                } else {
                    array_push($this->stack, 0); // Out of bounds returns 0
                }
                break;

            // No-operation cases
            case " ":
                // Space character does nothing - allows for formatting and spacing
                break;

            default:
                // Unknown instructions are treated as no-ops
                // This provides graceful degradation for invalid characters
                break;
        }
    }

    /**
     * Safe pop operation that returns 0 if stack is empty
     * This implements the Befunge specification that operations on empty stacks use 0
     *
     * This is crucial because Befunge programs often assume they can pop values
     * even when the stack might be empty, and the language defines this behavior
     */
    private function pop(): string
    {
        if (empty($this->stack)) {
            return 0;
        }
        return array_pop($this->stack);
    }

    /**
     * Move the instruction pointer in the current direction with wrapping
     * This implements the toroidal topology of Befunge programs
     *
     * Imagine the program grid as being printed on a torus (donut shape) where
     * moving off one edge brings you to the opposite edge
     */
    private function movePointer(): void
    {
        $this->x += $this->dx;
        $this->y += $this->dy;

        // Implement wrapping behavior using modulo arithmetic
        // PHP's modulo handles negative numbers differently than some languages,
        // so we add the dimension and modulo again for negative wrapping
        if ($this->x < 0) {
            $this->x = $this->width - 1;
        }
        if ($this->x >= $this->width) {
            $this->x = 0;
        }
        if ($this->y < 0) {
            $this->y = $this->height - 1;
        }
        if ($this->y >= $this->height) {
            $this->y = 0;
        }
    }
}

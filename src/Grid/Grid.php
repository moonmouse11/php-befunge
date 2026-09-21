<?php

declare(strict_types=1);

namespace Pots\PhpBefunge\Grid;

/**
 * Two-dimensional, fixed-size program space (torus) for a Befunge-93 program.
 *
 * The grid is padded with spaces up to the widest line, so ragged programs
 * keep their rectangular shape. Reading outside the grid yields a space,
 * mirroring the behaviour of the reference Befunge-93 playfield.
 */
final class Grid
{
    /** @var array<int, array<int, string>> */
    private array $cells;

    private int $width;
    private int $height;

    private function __construct()
    {
        // Instances are created exclusively through the fromString() factory.
    }

    public static function fromString(string $code): self
    {
        $code = str_replace(["\r\n", "\r"], "\n", $code);
        $lines = explode("\n", $code);

        $width = 0;
        foreach ($lines as $line) {
            $width = max($width, strlen($line));
        }

        $cells = [];
        foreach ($lines as $line) {
            $row = [];
            for ($x = 0; $x < $width; $x++) {
                $row[] = $x < strlen($line) ? $line[$x] : ' ';
            }
            $cells[] = $row;
        }

        $grid = new self();
        $grid->width = $width;
        $grid->height = count($lines);
        $grid->cells = $cells;

        return $grid;
    }

    /**
     * Returns the character at the given coordinates.
     * Out-of-bounds coordinates yield a space.
     */
    public function getChar(int $x, int $y): string
    {
        if (!$this->isInside($x, $y)) {
            return ' ';
        }

        return $this->cells[$y][$x];
    }

    /**
     * Writes a single character at the given coordinates.
     * Out-of-bounds writes are silently ignored.
     */
    public function putChar(int $x, int $y, string $char): void
    {
        if (!$this->isInside($x, $y) || $char === '') {
            return;
        }

        $this->cells[$y][$x] = $char[0];
    }

    /**
     * Wraps the given coordinates onto the torus, so the instruction
     * pointer re-enters the grid on the opposite edge.
     *
     * @return array{x: int, y: int}
     */
    public function wrap(int $x, int $y): array
    {
        if ($this->width === 0 || $this->height === 0) {
            return ['x' => 0, 'y' => 0];
        }

        return [
            'x' => (($x % $this->width) + $this->width) % $this->width,
            'y' => (($y % $this->height) + $this->height) % $this->height,
        ];
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    private function isInside(int $x, int $y): bool
    {
        return $x >= 0 && $x < $this->width && $y >= 0 && $y < $this->height;
    }
}

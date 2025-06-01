<?php

declare(strict_types=1);

namespace Pots\PhpBefunge\Befunge\Grid;

final class Grid
{
    private array $grid;
    private int $width;
    private int $height;
    private int $x;
    private int $y;

    private function __construct() {}

    /**
     * @description Sets up the grid based on the provided code.
     *
     * @param string $code The Befunge code to be parsed.
     * @return Grid The initialized grid object.
     */
    public function setupGrid(string $code): Grid
    {
        $lines = explode("\n", $code);
        $this->height = count($lines);
        $this->width = 0;

        foreach ($lines as $line) {
            $this->width = max($this->width, strlen($line));
        }

        $this->grid = [];
        for ($i = 0; $i < $this->height; $i++) {
            $this->grid[$i] = [];
            for ($j = 0; $j < $this->width; $j++) {
                $this->grid[$i][$j] = " ";
            }
        }

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            for ($j = 0; $j < strlen($line); $j++) {
                $this->grid[$i][$j] = $line[$j];
            }
        }

        return $this;
    }

    /**
     * @description Get the character at current position, handling boundary conditions gracefully
     * This is our interface between the instruction pointer and the program grid
     */
    public function getCurrentChar(): string
    {
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
     * @description Move the instruction pointer in the current direction with wrapping
     *
     * @param int $moveX The x-axis movement
     * @param int $moveY The y-axis movement
     */
    public function movePointer(int $moveX, int $moveY): void
    {
        $this->x += $moveX;
        $this->y += $$moveY;

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

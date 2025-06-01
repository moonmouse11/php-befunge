<?php

declare(strict_types=1);

namespace Pots\PhpBefunge\Befunge\Grid;

final class Grid
{
    private array $grid;
    private int $width;
    private int $height;
    private int $dx;
    private int $dy;

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
}

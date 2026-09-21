<?php

declare(strict_types=1);

namespace Pots\PhpBefunge\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Pots\PhpBefunge\Grid\Grid;

#[CoversClass(Grid::class)]
final class GridTest extends TestCase
{
    #[Test]
    public function it_builds_a_padded_rectangular_grid(): void
    {
        $grid = Grid::fromString("ab\ncde");

        self::assertSame(3, $grid->getWidth());
        self::assertSame(2, $grid->getHeight());
        self::assertSame('a', $grid->getChar(0, 0));
        self::assertSame('b', $grid->getChar(1, 0));
        // Missing cells are padded with spaces.
        self::assertSame(' ', $grid->getChar(2, 0));
        self::assertSame('e', $grid->getChar(2, 1));
    }

    #[Test]
    public function it_normalizes_windows_line_endings(): void
    {
        $grid = Grid::fromString("ab\r\ncd");

        self::assertSame(2, $grid->getHeight());
        self::assertSame('c', $grid->getChar(0, 1));
    }

    #[Test]
    public function it_returns_space_for_out_of_bounds_reads(): void
    {
        $grid = Grid::fromString('ab');

        self::assertSame(' ', $grid->getChar(-1, 0));
        self::assertSame(' ', $grid->getChar(5, 0));
        self::assertSame(' ', $grid->getChar(0, 9));
    }

    #[Test]
    public function it_writes_and_reads_characters(): void
    {
        $grid = Grid::fromString('ab');

        $grid->putChar(1, 0, 'Z');
        self::assertSame('Z', $grid->getChar(1, 0));

        // Out-of-bounds and empty writes are ignored.
        $grid->putChar(10, 10, 'X');
        $grid->putChar(0, 0, '');
        self::assertSame('a', $grid->getChar(0, 0));
    }

    #[Test]
    public function it_wraps_coordinates_onto_the_torus(): void
    {
        $grid = Grid::fromString("ab\ncd");

        self::assertSame(['x' => 1, 'y' => 0], $grid->wrap(1, 2));
        self::assertSame(['x' => 1, 'y' => 1], $grid->wrap(-1, 1));
        self::assertSame(['x' => 1, 'y' => 1], $grid->wrap(3, 5));
        self::assertSame(['x' => 0, 'y' => 0], $grid->wrap(-2, -2));
    }
}

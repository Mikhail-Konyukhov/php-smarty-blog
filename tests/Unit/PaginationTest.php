<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Pagination;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PaginationTest extends TestCase
{
    /**
     * 0 статей — одна страница: пустая категория открывается, а не даёт 404.
     */
    public function testEmptyCategoryHasOnePage(): void
    {
        $this->assertSame(1, (new Pagination(0, 1, 9))->pages);
    }

    /**
     * По 9 на странице: 9 статей — 1 страница, 10 — 2.
     */
    public function testPageCountRoundsUp(): void
    {
        $this->assertSame(1, (new Pagination(9, 1, 9))->pages);
        $this->assertSame(2, (new Pagination(10, 1, 9))->pages);
    }

    /**
     * По 9 на странице: страница 1 начинается с 0, страница 3 — с 18.
     */
    public function testOffsetSkipsPreviousPages(): void
    {
        $this->assertSame(0, (new Pagination(100, 1, 9))->offset);
        $this->assertSame(18, (new Pagination(100, 3, 9))->offset);
    }

    /**
     * Случаи для links(). По одной статье на странице, поэтому число статей
     * равно числу страниц.
     *
     * @return iterable<string, array{
     *     int,
     *     int,
     *     list<int|null>
     * }> всего статей, текущая страница, ожидаемый результат links()
     */
    public static function linksProvider(): iterable
    {
        yield 'мало страниц, без пропусков' => [5, 3, [1, 2, 3, 4, 5]];
        yield 'пропуски с двух сторон' => [12, 6, [1, null, 4, 5, 6, 7, 8, null, 12]];
        yield 'первая страница' => [12, 1, [1, 2, 3, null, 12]];
        yield 'последняя страница' => [12, 12, [1, null, 10, 11, 12]];
        yield 'пропуск в одну страницу выводится номером' => [12, 5, [1, 2, 3, 4, 5, 6, 7, null, 12]];
        // Решение 38: тот же пропуск справа, иначе ошибку на единицу в правой границе тесты не заметят.
        yield 'пропуск в одну страницу справа' => [12, 8, [1, null, 6, 7, 8, 9, 10, 11, 12]];
    }

    /**
     * @param int            $total    всего статей, по одной на странице
     * @param int            $page     текущая страница
     * @param list<int|null> $expected ожидаемый результат links()
     */
    #[DataProvider('linksProvider')]
    public function testLinks(int $total, int $page, array $expected): void
    {
        $this->assertSame($expected, (new Pagination($total, $page, 1))->links());
    }
}

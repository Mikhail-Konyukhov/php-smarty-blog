<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\ArticleRepository;

final class ArticleRepositoryTest extends DatabaseTestCase
{
    /**
     * Пять статей в одной категории: в блоке три самые новые, сначала новая;
     * у карточки ровно поля карточки, без body.
     */
    public function testLatestPerCategoryReturnsThreeNewest(): void
    {
        $category = $this->insertCategory();
        $ids = [];
        foreach (['2026-01-01', '2026-01-05', '2026-01-02', '2026-01-04', '2026-01-03'] as $date) {
            $ids[$date] = $this->insertArticle([$category], "$date 00:00:00");
        }

        $blocks = (new ArticleRepository(self::$pdo))->findLatestPerCategory(3);

        $this->assertCount(1, $blocks);
        $this->assertSame($category, $blocks[0]['id']);
        $this->assertSame(
            [$ids['2026-01-05'], $ids['2026-01-04'], $ids['2026-01-03']],
            array_column($blocks[0]['articles'], 'id'),
        );
        $this->assertSame(
            ['id', 'title', 'description', 'image', 'views', 'published_at'],
            array_keys($blocks[0]['articles'][0]),
        );
    }

    /**
     * Статья из двух категорий есть в обоих блоках, пустой категории нет,
     * блоки идут по названию категории.
     */
    public function testLatestPerCategoryPutsSharedArticleInBothBlocksAndSkipsEmpty(): void
    {
        // Вставлены не по алфавиту: порядок по id дал бы Б, А
        $b = $this->insertCategory('Б');
        $a = $this->insertCategory('А');
        $this->insertCategory('В');
        $shared = $this->insertArticle([$a, $b]);
        $onlyB = $this->insertArticle([$b], '2025-12-01 00:00:00');

        $blocks = (new ArticleRepository(self::$pdo))->findLatestPerCategory(3);

        $this->assertSame(['А', 'Б'], array_column($blocks, 'name'));
        $this->assertSame([$a, $b], array_column($blocks, 'id'));
        $this->assertSame([$shared], array_column($blocks[0]['articles'], 'id'));
        $this->assertSame([$shared, $onlyB], array_column($blocks[1]['articles'], 'id'));
    }
}

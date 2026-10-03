<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\ArticleRepository;
use App\ArticleSort;

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

    /**
     * Считаются только статьи этой категории.
     */
    public function testCountByCategoryCountsOnlyThatCategory(): void
    {
        $a = $this->insertCategory();
        $b = $this->insertCategory();
        $this->insertArticle([$a]);
        $this->insertArticle([$a, $b]);
        $this->insertArticle([$b]);
        $this->insertArticle([$b]);

        $repository = new ArticleRepository(self::$pdo);

        $this->assertSame(2, $repository->countByCategory($a));
        $this->assertSame(3, $repository->countByCategory($b));
    }

    /**
     * По дате: сначала новые, статей других категорий нет.
     */
    public function testFindByCategorySortsByDateNewestFirst(): void
    {
        $own = $this->insertCategory();
        $other = $this->insertCategory();
        $jan2 = $this->insertArticle([$own], '2026-01-02 00:00:00');
        $jan3 = $this->insertArticle([$own], '2026-01-03 00:00:00');
        $jan1 = $this->insertArticle([$own], '2026-01-01 00:00:00');
        // Новее всех, но в другой категории
        $this->insertArticle([$other], '2026-02-01 00:00:00');

        $cards = (new ArticleRepository(self::$pdo))->findByCategory($own, ArticleSort::Date, 9, 0);

        $this->assertSame([$jan3, $jan2, $jan1], array_column($cards, 'id'));
    }

    /**
     * По просмотрам: по убыванию, статьи с равными просмотрами — по id. Две
     * страницы одного списка не делят ни одной статьи и вместе дают все статьи.
     */
    public function testFindByCategorySortsByViewsAndPagesDoNotOverlap(): void
    {
        $category = $this->insertCategory();
        // Две статьи по 30 просмотров стоят на стыке страниц: без второго ключа
        // сортировки одна из них могла бы попасть на обе страницы
        $tieLowerId = $this->insertArticle([$category], views: 30);
        $views10 = $this->insertArticle([$category], views: 10);
        $views50 = $this->insertArticle([$category], views: 50);
        $tieHigherId = $this->insertArticle([$category], views: 30);
        $views40 = $this->insertArticle([$category], views: 40);

        $repository = new ArticleRepository(self::$pdo);
        $first = $repository->findByCategory($category, ArticleSort::Views, 3, 0);
        $second = $repository->findByCategory($category, ArticleSort::Views, 3, 3);

        $this->assertSame([$views50, $views40, $tieHigherId], array_column($first, 'id'));
        $this->assertSame([$tieLowerId, $views10], array_column($second, 'id'));
    }

    /**
     * После двух вызовов просмотров на 2 больше.
     */
    public function testIncrementViewsAddsOneEachCall(): void
    {
        $id = $this->insertArticle([$this->insertCategory()], views: 5);

        $repository = new ArticleRepository(self::$pdo);
        $repository->incrementViews($id);
        $repository->incrementViews($id);

        $this->assertSame(7, self::$pdo->query("SELECT views FROM articles WHERE id = $id")->fetchColumn());
    }

    /**
     * Есть статья — все поля, с body; нет статьи — null.
     */
    public function testFindReturnsFullArticleOrNull(): void
    {
        $id = $this->insertArticle([$this->insertCategory()], '2026-01-02 03:04:05', 12);

        $repository = new ArticleRepository(self::$pdo);

        $this->assertSame(
            [
                'id' => $id,
                'title' => 'Заголовок',
                'description' => 'Анонс',
                'body' => 'Текст',
                'image' => '/images/seed/01.jpg',
                'views' => 12,
                'published_at' => '2026-01-02 03:04:05',
            ],
            $repository->find($id),
        );
        $this->assertNull($repository->find($id + 1));
    }

    /**
     * Статья с двумя общими категориями выше статьи с одной, даже если старше;
     * при равном числе общих — новая выше; самой статьи и повторов нет.
     */
    public function testFindSimilarRanksBySharedCategoriesThenDate(): void
    {
        $a = $this->insertCategory();
        $b = $this->insertCategory();
        $source = $this->insertArticle([$a, $b], '2026-01-01 00:00:00');
        $twoShared = $this->insertArticle([$a, $b], '2025-01-01 00:00:00');
        $oneSharedOlder = $this->insertArticle([$b], '2026-02-01 00:00:00');
        $oneSharedNewer = $this->insertArticle([$a], '2026-03-01 00:00:00');

        $cards = (new ArticleRepository(self::$pdo))->findSimilar($source, 3);

        $this->assertSame([$twoShared, $oneSharedNewer, $oneSharedOlder], array_column($cards, 'id'));
    }

    /**
     * Статьи без общих категорий нет; если подходящих меньше лимита,
     * возвращаются все подходящие.
     */
    public function testFindSimilarSkipsUnrelatedAndMayReturnFewer(): void
    {
        $a = $this->insertCategory();
        $b = $this->insertCategory();
        $source = $this->insertArticle([$a]);
        $related = $this->insertArticle([$a]);
        // Новее всех, но без общих категорий
        $this->insertArticle([$b], '2026-02-01 00:00:00');

        $cards = (new ArticleRepository(self::$pdo))->findSimilar($source, 3);

        $this->assertSame([$related], array_column($cards, 'id'));
    }
}

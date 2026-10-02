<?php

declare(strict_types=1);

namespace App;

use PDO;

/**
 * Запросы, которые возвращают строки статей. Один метод — один SQL-запрос.
 *
 * Карточка статьи — то, что выводит partials/article-card.tpl:
 *  - id            int     id статьи
 *  - title         string  заголовок
 *  - description   string  анонс, короткий текст под заголовком
 *  - image         string  путь к картинке от корня сайта, например /images/seed/03.jpg
 *  - views         int     число просмотров
 *  - published_at  string  дата публикации в формате MySQL, 'Y-m-d H:i:s'
 *
 * Статья целиком — поля карточки и body, текст статьи: абзацы разделены
 * пустой строкой.
 */
final class ArticleRepository
{
    /**
     * @param PDO $pdo подключение из src/db.php
     */
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Блоки главной: каждая категория, в которой есть статьи, и её последние статьи.
     *
     * Один запрос нумерует статьи внутри каждой категории через ROW_NUMBER()
     * и оставляет первые $limit; этот метод раскладывает строки запроса по
     * блокам. Статья из двух категорий попадает в оба блока. Категории без
     * статей в результат не попадают.
     *
     * @param int $limit сколько статей показать в каждой категории, от 1
     *
     * @return list<array{
     *     id: int,
     *     name: string,
     *     articles: list<array{
     *         id: int,
     *         title: string,
     *         description: string,
     *         image: string,
     *         views: int,
     *         published_at: string
     *     }>
     * }> блоки по названию категории. В каждом id и название категории и от 1
     *    до $limit карточек, сначала новые. Пустой список, если статей нет совсем
     */
    public function findLatestPerCategory(int $limit): array
    {
        $stmt = $this->pdo->prepare(
            'WITH ranked AS (
                 SELECT c.id   AS category_id,
                        c.name AS category_name,
                        a.id, a.title, a.description, a.image, a.views, a.published_at,
                        ROW_NUMBER() OVER (PARTITION BY c.id ORDER BY a.published_at DESC, a.id DESC) AS rn
                 FROM categories c
                 JOIN article_category ac ON ac.category_id = c.id
                 JOIN articles a ON a.id = ac.article_id
             )
             SELECT category_id, category_name, id, title, description, image, views, published_at
             FROM ranked
             WHERE rn <= :limit
             ORDER BY category_name, category_id, rn'
        );
        $stmt->execute(['limit' => $limit]);

        // Строки уже идут блоками: по категории, внутри — от новой статьи к старой
        $blocks = [];
        foreach ($stmt as $row) {
            $id = $row['category_id'];
            $blocks[$id] ??= ['id' => $id, 'name' => $row['category_name'], 'articles' => []];
            unset($row['category_id'], $row['category_name']);
            $blocks[$id]['articles'][] = $row;
        }

        return array_values($blocks);
    }

    /**
     * Сколько статей в категории, для расчёта страниц.
     *
     * @param int $categoryId id категории
     *
     * @return int от 0; 0 и у пустой категории, и у несуществующей
     */
    public function countByCategory(int $categoryId): int
    {
    }

    /**
     * Одна страница статей категории.
     *
     * @param int         $categoryId id категории
     * @param ArticleSort $sort       порядок: по дате или по просмотрам, при равенстве — по id
     * @param int         $limit      сколько статей на странице, значение для LIMIT
     * @param int         $offset     сколько статей пропустить, значение для OFFSET
     *
     * @return list<array{
     *     id: int,
     *     title: string,
     *     description: string,
     *     image: string,
     *     views: int,
     *     published_at: string
     * }> карточки в порядке $sort; пустой список, если на этой странице статей нет
     */
    public function findByCategory(int $categoryId, ArticleSort $sort, int $limit, int $offset): array
    {
    }

    /**
     * Прибавляет один просмотр одним UPDATE, поэтому одновременные просмотры
     * не теряются.
     *
     * @param int $id id статьи; если такой нет, ничего не меняется и ошибки нет
     */
    public function incrementViews(int $id): void
    {
    }

    /**
     * Статья целиком, для её страницы.
     *
     * @param int $id id статьи
     *
     * @return array{
     *     id: int,
     *     title: string,
     *     description: string,
     *     body: string,
     *     image: string,
     *     views: int,
     *     published_at: string
     * }|null поля карточки и body; null, если такой статьи нет
     */
    public function find(int $id): ?array
    {
    }

    /**
     * Похожие статьи: с наибольшим числом общих категорий, при равенстве — новые.
     *
     * @param int $id    id статьи, для которой ищем похожие; сама она в результат не входит
     * @param int $limit сколько статей нужно, от 1
     *
     * @return list<array{
     *     id: int,
     *     title: string,
     *     description: string,
     *     image: string,
     *     views: int,
     *     published_at: string
     * }> от 0 до $limit карточек, каждая статья один раз. Меньше $limit,
     *    если статей с общими категориями меньше
     */
    public function findSimilar(int $id, int $limit): array
    {
    }
}

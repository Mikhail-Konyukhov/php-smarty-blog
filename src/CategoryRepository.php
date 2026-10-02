<?php

declare(strict_types=1);

namespace App;

use PDO;

/**
 * Запросы, которые возвращают строки категорий. Один метод — один SQL-запрос.
 */
final class CategoryRepository
{
    /**
     * @param PDO $pdo подключение из src/db.php
     */
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Категория для её страницы.
     *
     * @param int $id id категории
     *
     * @return array{
     *     id: int,
     *     name: string,
     *     description: string
     * }|null id, название и описание; null, если такой категории нет
     */
    public function find(int $id): ?array
    {
    }

    /**
     * Категории одной статьи, для ссылок на странице статьи.
     *
     * @param int $articleId id статьи
     *
     * @return list<array{
     *     id: int,
     *     name: string
     * }> id и название каждой категории статьи, по названию;
     *    пустой список, если такой статьи нет
     */
    public function findByArticle(int $articleId): array
    {
    }
}

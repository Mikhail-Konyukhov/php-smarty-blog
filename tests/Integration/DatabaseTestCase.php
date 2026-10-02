<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Основа тестов SQL на базе blog_test: phpunit.xml подменяет DB_NAME с force="true",
 * подключается src/db.php. Таблицы пересоздаются один раз на класс тестов,
 * каждый тест идёт в транзакции и откатывается, поэтому тесты не видят строк
 * друг друга.
 */
abstract class DatabaseTestCase extends TestCase
{
    /** Одно подключение на класс тестов. */
    protected static PDO $pdo;

    /**
     * Подключается, проверяет, что база — blog_test, и выполняет database/schema.sql
     * одним exec(): PDO выбрасывает исключение при ошибке в любом запросе файла,
     * не только в первом.
     *
     * Если SELECT DATABASE() вернул не blog_test, тест падает до schema.sql:
     * в schema.sql есть DROP TABLE, и на базе сайта он удалил бы все статьи.
     * Так бывает, если в phpunit.xml у <env name="DB_NAME"> нет force="true":
     * в контейнере app уже задано DB_NAME=blog, и PHPUnit без force его не
     * меняет (решение 37 уровня 3).
     */
    public static function setUpBeforeClass(): void
    {
        self::$pdo = require __DIR__ . '/../../src/db.php';

        $database = self::$pdo->query('SELECT DATABASE()')->fetchColumn();
        if ($database !== 'blog_test') {
            self::fail("Тесты подключены к базе {$database}, а не к blog_test: schema.sql удалил бы её таблицы");
        }

        self::$pdo->exec(file_get_contents(__DIR__ . '/../../database/schema.sql'));
    }

    /**
     * Открывает транзакцию, в которой пройдёт тест.
     */
    protected function setUp(): void
    {
        self::$pdo->beginTransaction();
    }

    /**
     * Откатывает транзакцию: строки, вставленные тестом, исчезают.
     */
    protected function tearDown(): void
    {
        self::$pdo->rollBack();
    }

    /**
     * Вставляет категорию тем же INSERT, что и bin/seed.php.
     *
     * @param string $name название; от него зависит порядок блоков на главной
     *                     и порядок категорий статьи
     *
     * @return int id новой категории
     */
    protected function insertCategory(string $name = 'Категория'): int
    {
        self::$pdo->prepare('INSERT INTO categories (name, description) VALUES (:name, :description)')
            ->execute(['name' => $name, 'description' => 'Описание']);

        return (int) self::$pdo->lastInsertId();
    }

    /**
     * Вставляет статью и её связи с категориями теми же INSERT, что и bin/seed.php.
     *
     * @param list<int> $categoryIds id категорий статьи, хотя бы одна
     * @param string    $publishedAt дата публикации, 'Y-m-d H:i:s'
     * @param int       $views       число просмотров
     *
     * @return int id новой статьи
     */
    protected function insertArticle(array $categoryIds, string $publishedAt = '2026-01-01 00:00:00', int $views = 0): int
    {
        self::$pdo->prepare(
            'INSERT INTO articles (title, description, body, image, views, published_at)
             VALUES (:title, :description, :body, :image, :views, :published_at)'
        )->execute([
            'title' => 'Заголовок',
            'description' => 'Анонс',
            'body' => 'Текст',
            'image' => '/images/seed/01.jpg',
            'views' => $views,
            'published_at' => $publishedAt,
        ]);
        $articleId = (int) self::$pdo->lastInsertId();

        $insertLink = self::$pdo->prepare('INSERT INTO article_category (category_id, article_id) VALUES (:category_id, :article_id)');
        foreach ($categoryIds as $categoryId) {
            $insertLink->execute(['category_id' => $categoryId, 'article_id' => $articleId]);
        }

        return $articleId;
    }
}

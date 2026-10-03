<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\CategoryRepository;

final class CategoryRepositoryTest extends DatabaseTestCase
{
    /**
     * Есть категория — id, название и описание; нет категории — null.
     */
    public function testFindReturnsCategoryOrNull(): void
    {
        $id = $this->insertCategory('Путешествия');

        $repository = new CategoryRepository(self::$pdo);

        $this->assertSame(['id' => $id, 'name' => 'Путешествия', 'description' => 'Описание'], $repository->find($id));
        $this->assertNull($repository->find($id + 1));
    }

    /**
     * Только категории этой статьи, по названию.
     */
    public function testFindByArticleReturnsItsCategoriesByName(): void
    {
        // Вставлены не по алфавиту: порядок по id дал бы Б, А
        $b = $this->insertCategory('Б');
        $a = $this->insertCategory('А');
        $other = $this->insertCategory('В');
        $article = $this->insertArticle([$b, $a]);
        $this->insertArticle([$other]);

        $this->assertSame(
            [['id' => $a, 'name' => 'А'], ['id' => $b, 'name' => 'Б']],
            (new CategoryRepository(self::$pdo))->findByArticle($article),
        );
    }
}

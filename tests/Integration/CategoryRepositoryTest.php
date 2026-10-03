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
}

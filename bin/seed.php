<?php

declare(strict_types=1);

// Пересоздаёт данные блога: 8 категорий и 300 статей.
// Запуск: docker compose exec app php bin/seed.php
//
// Всё идёт в одной транзакции. При ошибке исключение не ловится: скрипт
// завершается с кодом 255, а MySQL откатывает незакоммиченную транзакцию,
// когда закрывается соединение. Старые данные остаются как были.

require __DIR__ . '/../vendor/autoload.php';

const ARTICLES = 300;

// Статьи получают 1–3 категории из первых шести: у каждой около 12 страниц,
// видно сокращение номеров. Седьмая есть только у пяти статей: одна страница,
// пагинации нет. Восьмая пустая: на главной видно, что пустые скрыты.
const CATEGORIES = [
    'Путешествия' => 'Маршруты, города и дороги: куда поехать и что посмотреть.',
    'Технологии'  => 'Гаджеты, программы и то, как они меняют повседневную жизнь.',
    'Наука'       => 'Открытия, эксперименты и объяснения того, как устроен мир.',
    'Кулинария'   => 'Рецепты, продукты и истории блюд.',
    'Здоровье'    => 'Сон, питание, движение и привычки, которые помогают.',
    'Культура'    => 'Книги, кино, музыка и выставки.',
    'Интервью'    => 'Разговоры с людьми, которые делают что-то интересное.',
    'Архив'       => 'Старые материалы. Пока здесь пусто.',
];

/** @var PDO $pdo */
$pdo = require __DIR__ . '/../src/db.php';
$faker = Faker\Factory::create('ru_RU');

$images = glob(__DIR__ . '/../public/images/seed/*.jpg');
if (!$images) {
    throw new RuntimeException('Нет картинок в public/images/seed/');
}

$insertCategory = $pdo->prepare('INSERT INTO categories (name, description) VALUES (:name, :description)');
$insertArticle = $pdo->prepare(
    'INSERT INTO articles (title, description, body, image, views, published_at)
     VALUES (:title, :description, :body, :image, :views, :published_at)'
);
$insertLink = $pdo->prepare('INSERT INTO article_category (category_id, article_id) VALUES (:category_id, :article_id)');

$pdo->beginTransaction();

// Не TRUNCATE: MySQL не выполняет его для таблиц, на которые ссылаются FK
$pdo->exec('DELETE FROM article_category');
$pdo->exec('DELETE FROM articles');
$pdo->exec('DELETE FROM categories');

$categoryIds = [];
foreach (CATEGORIES as $name => $description) {
    $insertCategory->execute(['name' => $name, 'description' => $description]);
    $categoryIds[] = (int) $pdo->lastInsertId();
}

$links = 0;
for ($i = 0; $i < ARTICLES; $i++) {
    // Абзацы разделены пустой строкой, шаблон выводит их через white-space: pre-line
    $paragraphs = array_map(fn () => $faker->realText(600), range(1, random_int(3, 6)));

    $insertArticle->execute([
        'title' => rtrim($faker->realText(60), '.'),
        'description' => $faker->realText(200),
        'body' => implode("\n\n", $paragraphs),
        'image' => '/images/seed/' . basename($faker->randomElement($images)),
        'views' => random_int(0, 5000),
        'published_at' => $faker->dateTimeBetween('-1 year')->format('Y-m-d H:i:s'),
    ]);
    $articleId = (int) $pdo->lastInsertId();

    $articleCategories = $faker->randomElements(array_slice($categoryIds, 0, 6), random_int(1, 3));
    if ($i < 5) {
        $articleCategories[] = $categoryIds[6];
    }
    foreach ($articleCategories as $categoryId) {
        $insertLink->execute(['category_id' => $categoryId, 'article_id' => $articleId]);
        $links++;
    }
}

$pdo->commit();

printf("Категорий: %d, статей: %d, связей статей с категориями: %d\n", count($categoryIds), ARTICLES, $links);

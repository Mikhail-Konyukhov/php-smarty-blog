<?php

declare(strict_types=1);

// Пересоздаёт данные блога: 8 категорий и статьи, по умолчанию 300.
// Запуск: docker compose exec app php bin/seed.php
// Для замера на большом объёме: docker compose exec app php bin/seed.php 200000
//
// Всё идёт в одной транзакции. При ошибке исключение не ловится: скрипт
// завершается с кодом 255, а MySQL откатывает незакоммиченную транзакцию,
// когда закрывается соединение. Старые данные остаются как были.

require __DIR__ . '/../vendor/autoload.php';

// Число статей — первый аргумент. Проверяется до DELETE: иначе «abc» стал бы
// нулём, и сидер удалил бы все статьи и завершился бы с кодом 0.
$articleCount = filter_var($argv[1] ?? 300, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($articleCount === false) {
    fwrite(STDERR, "Число статей — целое от 1, например: php bin/seed.php 200000\n");
    exit(1);
}

// Сколько заготовить абзацев, заголовков и анонсов
const TEXTS = 1000;

// Статьи получают 1–3 категории из первых шести: при 300 статьях у каждой
// около 12 страниц, видно сокращение номеров. Седьмая есть только у пяти
// статей: одна страница, пагинации нет. Восьмая пустая: на главной видно,
// что пустые скрыты.
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

// Каждый вызов Faker realText занимает 5–8 мс, на 200 000 статей это больше
// часа. Поэтому тексты готовятся один раз. Абзацы статьи берут случайно,
// заголовки и анонсы — по кругу, чтобы до 1000 статей они не повторялись.
$texts = min(TEXTS, $articleCount);
$titles = array_map(fn () => rtrim($faker->realText(60), '.'), range(1, $texts));
$descriptions = array_map(fn () => $faker->realText(200), range(1, $texts));
$paragraphs = array_map(fn () => $faker->realText(600), range(1, TEXTS));

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
for ($i = 0; $i < $articleCount; $i++) {
    $insertArticle->execute([
        'title' => $titles[$i % $texts],
        'description' => $descriptions[$i % $texts],
        // Абзацы разделены пустой строкой, шаблон выводит их через white-space: pre-line
        'body' => implode("\n\n", $faker->randomElements($paragraphs, random_int(3, 6))),
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

printf("Категорий: %d, статей: %d, связей статей с категориями: %d\n", count($categoryIds), $articleCount, $links);

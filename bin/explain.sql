-- Замер самых тяжёлых запросов сайта через EXPLAIN ANALYZE: сначала как в
-- коде, потом с исправлениями. Результаты — в README, «Замер на 200 000 статей».
--
-- Запуск после сидера, например php bin/seed.php 200000:
--   docker compose exec -T db mysql -ublog -pblog -rN blog < bin/explain.sql
--
-- EXPLAIN ANALYZE выполняет запрос на самом деле. Для исправлений скрипт
-- создаёт таблицу article_category_sorted и в конце удаляет её, таблицы
-- сайта не меняются. Запросы готовятся через PREPARE с ?, как в PDO.

-- id меняются при каждом запуске сидера, поэтому параметры ищутся здесь:
-- самая большая категория, смещение её последней страницы по 9 статей и
-- самая новая статья с двумя категориями
SET @category = (SELECT category_id FROM article_category GROUP BY category_id ORDER BY COUNT(*) DESC LIMIT 1);
SET @last_offset = (SELECT (COUNT(*) - 1) DIV 9 * 9 FROM article_category WHERE category_id = @category);
SET @article = (SELECT article_id FROM article_category GROUP BY article_id HAVING COUNT(*) = 2 ORDER BY article_id DESC LIMIT 1);
SET @latest = 3, @per_page = 9, @first_offset = 0, @similar = 3;

-- На пустой базе запросы ответили бы мгновенно, поэтому сначала объём данных
SELECT CONCAT('Статей: ', COUNT(*), ', категория: ', @category, ', смещение последней страницы: ', @last_offset, ', статья: ', @article)
FROM articles;

-- Как в коде: ArticleRepository

SELECT '== Главная, findLatestPerCategory';
PREPARE q FROM 'EXPLAIN ANALYZE
WITH ranked AS (
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
WHERE rn <= ?
ORDER BY category_name, category_id, rn';
EXECUTE q USING @latest;

PREPARE q FROM 'EXPLAIN ANALYZE
SELECT a.id, a.title, a.description, a.image, a.views, a.published_at
FROM article_category ac
JOIN articles a ON a.id = ac.article_id
WHERE ac.category_id = ?
ORDER BY published_at DESC, id DESC
LIMIT ? OFFSET ?';
SELECT '== Первая страница категории, findByCategory';
EXECUTE q USING @category, @per_page, @first_offset;
SELECT '== Последняя страница категории, findByCategory';
EXECUTE q USING @category, @per_page, @last_offset;

SELECT '== Похожие статьи, findSimilar';
PREPARE q FROM 'EXPLAIN ANALYZE
SELECT a.id, a.title, a.description, a.image, a.views, a.published_at
FROM article_category own
JOIN article_category other ON other.category_id = own.category_id
                           AND other.article_id <> own.article_id
JOIN articles a ON a.id = other.article_id
WHERE own.article_id = ?
GROUP BY a.id
ORDER BY COUNT(*) DESC, a.published_at DESC, a.id DESC
LIMIT ?';
EXECUTE q USING @article, @similar;

-- Исправление: копия связей с датой публикации статьи. Вторичный индекс
-- InnoDB хранит и первичный ключ, поэтому по индексу (category_id, published_at)
-- статьи категории уже отсортированы по дате и id, и сортировать нечего.

DROP TABLE IF EXISTS article_category_sorted;
CREATE TABLE article_category_sorted (
    category_id  INT UNSIGNED NOT NULL,
    article_id   INT UNSIGNED NOT NULL,
    published_at DATETIME     NOT NULL,
    PRIMARY KEY (category_id, article_id),
    INDEX (article_id),
    INDEX (category_id, published_at)
) ENGINE = InnoDB
SELECT ac.category_id, ac.article_id, a.published_at
FROM article_category ac
JOIN articles a ON a.id = ac.article_id;
ANALYZE TABLE article_category_sorted;

-- Главная: по три статьи каждой категории прямо из индекса
SELECT '== Главная, исправление';
PREPARE q FROM 'EXPLAIN ANALYZE
SELECT c.id AS category_id, c.name AS category_name,
       a.id, a.title, a.description, a.image, a.views, a.published_at
FROM categories c
JOIN LATERAL (
    SELECT s.article_id, s.published_at
    FROM article_category_sorted s
    WHERE s.category_id = c.id
    ORDER BY s.published_at DESC, s.article_id DESC
    LIMIT ?
) latest ON TRUE
JOIN articles a ON a.id = latest.article_id
ORDER BY c.name, c.id, latest.published_at DESC, latest.article_id DESC';
EXECUTE q USING @latest;

-- Страница категории: сначала id статей страницы по индексу, без чтения
-- articles, потом статьи только для этих id
PREPARE q FROM 'EXPLAIN ANALYZE
SELECT a.id, a.title, a.description, a.image, a.views, a.published_at
FROM (
    SELECT s.article_id, s.published_at
    FROM article_category_sorted s
    WHERE s.category_id = ?
    ORDER BY s.published_at DESC, s.article_id DESC
    LIMIT ? OFFSET ?
) page_ids
JOIN articles a ON a.id = page_ids.article_id
ORDER BY page_ids.published_at DESC, page_ids.article_id DESC';
SELECT '== Первая страница категории, исправление';
EXECUTE q USING @category, @per_page, @first_offset;
SELECT '== Последняя страница категории, исправление';
EXECUTE q USING @category, @per_page, @last_offset;

-- Похожие: та же группировка, но по копии связей, без чтения articles.
-- MAX(published_at) — дата самой статьи: у всех её строк она одна
SELECT '== Похожие статьи, исправление';
PREPARE q FROM 'EXPLAIN ANALYZE
SELECT a.id, a.title, a.description, a.image, a.views, a.published_at
FROM (
    SELECT other.article_id, COUNT(*) AS shared, MAX(other.published_at) AS published_at
    FROM article_category_sorted own
    JOIN article_category_sorted other ON other.category_id = own.category_id
                                      AND other.article_id <> own.article_id
    WHERE own.article_id = ?
    GROUP BY other.article_id
    ORDER BY shared DESC, published_at DESC, other.article_id DESC
    LIMIT ?
) best
JOIN articles a ON a.id = best.article_id
ORDER BY best.shared DESC, best.published_at DESC, best.article_id DESC';
EXECUTE q USING @article, @similar;

DEALLOCATE PREPARE q;
DROP TABLE article_category_sorted;

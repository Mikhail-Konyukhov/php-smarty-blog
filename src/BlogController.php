<?php

declare(strict_types=1);

namespace App;

use Smarty\Smarty;

/**
 * Собирает данные страницы из репозиториев и выводит её шаблон.
 * Про $_GET, URL и коды ответа не знает: это работа index.php.
 */
final class BlogController
{
    /** Статей на странице категории: сетка 3×3. */
    private const PER_PAGE = 9;

    /** Статей в блоке категории на главной, по ТЗ. */
    private const LATEST_PER_CATEGORY = 3;

    /** Похожих статей на странице статьи, по ТЗ. */
    private const SIMILAR_LIMIT = 3;

    /**
     * @param CategoryRepository $categories запросы категорий
     * @param ArticleRepository  $articles   запросы статей
     * @param Smarty             $smarty     настроен в index.php: папка шаблонов, автоэкранирование
     */
    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly ArticleRepository $articles,
        private readonly Smarty $smarty,
    ) {
    }

    /**
     * Главная. Выводит home.tpl с переменной:
     *  - categories — блоки из ArticleRepository::findLatestPerCategory():
     *    категория и до трёх её последних статей; пустой список, если статей нет.
     */
    public function home(): void
    {
        $this->smarty->assign('categories', $this->articles->findLatestPerCategory(self::LATEST_PER_CATEGORY));
        $this->smarty->display('home.tpl');
    }

    /**
     * Страница категории. Выводит category.tpl с переменными:
     *  - category — id, название и описание категории;
     *  - articles — карточки текущей страницы, до 9, может быть пустым;
     *  - sort     — 'date' или 'views', для ссылок сортировки и номеров страниц;
     *  - page     — номер текущей страницы;
     *  - pages    — число страниц, от 1;
     *  - links    — номера страниц для вывода, null на месте «…» (Pagination::links()).
     *
     * @param int         $id   id категории из пути /category/{id}
     * @param ArticleSort $sort порядок из ?sort=; неизвестное значение index.php уже заменил на Date
     * @param int         $page номер страницы из ?page=, от 1
     *
     * @throws NotFoundException нет такой категории или $page больше последней страницы
     */
    public function category(int $id, ArticleSort $sort, int $page): void
    {
    }

    /**
     * Страница статьи. Сначала прибавляет просмотр, потом читает статью,
     * поэтому на странице уже новое число. Выводит article.tpl с переменными:
     *  - article    — статья целиком, с body;
     *  - categories — категории статьи, id и название, по названию;
     *  - similar    — до трёх похожих статей карточками, может быть пустым.
     *
     * @param int $id id статьи из пути /article/{id}
     *
     * @throws NotFoundException нет такой статьи
     */
    public function article(int $id): void
    {
    }
}

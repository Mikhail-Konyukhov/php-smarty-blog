<?php

declare(strict_types=1);

namespace App;

/**
 * Порядок статей на странице категории. Значение — то, что приходит в ?sort=.
 */
enum ArticleSort: string
{
    /** Сначала новые. */
    case Date = 'date';

    /** Сначала старые. */
    case DateAsc = 'date-asc';

    /** Сначала самые просматриваемые. */
    case Views = 'views';

    /** Сначала наименее просматриваемые. */
    case ViewsAsc = 'views-asc';

    /**
     * Выражение для ORDER BY.
     *
     * Строка вставляется в SQL как есть, а не параметром, поэтому взяться она
     * может только отсюда. Второй ключ, id, держит порядок стабильным, когда у
     * статей одинаковые дата или просмотры: иначе одна статья могла бы
     * оказаться на двух страницах сразу.
     *
     * @return string 'published_at DESC, id DESC' для Date, 'views DESC, id DESC' для Views,
     *    у DateAsc и ViewsAsc то же с ASC
     */
    public function orderBy(): string
    {
        return match ($this) {
            self::Date => 'published_at DESC, id DESC',
            self::DateAsc => 'published_at ASC, id ASC',
            self::Views => 'views DESC, id DESC',
            self::ViewsAsc => 'views ASC, id ASC',
        };
    }
}

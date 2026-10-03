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

    /** Сначала самые просматриваемые. */
    case Views = 'views';

    /**
     * Выражение для ORDER BY.
     *
     * Строка вставляется в SQL как есть, а не параметром, поэтому взяться она
     * может только отсюда. Второй ключ, id, держит порядок стабильным, когда у
     * статей одинаковые дата или просмотры: иначе одна статья могла бы
     * оказаться на двух страницах сразу.
     *
     * @return string 'published_at DESC, id DESC' для Date, 'views DESC, id DESC' для Views
     */
    public function orderBy(): string
    {
        return match ($this) {
            self::Date => 'published_at DESC, id DESC',
            self::Views => 'views DESC, id DESC',
        };
    }
}

<?php

declare(strict_types=1);

namespace App;

/**
 * Страницы нет: неизвестный путь, нет такой категории или статьи, номер
 * страницы больше последней. index.php отвечает на неё кодом 404.
 */
final class NotFoundException extends \RuntimeException
{
}

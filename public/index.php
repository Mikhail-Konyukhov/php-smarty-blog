<?php

declare(strict_types=1);

use App\ArticleRepository;
use App\ArticleSort;
use App\BlogController;
use App\CategoryRepository;
use App\NotFoundException;
use Smarty\Smarty;

require __DIR__ . '/../vendor/autoload.php';

// Предупреждение PHP — исключение, иначе страница с ошибкой уйдёт с кодом 200.
// Подавленные через @ пропускаем: так библиотеки глушат ожидаемые ошибки.
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Smarty создаётся до PDO: страница 500 нужна и тогда, когда MySQL недоступен.
$smarty = new Smarty();
$smarty->setTemplateDir(__DIR__ . '/../templates');
// Не в папке проекта: на Linux-хосте Apache не сможет писать в смонтированную папку
$smarty->setCompileDir(sys_get_temp_dir() . '/smarty');
$smarty->setEscapeHtml(true);
// Неизвестная переменная в шаблоне — ошибка, а не пустая строка
$smarty->error_unassigned = true;

try {
    $pdo = require __DIR__ . '/../src/db.php';
    $controller = new BlogController(new CategoryRepository($pdo), new ArticleRepository($pdo), $smarty);

    $path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    if ($path === '/') {
        $controller->home();
    } elseif (preg_match('#^/category/(\d+)$#', $path, $m)) {
        // filter_input, а не $_GET: ?sort[]=x придёт массивом, filter_input вернёт false
        $sort = ArticleSort::tryFrom((string) filter_input(INPUT_GET, 'sort')) ?? ArticleSort::Date;
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
        $controller->category((int) $m[1], $sort, $page);
    } elseif (preg_match('#^/article/(\d+)$#', $path, $m)) {
        $controller->article((int) $m[1]);
    } else {
        throw new NotFoundException();
    }
} catch (NotFoundException) {
    http_response_code(404);
    $smarty->assign('code', 404);
    $smarty->display('error.tpl');
} catch (Throwable $e) {
    error_log((string) $e);
    http_response_code(500);
    $smarty->assign('code', 500);
    $smarty->display('error.tpl');
}

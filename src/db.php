<?php

declare(strict_types=1);

// Подключение к MySQL из переменных окружения, заданных в docker-compose.yml.
// Подключают public/index.php, bin/seed.php и тесты.
return new PDO(
    sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', getenv('DB_HOST'), getenv('DB_NAME')),
    getenv('DB_USER'),
    getenv('DB_PASSWORD'),
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        // LIMIT и OFFSET параметрами и int из базы как int
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ],
);

#!/bin/sh
# Сборка и запуск блога одной командой: ./start.sh
# Порт — APP_PORT, по умолчанию свободный из 8080-8099. Повторный запуск пересоздаёт данные.
set -e
cd "$(dirname "$0")"

docker compose up -d --build
docker compose exec -T app php bin/seed.php
# Контейнер запущен — ещё не значит, что сайт отвечает. curl внутри контейнера:
# на хосте его может не быть
docker compose exec -T app curl -fsS -o /dev/null --retry 5 --retry-connrefused --retry-delay 1 http://localhost/

# Порт, который выбрал Docker: из «0.0.0.0:8082» остаётся «8082»
addr=$(docker compose port app 80)
url="http://localhost:${addr##*:}"
echo "Готово: $url"
xdg-open "$url" >/dev/null 2>&1 || true

@echo off
rem Build and run the blog with a double click. Port: APP_PORT, default a free one from 8080-8099.
chcp 65001 >nul
cd /d "%~dp0"

rem Повторный запуск пересоздаёт данные
docker compose up -d --build || goto fail
docker compose exec -T app php bin/seed.php || goto fail
rem Контейнер запущен — ещё не значит, что сайт отвечает. curl внутри контейнера:
rem в старых Windows 10 своего нет
docker compose exec -T app curl -fsS -o /dev/null --retry 5 --retry-connrefused --retry-delay 1 http://localhost/ || goto fail

rem Порт, который выбрал Docker: из «0.0.0.0:8082» или «[::]:8082» остаётся «8082»
for /f "delims=" %%a in ('docker compose port app 80') do set "PORT=%%a"
set "PORT=%PORT:*]:=%"
set "PORT=%PORT:*:=%"
echo Готово: http://localhost:%PORT%
start "" http://localhost:%PORT%
pause
exit /b 0

:fail
echo Запуск не удался, причина выше.
pause
exit /b 1

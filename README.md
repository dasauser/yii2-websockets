# Installation
```shell
composer install
docker compose up -d --build
php yii migrate
# Запуск сервера$ 21080 - дефолтный порт
php yii server/start 21080
```
# Testing
## Браузер
* Открыть http://localhost:20080 (по дефолту)
* Открыть консоль браузера.
* В консоли должны быть следующие сообщения: "Connection established!", "connected", "authenticated"
* При вызове `conn.close()`: "closing connection..."
** При обновлении страницы подключение закрывается и открывается заново.
* Отправлять сообщения (`conn.send()`)
## Postman
* Открыть postman
* Добавить новый websocket запрос (New -> Websocket)
* Отправить аутентифицирующее сообщение, с токеном, например `{"type":"auth", "token": "abcdefg"}`
* Отправлять сообщения
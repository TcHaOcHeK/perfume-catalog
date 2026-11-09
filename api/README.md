# REST API каталога парфюмерии

Базовый адрес: `/perfume-catalog/api` (в dev через прокси Vite: `/api`). Формат — JSON (UTF-8).
Ответ при успехе содержит `"success": true`, при ошибке — `{"success": false, "error": "текст"}`
(при ошибках валидации добавляется `"errors": {"поле": "сообщение"}`).

## Аутентификация

`POST /login` с телом `{"login": "...", "password": "..."}` возвращает `{token, user}`.
Токен (JWT, HS256, 8 часов) передаётся в заголовке `Authorization: Bearer <token>`.

| Уровень доступа | Кому открыт |
|---|---|
| public | всем: `/ping`, `/health`, `/login`, `/logout` |
| any | любой валидный токен: `/me` |
| staff | роли `admin`, `editor`: products, brands, categories, families, collections |
| admin | только `admin`: users |

## Эндпоинты

| Метод и путь | Описание |
|---|---|
| `GET /ping`, `GET /health` | проверка API и БД |
| `POST /login`, `POST /logout`, `GET /me` | вход, выход, текущий пользователь |
| `GET /products`, `GET /products/{id}`, `POST /products`, `PUT /products/{id}`, `DELETE /products/{id}` | товары |
| то же для `/brands`, `/categories` (таблица `perfume_types`), `/families` (`fragrance_families`), `/collections` | справочники |
| то же для `/users` | пользователи (только admin) |

Создание возвращает `201` и объект в поле `item`; изменение — `200` и `item`; удаление — `200`.

### Параметры списков
`page`, `perPage` (до 100), `search`, `sort` (из белого списка), `order=asc|desc`.
Фильтры товаров: `brand`, `type`, `family`, `collection`, `gender`, `priceFrom`, `priceTo`.
Фильтры пользователей: `role`. Фильтр коллекций: `brand`. Фильтр брендов: `country`.
Ответ: `{items, total, page, perPage, pages}`.

### Поля товара (POST/PUT)
| Поле | Тип | Обязательно | Примечание |
|---|---|---|---|
| `title` | строка до 255 | да | |
| `brand_id`, `type_id` | id | да | ссылки на `brands` и `perfume_types` |
| `family_id`, `collection_id` | id | нет | `NULL`, если не заданы |
| `gender` | `men`, `women`, `unisex` | нет | по умолчанию `unisex` |
| `price` | число, 2 знака | да | |
| `description` | текст | нет | |
| `release_date` | `ГГГГ-ММ-ДД` | нет | |
| `volume_ml` | целое > 0 | нет | объём в мл |
| `concentration` | строка до 50 | нет | например, «EDP» |
| `is_featured`, `is_new` | `true/false` или `0/1` | нет | |

В ответе `sku` вычисляется сервером как `#` + id из 8 цифр (колонки `sku` в таблице нет),
также возвращаются `brand_name`, `type_name`, `family_name`, `collection_name`, `image` (главное фото).

### Поля пользователя (POST/PUT)
`login` (до 50, уникален), `email` (уникален, обязателен), `password` (8–72 байта; в БД хранится только хэш),
`role` (`admin | editor | user`). При `PUT` пароль, если его не передать или передать пустым, не меняется.
Ответы содержат `id`, `login`, `email`, `role`, `created_at`. Хэш пароля не возвращается.

### Поля бренда, категории, семейства, коллекции
- бренд: `name` (уникален), `description`, `country`, `website`, `logo_path`;
- категория (таблица `perfume_types`): `name` (уникален, до 50), `description`;
- семейство (`fragrance_families`): `name` (уникально), `description`;
- коллекция: `name`, `description`, `brand_id`, `release_year` (1901–2155).

## Коды ответов
`200/201` успех · `400` неверный запрос или id · `401` нет/недействителен токен · `403` недостаточно прав ·
`404` не найдено · `405` метод не поддерживается · `409` конфликт (дубликат, есть связанные записи) ·
`422` ошибка валидации · `500` внутренняя ошибка.

## Важно про удаление
По дампу БД: `products.brand_id` и `products.type_id` — `ON DELETE RESTRICT`. Поэтому API **не удаляет** бренд
или категорию, у которых есть товары (`409`, поле `dependents`). `products.family_id` и `products.collection_id` —
`ON DELETE SET NULL`: при удалении семейства или коллекции товары остаются, а ссылка обнуляется.

## Тестирование
Коллекция Postman: `postman/perfume-catalog-api.postman_collection.json` (запускать целиком через Collection Runner).
Перед запуском создайте администратора: `php sql/create_admin.php admin <пароль>` и задайте переменные коллекции `username` / `password`.

## Схема БД
`sql/schema.sql` — дамп фактической структуры `perfume_db`. Он содержит `DROP TABLE`, поэтому импортировать его
следует только в пустую базу или с осознанием, что существующие таблицы будут удалены.

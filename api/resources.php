<?php
declare(strict_types=1);

/**
 * Описание REST-ресурсов (CRUD) под фактическую структуру БД perfume_db (sql/schema.sql = дамп).
 * Подключается из api/index.php; константа USER_ROLES и функция db() определены там.
 *
 * Ключи ресурса:
 *   table, alias      — таблица и её алиас в запросах
 *   access            — 'staff' (admin/editor, по умолчанию) или 'admin'
 *   select, joins     — SQL для списка и карточки; это код, а не пользовательский ввод
 *   fields            — поля для записи и правила проверки (type, required, nullable, max, min ...)
 *   filters           — query-параметры списка: параметр => [выражение, оператор]
 *   search            — выражения для ?search= (LIKE, через OR)
 *   sort              — допустимые значения ?sort= => выражение
 *   unique            — поле => сообщение: проверка уникальности приложением (дополняет UNIQUE в БД)
 *   dependents        — [таблица, колонка, подпись]: записи, которые блокируют удаление (RESTRICT в БД)
 *   beforeSave        — fn(array $data, bool $isCreate): array — подготовка данных перед записью
 *   canUpdate/canDelete — fn(...): ?string — текст причины отказа или null
 */
return (static function (): array {
    $name = ['type' => 'string', 'max' => 100, 'required' => true];

    return [
        // ---------------------------------------------------------------- Товары
        'products' => [
            'table'  => 'products',
            'alias'  => 'p',
            // sku в таблице нет: его формируем так же, как публичный сайт — «#» + 8 цифр id
            'select' => "p.*, CONCAT('#', LPAD(p.`id`, 8, '0')) AS `sku`, "
                . 'b.`name` AS `brand_name`, pt.`name` AS `type_name`, '
                . 'ff.`name` AS `family_name`, c.`name` AS `collection_name`, '
                . '(SELECT pi.`image_path` FROM `product_images` pi '
                . 'WHERE pi.`product_id` = p.`id` AND pi.`is_main` = 1 '
                . 'ORDER BY pi.`sort_order`, pi.`id` LIMIT 1) AS `image`',
            'joins'  => 'LEFT JOIN `brands` b ON b.`id` = p.`brand_id` '
                . 'LEFT JOIN `perfume_types` pt ON pt.`id` = p.`type_id` '
                . 'LEFT JOIN `fragrance_families` ff ON ff.`id` = p.`family_id` '
                . 'LEFT JOIN `collections` c ON c.`id` = p.`collection_id`',
            'fields' => [
                'title'         => ['type' => 'string', 'max' => 255, 'required' => true],
                'brand_id'      => ['type' => 'int', 'required' => true],
                'type_id'       => ['type' => 'int', 'required' => true],
                'family_id'     => ['type' => 'int', 'nullable' => true],
                'collection_id' => ['type' => 'int', 'nullable' => true],
                // значения enum в БД: men / women / unisex
                'gender'        => ['type' => 'enum', 'values' => ['men', 'women', 'unisex']],
                'price'         => ['type' => 'decimal', 'required' => true],
                'description'   => ['type' => 'string', 'max' => 20000, 'nullable' => true],
                'release_date'  => ['type' => 'date', 'nullable' => true],
                'volume_ml'     => ['type' => 'int', 'min' => 1, 'max' => 100000, 'nullable' => true],
                'concentration' => ['type' => 'string', 'max' => 50, 'nullable' => true],
                'is_featured'   => ['type' => 'bool'],
                'is_new'        => ['type' => 'bool'],
            ],
            'filters' => [
                'brand'      => ['p.`brand_id`', '='],
                'type'       => ['p.`type_id`', '='],
                'family'     => ['p.`family_id`', '='],
                'collection' => ['p.`collection_id`', '='],
                'gender'     => ['p.`gender`', '='],
                'priceFrom'  => ['p.`price`', '>='],
                'priceTo'    => ['p.`price`', '<='],
            ],
            'search' => ['p.`title`'],
            'sort'   => [
                'id'           => 'p.`id`',
                'title'        => 'p.`title`',
                'price'        => 'p.`price`',
                'views'        => 'p.`views`',
                'release_date' => 'p.`release_date`',
                'created_at'   => 'p.`created_at`',
            ],
        ],

        // ---------------------------------------------------------------- Бренды
        'brands' => [
            'table'  => 'brands',
            'alias'  => 'b',
            'fields' => [
                'name'        => $name,
                'description' => ['type' => 'string', 'max' => 20000, 'nullable' => true],
                'country'     => ['type' => 'string', 'max' => 50, 'nullable' => true],
                'website'     => ['type' => 'string', 'max' => 255, 'nullable' => true],
                'logo_path'   => ['type' => 'string', 'max' => 255, 'nullable' => true],
            ],
            'filters'    => ['country' => ['b.`country`', '=']],
            'search'     => ['b.`name`'],
            'sort'       => ['id' => 'b.`id`', 'name' => 'b.`name`', 'created_at' => 'b.`created_at`'],
            'unique'     => ['name' => 'Бренд с таким названием уже существует'],
            // products.brand_id: ON DELETE RESTRICT
            'dependents' => [['products', 'brand_id', 'товаров']],
        ],

        // ---------------------------------------------------------------- Категории (perfume_types)
        'categories' => [
            'table'  => 'perfume_types',
            'alias'  => 'pt',
            'fields' => [
                'name'        => ['type' => 'string', 'max' => 50, 'required' => true],
                'description' => ['type' => 'string', 'max' => 20000, 'nullable' => true],
            ],
            'search'     => ['pt.`name`'],
            'sort'       => ['id' => 'pt.`id`', 'name' => 'pt.`name`'],
            'unique'     => ['name' => 'Категория с таким названием уже существует'],
            // products.type_id: ON DELETE RESTRICT
            'dependents' => [['products', 'type_id', 'товаров']],
        ],

        // ---------------------------------------------------------------- Семейства ароматов
        'families' => [
            'table'  => 'fragrance_families',
            'alias'  => 'ff',
            'fields' => [
                'name'        => ['type' => 'string', 'max' => 100, 'required' => true],
                'description' => ['type' => 'string', 'max' => 20000, 'nullable' => true],
            ],
            'search' => ['ff.`name`'],
            'sort'   => ['id' => 'ff.`id`', 'name' => 'ff.`name`'],
            'unique' => ['name' => 'Семейство с таким названием уже существует'],
            // products.family_id: ON DELETE SET NULL — БД сама обнулит family_id, блокировать не нужно
        ],

        // ---------------------------------------------------------------- Коллекции
        'collections' => [
            'table'  => 'collections',
            'alias'  => 'c',
            'select' => 'c.*, b.`name` AS `brand_name`',
            'joins'  => 'LEFT JOIN `brands` b ON b.`id` = c.`brand_id`',
            'fields' => [
                'name'         => ['type' => 'string', 'max' => 100, 'required' => true],
                'description'  => ['type' => 'string', 'max' => 20000, 'nullable' => true],
                'brand_id'     => ['type' => 'int', 'nullable' => true],
                'release_year' => ['type' => 'int', 'min' => 1901, 'max' => 2155, 'nullable' => true],
            ],
            'filters' => ['brand' => ['c.`brand_id`', '=']],
            'search'  => ['c.`name`'],
            'sort'    => ['id' => 'c.`id`', 'name' => 'c.`name`', 'release_year' => 'c.`release_year`'],
            // products.collection_id: ON DELETE SET NULL, товары остаются без коллекции
        ],

        // ---------------------------------------------------------------- Пользователи (только admin)
        'users' => [
            'table'  => 'users',
            'alias'  => 'u',
            'access' => 'admin',
            // password_hash наружу не отдаём; login отдаём как есть (в БД колонка называется login)
            'select' => 'u.`id`, u.`login`, u.`email`, u.`role`, u.`created_at`',
            'fields' => [
                'login'    => ['type' => 'string', 'max' => 50, 'required' => true],
                'email'    => ['type' => 'email', 'max' => 100, 'required' => true],
                // «password» — виртуальное поле: в БД попадает только хэш (см. beforeSave).
                // Лимит 72 байта — ограничение bcrypt. Пустой пароль при PUT означает «не менять».
                'password' => [
                    'type' => 'string', 'minLen' => 8, 'max' => 72, 'maxBytes' => 72,
                    'trim' => false, 'required' => true, 'keepIfEmpty' => true,
                ],
                'role'     => ['type' => 'enum', 'values' => USER_ROLES, 'required' => true],
            ],
            'filters' => ['role' => ['u.`role`', '=']],
            'search'  => ['u.`login`', 'u.`email`'],
            'sort'    => [
                'id'         => 'u.`id`',
                'login'      => 'u.`login`',
                'role'       => 'u.`role`',
                'created_at' => 'u.`created_at`',
            ],
            'unique' => [
                'login' => 'Пользователь с таким логином уже существует',
                'email' => 'Пользователь с таким email уже существует',
            ],
            'beforeSave' => function (array $data, bool $isCreate): array {
                if (isset($data['password'])) {
                    $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
                    unset($data['password']);
                }
                return $data;
            },
            // Нельзя лишить себя прав администратора и нельзя удалить собственную учётную запись
            'canUpdate' => function (int $id, array $data, array $auth): ?string {
                if ($id === (int) $auth['sub'] && isset($data['role']) && $data['role'] !== 'admin') {
                    return 'Нельзя снять с себя роль администратора';
                }
                return null;
            },
            'canDelete' => function (int $id, array $auth): ?string {
                return $id === (int) $auth['sub'] ? 'Нельзя удалить собственную учётную запись' : null;
            },
        ],
    ];
})();

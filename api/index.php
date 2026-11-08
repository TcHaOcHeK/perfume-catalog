<?php
declare(strict_types=1);

/**
 * Точка входа REST API (/perfume-catalog/api/...).
 *
 * Публичные эндпоинты: /ping, /health, /login, /logout.
 * Остальные требуют JWT в заголовке "Authorization: Bearer <token>"
 * и роль из STAFF_ROLES (кроме /me — ему достаточно любого валидного токена).
 * Эндпоинты ПР8 (products, brands, categories, users) добавляются в $routes.
 */

require __DIR__ . '/jwt.php';

header('Content-Type: application/json; charset=utf-8');

const STAFF_ROLES = ['admin', 'editor'];
// Валидный bcrypt-хэш-«пустышка»: проверяем его, если логин не найден,
// чтобы время ответа не выдавало, существует ли такой пользователь
const DUMMY_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

// CORS для dev-сервера Vite (при работе через прокси не обязателен, но пригодится при деплое)
$allowedOrigins = ['http://localhost:5173', 'http://127.0.0.1:5173'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ---------- Вспомогательные функции ----------

function respond(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        require __DIR__ . '/../config/db.php'; // создаёт $pdo в области видимости функции
    }
    return $pdo;
}

function jwtConfig(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require __DIR__ . '/../config/jwt.php';
        if (strlen((string) $cfg['secret']) < 32 || str_starts_with((string) $cfg['secret'], 'change-me')) {
            respond(['success' => false, 'error' => 'JWT secret is not configured'], 500);
        }
    }
    return $cfg;
}

/** Тело запроса как массив (JSON). Пустое тело -> []. */
function jsonBody(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        respond(['success' => false, 'error' => 'Invalid JSON body'], 400);
    }
    return $data;
}

/** Достаёт токен из "Authorization: Bearer ...". */
function bearerToken(): ?string
{
    // После rewrite в .htaccess переменная приходит как REDIRECT_HTTP_AUTHORIZATION
    $header = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? null;

    if ($header === null && function_exists('getallheaders')) {
        $header = array_change_key_case(getallheaders())['authorization'] ?? null;
    }

    if (is_string($header) && preg_match('/^Bearer\s+(\S+)$/i', $header, $m)) {
        return $m[1];
    }
    return null;
}

/** Middleware: проверяет JWT, возвращает payload или отвечает 401. */
function authenticate(): array
{
    $token = bearerToken();
    if ($token === null) {
        respond(['success' => false, 'error' => 'Требуется авторизация'], 401);
    }

    $cfg = jwtConfig();
    try {
        $payload = jwt_decode($token, $cfg['secret']);
    } catch (RuntimeException) {
        respond(['success' => false, 'error' => 'Сессия истекла или токен недействителен'], 401);
    }

    if (($payload['iss'] ?? '') !== $cfg['issuer']) {
        respond(['success' => false, 'error' => 'Токен недействителен'], 401);
    }
    return $payload;
}

/** Проверка совпадения пути с паттерном и извлечение параметров.
 *  Паттерн: '/products/:id' -> совпадает с '/products/123', возвращает ['id' => '123']
 */
function matchRoute(string $pattern, string $path): ?array
{
    // Экранируем спецсимволы, кроме :param
    $regex = preg_replace('/:([a-zA-Z_][a-zA-Z0-9_]*)/', '(?P<$1>[^/]+)', preg_quote($pattern, '#'));
    $regex = '#^' . $regex . '$#';
    if (preg_match($regex, $path, $matches)) {
        // Возвращаем только именованные группы
        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }
        return $params;
    }
    return null;
}

// ---------- Разбор запроса ----------

// "/perfume-catalog/api/ping?x=1" -> "/ping"
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = preg_replace('#^.*?/api(?=/|$)#', '', $path, 1);
$path = '/' . trim($path, '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$publicRoutes  = ['GET /ping', 'GET /health', 'POST /login', 'POST /logout'];
$anyRoleRoutes = ['GET /me'];

// ---------- CRUD хелперы ----------

/** Список с пагинацией и фильтрами */
function listHandler(string $table, array $allowedFilters = [], array $joins = [], array $select = ['*']): callable
{
    return function () use ($table, $allowedFilters, $joins, $select) {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['perPage'] ?? 20)));
        $offset = ($page - 1) * $perPage;

        $where = [];
        $params = [];
        foreach ($allowedFilters as $param => $column) {
            $value = $_GET[$param] ?? null;
            if ($value !== null && $value !== '') {
                $where[] = "`$column` = ?";
                $params[] = $value;
            }
        }
        // Поиск по названию (title/name)
        $search = trim((string) ($_GET['search'] ?? ''));
        if ($search !== '') {
            $where[] = '(`name` LIKE ? OR `title` LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $joinSql = $joins ? ' ' . implode(' ', $joins) : '';

        $selectSql = implode(', ', array_map(fn($c) => "`$c`", $select));

        $sql = "SELECT $selectSql FROM `$table`$joinSql $whereSql ORDER BY `id` DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        // Total count
        $countSql = "SELECT COUNT(*) FROM `$table`$joinSql $whereSql";
        $countStmt = db()->prepare($countSql);
        $countStmt->execute(array_slice($params, 0, -2));
        $total = (int) $countStmt->fetchColumn();

        return [
            'success' => true,
            'items'   => $items,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
        ];
    };
}

/** Получить один по ID */
function getHandler(string $table, array $select = ['*']): callable
{
    return function (array $auth, array $params) use ($table, $select) {
        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            respond(['success' => false, 'error' => 'Invalid ID'], 400);
        }
        $selectSql = implode(', ', array_map(fn($c) => "`$c`", $select));
        $stmt = db()->prepare("SELECT $selectSql FROM `$table` WHERE `id` = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) {
            respond(['success' => false, 'error' => 'Not found'], 404);
        }
        return ['success' => true, 'item' => $item];
    };
}

/** Создать */
function createHandler(string $table, array $fields, array $required = []): callable
{
    return function () use ($table, $fields, $required) {
        $body = jsonBody();
        $data = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $body)) {
                $data[$field] = $body[$field];
            }
        }
        // Проверка обязательных полей
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                respond(['success' => false, 'error' => "Field '$field' is required"], 422);
            }
        }
        if (empty($data)) {
            respond(['success' => false, 'error' => 'No data provided'], 422);
        }

        $columns = implode(', ', array_map(fn($f) => "`$f`", array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO `$table` ($columns) VALUES ($placeholders)";
        $stmt = db()->prepare($sql);
        $stmt->execute(array_values($data));
        $id = (int) db()->lastInsertId();

        // Вернуть созданный объект
        $stmt = db()->prepare("SELECT * FROM `$table` WHERE `id` = ?");
        $stmt->execute([$id]);
        return ['success' => true, 'item' => $stmt->fetch()];
    };
}

/** Обновить */
function updateHandler(string $table, array $fields): callable
{
    return function (array $auth, array $params) use ($table, $fields) {
        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            respond(['success' => false, 'error' => 'Invalid ID'], 400);
        }
        $body = jsonBody();
        $data = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $body)) {
                $data[$field] = $body[$field];
            }
        }
        if (empty($data)) {
            respond(['success' => false, 'error' => 'No data provided'], 422);
        }

        $set = implode(', ', array_map(fn($f) => "`$f` = ?", array_keys($data)));
        $sql = "UPDATE `$table` SET $set WHERE `id` = ?";
        $values = array_values($data);
        $values[] = $id;
        $stmt = db()->prepare($sql);
        $stmt->execute($values);

        if ($stmt->rowCount() === 0) {
            respond(['success' => false, 'error' => 'Not found'], 404);
        }

        $stmt = db()->prepare("SELECT * FROM `$table` WHERE `id` = ?");
        $stmt->execute([$id]);
        return ['success' => true, 'item' => $stmt->fetch()];
    };
}

/** Удалить */
function deleteHandler(string $table): callable
{
    return function (array $auth, array $params) use ($table) {
        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            respond(['success' => false, 'error' => 'Invalid ID'], 400);
        }
        $stmt = db()->prepare("DELETE FROM `$table` WHERE `id` = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) {
            respond(['success' => false, 'error' => 'Not found'], 404);
        }
        return ['success' => true];
    };
}

// Каждый обработчик получает payload токена ($auth; для публичных — null)
$routes = [
    'GET /ping' => fn() => ['success' => true, 'message' => 'pong', 'time' => date('c')],

    'GET /health' => function () {
        try {
            db()->query('SELECT 1');
            return ['success' => true, 'db' => 'ok'];
        } catch (Throwable $e) {
            respond(['success' => false, 'db' => 'error'], 500);
        }
    },

    // Контракт для фронтенда: { login, password } -> { success, token, user: { id, username, role } }
    'POST /login' => function () {
        $body = jsonBody();
        $login = trim((string) ($body['login'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        if ($login === '' || $password === '') {
            respond(['success' => false, 'error' => 'Введите логин и пароль'], 422);
        }

        $stmt = db()->prepare('SELECT id, login as username, password_hash, role FROM users WHERE login = ?');
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        $passwordOk = password_verify($password, $user['password_hash'] ?? DUMMY_HASH);
        if (!$user || !$passwordOk) {
            respond(['success' => false, 'error' => 'Неверный логин или пароль'], 401);
        }

        $cfg = jwtConfig();
        $now = time();
        $token = jwt_encode([
            'iss'      => $cfg['issuer'],
            'sub'      => (int) $user['id'],
            'username' => $user['username'],
            'role'     => $user['role'],
            'iat'      => $now,
            'exp'      => $now + (int) $cfg['ttl'],
        ], $cfg['secret']);

        return [
            'success' => true,
            'token'   => $token,
            'user'    => [
                'id'       => (int) $user['id'],
                'username' => $user['username'],
                'role'     => $user['role'],
            ],
        ];
    },

    // JWT хранится на клиенте, поэтому «выход» = удаление токена в браузере
    'POST /logout' => fn() => ['success' => true],

    // Текущий пользователь (актуальные данные из БД) — фронтенд зовёт после перезагрузки страницы
    'GET /me' => function (array $auth) {
        $stmt = db()->prepare('SELECT id, login as username, role FROM users WHERE id = ?');
        $stmt->execute([(int) $auth['sub']]);
        $user = $stmt->fetch();
        if (!$user) {
            respond(['success' => false, 'error' => 'Пользователь не найден'], 401);
        }
        return ['success' => true, 'user' => $user];
    },

    // ПР8: CRUD для products
    // GET /products — список с пагинацией и фильтрами
    'GET /products' => listHandler('products', [
        'brand' => 'brand_id',
        'type'  => 'type_id',
        'family' => 'family_id',
        'gender' => 'gender',
        'priceFrom' => 'price',
        'priceTo' => 'price',
    ], [
        'LEFT JOIN `brands` b ON p.`brand_id` = b.`id`',
        'LEFT JOIN `perfume_types` pt ON p.`type_id` = pt.`id`',
        'LEFT JOIN `fragrance_families` ff ON p.`family_id` = ff.`id`',
        'LEFT JOIN `collections` c ON p.`collection_id` = c.`id`',
    ], [
        'p.*', 'b.`name` as `brand_name`', 'pt.`name` as `type_name`',
        'ff.`name` as `family_name`', 'c.`name` as `collection_name`'
    ]),

    // GET /products/:id
    'GET /products/:id' => getHandler('products', [
        'p.*', 'b.`name` as `brand_name`', 'pt.`name` as `type_name`',
        'ff.`name` as `family_name`', 'c.`name` as `collection_name`'
    ]),

    // POST /products
    'POST /products' => createHandler('products', [
        'title', 'description', 'price', 'brand_id', 'type_id',
        'family_id', 'collection_id', 'gender', 'release_date',
        'volume_ml', 'concentration', 'is_featured', 'is_new'
    ], ['title', 'price', 'brand_id', 'type_id']),

    // PUT /products/:id
    'PUT /products/:id' => updateHandler('products', [
        'title', 'description', 'price', 'brand_id', 'type_id',
        'family_id', 'collection_id', 'gender', 'release_date',
        'volume_ml', 'concentration', 'is_featured', 'is_new'
    ]),

    // DELETE /products/:id
    'DELETE /products/:id' => deleteHandler('products'),

    // ПР8: CRUD для brands
    'GET /brands' => listHandler('brands', ['country']),
    'GET /brands/:id' => getHandler('brands'),
    'POST /brands' => createHandler('brands', ['name', 'description', 'country', 'website', 'logo_path'], ['name']),
    'PUT /brands/:id' => updateHandler('brands', ['name', 'description', 'country', 'website', 'logo_path']),
    'DELETE /brands/:id' => deleteHandler('brands'),

    // ПР8: CRUD для categories (perfume_types)
    'GET /categories' => listHandler('perfume_types'),
    'GET /categories/:id' => getHandler('perfume_types'),
    'POST /categories' => createHandler('perfume_types', ['name', 'description'], ['name']),
    'PUT /categories/:id' => updateHandler('perfume_types', ['name', 'description']),
    'DELETE /categories/:id' => deleteHandler('perfume_types'),

    // ПР8: CRUD для users
    'GET /users' => listHandler('users', ['role']),
    'GET /users/:id' => getHandler('users', ['id', 'login as username', 'email', 'role', 'created_at']),
    'POST /users' => createHandler('users', ['login', 'email', 'password_hash', 'role'], ['login', 'email', 'password_hash']),
    'PUT /users/:id' => updateHandler('users', ['login', 'email', 'password_hash', 'role']),
    'DELETE /users/:id' => deleteHandler('users'),

    // ПР8: 'GET /products' => ..., 'POST /products' => ... и т.д.
];

// Поиск обработчика: сначала точное совпадение, потом паттерны
$key = "$method $path";
$handler = $routes[$key] ?? null;
$routeParams = [];

if ($handler === null) {
    // Проверяем паттерны с параметрами (например, /products/:id)
    foreach ($routes as $pattern => $h) {
        if (str_contains($pattern, ':')) {
            $params = matchRoute($pattern, $path);
            if ($params !== null && str_starts_with($pattern, "$method ")) {
                $handler = $h;
                $routeParams = $params;
                break;
            }
        }
    }
}

if ($handler === null) {
    respond(['success' => false, 'error' => 'Not found'], 404);
}

$auth = null;
// Для проверки публичных маршрутов используем паттерн без параметров
$routeKey = $key;
if ($routeParams) {
    // Находим паттерн, который совпал
    foreach ($routes as $pattern => $h) {
        if ($h === $handler && str_contains($pattern, ':')) {
            $routeKey = $pattern;
            break;
        }
    }
}
if (!in_array($routeKey, $publicRoutes, true)) {
    $auth = authenticate();
    if (!in_array($routeKey, $anyRoleRoutes, true) && !in_array($auth['role'] ?? '', STAFF_ROLES, true)) {
        respond(['success' => false, 'error' => 'Доступ запрещён'], 403);
    }
}

try {
    respond($handler($auth, $routeParams));
} catch (Throwable $e) {
    error_log($e->getMessage());
    respond(['success' => false, 'error' => 'Server error'], 500);
}

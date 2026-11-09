<?php
declare(strict_types=1);

/**
 * Точка входа REST API (/perfume-catalog/api/...).
 *
 * Уровни доступа маршрутов:
 *   public — без токена (/ping, /health, /login, /logout)
 *   any    — любой валидный JWT (/me)
 *   staff  — JWT с ролью admin или editor (CRUD каталога)
 *   admin  — только admin (управление пользователями)
 *
 * CRUD-ресурсы описаны декларативно в api/resources.php,
 * общие обработчики (список / один / создание / изменение / удаление) — ниже.
 */

require __DIR__ . '/jwt.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

// Роли, которым открыта админ-панель
const STAFF_ROLES = ['admin', 'editor'];
// Допустимые значения users.role — ровно как enum в БД: ('user','editor','admin')
const USER_ROLES = ['admin', 'editor', 'user'];
// Валидный bcrypt-хэш строки "password": проверяем его, если логин не найден,
// чтобы время ответа не выдавало, существует ли такой пользователь
const DUMMY_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

// CORS только для dev-сервера Vite (в проде админка и API на одном origin).
// Заголовки выставляются здесь; в api/.htaccess их быть не должно (иначе «*» перекроет список).
$allowedOrigins = ['http://localhost:5173', 'http://127.0.0.1:5173'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 86400');
}
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ====================================================================
// Ответы, БД, JWT
// ====================================================================

function respond(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function fail(string $message, int $status = 400, array $extra = []): never
{
    respond(['success' => false, 'error' => $message] + $extra, $status);
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
        if (strlen((string) $cfg['secret']) < 32) {
            fail('JWT secret is not configured', 500);
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
        fail('Invalid JSON body', 400);
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
        fail('Требуется авторизация', 401);
    }

    $cfg = jwtConfig();
    try {
        $payload = jwt_decode($token, $cfg['secret']);
    } catch (RuntimeException) {
        fail('Сессия истекла или токен недействителен', 401);
    }

    if (($payload['iss'] ?? '') !== $cfg['issuer']) {
        fail('Токен недействителен', 401);
    }
    return $payload;
}

/** Понятные ответы вместо «Server error» для типовых ошибок MySQL. */
function handleDbError(PDOException $e): never
{
    $code = (int) ($e->errorInfo[1] ?? 0);
    error_log('[api] DB error ' . $code . ': ' . $e->getMessage());

    if ($code === 1062) {
        fail('Запись с таким значением уже существует', 409);
    }
    if ($code === 1451) {
        fail('Нельзя удалить: есть связанные записи', 409);
    }
    if ($code === 1452) {
        fail('Указан несуществующий связанный объект', 422);
    }
    if (in_array($code, [1264, 1265, 1292, 1366, 1406], true)) {
        fail('Некорректное значение поля', 422);
    }
    fail('Server error', 500);
}

// ====================================================================
// Маршрутизация
// ====================================================================

/** Сопоставляет шаблон '/products/:id' с путём; возвращает параметры или null. */
function matchPath(string $pattern, string $path): ?array
{
    $p = explode('/', trim($pattern, '/'));
    $u = explode('/', trim($path, '/'));
    if (count($p) !== count($u)) {
        return null;
    }

    $params = [];
    foreach ($p as $i => $segment) {
        if ($segment !== '' && $segment[0] === ':') {
            if ($u[$i] === '') {
                return null;
            }
            $params[substr($segment, 1)] = $u[$i];
        } elseif ($segment !== $u[$i]) {
            return null;
        }
    }
    return $params;
}

function idParam(array $params): int
{
    $raw = (string) ($params['id'] ?? '');
    if (!ctype_digit($raw) || (int) $raw <= 0) {
        fail('Invalid ID', 400);
    }
    return (int) $raw;
}

// ====================================================================
// Валидация входных данных
// ====================================================================

/** @return array{0: mixed, 1: ?string} [приведённое значение, текст ошибки] */
function castValue(array $spec, mixed $value): array
{
    switch ($spec['type']) {
        case 'string':
            if (!is_string($value) && !is_int($value) && !is_float($value)) {
                return [null, 'Ожидается строка'];
            }
            $text = (string) $value;
            if ($spec['trim'] ?? true) {
                $text = trim($text);
            }
            if (isset($spec['minLen']) && mb_strlen($text) < $spec['minLen']) {
                return [null, 'Минимальная длина — ' . $spec['minLen'] . ' симв.'];
            }
            $max = $spec['max'] ?? 255;
            if (mb_strlen($text) > $max) {
                return [null, 'Максимальная длина — ' . $max . ' симв.'];
            }
            if (isset($spec['maxBytes']) && strlen($text) > $spec['maxBytes']) {
                return [null, 'Слишком длинное значение (максимум ' . $spec['maxBytes'] . ' байт)'];
            }
            return [$text, null];

        case 'int':
            if (is_string($value) && preg_match('/^\d+$/', $value) === 1) {
                $value = (int) $value;
            }
            if (!is_int($value)) {
                return [null, 'Ожидается целое число'];
            }
            $min = $spec['min'] ?? 1;
            if ($value < $min) {
                return [null, 'Значение должно быть не меньше ' . $min];
            }
            if (isset($spec['max']) && $value > $spec['max']) {
                return [null, 'Значение должно быть не больше ' . $spec['max']];
            }
            return [$value, null];

        case 'bool':
            // PDO передаёт false как пустую строку, поэтому приводим к 0/1
            if (is_bool($value)) {
                return [(int) $value, null];
            }
            if ($value === 0 || $value === 1 || $value === '0' || $value === '1') {
                return [(int) $value, null];
            }
            return [null, 'Ожидается true/false'];

        case 'email':
            if (is_string($value) && filter_var(trim($value), FILTER_VALIDATE_EMAIL) !== false) {
                return [trim($value), null];
            }
            return [null, 'Некорректный email'];

        case 'decimal':
            $numeric = is_int($value) || is_float($value)
                || (is_string($value) && preg_match('/^\d+(\.\d{1,2})?$/', trim($value)) === 1);
            if (!$numeric) {
                return [null, 'Ожидается число (не более 2 знаков после запятой)'];
            }
            $number = (float) $value;
            if ($number < ($spec['min'] ?? 0) || $number > ($spec['max'] ?? 99999999.99)) {
                return [null, 'Значение вне допустимого диапазона'];
            }
            return [number_format($number, 2, '.', ''), null];

        case 'enum':
            if (in_array($value, $spec['values'], true)) {
                return [$value, null];
            }
            return [null, 'Допустимые значения: ' . implode(', ', $spec['values'])];

        case 'date':
            if (is_string($value) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) === 1
                && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
                return [$value, null];
            }
            return [null, 'Ожидается дата в формате ГГГГ-ММ-ДД'];
    }
    return [null, 'Неизвестный тип поля'];
}

/**
 * Проверяет тело запроса по описанию полей и возвращает только известные поля.
 * $partial = true (PUT): обязательность не требуется, нужны лишь переданные поля.
 * При ошибках отвечает 422 { error, errors: { поле: сообщение } }.
 */
function validateFields(array $fields, array $body, bool $partial): array
{
    $data = [];
    $errors = [];

    foreach ($fields as $name => $spec) {
        if (!array_key_exists($name, $body)) {
            if (!$partial && !empty($spec['required'])) {
                $errors[$name] = 'Обязательное поле';
            }
            continue;
        }

        $value = $body[$name];
        $isEmpty = $value === null || (is_string($value) && trim($value) === '');

        if ($isEmpty) {
            if ($partial && !empty($spec['keepIfEmpty'])) {
                continue; // например, пустой пароль при редактировании = «не менять»
            }
            if (!empty($spec['required'])) {
                $errors[$name] = 'Обязательное поле';
            } elseif (!empty($spec['nullable'])) {
                $data[$name] = null;
            }
            continue;
        }

        [$cast, $error] = castValue($spec, $value);
        if ($error !== null) {
            $errors[$name] = $error;
        } else {
            $data[$name] = $cast;
        }
    }

    if ($errors) {
        fail('Ошибка валидации', 422, ['errors' => $errors]);
    }
    return $data;
}

/** Проверка уникальности на уровне приложения (в БД UNIQUE есть не у всех таблиц). */
function checkUnique(array $res, array $data, ?int $exceptId): void
{
    foreach ($res['unique'] ?? [] as $field => $message) {
        if (!array_key_exists($field, $data) || $data[$field] === null) {
            continue;
        }
        $sql = 'SELECT `id` FROM `' . $res['table'] . '` WHERE `' . $field . '` = ?';
        $args = [$data[$field]];
        if ($exceptId !== null) {
            $sql .= ' AND `id` <> ?';
            $args[] = $exceptId;
        }
        $stmt = db()->prepare($sql . ' LIMIT 1');
        $stmt->execute($args);
        if ($stmt->fetchColumn() !== false) {
            fail($message, 409);
        }
    }
}

// ====================================================================
// Универсальные CRUD-обработчики
// ====================================================================

function resourceFrom(array $res): string
{
    return '`' . $res['table'] . '` AS `' . $res['alias'] . '`'
        . (isset($res['joins']) ? ' ' . $res['joins'] : '');
}

function resourceSelect(array $res): string
{
    return $res['select'] ?? ('`' . $res['alias'] . '`.*');
}

function fetchOne(array $res, int $id): ?array
{
    $sql = 'SELECT ' . resourceSelect($res) . ' FROM ' . resourceFrom($res)
        . ' WHERE `' . $res['alias'] . '`.`id` = ?';
    $stmt = db()->prepare($sql);
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

/**
 * GET /<resource>?page=1&perPage=20&search=...&sort=...&order=asc|desc&<фильтры>
 * Ответ: { success, items, total, page, perPage, pages }
 */
function resourceList(array $res): array
{
    $alias = $res['alias'];
    $where = [];
    $params = [];

    foreach ($res['filters'] ?? [] as $param => [$column, $op]) {
        $value = $_GET[$param] ?? '';
        if (is_string($value) && $value !== '') {
            $where[] = "$column $op ?";
            $params[] = $value;
        }
    }

    $search = $_GET['search'] ?? '';
    if (is_string($search) && trim($search) !== '' && !empty($res['search'])) {
        $like = '%' . addcslashes(trim($search), '%_\\') . '%';
        $parts = [];
        foreach ($res['search'] as $column) {
            $parts[] = "$column LIKE ?";
            $params[] = $like;
        }
        $where[] = '(' . implode(' OR ', $parts) . ')';
    }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // Сортировка только по белому списку колонок
    $sortKey = $_GET['sort'] ?? '';
    $sorts = $res['sort'] ?? [];
    $hasSort = is_string($sortKey) && isset($sorts[$sortKey]);
    $orderCol = $hasSort ? $sorts[$sortKey] : '`' . $alias . '`.`id`';
    $order = strtolower((string) ($_GET['order'] ?? ''));
    if ($order === 'asc') {
        $dir = 'ASC';
    } elseif ($order === 'desc') {
        $dir = 'DESC';
    } else {
        $dir = $hasSort ? 'ASC' : 'DESC';
    }

    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = min(100, max(1, (int) ($_GET['perPage'] ?? 20)));
    $offset = ($page - 1) * $perPage;

    $from = resourceFrom($res);
    $sql = 'SELECT ' . resourceSelect($res) . " FROM $from $whereSql"
        . " ORDER BY $orderCol $dir, `$alias`.`id` DESC LIMIT ? OFFSET ?";
    $stmt = db()->prepare($sql);
    foreach ($params as $i => $value) {
        $stmt->bindValue($i + 1, $value);
    }
    $stmt->bindValue(count($params) + 1, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $items = $stmt->fetchAll();

    $countStmt = db()->prepare("SELECT COUNT(*) FROM $from $whereSql");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    return [
        'success' => true,
        'items'   => $items,
        'total'   => $total,
        'page'    => $page,
        'perPage' => $perPage,
        'pages'   => (int) ceil($total / $perPage),
    ];
}

function resourceGet(array $res, array $params): array
{
    $item = fetchOne($res, idParam($params));
    if ($item === null) {
        fail('Not found', 404);
    }
    return ['success' => true, 'item' => $item];
}

function resourceCreate(array $res): never
{
    $data = validateFields($res['fields'], jsonBody(), false);
    if (!$data) {
        fail('Нет данных для сохранения', 422);
    }
    checkUnique($res, $data, null);
    if (isset($res['beforeSave'])) {
        $data = $res['beforeSave']($data, true);
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $columns = array_keys($data);
        $sql = 'INSERT INTO `' . $res['table'] . '` (`' . implode('`, `', $columns) . '`) VALUES ('
            . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $pdo->prepare($sql)->execute(array_values($data));
        $id = (int) $pdo->lastInsertId();
        if (isset($res['afterInsert'])) {
            $res['afterInsert']($id);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    respond(['success' => true, 'item' => fetchOne($res, $id)], 201);
}

function resourceUpdate(array $res, array $params, array $auth): array
{
    $id = idParam($params);
    if (fetchOne($res, $id) === null) {
        fail('Not found', 404);
    }

    $data = validateFields($res['fields'], jsonBody(), true);
    if (!$data) {
        fail('Нет данных для обновления', 422);
    }
    if (isset($res['canUpdate'])) {
        $error = $res['canUpdate']($id, $data, $auth);
        if ($error !== null) {
            fail($error, 403);
        }
    }
    checkUnique($res, $data, $id);
    if (isset($res['beforeSave'])) {
        $data = $res['beforeSave']($data, false);
    }

    $set = implode(', ', array_map(fn($column) => "`$column` = ?", array_keys($data)));
    $values = array_values($data);
    $values[] = $id;
    db()->prepare('UPDATE `' . $res['table'] . "` SET $set WHERE `id` = ?")->execute($values);

    return ['success' => true, 'item' => fetchOne($res, $id)];
}

function resourceDelete(array $res, array $params, array $auth): array
{
    $id = idParam($params);
    if (fetchOne($res, $id) === null) {
        fail('Not found', 404);
    }
    if (isset($res['canDelete'])) {
        $error = $res['canDelete']($id, $auth);
        if ($error !== null) {
            fail($error, 403);
        }
    }

    // Проверка связей повторяет поведение БД: brands/perfume_types — ON DELETE RESTRICT
    // (без проверки MySQL вернул бы ошибку 1451), collections/families — ON DELETE SET NULL
    // (в ресурсе families связей не блокируем: БД сама обнулит family_id у товаров).
    foreach ($res['dependents'] ?? [] as [$table, $column, $label]) {
        $stmt = db()->prepare("SELECT COUNT(*) FROM `$table` WHERE `$column` = ?");
        $stmt->execute([$id]);
        $count = (int) $stmt->fetchColumn();
        if ($count > 0) {
            fail(
                "Нельзя удалить: связано $label — $count. Сначала удалите или переназначьте их.",
                409,
                ['dependents' => $count]
            );
        }
    }

    db()->prepare('DELETE FROM `' . $res['table'] . '` WHERE `id` = ?')->execute([$id]);
    return ['success' => true];
}

/** Пять маршрутов CRUD для ресурса из api/resources.php. */
function crudRoutes(string $name, array $res): array
{
    $access = $res['access'] ?? 'staff';
    return [
        ['GET',    "/$name",     fn($auth, $p) => resourceList($res), $access],
        ['GET',    "/$name/:id", fn($auth, $p) => resourceGet($res, $p), $access],
        ['POST',   "/$name",     fn($auth, $p) => resourceCreate($res), $access],
        ['PUT',    "/$name/:id", fn($auth, $p) => resourceUpdate($res, $p, $auth), $access],
        ['DELETE', "/$name/:id", fn($auth, $p) => resourceDelete($res, $p, $auth), $access],
    ];
}

// ====================================================================
// Таблица маршрутов: [метод, шаблон пути, обработчик, уровень доступа]
// ====================================================================

$routes = [
    ['GET', '/ping', fn() => ['success' => true, 'message' => 'pong', 'time' => date('c')], 'public'],

    ['GET', '/health', function () {
        try {
            db()->query('SELECT 1');
            return ['success' => true, 'db' => 'ok'];
        } catch (Throwable $e) {
            error_log('[api] health: ' . $e->getMessage());
            fail('DB error', 500, ['db' => 'error']);
        }
    }, 'public'],

    // Контракт для фронтенда: { login, password } -> { success, token, user: { id, username, role } }
    ['POST', '/login', function () {
        $body = jsonBody();
        $login = trim((string) ($body['login'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        if ($login === '' || $password === '') {
            fail('Введите логин и пароль', 422);
        }

        $stmt = db()->prepare('SELECT id, login AS username, password_hash, role FROM users WHERE login = ?');
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        $passwordOk = password_verify($password, $user['password_hash'] ?? DUMMY_HASH);
        if (!$user || !$passwordOk) {
            fail('Неверный логин или пароль', 401);
        }

        $role = $user['role'] ?? 'user'; // в БД role допускает NULL
        $cfg = jwtConfig();
        $now = time();
        $token = jwt_encode([
            'iss'      => $cfg['issuer'],
            'sub'      => (int) $user['id'],
            'username' => $user['username'],
            'role'     => $role,
            'iat'      => $now,
            'exp'      => $now + (int) $cfg['ttl'],
        ], $cfg['secret']);

        return [
            'success' => true,
            'token'   => $token,
            'user'    => ['id' => (int) $user['id'], 'username' => $user['username'], 'role' => $role],
        ];
    }, 'public'],

    // JWT хранится на клиенте, поэтому «выход» = удаление токена в браузере
    ['POST', '/logout', fn() => ['success' => true], 'public'],

    // Текущий пользователь (актуальные данные из БД): фронтенд зовёт после перезагрузки страницы
    ['GET', '/me', function (array $auth) {
        $stmt = db()->prepare('SELECT id, login AS username, role FROM users WHERE id = ?');
        $stmt->execute([(int) $auth['sub']]);
        $user = $stmt->fetch();
        if (!$user) {
            fail('Пользователь не найден', 401);
        }
        $user['role'] = $user['role'] ?? 'user';
        return ['success' => true, 'user' => $user];
    }, 'any'],
];

foreach (require __DIR__ . '/resources.php' as $resourceName => $resource) {
    array_push($routes, ...crudRoutes($resourceName, $resource));
}

// ====================================================================
// Разбор запроса и выполнение
// ====================================================================

// "/perfume-catalog/api/ping?x=1" -> "/ping"
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = preg_replace('#^.*?/api(?=/|$)#', '', $path, 1);
$path = '/' . trim($path, '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$handler = null;
$access = 'staff';
$routeParams = [];
$pathKnown = false;

foreach ($routes as [$routeMethod, $pattern, $routeHandler, $routeAccess]) {
    $found = matchPath($pattern, $path);
    if ($found === null) {
        continue;
    }
    $pathKnown = true;
    if ($routeMethod !== $method) {
        continue;
    }
    $handler = $routeHandler;
    $access = $routeAccess;
    $routeParams = $found;
    break;
}

if ($handler === null) {
    fail($pathKnown ? 'Method not allowed' : 'Not found', $pathKnown ? 405 : 404);
}

$auth = null;
if ($access !== 'public') {
    $auth = authenticate();
    $allowedRoles = match ($access) {
        'any'   => null,          // достаточно валидного токена
        'admin' => ['admin'],
        default => STAFF_ROLES,   // 'staff'
    };
    if ($allowedRoles !== null && !in_array($auth['role'] ?? '', $allowedRoles, true)) {
        fail('Доступ запрещён', 403);
    }
}

try {
    respond($handler($auth, $routeParams));
} catch (PDOException $e) {
    handleDbError($e);
} catch (Throwable $e) {
    error_log('[api] ' . $e->getMessage());
    fail('Server error', 500);
}

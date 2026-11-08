<?php
// api/index.php — основной роутер API с JWT middleware

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/jwt.php';

// CORS заголовки
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Обработка preflight запросов
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Вспомогательные функции
function jsonResponse(int $statusCode, array $data): void {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function requireAuth(): array {
    $payload = JWT::decode(JWT::getTokenFromHeader() ?? '');
    if (!$payload) {
        jsonResponse(401, ['success' => false, 'error' => 'Unauthorized: invalid or expired token']);
    }
    return $payload;
}

function requireAdminOrEditor(array $payload): void {
    $role = $payload['role'] ?? 'user';
    if (!in_array($role, ['admin', 'editor'], true)) {
        jsonResponse(403, ['success' => false, 'error' => 'Forbidden: admin or editor role required']);
    }
}

// Получение пути и метода
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = '/perfume-catalog/api';
$path = substr($requestUri, strlen($basePath));
$path = parse_url($path, PHP_URL_PATH); // убираем query string
$method = $_SERVER['REQUEST_METHOD'];

// Разбор маршрута
$segments = array_values(array_filter(explode('/', $path)));
$resource = $segments[0] ?? '';
$id = $segments[1] ?? null;

// Публичные маршруты (без авторизации)
$publicRoutes = [
    'POST:/login' => 'handleLogin',
    'POST:/logout' => 'handleLogout',
    'GET:/ping' => function() { jsonResponse(200, ['success' => true, 'message' => 'pong']); },
    'GET:/health' => function() { jsonResponse(200, ['success' => true, 'status' => 'ok']); },
];

$routeKey = "$method:/$resource";
if (isset($publicRoutes[$routeKey])) {
    $publicRoutes[$routeKey]();
}

// Все остальные маршруты требуют авторизацию
$payload = requireAuth();
requireAdminOrEditor($payload);

// Входная валидация JSON
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// Роутинг CRUD
switch ($resource) {
    case 'products':
        handleProducts($method, $id, $input);
        break;
    case 'brands':
        handleBrands($method, $id, $input);
        break;
    case 'categories':
        handleCategories($method, $id, $input);
        break;
    case 'types':
        handleTypes($method, $id, $input);
        break;
    case 'families':
        handleFamilies($method, $id, $input);
        break;
    case 'collections':
        handleCollections($method, $id, $input);
        break;
    case 'users':
        handleUsers($method, $id, $input);
        break;
    case 'me':
        if ($method === 'GET') {
            jsonResponse(200, ['success' => true, 'user' => [
                'id' => $payload['id'],
                'login' => $payload['login'],
                'role' => $payload['role'],
            ]]);
        }
        break;
    default:
        jsonResponse(404, ['success' => false, 'error' => 'Not found']);
}

/**
 * Аутентификация
 */
function handleLogin(): void {
    global $pdo;
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $login = trim($input['login'] ?? '');
    $password = $input['password'] ?? '';

    if (!$login || !$password) {
        jsonResponse(400, ['success' => false, 'error' => 'Login and password required']);
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE login = ?');
    $stmt->execute([$login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        jsonResponse(401, ['success' => false, 'error' => 'Invalid credentials']);
    }

    $config = require __DIR__ . '/../config/jwt.php';
    $payload = [
        'id' => $user['id'],
        'login' => $user['login'],
        'role' => $user['role'],
        'iat' => time(),
        'exp' => time() + $config['ttl'],
    ];

    $token = JWT::encode($payload);

    jsonResponse(200, [
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'login' => $user['login'],
            'role' => $user['role'],
        ]
    ]);
}

function handleLogout(): void {
    // Для stateless JWT достаточно клиентского удаления токена
    // Можно добавить blacklist в БД при необходимости
    jsonResponse(200, ['success' => true, 'message' => 'Logged out']);
}

/**
 * CRUD Products
 */
function handleProducts(string $method, ?string $id, array $input): void {
    global $pdo;

    switch ($method) {
        case 'GET':
            if ($id) {
                // GET /api/products/{id}
                $stmt = $pdo->prepare('
                    SELECT p.*, b.name as brand_name, pt.name as type_name, 
                           ff.name as family_name, c.name as collection_name
                    FROM products p
                    LEFT JOIN brands b ON p.brand_id = b.id
                    LEFT JOIN perfume_types pt ON p.type_id = pt.id
                    LEFT JOIN fragrance_families ff ON p.family_id = ff.id
                    LEFT JOIN collections c ON p.collection_id = c.id
                    WHERE p.id = ?
                ');
                $stmt->execute([$id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$product) {
                    jsonResponse(404, ['success' => false, 'error' => 'Product not found']);
                }
                $product['sku'] = '#' . sprintf('%08d', $product['id']);
                
                // Изображения
                $imgStmt = $pdo->prepare('SELECT image_path, is_main FROM product_images WHERE product_id = ? ORDER BY is_main DESC');
                $imgStmt->execute([$id]);
                $product['images'] = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Ноты
                $notesStmt = $pdo->prepare('
                    SELECT pn.note_stage, n.name as note_name
                    FROM product_notes pn
                    JOIN notes n ON pn.note_id = n.id
                    WHERE pn.product_id = ?
                    ORDER BY FIELD(pn.note_stage, "top", "heart", "base"), pn.sort_order
                ');
                $notesStmt->execute([$id]);
                $notes = $notesStmt->fetchAll(PDO::FETCH_ASSOC);
                $product['fragrance_notes'] = ['top' => [], 'heart' => [], 'base' => []];
                foreach ($notes as $note) {
                    $product['fragrance_notes'][$note['note_stage']][] = $note['note_name'];
                }
                
                jsonResponse(200, ['success' => true, 'product' => $product]);
            } else {
                // GET /api/products — список с фильтрами и пагинацией
                $page = max(1, intval($_GET['page'] ?? 1));
                $perPage = intval($_GET['perPage'] ?? 12);
                $offset = ($page - 1) * $perPage;

                $where = ['1=1'];
                $params = [];

                if (!empty($_GET['brand'])) {
                    $where[] = 'p.brand_id = ?';
                    $params[] = $_GET['brand'];
                }
                if (!empty($_GET['type'])) {
                    $where[] = 'p.type_id = ?';
                    $params[] = $_GET['type'];
                }
                if (!empty($_GET['family'])) {
                    $where[] = 'p.family_id = ?';
                    $params[] = $_GET['family'];
                }
                if (!empty($_GET['collection'])) {
                    $where[] = 'p.collection_id = ?';
                    $params[] = $_GET['collection'];
                }
                if (!empty($_GET['gender'])) {
                    $where[] = 'p.gender = ?';
                    $params[] = $_GET['gender'];
                }
                if (!empty($_GET['priceFrom'])) {
                    $where[] = 'p.price >= ?';
                    $params[] = floatval($_GET['priceFrom']);
                }
                if (!empty($_GET['priceTo'])) {
                    $where[] = 'p.price <= ?';
                    $params[] = floatval($_GET['priceTo']);
                }
                if (!empty($_GET['search'])) {
                    $where[] = '(p.title LIKE ? OR p.description LIKE ? OR b.name LIKE ?)';
                    $searchParam = "%{$_GET['search']}%";
                    $params[] = $searchParam;
                    $params[] = $searchParam;
                    $params[] = $searchParam;
                }

                $whereClause = implode(' AND ', $where);

                $orderBy = 'p.views DESC';
                switch ($_GET['sortBy'] ?? 'popularity') {
                    case 'price-asc': $orderBy = 'p.price ASC'; break;
                    case 'price-desc': $orderBy = 'p.price DESC'; break;
                    case 'novelty': $orderBy = 'p.release_date DESC'; break;
                }

                // Total count
                $countSql = "SELECT COUNT(*) FROM products p LEFT JOIN brands b ON p.brand_id = b.id WHERE $whereClause";
                $countStmt = $pdo->prepare($countSql);
                $countStmt->execute($params);
                $total = $countStmt->fetchColumn();

                // Data
                $sql = "SELECT p.*, b.name as brand_name, pt.name as type_name, ff.name as family_name, c.name as collection_name
                        FROM products p
                        LEFT JOIN brands b ON p.brand_id = b.id
                        LEFT JOIN perfume_types pt ON p.type_id = pt.id
                        LEFT JOIN fragrance_families ff ON p.family_id = ff.id
                        LEFT JOIN collections c ON p.collection_id = c.id
                        WHERE $whereClause
                        ORDER BY $orderBy
                        LIMIT ? OFFSET ?";
                $params[] = $perPage;
                $params[] = $offset;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $products = array_map(fn($p) => array_merge($p, ['sku' => '#' . sprintf('%08d', $p['id'])]), $products);

                jsonResponse(200, [
                    'success' => true,
                    'products' => $products,
                    'pagination' => [
                        'currentPage' => $page,
                        'perPage' => $perPage,
                        'total' => $total,
                        'totalPages' => ceil($total / $perPage),
                    ]
                ]);
            }
            break;

        case 'POST':
            // POST /api/products — создание
            $required = ['title', 'price', 'brand_id', 'type_id'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    jsonResponse(400, ['success' => false, 'error' => "Field '$field' is required"]);
                }
            }

            $sql = 'INSERT INTO products (title, description, price, brand_id, type_id, family_id, collection_id, gender, release_date, volume_ml, concentration, is_featured, is_new, views)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $input['title'],
                $input['description'] ?? '',
                floatval($input['price']),
                intval($input['brand_id']),
                intval($input['type_id']),
                $input['family_id'] ? intval($input['family_id']) : null,
                $input['collection_id'] ? intval($input['collection_id']) : null,
                $input['gender'] ?? 'unisex',
                $input['release_date'] ?? date('Y-m-d'),
                $input['volume_ml'] ?? null,
                $input['concentration'] ?? '',
                $input['is_featured'] ?? false,
                $input['is_new'] ?? false,
            ]);
            $newId = $pdo->lastInsertId();

            // Обработка нот, если переданы
            if (!empty($input['notes']) && is_array($input['notes'])) {
                $noteStmt = $pdo->prepare('INSERT INTO product_notes (product_id, note_id, note_stage, sort_order) VALUES (?, ?, ?, ?)');
                foreach (['top', 'heart', 'base'] as $stage) {
                    if (!empty($input['notes'][$stage]) && is_array($input['notes'][$stage])) {
                        foreach ($input['notes'][$stage] as $sortOrder => $noteName) {
                            // Найти или создать ноту
                            $noteFind = $pdo->prepare('SELECT id FROM notes WHERE name = ?');
                            $noteFind->execute([$noteName]);
                            $note = $noteFind->fetch(PDO::FETCH_ASSOC);
                            if (!$note) {
                                $pdo->prepare('INSERT INTO notes (name) VALUES (?)')->execute([$noteName]);
                                $noteId = $pdo->lastInsertId();
                            } else {
                                $noteId = $note['id'];
                            }
                            $noteStmt->execute([$newId, $noteId, $stage, $sortOrder]);
                        }
                    }
                }
            }

            // Обработка изображений
            if (!empty($input['images']) && is_array($input['images'])) {
                $imgStmt = $pdo->prepare('INSERT INTO product_images (product_id, image_path, is_main, sort_order) VALUES (?, ?, ?, ?)');
                foreach ($input['images'] as $index => $img) {
                    $imgStmt->execute([$newId, $img['path'] ?? $img, $index === 0 ? 1 : 0, $index]);
                }
            }

            jsonResponse(201, ['success' => true, 'id' => $newId, 'message' => 'Product created']);
            break;

        case 'PUT':
            if (!$id) {
                jsonResponse(400, ['success' => false, 'error' => 'Product ID required']);
            }
            // Проверка существования
            $check = $pdo->prepare('SELECT id FROM products WHERE id = ?');
            $check->execute([$id]);
            if (!$check->fetch()) {
                jsonResponse(404, ['success' => false, 'error' => 'Product not found']);
            }

            $allowedFields = ['title', 'description', 'price', 'brand_id', 'type_id', 'family_id', 'collection_id', 'gender', 'release_date', 'volume_ml', 'concentration', 'is_featured', 'is_new'];
            $updates = [];
            $params = [];
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $input)) {
                    $updates[] = "$field = ?";
                    $params[] = $input[$field];
                }
            }
            if ($updates) {
                $params[] = $id;
                $sql = 'UPDATE products SET ' . implode(', ', $updates) . ' WHERE id = ?';
                $pdo->prepare($sql)->execute($params);
            }

            // Обновление нот (полная замена)
            if (array_key_exists('notes', $input)) {
                $pdo->prepare('DELETE FROM product_notes WHERE product_id = ?')->execute([$id]);
                if (!empty($input['notes']) && is_array($input['notes'])) {
                    $noteStmt = $pdo->prepare('INSERT INTO product_notes (product_id, note_id, note_stage, sort_order) VALUES (?, ?, ?, ?)');
                    foreach (['top', 'heart', 'base'] as $stage) {
                        if (!empty($input['notes'][$stage]) && is_array($input['notes'][$stage])) {
                            foreach ($input['notes'][$stage] as $sortOrder => $noteName) {
                                $noteFind = $pdo->prepare('SELECT id FROM notes WHERE name = ?');
                                $noteFind->execute([$noteName]);
                                $note = $noteFind->fetch(PDO::FETCH_ASSOC);
                                if (!$note) {
                                    $pdo->prepare('INSERT INTO notes (name) VALUES (?)')->execute([$noteName]);
                                    $noteId = $pdo->lastInsertId();
                                } else {
                                    $noteId = $note['id'];
                                }
                                $noteStmt->execute([$id, $noteId, $stage, $sortOrder]);
                            }
                        }
                    }
                }
            }

            // Обновление изображений
            if (array_key_exists('images', $input)) {
                $pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$id]);
                if (!empty($input['images']) && is_array($input['images'])) {
                    $imgStmt = $pdo->prepare('INSERT INTO product_images (product_id, image_path, is_main, sort_order) VALUES (?, ?, ?, ?)');
                    foreach ($input['images'] as $index => $img) {
                        $imgStmt->execute([$id, $img['path'] ?? $img, $index === 0 ? 1 : 0, $index]);
                    }
                }
            }

            jsonResponse(200, ['success' => true, 'message' => 'Product updated']);
            break;

        case 'DELETE':
            if (!$id) {
                jsonResponse(400, ['success' => false, 'error' => 'Product ID required']);
            }
            $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) {
                jsonResponse(404, ['success' => false, 'error' => 'Product not found']);
            }
            jsonResponse(200, ['success' => true, 'message' => 'Product deleted']);
            break;

        default:
            jsonResponse(405, ['success' => false, 'error' => 'Method not allowed']);
    }
}

/**
 * CRUD Brands
 */
function handleBrands(string $method, ?string $id, array $input): void {
    global $pdo;

    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $pdo->prepare('SELECT * FROM brands WHERE id = ?');
                $stmt->execute([$id]);
                $brand = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$brand) jsonResponse(404, ['success' => false, 'error' => 'Brand not found']);
                jsonResponse(200, ['success' => true, 'brand' => $brand]);
            } else {
                $stmt = $pdo->query('SELECT * FROM brands ORDER BY name');
                $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
                jsonResponse(200, ['success' => true, 'brands' => $brands]);
            }
            break;

        case 'POST':
            if (empty($input['name'])) {
                jsonResponse(400, ['success' => false, 'error' => 'Name is required']);
            }
            $stmt = $pdo->prepare('INSERT INTO brands (name, description, country, website, logo_path) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([
                $input['name'],
                $input['description'] ?? '',
                $input['country'] ?? '',
                $input['website'] ?? '',
                $input['logo_path'] ?? '',
            ]);
            jsonResponse(201, ['success' => true, 'id' => $pdo->lastInsertId(), 'message' => 'Brand created']);
            break;

        case 'PUT':
            if (!$id) jsonResponse(400, ['success' => false, 'error' => 'Brand ID required']);
            $allowed = ['name', 'description', 'country', 'website', 'logo_path'];
            $updates = []; $params = [];
            foreach ($allowed as $f) if (array_key_exists($f, $input)) { $updates[] = "$f=?"; $params[] = $input[$f]; }
            if ($updates) { $params[] = $id; $pdo->prepare('UPDATE brands SET ' . implode(',', $updates) . ' WHERE id=?')->execute($params); }
            jsonResponse(200, ['success' => true, 'message' => 'Brand updated']);
            break;

        case 'DELETE':
            if (!$id) jsonResponse(400, ['success' => false, 'error' => 'Brand ID required']);
            $pdo->prepare('DELETE FROM brands WHERE id=?')->execute([$id]);
            jsonResponse(200, ['success' => true, 'message' => 'Brand deleted']);
            break;
        default: jsonResponse(405, ['success' => false, 'error' => 'Method not allowed']);
    }
}

/**
 * CRUD Categories (perfume_types)
 */
function handleCategories(string $method, ?string $id, array $input): void {
    global $pdo;
    $table = 'perfume_types';
    $nameField = 'name';
    
    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
                $stmt->execute([$id]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$item) jsonResponse(404, ['success' => false, 'error' => 'Category not found']);
                jsonResponse(200, ['success' => true, 'category' => $item]);
            } else {
                $stmt = $pdo->query("SELECT * FROM $table ORDER BY $nameField");
                jsonResponse(200, ['success' => true, 'categories' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            }
            break;
        case 'POST':
            if (empty($input['name'])) jsonResponse(400, ['success' => false, 'error' => 'Name is required']);
            $stmt = $pdo->prepare("INSERT INTO $table (name, description) VALUES (?, ?)");
            $stmt->execute([$input['name'], $input['description'] ?? '']);
            jsonResponse(201, ['success' => true, 'id' => $pdo->lastInsertId(), 'message' => 'Category created']);
            break;
        case 'PUT':
            if (!$id) jsonResponse(400, ['success' => false, 'error' => 'Category ID required']);
            $updates = []; $params = [];
            foreach (['name', 'description'] as $f) if (array_key_exists($f, $input)) { $updates[] = "$f=?"; $params[] = $input[$f]; }
            if ($updates) { $params[] = $id; $pdo->prepare("UPDATE $table SET " . implode(',', $updates) . " WHERE id=?")->execute($params); }
            jsonResponse(200, ['success' => true, 'message' => 'Category updated']);
            break;
        case 'DELETE':
            if (!$id) jsonResponse(400, ['success' => false, 'error' => 'Category ID required']);
            $pdo->prepare("DELETE FROM $table WHERE id=?")->execute([$id]);
            jsonResponse(200, ['success' => true, 'message' => 'Category deleted']);
            break;
        default: jsonResponse(405, ['success' => false, 'error' => 'Method not allowed']);
    }
}

/**
 * CRUD Types (alias for categories)
 */
function handleTypes(string $method, ?string $id, array $input): void {
    handleCategories($method, $id, $input);
}

/**
 * CRUD Fragrance Families
 */
function handleFamilies(string $method, ?string $id, array $input): void {
    global $pdo;
    $table = 'fragrance_families';
    
    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
                $stmt->execute([$id]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$item) jsonResponse(404, ['success' => false, 'error' => 'Family not found']);
                jsonResponse(200, ['success' => true, 'family' => $item]);
            } else {
                $stmt = $pdo->query("SELECT * FROM $table ORDER BY name");
                jsonResponse(200, ['success' => true, 'families' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            }
            break;
        case 'POST':
            if (empty($input['name'])) jsonResponse(400, ['success' => false, 'error' => 'Name is required']);
            $stmt = $pdo->prepare("INSERT INTO $table (name, description) VALUES (?, ?)");
            $stmt->execute([$input['name'], $input['description'] ?? '']);
            jsonResponse(201, ['success' => true, 'id' => $pdo->lastInsertId(), 'message' => 'Family created']);
            break;
        case 'PUT':
            if (!$id) jsonResponse(400, ['success' => false, 'error' => 'Family ID required']);
            $updates = []; $params = [];
            foreach (['name', 'description'] as $f) if (array_key_exists($f, $input)) { $updates[] = "$f=?"; $params[] = $input[$f]; }
            if ($updates) { $params[] = $id; $pdo->prepare("UPDATE $table SET " . implode(',', $updates) . " WHERE id=?")->execute($params); }
            jsonResponse(200, ['success' => true, 'message' => 'Family updated']);
            break;
        case 'DELETE':
            if (!$id) jsonResponse(400, ['success' => false, 'error' => 'Family ID required']);
            $pdo->prepare("DELETE FROM $table WHERE id=?")->execute([$id]);
            jsonResponse(200, ['success' => true, 'message' => 'Family deleted']);
            break;
        default: jsonResponse(405, ['success' => false, 'error' => 'Method not allowed']);
    }
}

/**
 * CRUD Collections
 */
function handleCollections(string $method, ?string $id, array $input): void {
    global $pdo;
    $table = 'collections';
    
    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
                $stmt->execute([$id]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$item) jsonResponse(404, ['success' => false, 'error' => 'Collection not found']);
                jsonResponse(200, ['success' => true, 'collection' => $item]);
            } else {
                $stmt = $pdo->query("SELECT * FROM $table ORDER BY name");
                jsonResponse(200, ['success' => true, 'collections' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            }
            break;
        case 'POST':
            if (empty($input['name'])) jsonResponse(400, ['success' => false, 'error' => 'Name is required']);
            $stmt = $pdo->prepare("INSERT INTO $table (name, description, brand_id, release_year) VALUES (?, ?, ?, ?)");
            $stmt->execute([$input['name'], $input['description'] ?? '', $input['brand_id'] ?? null, $input['release_year'] ?? null]);
            jsonResponse(201, ['success' => true, 'id' => $pdo->lastInsertId(), 'message' => 'Collection created']);
            break;
        case 'PUT':
            if (!$id) jsonResponse(400, ['success' => false, 'error' => 'Collection ID required']);
            $updates = []; $params = [];
            foreach (['name', 'description', 'brand_id', 'release_year'] as $f) if (array_key_exists($f, $input)) { $updates[] = "$f=?"; $params[] = $input[$f]; }
            if ($updates) { $params[] = $id; $pdo->prepare("UPDATE $table SET " . implode(',', $updates) . " WHERE id=?")->execute($params); }
            jsonResponse(200, ['success' => true, 'message' => 'Collection updated']);
            break;
        case 'DELETE':
            if (!$id) jsonResponse(400, ['success' => false, 'error' => 'Collection ID required']);
            $pdo->prepare("DELETE FROM $table WHERE id=?")->execute([$id]);
            jsonResponse(200, ['success' => true, 'message' => 'Collection deleted']);
            break;
        default: jsonResponse(405, ['success' => false, 'error' => 'Method not allowed']);
    }
}

/**
 * CRUD Users
 */
function handleUsers(string $method, ?string $id, array $input): void {
    global $pdo;

    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $pdo->prepare('SELECT id, login, email, role, created_at FROM users WHERE id = ?');
                $stmt->execute([$id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$user) jsonResponse(404, ['success' => false, 'error' => 'User not found']);
                jsonResponse(200, ['success' => true, 'user' => $user]);
            } else {
                $stmt = $pdo->query('SELECT id, login, email, role, created_at FROM users ORDER BY created_at DESC');
                jsonResponse(200, ['success' => true, 'users' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            }
            break;

        case 'POST':
            $required = ['login', 'password', 'email'];
            foreach ($required as $f) if (empty($input[$f])) jsonResponse(400, ['success' => false, 'error' => "Field '$f' is required"]);

            // Проверка уникальности
            $check = $pdo->prepare('SELECT id FROM users WHERE login = ? OR email = ?');
            $check->execute([$input['login'], $input['email']]);
            if ($check->fetch()) jsonResponse(409, ['success' => false, 'error' => 'Login or email already exists']);

            $hash = password_hash($input['password'], PASSWORD_DEFAULT);
            $role = in_array($input['role'] ?? 'user', ['admin', 'editor', 'user']) ? $input['role'] : 'user';

            $stmt = $pdo->prepare('INSERT INTO users (login, email, password_hash, role) VALUES (?, ?, ?, ?)');
            $stmt->execute([$input['login'], $input['email'], $hash, $role]);
            jsonResponse(201, ['success' => true, 'id' => $pdo->lastInsertId(), 'message' => 'User created']);
            break;

        case 'PUT':
            if (!$id) jsonResponse(400, ['success' => false, 'error' => 'User ID required']);
            $updates = []; $params = [];
            foreach (['login', 'email', 'role'] as $f) {
                if (array_key_exists($f, $input)) {
                    if ($f === 'role' && !in_array($input[$f], ['admin', 'editor', 'user'])) {
                        jsonResponse(400, ['success' => false, 'error' => 'Invalid role']);
                    }
                    $updates[] = "$f = ?";
                    $params[] = $input[$f];
                }
            }
            if (array_key_exists('password', $input) && $input['password']) {
                $updates[] = 'password_hash = ?';
                $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
            }
            if ($updates) {
                $params[] = $id;
                $pdo->prepare('UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = ?')->execute($params);
            }
            jsonResponse(200, ['success' => true, 'message' => 'User updated']);
            break;

        case 'DELETE':
            if (!$id) jsonResponse(400, ['success' => false, 'error' => 'User ID required']);
            if ($id == ($GLOBALS['payload']['id'] ?? 0)) jsonResponse(400, ['success' => false, 'error' => 'Cannot delete yourself']);
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            jsonResponse(200, ['success' => true, 'message' => 'User deleted']);
            break;

        default:
            jsonResponse(405, ['success' => false, 'error' => 'Method not allowed']);
    }
}
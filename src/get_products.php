<?php
/**
 * API для работы с каталогом товаров
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Подключение к БД
require_once '../config/db.php';

// Получение действия
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'getProducts':
        getProducts();
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

/**
 * Получение списка товаров с фильтрацией и пагинацией
 */
function getProducts() {
    global $pdo;

    try {
        // Получение параметров
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = intval($_GET['perPage'] ?? 12);
        $offset = ($page - 1) * $perPage;

        // Фильтры
        $brand = $_GET['brand'] ?? '';
        $type = $_GET['type'] ?? '';
        $gender = $_GET['gender'] ?? '';
        $priceFrom = $_GET['priceFrom'] ?? null;
        $priceTo = $_GET['priceTo'] ?? null;
        $search = $_GET['search'] ?? '';
        $sortBy = $_GET['sortBy'] ?? 'popularity';

        // Построение запроса
        $where = ['1=1'];
        $params = [];

        if ($brand) {
            $where[] = 'p.brand_id = ?';
            $params[] = $brand;
        }

        if ($type) {
            $where[] = 'p.type_id = ?';
            $params[] = $type;
        }

        if ($gender) {
            $where[] = 'p.gender = ?';
            $params[] = $gender;
        }

        if ($priceFrom !== null && $priceFrom !== '') {
            $where[] = 'p.price >= ?';
            $params[] = floatval($priceFrom);
        }

        if ($priceTo !== null && $priceTo !== '') {
            $where[] = 'p.price <= ?';
            $params[] = floatval($priceTo);
        }

        if ($search) {
            $where[] = '(p.title LIKE ? OR p.description LIKE ? OR b.name LIKE ?)';
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }

        $whereClause = implode(' AND ', $where);

        // Сортировка
        $orderBy = 'p.views DESC';
        switch ($sortBy) {
            case 'price-asc':
                $orderBy = 'p.price ASC';
                break;
            case 'price-desc':
                $orderBy = 'p.price DESC';
                break;
            case 'novelty':
                $orderBy = 'p.release_date DESC';
                break;
        }

        // Получение общего количества
        $countSql = "SELECT COUNT(*) as total FROM products p 
                     LEFT JOIN brands b ON p.brand_id = b.id 
                     WHERE {$whereClause}";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        // Получение товаров
        $sql = "SELECT p.*, 
                       b.name as brand_name, 
                       b.description AS brand_description,
                       pt.name as type_name, 
                       ff.name as family_name,
                       pi.image_path as image
                FROM products p
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN perfume_types pt ON p.type_id = pt.id
                LEFT JOIN fragrance_families ff ON p.family_id = ff.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                WHERE {$whereClause}
                ORDER BY {$orderBy}
                LIMIT ? OFFSET ?";

        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // === ГЕНЕРАЦИЯ SKU: # + 8-значный id ===
        $formattedProducts = array_map(function ($p) {
            $p['sku'] = '#' . sprintf('%08d', $p['id']);
            return $p;
        }, $products);

        // Форматирование ответа
        $response = [
            'success' => true,
            'products' => $formattedProducts,
            'pagination' => [
                'currentPage' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => ceil($total / $perPage)
            ]
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
}
?>
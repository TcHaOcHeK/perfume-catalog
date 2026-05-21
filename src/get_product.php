<?php
/**
 * API для получения информации о конкретном товаре
 * Файл: src/get_product.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Подключение к БД
require_once '/perfume-catalog/config/db.php';

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$productId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Product ID is required']);
    exit;
}

try {
    // Основная информация о товаре
    $sql = "SELECT p.*, 
                   b.name as brand_name, 
                   pt.name as type_name, 
                   ff.name as family_name,
                   c.name as collection_name
            FROM products p
            LEFT JOIN brands b ON p.brand_id = b.id
            LEFT JOIN perfume_types pt ON p.type_id = pt.id
            LEFT JOIN fragrance_families ff ON p.family_id = ff.id
            LEFT JOIN collections c ON p.collection_id = c.id
            WHERE p.id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $product['sku'] = '#' . sprintf('%08d', $product['id']);
    }

    if (!$product) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Product not found']);
        exit;
    }

    // Изображения товара
    $imagesSql = "SELECT image_path, is_main 
                  FROM product_images 
                  WHERE product_id = ? 
                  ORDER BY is_main DESC, id ASC";

    $imagesStmt = $pdo->prepare($imagesSql);
    $imagesStmt->execute([$productId]);
    $images = $imagesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Форматирование даты
    $product['formatted_date'] = date('d.m.Y', strtotime($product['release_date']));

    // Форматирование цены (без копеек)
    $product['formatted_price'] = number_format((float)$product['price'], 0, '.', ' ');

    // === НОВЫЙ ЗАПРОС: Получение нот аромата из базы данных ===
    $product['fragrance_notes'] = getProductNotes($pdo, $productId);
    // ===========================================================

    // Долговечность на основе типа
    $product['longevity'] = getLongevity($product['type_name']);

    // Увеличение счётчика просмотров
    $updateViewsSql = "UPDATE products SET views = views + 1 WHERE id = ?";
    $updateStmt = $pdo->prepare($updateViewsSql);
    $updateStmt->execute([$productId]);

    $response = [
        'success' => true,
        'product' => $product,
        'images' => $images
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

/**
 * Получить ноты аромата из базы данных (многие-ко-многим)
 * Возвращает массив вида: ['top' => [...], 'heart' => [...], 'base' => [...]]
 */
function getProductNotes($pdo, $productId) {
    $sql = "SELECT 
                pn.note_stage,
                n.name as note_name,
                pn.sort_order
            FROM product_notes pn
            JOIN notes n ON pn.note_id = n.id
            WHERE pn.product_id = ?
            ORDER BY 
                FIELD(pn.note_stage, 'top', 'heart', 'base'),
                pn.sort_order";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$productId]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Группируем по стадиям пирамиды
    $result = ['top' => [], 'heart' => [], 'base' => []];
    foreach ($notes as $note) {
        $result[$note['note_stage']][] = $note['note_name'];
    }

    return $result;
}

/**
 * Получить долговечность на основе типа парфюма
 */
function getLongevity($typeName) {
    $longevity = [
        'Eau de Parfum' => '6-8 hours',
        'Eau de Toilette' => '4-6 hours',
        'Parfum' => '8-12 hours',
        'Toilet water' => '3-4 hours'
    ];

    return $longevity[$typeName] ?? '4-6 hours';
}
?>
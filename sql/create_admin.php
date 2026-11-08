<?php
/**
 * sql/create_admin.php — консольный скрипт для создания админа
 * Запуск: php sql/create_admin.php
 */

require_once __DIR__ . '/../config/db.php';

echo "=== Создание администратора ===\n\n";

// Данные админа (можно менять)
$login = 'admin';
$email = 'admin@perfume-catalog.local';
$password = 'admin123';
$role = 'admin';

try {
    // Проверка существования
    $stmt = $pdo->prepare('SELECT id FROM users WHERE login = ? OR email = ?');
    $stmt->execute([$login, $email]);
    if ($stmt->fetch()) {
        echo "Пользователь с login='$login' или email='$email' уже существует.\n";
        exit(1);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare('INSERT INTO users (login, email, password_hash, role) VALUES (?, ?, ?, ?)');
    $stmt->execute([$login, $email, $hash, $role]);
    
    $id = $pdo->lastInsertId();
    
    echo "✓ Администратор создан успешно!\n";
    echo "  ID: $id\n";
    echo "  Login: $login\n";
    echo "  Email: $email\n";
    echo "  Password: $password\n";
    echo "  Role: $role\n\n";
    echo "Используйте эти данные для входа в админку: /perfume-catalog/login\n";
    
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
    exit(1);
}
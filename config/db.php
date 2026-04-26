<?php
// config/db.php
$host = '127.0.0.1';
$db   = 'perfume_db';
$user = 'root';
$pass = ''; // По умолчанию в XAMPP пароль пустой
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Выбрасывать исключения при ошибках
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Возвращать массивы
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Отключить эмуляцию подготовленных запросов
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
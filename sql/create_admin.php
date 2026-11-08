<?php
/**
 * Создаёт (или обновляет) пользователя с хэшем пароля.
 * Запуск ТОЛЬКО из консоли:
 *   php sql/create_admin.php <login> <password> [role]
 * Роль по умолчанию — admin.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

[$script, $login, $password] = $argv + [null, null, null];
$role = $argv[3] ?? 'admin';

if (!$login || !$password) {
    fwrite(STDERR, "Usage: php sql/create_admin.php <login> <password> [role]\n");
    exit(1);
}

require __DIR__ . '/../config/db.php';

$stmt = $pdo->prepare(
    'INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = VALUES(role)'
);
$stmt->execute([$login, password_hash($password, PASSWORD_DEFAULT), $role]);

echo "OK: пользователь '$login' (роль: $role) сохранён\n";

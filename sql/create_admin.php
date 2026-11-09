<?php
/**
 * Создаёт пользователя или обновляет существующего (пароль и роль) в таблице users.
 * Колонки таблицы: login, email (NOT NULL, UNIQUE), password_hash, role ('user','editor','admin').
 *
 * Запуск ТОЛЬКО из консоли:
 *   php sql/create_admin.php <логин> [пароль] [роль] [email]
 * Роль по умолчанию — admin. Если пароль не указан, он запрашивается интерактивно
 * (так он не попадает в историю shell). Если email не указан, берётся <логин>@perfume-catalog.local.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$login    = $argv[1] ?? null;
$password = $argv[2] ?? null;
$role     = $argv[3] ?? 'admin';
$email    = $argv[4] ?? null;

if (!$login) {
    fwrite(STDERR, "Использование: php sql/create_admin.php <логин> [пароль] [роль] [email]\n");
    exit(1);
}
if ($password === null) {
    fwrite(STDOUT, 'Пароль (минимум 8 символов): ');
    $password = trim((string) fgets(STDIN));
}
if (mb_strlen($password) < 8 || strlen($password) > 72) {
    fwrite(STDERR, "Пароль должен быть от 8 символов и не длиннее 72 байт\n");
    exit(1);
}
if (!in_array($role, ['admin', 'editor', 'user'], true)) {
    fwrite(STDERR, "Недопустимая роль: $role (admin, editor, user)\n");
    exit(1);
}
$email ??= $login . '@perfume-catalog.local';
if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    fwrite(STDERR, "Некорректный email: $email\n");
    exit(1);
}

require __DIR__ . '/../config/db.php'; // создаёт $pdo

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare('SELECT id FROM users WHERE login = ?');
$stmt->execute([$login]);
$id = $stmt->fetchColumn();

if ($id !== false) {
    $pdo->prepare('UPDATE users SET password_hash = ?, role = ? WHERE id = ?')->execute([$hash, $role, $id]);
    echo "OK: пользователь '$login' обновлён (роль: $role). Email не менялся.\n";
} else {
    $pdo->prepare('INSERT INTO users (login, email, password_hash, role) VALUES (?, ?, ?, ?)')
        ->execute([$login, $email, $hash, $role]);
    echo "OK: пользователь '$login' создан (роль: $role, email: $email)\n";
}

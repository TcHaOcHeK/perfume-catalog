<?php
declare(strict_types=1);

/**
 * Минимальная реализация JWT (HS256) без внешних библиотек.
 * Формат: base64url(header).base64url(payload).base64url(HMAC-SHA256)
 */

function base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode(string $data): string|false
{
    $remainder = strlen($data) % 4;
    if ($remainder !== 0) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($data, '-_', '+/'), true);
}

function jwt_encode(array $payload, string $secret): string
{
    $header = ['typ' => 'JWT', 'alg' => 'HS256'];

    $segments = [
        base64url_encode(json_encode($header)),
        base64url_encode(json_encode($payload, JSON_UNESCAPED_UNICODE)),
    ];
    $signature = hash_hmac('sha256', implode('.', $segments), $secret, true);
    $segments[] = base64url_encode($signature);

    return implode('.', $segments);
}

/**
 * Проверяет подпись и срок действия, возвращает payload.
 * @throws RuntimeException если токен некорректен или просрочен
 */
function jwt_decode(string $token, string $secret): array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        throw new RuntimeException('Malformed token');
    }
    [$h, $p, $s] = $parts;

    $header = json_decode((string) base64url_decode($h), true);
    // Жёстко требуем HS256 (защита от подмены alg, например на "none")
    if (!is_array($header) || ($header['alg'] ?? '') !== 'HS256') {
        throw new RuntimeException('Unsupported algorithm');
    }

    $expected = base64url_encode(hash_hmac('sha256', "$h.$p", $secret, true));
    if (!hash_equals($expected, $s)) {
        throw new RuntimeException('Invalid signature');
    }

    $payload = json_decode((string) base64url_decode($p), true);
    if (!is_array($payload)) {
        throw new RuntimeException('Invalid payload');
    }
    if (!isset($payload['exp']) || (int) $payload['exp'] < time()) {
        throw new RuntimeException('Token expired');
    }

    return $payload;
}

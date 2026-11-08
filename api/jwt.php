<?php
// api/jwt.php — собственная реализация JWT (HS256) без Composer

class JWT {
    private static $secret;
    private static $algorithm = 'HS256';

    public static function init(): void {
        $config = require __DIR__ . '/../config/jwt.php';
        self::$secret = $config['secret'];
        self::$algorithm = $config['algorithm'] ?? 'HS256';
    }

    /**
     * Создать JWT токен
     * @param array $payload Данные для включения в токен (должен содержать 'exp')
     * @return string JWT токен
     */
    public static function encode(array $payload): string {
        self::init();

        $header = json_encode(['alg' => self::$algorithm, 'typ' => 'JWT']);
        $payloadJson = json_encode($payload);

        $base64Header = self::base64UrlEncode($header);
        $base64Payload = self::base64UrlEncode($payloadJson);

        $signature = self::sign("$base64Header.$base64Payload");
        $base64Signature = self::base64UrlEncode($signature);

        return "$base64Header.$base64Payload.$base64Signature";
    }

    /**
     * Проверить и декодировать JWT токен
     * @param string $token JWT токен
     * @return array|null Декодированный payload или null если невалидный
     */
    public static function decode(string $token): ?array {
        self::init();

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$base64Header, $base64Payload, $base64Signature] = $parts;

        // Проверяем подпись
        $expectedSignature = self::sign("$base64Header.$base64Payload");
        $actualSignature = self::base64UrlDecode($base64Signature);

        if (!hash_equals($expectedSignature, $actualSignature)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($base64Payload), true);
        if (!$payload) {
            return null;
        }

        // Проверяем срок действия (exp)
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    /**
     * Получить токен из заголовка Authorization
     * @return string|null
     */
    public static function getTokenFromHeader(): ?string {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private static function sign(string $data): string {
        return hash_hmac('sha256', $data, self::$secret, true);
    }

    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string {
        $padding = strlen($data) % 4;
        if ($padding) {
            $data .= str_repeat('=', 4 - $padding);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
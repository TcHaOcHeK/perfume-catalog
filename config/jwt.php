<?php
// config/jwt.php
return [
    'secret' => 'perfume_catalog_jwt_secret_key_2024_change_in_production',
    'algorithm' => 'HS256',
    'ttl' => 8 * 3600, // 8 часов в секундах
];
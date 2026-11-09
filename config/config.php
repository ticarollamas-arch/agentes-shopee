<?php
declare(strict_types=1);

// Configuração via variáveis de ambiente (Railway). Sem segredos no repositório.
$e = static fn(string $k, string $def = '') => getenv($k) !== false && getenv($k) !== '' ? getenv($k) : $def;
$pass = $e('ADMIN_PASSWORD');
return [
    'app' => [
        'name' => $e('APP_NAME', 'Achadinhos — Central de Agentes'),
        'base_url' => $e('APP_URL'),
        'key' => $e('APP_KEY'),
        'timezone' => 'America/Sao_Paulo',
    ],
    'database' => [
        'host' => $e('DB_HOST', 'localhost'),
        'port' => (int)$e('DB_PORT', '3306'),
        'name' => $e('DB_DATABASE'),
        'username' => $e('DB_USERNAME'),
        'password' => $e('DB_PASSWORD'),
        'charset' => 'utf8mb4',
    ],
    'admin' => [
        'username' => $e('ADMIN_USERNAME', 'admin'),
        'password_hash' => $pass !== '' ? password_hash($pass, PASSWORD_DEFAULT) : '',
    ],
    'integrations' => [
        'shopee' => ['enabled' => $e('SHOPEE_PARTNER_ID') !== '', 'partner_id' => $e('SHOPEE_PARTNER_ID'), 'partner_key' => $e('SHOPEE_PARTNER_KEY')],
        'google' => ['enabled' => $e('GOOGLE_CLIENT_ID') !== '', 'client_id' => $e('GOOGLE_CLIENT_ID'), 'client_secret' => $e('GOOGLE_CLIENT_SECRET'), 'redirect_uri' => $e('GOOGLE_REDIRECT_URI')],
    ],
];

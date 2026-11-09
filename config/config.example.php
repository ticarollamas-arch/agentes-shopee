<?php
declare(strict_types=1);

/*
 * Copie para um caminho FORA do Document Root e defina APP_CONFIG para esse arquivo.
 * Gere APP_KEY com: php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
 * Gere hash da senha com: php -r "echo password_hash('SUA_SENHA_FORTE', PASSWORD_DEFAULT), PHP_EOL;"
 */
return [
    'app' => [
        'name' => 'Achadinhos — Central de Agentes',
        'base_url' => 'https://painel.seudominio.com.br',
        'key' => 'SUBSTITUA_POR_64_CARACTERES_HEXADECIMAIS',
        'timezone' => 'America/Sao_Paulo',
    ],
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'NOME_DO_BANCO',
        'username' => 'USUARIO_DO_BANCO',
        'password' => 'SENHA_DO_BANCO',
        'charset' => 'utf8mb4',
    ],
    'admin' => [
        'username' => 'admin',
        'password_hash' => 'COLE_AQUI_O_HASH_GERADO_COM_PASSWORD_HASH',
    ],
    'integrations' => [
        'shopee' => ['enabled' => false, 'partner_id' => '', 'partner_key' => ''],
        'google' => ['enabled' => false, 'client_id' => '', 'client_secret' => '', 'redirect_uri' => ''],
    ],
];

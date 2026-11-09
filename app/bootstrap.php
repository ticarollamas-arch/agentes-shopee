<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
$configPath = getenv('APP_CONFIG') ?: APP_ROOT . '/config/config.php';
if (!is_file($configPath)) {
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>false,'error'=>'Configuração ausente. Consulte docs/INSTALACAO.md.'], JSON_UNESCAPED_UNICODE);
    exit;
}
$config = require $configPath;
date_default_timezone_set($config['app']['timezone'] ?? 'America/Sao_Paulo');

function app_config(?string $key = null) {
    global $config;
    if ($key === null) return $config;
    $value = $config;
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) return null;
        $value = $value[$part];
    }
    return $value;
}
function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $d = app_config('database');
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $d['host'], (int)($d['port'] ?? 3306), $d['name'], $d['charset'] ?? 'utf8mb4');
    $pdo = new PDO($dsn, $d['username'], $d['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 8,
    ]);
    return $pdo;
}
function json_response(array $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, max-age=0');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function request_json(): array {
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') return [];
    $data = json_decode($raw, true);
    if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) json_response(['ok'=>false,'error'=>'JSON inválido'], 400);
    return $data;
}
function require_method(string ...$methods): void {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, $methods, true)) {
        header('Allow: ' . implode(', ', $methods));
        json_response(['ok'=>false,'error'=>'Método não permitido'], 405);
    }
}
function start_secure_session(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        session_name('achadinhos_admin');
        session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Strict']);
        session_start();
    }
}
function require_auth(): void {
    start_secure_session();
    if (empty($_SESSION['admin_ok'])) json_response(['ok'=>false,'error'=>'Não autenticado'], 401);
}
function require_csrf(): void {
    start_secure_session();
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $sent)) {
        json_response(['ok'=>false,'error'=>'Token CSRF inválido'], 419);
    }
}
function log_activity(string $type, string $message, array $meta = []): void {
    $stmt = db()->prepare('INSERT INTO activities (type, message, meta_json, created_at) VALUES (?, ?, ?, NOW())');
    $stmt->execute([$type, $message, json_encode($meta, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
}

<?php
$cfg = require __DIR__ . '/../config/config.php';
$d = $cfg['database'];
$pdo = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $d['host'], $d['port'], $d['name']),
    $d['username'], $d['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 10]);
$pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
echo "[start] schema ok\n";

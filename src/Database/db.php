<?php
use Dotenv\Dotenv;

require_once __DIR__ . '/../../vendor/autoload.php';

// .env dosyasını oku
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// .env'den ayarları al
$host = $_ENV['DB_HOST'];
$db   = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode([
        'status' => 'error',
        'message' => 'Veritabanı bağlantı hatası',
        'error' => $e->getMessage()
    ]));
}

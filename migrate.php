<?php

// Load environment variables
require_once __DIR__ . '/core/Env.php';
Env::load(__DIR__ . '/.env');

$host   = Env::get('DB_HOST',     'localhost');
$dbName = Env::get('DB_NAME',     'cbelms');
$user   = Env::get('DB_USERNAME', 'root');
$pass   = Env::get('DB_PASSWORD', '');

try {
    $db = new PDO("mysql:host={$host};dbname={$dbName}", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = file_get_contents(__DIR__ . '/database/pathway_schema.sql');
    $db->exec($sql);
    echo "Pathway Schema Migration successful.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

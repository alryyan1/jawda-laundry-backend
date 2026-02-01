<?php

$host = '127.0.0.1';
$db   = 'radhi';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Get column info
    $stmt = $pdo->query("DESCRIBE services");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    file_put_contents('columns.txt', implode(",", $columns));

    // Get sample data
    $stmt = $pdo->query("SELECT * FROM services LIMIT 1");
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    file_put_contents('sample_data.txt', print_r($data, true));
} catch (\PDOException $e) {
    file_put_contents('error.txt', $e->getMessage());
}

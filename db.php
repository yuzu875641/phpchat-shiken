<?php
// データベース接続設定
$dsn = 'mysql:host=localhost;dbname=your_database_name;charset=utf8';
$username = 'your_db_username';
$password = 'your_db_password';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("データベース接続エラー: " . $e->getMessage());
}
?>

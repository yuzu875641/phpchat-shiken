<?php
// 環境変数からSupabaseの接続URIとサービスキーを取得
$conn_uri = getenv('DATABASE_URL');
$service_key = getenv('SUPABASE_SERVICE_KEY');

// 環境変数が設定されているか確認
if ($conn_uri === false || $service_key === false) {
    die("エラー: データベース接続に必要な環境変数が設定されていません。");
}

// データベース接続URIを解析し、PDOの引数を生成
// これにより、ホスト名、ポート、データベース名などを個別に取得
$url = parse_url($conn_uri);

if ($url === false) {
    die("エラー: DATABASE_URLの形式が不正です。");
}

// Supabaseへの接続文字列を再構築
// パスワードをサービスキーに置き換え、ユーザー名を "service_role" に設定
$dsn = "pgsql:host={$url['host']};port={$url['port']};dbname={$url['path']};sslmode=require";
$username = "service_role";
$password = $service_key;

try {
    // データベースにPDOで接続
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // 接続失敗時に詳細なエラーを出力
    die("データベース接続エラー: " . $e->getMessage());
}
?>

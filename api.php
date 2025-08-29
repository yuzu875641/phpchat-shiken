<?php

// クロスオリジンリソース共有（CORS）を許可するヘッダー
// これにより、異なるドメインからのリクエストも受け入れ可能になる
header('Access-Control-Allow-Origin: *');
// レスポンスの形式をJSONに設定
header('Content-Type: application/json');

// HTTPリクエストのメソッドが 'POST' かどうかをチェック
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // リクエストボディからJSONデータを取得
    $data = json_decode(file_get_contents('php://input'), true);

    // ユーザーIDとコンテンツがリクエストに含まれているか検証
    if (!isset($data['user_id']) || !isset($data['content'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'User ID and content are required.']);
        exit;
    }

    $user_id = $data['user_id'];
    $content = $data['content'];

    try {
        // データベース接続ファイルを読み込む
        require_once('db.php');

        // メッセージを挿入するためのプリペアドステートメントを準備
        $sql = "INSERT INTO messages (user_id, content) VALUES (:user_id, :content)";
        $stmt = $pdo->prepare($sql);

        // SQLインジェクションを防ぐためにパラメータを安全にバインド
        $stmt->bindValue(':user_id', $user_id);
        $stmt->bindValue(':content', $content);

        // SQLステートメントを実行
        $stmt->execute();

        // 成功した場合は、201 Created ステータスコードを返す
        http_response_code(201);
        // レスポンスボディは空のままでも良いが、今回は成功メッセージを返す
        echo json_encode(['status' => 'success']);

    } catch (PDOException $e) {
        // データベース関連のエラーが発生した場合
        http_response_code(500); // Internal Server Error
        error_log("Database Error: " . $e->getMessage()); // エラーログに記録
        echo json_encode(['error' => 'Database operation failed.']);
    } catch (Exception $e) {
        // その他の予期せぬエラーが発生した場合
        http_response_code(500); // Internal Server Error
        error_log("General Error: " . $e->getMessage()); // エラーログに記録
        echo json_encode(['error' => 'An unexpected error occurred.']);
    }

} else {
    // POST以外のメソッドが使われた場合、405 Method Not Allowed を返す
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
}

?>

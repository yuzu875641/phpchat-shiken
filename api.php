<?php
require 'db.php';

header('Content-Type: application/json');

// リクエストパスの解析
$request_uri = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
$api_path = array_slice($request_uri, 1);

// APIキーの検証 (本サイト用)
$is_trusted_client = false;
if (isset($_SERVER['HTTP_X_API_KEY']) && $_SERVER['HTTP_X_API_KEY'] === '0209') {
    $is_trusted_client = true;
}

// レートリミットの適用
function checkRateLimit($ip) {
    // 実際にはRedisやデータベースで実装
    // ここでは概念的な説明
    $limit = 60;
    $interval = 60; // 60秒
    // このIPアドレスのリクエスト数を取得
    $requests = 0; // 実際には取得した値
    if ($requests >= $limit) {
        http_response_code(429);
        echo json_encode(['error' => 'Too Many Requests']);
        exit();
    }
}

// =======================
// メッセージ一覧取得エンドポイント
// GET /api/messages
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($api_path[1]) && $api_path[1] === 'messages') {
    try {
        $stmt = $pdo->prepare("
            SELECT
                m.id,
                m.content,
                u.username,
                u.id AS user_id,
                u.role,
                m.created_at
            FROM
                messages m
            JOIN
                users u ON m.user_id = u.id
            ORDER BY
                m.created_at ASC
        ");
        $stmt->execute();
        $messages = $stmt->fetchAll();
        echo json_encode($messages);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'サーバーエラー']);
    }
    exit();
}

// =======================
// メッセージ投稿エンドポイント
// POST /api/messages
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($api_path[1]) && $api_path[1] === 'messages') {
    if (!$is_trusted_client) {
        checkRateLimit($_SERVER['REMOTE_ADDR']);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    $content = $data['content'] ?? '';

    // 認証
    $stmt = $pdo->prepare("SELECT id, password_hash, role, message_count FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || hash('sha256', $password) !== $user['password_hash']) {
        http_response_code(401);
        echo json_encode(['error' => '認証失敗']);
        exit();
    }

    $user_id = $user['id'];
    $role = $user['role'];
    $message_count = $user['message_count'];

    // 権限ごとの投稿ルール
    $errors = [];
    if ($role === 'speaker' && $message_count >= 20) {
        $errors[] = '投稿メッセージ数が20件を超えています。';
    }
    if ($role === 'moderator' && mb_strlen($content) > 100) {
        $errors[] = 'モデレーターの投稿は100文字以内です。';
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['error' => implode(', ', $errors)]);
        exit();
    }

    // メッセージの保存
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO messages (user_id, content) VALUES (?, ?)");
        $stmt->execute([$user_id, $content]);

        // スピーカーの場合、メッセージカウントを更新
        if ($role === 'speaker') {
            $stmt = $pdo->prepare("UPDATE users SET message_count = message_count + 1 WHERE id = ?");
            $stmt->execute([$user_id]);
        }

        $pdo->commit();
        http_response_code(201);
        echo json_encode(['message' => '投稿成功']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => '投稿失敗']);
    }
    exit();
}

// 404 Not Found
http_response_code(404);
echo json_encode(['error' => 'API Not Found']);
?>

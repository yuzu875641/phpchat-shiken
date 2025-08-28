<?php

// 環境変数を読み込む（ローカル開発用）
if (file_exists(__DIR__ . '/.env')) {
    $env_vars = parse_ini_file(__DIR__ . '/.env');
    foreach ($env_vars as $key => $value) {
        putenv("$key=$value");
    }
}

// データベース接続
try {
    $db_connection_string = getenv('DB_CONNECTION_STRING');
    $pdo = new PDO($db_connection_string);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB接続エラー: " . $e->getMessage());
}

// メッセージ投稿処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['username']) && isset($_POST['message'])) {
        $username = trim($_POST['username']);
        $message = trim($_POST['message']);

        if (!empty($username) && !empty($message)) {
            $stmt = $pdo->prepare("INSERT INTO messages (username, message) VALUES (?, ?)");
            $stmt->execute([$username, $message]);

            // ユーザー名をCookieに保存（有効期限は7日間）
            setcookie('username', $username, time() + (86400 * 7), "/");
            
            // 投稿後にリダイレクト
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

// メッセージ一覧を取得
$stmt = $pdo->query("SELECT id, username, message, created_at FROM messages ORDER BY created_at DESC");
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>phpbbs</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <div class="chat-form">
            <form action="index.php" method="post" id="chatForm">
                <input type="text" name="username" id="usernameInput" placeholder="ユーザー名" required value="<?= htmlspecialchars($_COOKIE['username'] ?? '') ?>">
                <textarea name="message" id="messageInput" placeholder="メッセージ内容" required></textarea>
                <button type="submit">送信</button>
            </form>
        </div>
        <div class="chat-list" id="chatList">
            <?php foreach ($messages as $message): ?>
                <div class="message">
                    <div class="message-header">
                        <span class="message-id">No.<?= htmlspecialchars($message['id']) ?></span>
                        <span class="message-username"><?= htmlspecialchars($message['username']) ?></span>
                        <span class="message-time"><?= date('Y/m/d H:i:s', strtotime($message['created_at'])) ?></span>
                    </div>
                    <div class="message-body">
                        <?= nl2br(htmlspecialchars($message['message'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>

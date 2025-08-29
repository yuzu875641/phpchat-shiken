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

// 投稿内容がコマンドか解析する関数
function parseCommand($content) {
    preg_match('/^\/(\w+)\s+@\(?(\w+)\)?/', $content, $matches);
    if (count($matches) === 3) {
        return ['command' => $matches[1], 'target_id' => $matches[2]];
    }
    return null;
}

// ユーザーの権限を取得する関数
function getUserRole($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    return $user['role'] ?? null;
}

// ユーザーの投稿を処理するメインロジック (例)
// この部分は、投稿処理を行うAPIスクリプトなどに組み込んで使用します
function handlePost($senderId, $content) {
    $command_data = parseCommand($content);
    
    // 投稿がコマンドではない場合、通常通り処理
    if (!$command_data) {
        // 通常のメッセージ投稿処理...
        return ['status' => 'success', 'message' => '通常メッセージ投稿成功'];
    }

    $command = $command_data['command'];
    $target_id = $command_data['target_id'];
    $sender_role = getUserRole($senderId);

    // 実行者の権限をチェックし、コマンドを実行
    try {
        $update_role = null;
        $is_allowed = false;

        switch ($command) {
            case 'moderator':
                if (in_array($sender_role, ['summit', 'admin'])) {
                    $update_role = 'moderator';
                    $is_allowed = true;
                }
                break;
            case 'summit':
                if ($sender_role === 'admin') {
                    $update_role = 'summit';
                    $is_allowed = true;
                }
                break;
            case 'admin':
                if ($sender_role === 'admin') {
                    $update_role = 'admin';
                    $is_allowed = true;
                }
                break;
            case 'kill':
                if ($sender_role === 'admin') {
                    $update_role = 'speaker';
                    $is_allowed = true;
                }
                break;
            default:
                // 未知のコマンド、何もしない
                return ['status' => 'error', 'message' => '無効なコマンドです。'];
        }

        if ($is_allowed) {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$update_role, $target_id]);
            return ['status' => 'success', 'message' => 'コマンド実行成功'];
        } else {
            return ['status' => 'error', 'message' => 'コマンドを実行する権限がありません。'];
        }
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => 'コマンド実行中にエラーが発生しました。'];
    }
}
?>

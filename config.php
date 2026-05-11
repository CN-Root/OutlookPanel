<?php
session_start();
date_default_timezone_set('Asia/Shanghai');

$db_dir = __DIR__ . '/data';
$db_file = $db_dir . '/outlook.db';

if (!is_dir($db_dir)) @mkdir($db_dir, 0777, true);

try {
    $db = new PDO("sqlite:$db_file");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 自动建表（保持不变）
    $db->exec("CREATE TABLE IF NOT EXISTS admin (id INTEGER PRIMARY KEY, username TEXT, password TEXT)");
    $db->exec("CREATE TABLE IF NOT EXISTS settings (key TEXT PRIMARY KEY, value TEXT)");
    $db->exec("CREATE TABLE IF NOT EXISTS accounts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT,
        password TEXT,
        client_id TEXT,
        refresh_token TEXT,
        client_secret TEXT,
        remark TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 初始化管理员：只有没有任何账号时才插入默认账号
    $admin_count = $db->query("SELECT COUNT(*) FROM admin")->fetchColumn();
    if ($admin_count == 0) {
        $pwd = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
        $stmt->execute(['admin', $pwd]);
    }

    // 初始化设置：使用 INSERT OR IGNORE 防止重复
    $db->exec("INSERT OR IGNORE INTO settings (key, value) VALUES ('tg_token', ''), ('tg_chatid', '')");

} catch (PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}

/**
 * 权限检查函数
 * 增加对 Ajax 请求的兼容
 */
function check_auth() {
    if (!isset($_SESSION['logged_in'])) {
        // 如果是 Ajax 请求（API调用），返回 403 错误码
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['error' => 'Login expired']);
            exit;
        }
        // 普通页面请求，跳转回登录页
        header('Location: index.php');
        exit;
    }
}
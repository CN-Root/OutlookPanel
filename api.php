<?php
require 'config.php';
check_auth();
global $db;

$action = $_GET['action'] ?? '';

// 账号列表
if ($action === 'list') {
    echo json_encode($db->query("SELECT * FROM accounts ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// 添加账号
if ($action === 'add') {
    $raw = trim($_POST['raw_data'] ?? '');
    $p = explode('----', $raw);
    if (count($p) >= 4) {
        $email = trim($p[0]);
        $pass  = trim($p[1]);
        $cid   = trim($p[2]);
        $token = trim($p[3]); 

        $stmt = $db->prepare("INSERT INTO accounts (email,password,client_id,refresh_token,remark) VALUES (?,?,?,?,?)");
        $stmt->execute([$email, $pass, $cid, $token, $_POST['remark']]);
        echo json_encode(['status'=>'success']);
        exit;
    }
    exit;
}

// 更新账号信息 (核心修复：解决编辑备注无效)
if ($action === 'update_account') {
    $id = (int)($_POST['id'] ?? 0);
    $email = trim($_POST['email'] ?? '');
    $pass  = trim($_POST['password'] ?? '');
    $cid   = trim($_POST['client_id'] ?? '');
    $token = trim($_POST['refresh_token'] ?? '');
    $remark = $_POST['remark'] ?? '';

    if ($id > 0) {
        $stmt = $db->prepare("UPDATE accounts SET email=?, password=?, client_id=?, refresh_token=?, remark=? WHERE id=?");
        $stmt->execute([$email, $pass, $cid, $token, $remark, $id]);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ID无效']);
    }
    exit;
}

/**
 * 核心辅助函数：提取并解码邮件正文
 * 处理 Base64, Quoted-Printable 以及 GBK 编码
 */
function extract_message_body($part) {
    // 分割头部和正文（邮件协议中头部和正文由第一个空行分隔）
    $parts = preg_split('/\r?\n\r?\n/', $part, 2);
    if (count($parts) < 2) return '';
    
    $header = $parts[0];
    $content = trim($parts[1]);

    // 1. 处理传输编码 (Transfer-Encoding)
    if (preg_match('/Content-Transfer-Encoding:\s*base64/i', $header)) {
        $content = base64_decode(str_replace(["\r", "\n"], '', $content));
    } elseif (preg_match('/Content-Transfer-Encoding:\s*quoted-printable/i', $header)) {
        $content = quoted_printable_decode($content);
    }

    // 2. 处理字符集编码 (Charset)
    if (preg_match('/charset="?([^"\r\n;]+)"?/i', $header, $charMat)) {
        $charset = strtoupper($charMat[1]);
        if ($charset !== 'UTF-8' && $charset !== 'US-ASCII') {
            $content = mb_convert_encoding($content, 'UTF-8', $charset);
        }
    }
    
    return $content;
}

// =============================================================================
// 获取邮件逻辑 - 已修复 SEARCH 报错与乱码
// =============================================================================
if ($action === 'get_mails') {
    $id = (int)$_GET['id'];
    $acc = $db->query("SELECT * FROM accounts WHERE id=$id")->fetch(PDO::FETCH_ASSOC);
    if (!$acc) {
        echo json_encode(['value' => []]);
        exit;
    }

    $email         = trim($acc['email']);
    $client_id     = trim($acc['client_id']);
    $refresh_token = trim($acc['refresh_token']);

    // 刷新 AccessToken
    $data = [
        'client_id'     => $client_id,
        'grant_type'    => 'refresh_token',
        'refresh_token' => $refresh_token
    ];

    $ch = curl_init('https://login.microsoftonline.com/consumers/oauth2/v2.0/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data)
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    $res = json_decode($res, true);

    if (empty($res['access_token'])) {
        echo json_encode(['error' => 'Token失效，请重新获取账号']);
        exit;
    }

    // 更新 Token
    if (!empty($res['refresh_token']) && $res['refresh_token'] != $refresh_token) {
        $db->prepare("UPDATE accounts SET refresh_token=? WHERE id=?")
            ->execute([$res['refresh_token'], $id]);
    }

    $access_token = $res['access_token'];
    $messages = [];
    $hosts = ['outlook.live.com', 'outlook.office365.com'];

    foreach ($hosts as $host) {
        $sock = fsockopen('ssl://' . $host, 993, $errno, $errstr, 10);
        if (!$sock) continue;

        fgets($sock, 1024);
        $auth_str = base64_encode("user=$email\x01auth=Bearer $access_token\x01\x01");
        fputs($sock, "A01 AUTHENTICATE XOAUTH2 $auth_str\r\n");
        $login = fgets($sock, 1024);
        if (!str_contains($login, 'A01 OK')) { fclose($sock); continue; }

        fputs($sock, "A02 SELECT INBOX\r\n");
        while ($line = fgets($sock, 1024)) { if (str_contains($line, 'A02 OK')) break; }

        // --- 修复 SEARCH 部分，防止 Undefined variable $ids ---
        fputs($sock, "A03 SEARCH ALL\r\n");
        $ids = [];
        while ($line = fgets($sock, 4096)) {
            $line = trim($line);
            if (preg_match('/^\* SEARCH (.*)/i', $line, $match)) {
                $ids = array_filter(explode(' ', trim($match[1])));
            }
            if (str_contains($line, 'A03 OK')) break;
        }
        
        if (empty($ids)) { fclose($sock); continue; }

        rsort($ids, SORT_NUMERIC);
        $ids = array_slice($ids, 0, 15); // 获取最近15封

        foreach ($ids as $mid) {
            fputs($sock, "A04 FETCH $mid (RFC822)\r\n");
            $rawMail = '';
            while ($line = fgets($sock, 16384)) {
                $rawMail .= $line;
                // IMAP 响应结束标识
                if (preg_match('/^A04 OK/m', $line)) break;
            }

            // 提取主题和发件人
            preg_match('/^Subject:\s*(.*?)$/im', $rawMail, $subjMat);
            $subject = isset($subjMat[1]) ? iconv_mime_decode(trim($subjMat[1]), 0, 'UTF-8') : '无主题';

            preg_match('/^From:\s*(.*?)$/im', $rawMail, $fromMat);
            $from = isset($fromMat[1]) ? iconv_mime_decode(trim($fromMat[1]), 0, 'UTF-8') : '未知发件人';

            // 处理 Boundary
            preg_match('/boundary="?([^"\r\n;]+)"?/is', $rawMail, $bndMat);
            $boundary = $bndMat[1] ?? '';

            $body = '';
            if ($boundary) {
                $parts = explode("--$boundary", $rawMail);
                // 优先取 HTML
                foreach ($parts as $part) {
                    if (stripos($part, 'Content-Type: text/html') !== false) {
                        $body = extract_message_body($part);
                        break;
                    }
                }
                // 没 HTML 取纯文本
                if (empty($body)) {
                    foreach ($parts as $part) {
                        if (stripos($part, 'Content-Type: text/plain') !== false) {
                            $body = extract_message_body($part);
                            break;
                        }
                    }
                }
            } else {
                // 非多段格式
                $body = extract_message_body($rawMail);
            }

            $messages[] = [
                'subject' => $subject,
                'from'    => ['emailAddress' => ['name' => $from]],
                'body'    => ['content' => $body]
            ];
        }

        fclose($sock);
        if (!empty($messages)) break;
    }

    echo json_encode(['value' => $messages]);
    exit;
}

// -------------------------- 保持不变的辅助功能 --------------------------
if ($action === 'delete') {
    $id = $_POST['id'];
    $db->prepare("DELETE FROM accounts WHERE id=?")->execute([$id]);
    echo json_encode(['status'=>'success']);
    exit;
}

if ($action === 'save_settings') {
    // 1. 处理 TG 设置 (更新 settings 表)
    foreach(['tg_token', 'tg_chatid'] as $k){
        if(isset($_POST[$k])){
            $db->prepare("UPDATE settings SET value=? WHERE key=?")->execute([$_POST[$k], $k]);
        }
    }
    
    // 2. 处理管理员用户名 (更新 admin 表)
    if (!empty($_POST['admin_user'])) {
        // 假设你要修改 id 为 1 的那个管理员（即默认管理员）
        $db->prepare("UPDATE admin SET username=? WHERE id=1")->execute([$_POST['admin_user']]);
    }

    // 3. 处理管理员密码 (更新 admin 表)
    if (!empty($_POST['admin_pass'])) {
        $new_pass = password_hash($_POST['admin_pass'], PASSWORD_DEFAULT);
        $db->prepare("UPDATE admin SET password=? WHERE id=1")->execute([$new_pass]);
    }

    echo json_encode(['status'=>'success']);
    exit;
}

if ($action === 'import') {
    if(isset($_FILES['file'])){
        $data = json_decode(file_get_contents($_FILES['file']['tmp_name']),true);
        if($data){
            foreach($data as $i){
                $db->prepare("INSERT INTO accounts (email,password,client_id,refresh_token,remark) VALUES (?,?,?,?,?)")
                    ->execute([$i['email'],$i['password'],$i['client_id'],$i['refresh_token'],$i['remark']]);
            }
        }
    }
    echo json_encode(['status'=>'success']);
    exit;
}

if ($action === 'export') {
    $data = $db->query("SELECT * FROM accounts")->fetchAll(PDO::FETCH_ASSOC);
    
    // 生成文件名，带上当前日期
    $filename = "outlook_accounts_" . date('Ymd_His') . ".json";
    
    // 核心：设置响应头强制浏览器弹出下载框
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'get_settings') {
    // 获取 TG 设置
    $q = $db->query("SELECT * FROM settings");
    $data = [];
    while ($r = $q->fetch()) $data[$r['key']] = $r['value'];
    
    // 获取当前管理员用户名 (从 admin 表)
    $admin = $db->query("SELECT username FROM admin WHERE id=1")->fetch();
    $data['admin_user'] = $admin['username'] ?? 'admin';
    
    echo json_encode($data);
    exit;
}
// 退出登录
if ($action === 'logout') {
    session_start();
    $_SESSION = array(); // 清空所有 Session 变量
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-42000, '/');
    }
    session_destroy();
    header('Location: login.php'); // 退出后跳转回登录页
    exit;
}

// TG 备份功能
if ($action === 'tg_backup') {
    // 1. 获取所有账号数据
    $stmt = $db->query("SELECT * FROM accounts ORDER BY id DESC");
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $json_data = json_encode($accounts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    // 2. 获取 TG 配置
    $q = $db->query("SELECT * FROM settings");
    $settings = [];
    while ($r = $q->fetch()) $settings[$r['key']] = $r['value'];

    $token = trim($settings['tg_token'] ?? '');
    $chat_id = trim($settings['tg_chatid'] ?? '');

    if (empty($token) || empty($chat_id)) {
        echo json_encode(['status' => 'error', 'message' => '请先在设置中配置 TG Token 和 ChatID']);
        exit;
    }

    // 3. 发送 JSON 文件到 Telegram (推荐发送文件，因为账号多了会触发消息长度限制)
    // 创建临时文件
    $tmpFile = tempnam(sys_get_temp_dir(), 'backup_');
    file_put_contents($tmpFile, $json_data);
    
    $url = "https://api.telegram.org/bot{$token}/sendDocument";
    $post_data = [
        'chat_id' => $chat_id,
        'document' => new CURLFile($tmpFile, 'application/json', 'outlook_backup_' . date('Ymd_His') . '.json'),
        'caption' => "📅 Outlook Panel 自动备份\n⏰ 时间: " . date('Y-m-d H:i:s') . "\n👤 账号总数: " . count($accounts)
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post_data,
        CURLOPT_SSL_VERIFYPEER => false, // 如果服务器证书有问题，可跳过
        CURLOPT_TIMEOUT => 30
    ]);

    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    
    // 删除临时文件
    @unlink($tmpFile);

    if ($err) {
        echo json_encode(['status' => 'error', 'message' => 'CURL 错误: ' . $err]);
    } else {
        $res_arr = json_decode($res, true);
        if ($res_arr && $res_arr['ok']) {
            echo json_encode(['status' => 'success', 'message' => '备份已发送至 Telegram']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'TG 返回错误: ' . ($res_arr['description'] ?? '未知')]);
        }
    }
    exit;
}
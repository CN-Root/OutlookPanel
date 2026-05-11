<?php
require 'config.php';

// 如果已经登录，直接跳转到主面板
if (isset($_SESSION['logged_in'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['user'] ?? '';
    $pass = $_POST['pass'] ?? '';

    $stmt = $db->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$user]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($pass, $admin['password'])) {
        $_SESSION['logged_in'] = true;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = '账号或密码不正确';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - Outlook 管理后台</title>
    <style>
        body { 
            background: #F9FAFB; 
            font-family: -apple-system, "Noto Sans SC", sans-serif; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 100vh; 
            margin: 0; 
        }
        .login-card { 
            background: #FFFFFF; 
            padding: 40px; 
            border-radius: 16px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); 
            width: 100%;
            max-width: 320px; 
        }
        h2 { 
            margin: 0 0 24px; 
            font-weight: 600; 
            text-align: center; 
            color: #111; 
            letter-spacing: -0.5px;
        }
        input { 
            width: 100%; 
            padding: 12px; 
            margin-bottom: 15px; 
            border: 1px solid #E5E7EB; 
            border-radius: 8px; 
            box-sizing: border-box; 
            outline: none; 
            transition: border-color 0.2s; 
            font-size: 14px;
        }
        input:focus { border-color: #000; }
        button { 
            width: 100%; 
            padding: 12px; 
            background: #000; 
            color: #FFF; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-weight: 500; 
            font-size: 14px;
            transition: opacity 0.2s;
        }
        button:hover { opacity: 0.8; }
        .error { 
            color: #EF4444; 
            font-size: 13px; 
            margin-bottom: 15px; 
            text-align: center; 
            background: #FEF2F2;
            padding: 8px;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>Outlook Panel</h2>
        <?php if($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
        <form method="POST">
            <input type="text" name="user" placeholder="用户名" required>
            <input type="password" name="pass" placeholder="密码" required>
            <button type="submit">登录系统</button>
        </form>
    </div>
</body>
</html>
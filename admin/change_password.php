<?php
/**
 * 作者：走小散
 * 微信公众号：走小散
 */
require_once '../config/config.php';
require_once 'check_login.php';

$db = Database::getInstance()->getConnection();
$error = '';
$success = '';

// 确保settings表存在
try {
    $db->exec("CREATE TABLE IF NOT EXISTS `settings` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `setting_key` varchar(100) NOT NULL COMMENT '设置键名',
      `setting_value` text COMMENT '设置值',
      `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `setting_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch(PDOException $e) {
    // 表已存在，忽略错误
}

function getSetting($db, $key, $default = '') {
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : $default;
}

function setSetting($db, $key, $value) {
    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute([$key, $value]);
}

// 获取网站名称
$display_site_name = getSetting($db, 'site_name', SITE_NAME);

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($old_password)) {
        $error = '请输入原密码';
    } elseif (empty($new_password)) {
        $error = '请输入新密码';
    } elseif (strlen($new_password) < 6) {
        $error = '新密码长度至少6位';
    } elseif ($new_password !== $confirm_password) {
        $error = '两次输入的新密码不一致';
    } else {
        // 验证原密码
        $saved_password = getSetting($db, 'admin_password', '');
        $admin_password = !empty($saved_password) ? $saved_password : ADMIN_PASSWORD;
        
        if ($old_password !== $admin_password) {
            $error = '原密码错误';
        } else {
            // 更新密码
            setSetting($db, 'admin_password', $new_password);
            $success = '密码修改成功！';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>修改密码 - <?php echo htmlspecialchars($display_site_name); ?></title>
    <link rel="stylesheet" href="assets/admin.css">
    <style>
        .change-password-form {
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 500px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input[type="password"]:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .help-text {
            color: #666;
            font-size: 12px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1><?php echo htmlspecialchars($display_site_name); ?> - 管理后台</h1>
        <div class="admin-actions">
            <span>欢迎，<?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            <a href="logout.php" class="btn btn-secondary">退出</a>
        </div>
    </div>
    
    <div class="admin-container">
        <div class="admin-sidebar">
            <nav>
                <a href="system_settings.php">系统设置</a>
                <a href="index.php">项目管理</a>
                <a href="nav_menu.php">导航菜单设置</a>
                <a href="change_password.php" class="active">修改密码</a>
                <a href="card_management.php">名片管理</a>
            </nav>
        </div>
        
        <div class="admin-content">
            <div class="page-header">
                <h2>修改密码</h2>
            </div>
            
            <?php if ($error): ?>
                <div style="background: #fee; color: #c33; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div style="background: #efe; color: #3c3; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <div class="change-password-form">
                <form method="POST">
                    <div class="form-group">
                        <label for="old_password">原密码 *</label>
                        <input type="password" id="old_password" name="old_password" required autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">新密码 *</label>
                        <input type="password" id="new_password" name="new_password" required>
                        <div class="help-text">密码长度至少6位</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">确认新密码 *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">修改密码</button>
                        <a href="index.php" class="btn btn-secondary" style="margin-left: 10px;">取消</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>


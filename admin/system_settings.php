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

// 获取当前设置
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

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    // 处理logo上传
    $logo_path = getSetting($db, 'site_logo', '');
    
    if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/logo/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file = $_FILES['site_logo'];
        require_once '../config/security.php';
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $max_size = 2 * 1024 * 1024;
        
        $validation = validateFileUpload($file, $allowed_types, $max_size);
        if (!$validation['success']) {
            $error = $validation['error'];
        } else {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
            if (!in_array($extension, $allowed_ext)) {
                $error = '只支持 JPG、PNG、GIF、WebP 和 SVG 格式的图片';
            } else {
                $filename = 'logo_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
                $upload_path = $upload_dir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    if (!empty($logo_path) && file_exists('../' . $logo_path)) {
                        @unlink('../' . $logo_path);
                    }
                    $logo_path = 'uploads/logo/' . $filename;
                    setSetting($db, 'site_logo', $logo_path);
                    $success = 'Logo上传成功！';
                } else {
                    $error = 'Logo上传失败，请检查目录权限';
                }
            }
        }
    }
    
    // 更新网站名称
    if (!isset($error) || empty($error)) {
        $site_name = trim($_POST['site_name'] ?? '');
        // 如果为空，使用默认值
        if ($site_name === '') {
            $site_name = SITE_NAME;
        }
        // 保存到数据库
        setSetting($db, 'site_name', $site_name);
        // 立即更新当前页面的显示值
        $_SESSION['site_name_updated'] = true;
        if (empty($success)) {
            $success = '设置保存成功！';
        }
        
        // 更新版权信息
        $copyright_info = trim($_POST['copyright_info'] ?? '');
        setSetting($db, 'copyright_info', $copyright_info);
        
        // 更新密码
        if (!empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
            if ($_POST['new_password'] === $_POST['confirm_password']) {
                $new_password = trim($_POST['new_password']);
                if (strlen($new_password) >= 6) {
                    setSetting($db, 'admin_password', $new_password);
                    if (empty($success)) {
                        $success = '设置保存成功！';
                    }
                } else {
                    $error = '密码长度至少6位';
                }
            } else {
                $error = '两次输入的密码不一致';
            }
        }
    }
}

// 获取当前设置值（保存后立即刷新）
$site_name = getSetting($db, 'site_name', SITE_NAME);
$site_logo = getSetting($db, 'site_logo', '');
$copyright_info = getSetting($db, 'copyright_info', '');

// 清除更新标记
if (isset($_SESSION['site_name_updated'])) {
    unset($_SESSION['site_name_updated']);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统设置 - <?php echo htmlspecialchars($site_name); ?></title>
    <link rel="stylesheet" href="assets/admin.css">
    <style>
        .settings-form {
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 30px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .form-group input[type="text"] {
            width: 100%;
            max-width: 500px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .logo-upload-area {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            margin-top: 10px;
        }
        
        .logo-preview {
            width: 150px;
            height: 150px;
            border: 2px dashed #ddd;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #f9f9f9;
        }
        
        .logo-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .logo-preview-text {
            color: #999;
            text-align: center;
            padding: 20px;
        }
        
        .logo-upload-controls {
            flex: 1;
        }
        
        .file-upload-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .file-upload-wrapper input[type="file"] {
            position: absolute;
            left: -9999px;
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .file-upload-label {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
            border: none;
        }
        
        .file-upload-label:hover {
            background: #2980b9;
        }
        
        .file-upload-label:active {
            background: #21618c;
        }
        
        .file-name {
            margin-left: 10px;
            color: #666;
            font-size: 14px;
        }
        
        .logo-upload-controls .help-text {
            color: #666;
            font-size: 12px;
            margin-top: 10px;
        }
        
        .btn-remove-logo {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .btn-remove-logo:hover {
            background: #c0392b;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1><?php echo htmlspecialchars($site_name); ?> - 管理后台</h1>
        <div class="admin-actions">
            <span>欢迎，<?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            <a href="logout.php" class="btn btn-secondary">退出</a>
        </div>
    </div>
    
    <div class="admin-container">
        <div class="admin-sidebar">
            <nav>
                <a href="system_settings.php" class="active">系统设置</a>
                <a href="index.php">项目管理</a>
                <a href="nav_menu.php">导航菜单设置</a>
                <a href="change_password.php">修改密码</a>
                <a href="card_management.php">名片管理</a>
            </nav>
        </div>
        
        <div class="admin-content">
            <div class="page-header">
                <h2>系统设置</h2>
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
            
            <div class="settings-form">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <div class="form-group">
                        <label for="site_name">网站名称</label>
                        <input type="text" id="site_name" name="site_name" value="<?php echo htmlspecialchars($site_name); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>网站Logo</label>
                        <div class="logo-upload-area">
                            <div class="logo-preview">
                                <?php if (!empty($site_logo) && file_exists('../' . $site_logo)): ?>
                                    <img src="../<?php echo htmlspecialchars($site_logo); ?>" alt="Logo预览">
                                <?php else: ?>
                                    <div class="logo-preview-text">暂无Logo<br><small>请上传图片</small></div>
                                <?php endif; ?>
                            </div>
                            <div class="logo-upload-controls">
                                <div class="file-upload-wrapper">
                                    <input type="file" name="site_logo" accept="image/*" id="logo_upload">
                                    <label for="logo_upload" class="file-upload-label">📁 选择图片</label>
                                    <span class="file-name" id="file_name">未选择文件</span>
                                </div>
                                <div class="help-text">
                                    支持 JPG、PNG、GIF、WebP、SVG 格式，最大 2MB
                                </div>
                                <?php if (!empty($site_logo)): ?>
                                    <button type="button" class="btn-remove-logo" onclick="removeLogo()">删除Logo</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="copyright_info">版权信息</label>
                        <textarea id="copyright_info" name="copyright_info" rows="4" style="width: 100%; max-width: 500px; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; font-family: inherit;"><?php echo htmlspecialchars($copyright_info); ?></textarea>
                        <div class="help-text" style="color: #666; font-size: 12px; margin-top: 5px;">
                            支持HTML标签，将在前端侧边栏底部显示
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">保存设置</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // 预览上传的图片和显示文件名
        document.getElementById('logo_upload').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const fileName = document.getElementById('file_name');
            
            if (file) {
                fileName.textContent = file.name;
                fileName.style.color = '#333';
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.querySelector('.logo-preview');
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Logo预览">';
                };
                reader.readAsDataURL(file);
            } else {
                fileName.textContent = '未选择文件';
                fileName.style.color = '#666';
            }
        });
        
        function removeLogo() {
            if (confirm('确定要删除Logo吗？')) {
                // 通过AJAX删除logo
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="remove_logo">';
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>

<?php
// 处理删除logo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_logo') {
    $current_logo = getSetting($db, 'site_logo', '');
    if (!empty($current_logo) && file_exists('../' . $current_logo)) {
        @unlink('../' . $current_logo);
    }
    setSetting($db, 'site_logo', '');
    header('Location: system_settings.php');
    exit;
}
?>


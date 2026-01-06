<?php
/**
 * 作者：走小散
 * 微信公众号：走小散
 */
require_once '../config/config.php';
require_once 'check_login.php';
require_once 'functions.php';

$db = Database::getInstance()->getConnection();
$error = '';
$success = '';

// 获取网站名称
function getSetting($db, $key, $default = '') {
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : $default;
}
$display_site_name = getSetting($db, 'site_name', SITE_NAME);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (empty($name)) {
        $error = '项目名称不能为空';
    } else {
        // 自动生成slug（日期+序号格式）
        $slug = generateSlug($db, 'projects');
        
        try {
            $stmt = $db->prepare("INSERT INTO projects (name, description, slug) VALUES (?, ?, ?)");
            $stmt->execute([$name, $description, $slug]);
            $project_id = $db->lastInsertId();
            header('Location: project_edit.php?id=' . $project_id);
            exit;
        } catch(PDOException $e) {
            error_log("Project creation failed: " . $e->getMessage());
            $error = '创建项目失败，请稍后重试';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新建项目 - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/admin.css">
    <script>
        // URL标识预览提示
        document.addEventListener('DOMContentLoaded', function() {
            const slugPreview = document.getElementById('slug-preview');
            if (slugPreview) {
                const today = new Date();
                const dateStr = today.getFullYear() + 
                    String(today.getMonth() + 1).padStart(2, '0') + 
                    String(today.getDate()).padStart(2, '0');
                slugPreview.textContent = '保存时将自动生成（格式：' + dateStr + '-001）';
            }
        });
    </script>
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
                <a href="change_password.php">修改密码</a>
                <a href="card_management.php">名片管理</a>
            </nav>
        </div>
        
        <div class="admin-content">
            <div class="page-header">
                <h2>新建项目</h2>
                <a href="index.php" class="btn btn-secondary">返回列表</a>
            </div>
            
            <?php if ($error): ?>
                <div style="background: #fee; color: #c33; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div style="background: white; padding: 30px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <form method="POST">
                    <div class="form-group">
                        <label>项目名称 *</label>
                        <input type="text" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>URL标识</label>
                        <div style="padding: 10px; background: #f5f5f5; border-radius: 5px; color: #666;">
                            <span id="slug-preview">保存时将自动生成（格式：日期-序号，如：20250101-001）</span>
                        </div>
                        <small style="color: #999; margin-top: 5px; display: block;">URL标识将自动生成，格式为：今天的日期+序号</small>
                    </div>
                    
                    <div class="form-group">
                        <label>项目描述</label>
                        <textarea name="description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-primary">创建项目</button>
                        <a href="index.php" class="btn btn-secondary">取消</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>


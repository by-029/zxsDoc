<?php
/**
 * 作者：走小散
 * 微信公众号：走小散
 */
require_once '../config/config.php';
require_once 'check_login.php';
require_once 'functions.php';

$db = Database::getInstance()->getConnection();
$project_id = $_GET['id'] ?? 0;
$error = '';

// 获取网站名称
function getSetting($db, $key, $default = '') {
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : $default;
}
$display_site_name = getSetting($db, 'site_name', SITE_NAME);

// 获取项目信息
$stmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

if (!$project) {
    header('Location: index.php');
    exit;
}

// 更新项目信息
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_project') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (empty($name)) {
        $error = '项目名称不能为空';
    } else {
        // 编辑时保持原slug不变
        $slug = $project['slug'];
        
        try {
            $stmt = $db->prepare("UPDATE projects SET name = ?, description = ?, slug = ? WHERE id = ?");
            $stmt->execute([$name, $description, $slug, $project_id]);
            header('Location: project_edit.php?id=' . $project_id);
            exit;
        } catch(PDOException $e) {
            error_log("Project update failed: " . $e->getMessage());
            $error = '更新失败，请稍后重试';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑项目 - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/admin.css">
    <link rel="stylesheet" href="assets/custom-styles.css">
    <style>
        .project-tabs {
            display: flex;
            border-bottom: 2px solid #ddd;
            margin-bottom: 20px;
        }
        .project-tabs a {
            padding: 12px 24px;
            text-decoration: none;
            color: #666;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
        }
        .project-tabs a.active {
            color: #3498db;
            border-bottom-color: #3498db;
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
                <a href="change_password.php">修改密码</a>
                <a href="card_management.php">名片管理</a>
            </nav>
        </div>
        
        <div class="admin-content">
            <div class="page-header">
                <h2>编辑项目: <?php echo htmlspecialchars($project['name']); ?></h2>
                <a href="index.php" class="btn btn-secondary">返回列表</a>
            </div>
            
            <?php if ($error): ?>
                <div style="background: #fee; color: #c33; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="project-tabs">
                <a href="project_edit.php?id=<?php echo $project_id; ?>" class="active">项目信息</a>
                <a href="chapter_list.php?project_id=<?php echo $project_id; ?>">文档管理</a>
            </div>
            
            <div style="background: white; padding: 30px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <form method="POST">
                    <input type="hidden" name="action" value="update_project">
                    
                    <div class="form-group">
                        <label>项目名称 *</label>
                        <input type="text" name="name" required value="<?php echo htmlspecialchars($project['name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>URL标识</label>
                        <div style="padding: 10px; background: #f5f5f5; border-radius: 5px;">
                            <a href="<?php echo chapterUrl($project['slug'], ''); ?>" target="_blank" style="color: #3498db; text-decoration: none; font-weight: 500;">
                                <?php echo htmlspecialchars($project['slug']); ?>
                                <span style="margin-left: 8px; font-size: 12px;">↗</span>
                            </a>
                        </div>
                        <small style="color: #999; margin-top: 5px; display: block;">点击链接可查看前端页面，编辑时URL标识保持不变</small>
                    </div>
                    
                    <div class="form-group">
                        <label>项目描述</label>
                        <textarea name="description"><?php echo htmlspecialchars($project['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">保存更改</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>


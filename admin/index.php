<?php
/**
 * 作者：走小散
 * 微信公众号：走小散
 */
require_once '../config/config.php';
require_once 'check_login.php';
require_once 'functions.php';

$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT * FROM projects ORDER BY created_at DESC");
$projects = $stmt->fetchAll();

// 获取网站名称
function getSetting($db, $key, $default = '') {
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : $default;
}
$display_site_name = getSetting($db, 'site_name', SITE_NAME);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>项目管理 - <?php echo htmlspecialchars($display_site_name); ?></title>
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo time(); ?>">
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
                <a href="index.php" class="active">项目管理</a>
                <a href="nav_menu.php">导航菜单设置</a>
                <a href="change_password.php">修改密码</a>
                <a href="card_management.php">名片管理</a>
            </nav>
        </div>
        
        <div class="admin-content">
            <div class="page-header">
                <h2>项目管理</h2>
                <a href="project_add.php" class="btn btn-primary">+ 新建项目</a>
            </div>
            
            <div class="project-list">
                <?php if (empty($projects)): ?>
                    <div class="empty-state">
                        <p>还没有项目，<a href="project_add.php">创建第一个项目</a></p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>项目名称</th>
                                <th>URL标识</th>
                                <th>创建时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $display_index = 1;
                            foreach ($projects as $project): ?>
                                <tr>
                                    <td><?php echo $display_index++; ?></td>
                                    <td>
                                        <a href="project_edit.php?id=<?php echo $project['id']; ?>" class="project-name-link">
                                            <img src="../img/xm.png" alt="" class="project-name-icon">
                                            <?php echo htmlspecialchars($project['name']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($project['slug']); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($project['created_at'])); ?></td>
                                    <td>
                                        <a href="project_edit.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-primary">编辑</a>
                                        <a href="../index.php?project=<?php echo $project['slug']; ?>" target="_blank" class="btn btn-sm btn-secondary">查看</a>
                                        <a href="project_delete.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('确定要删除这个项目吗？')">删除</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>


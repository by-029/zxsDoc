<?php
/**
 * 作者：走小散
 * 微信公众号：走小散
 */
require_once '../config/config.php';
require_once 'check_login.php';

$db = Database::getInstance()->getConnection();
$project_id = $_GET['project_id'] ?? 0;
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

// 获取章节列表（树形结构）
function getChaptersTree($db, $project_id, $parent_id = 0) {
    $stmt = $db->prepare("SELECT * FROM chapters WHERE project_id = ? AND parent_id = ? ORDER BY `order` ASC, id ASC");
    $stmt->execute([$project_id, $parent_id]);
    $chapters = $stmt->fetchAll();
    
    foreach ($chapters as &$chapter) {
        $chapter['children'] = getChaptersTree($db, $project_id, $chapter['id']);
    }
    
    return $chapters;
}

$chapters_tree = getChaptersTree($db, $project_id);

// 渲染章节列表（文档大纲样式）
function renderChapters($chapters, $db, $project_id, $project_slug, $level = 0) {
    if (empty($chapters)) {
        echo '<div class="empty-chapter-state">';
        echo '<div class="empty-chapter-icon"><img src="../img/xm.png" alt=""></div>';
        echo '<h3 class="empty-chapter-title">还没有章节</h3>';
        echo '<p class="empty-chapter-description">开始创建您的第一个章节吧</p>';
        echo '<a href="chapter_add.php?project_id=' . $project_id . '" class="btn btn-primary empty-chapter-btn">+ 创建第一个章节</a>';
        echo '</div>';
        return;
    }
    
    echo '<div class="chapter-outline-tree">';
    foreach ($chapters as $chapter) {
        $has_children = !empty($chapter['children']);
        echo '<div class="outline-item" data-level="' . $level . '">';
        echo '<div class="outline-item-content">';
        
        // 缩进线
        if ($level > 0) {
            echo '<div class="outline-indent-lines">';
            for ($i = 0; $i < $level; $i++) {
                echo '<div class="outline-indent-line"></div>';
            }
            echo '</div>';
        }
        
        // 展开/折叠按钮（如果有子项）
        if ($has_children) {
            echo '<button class="outline-toggle" onclick="toggleOutlineItem(this)">▶</button>';
        } else {
            echo '<span class="outline-spacer"></span>';
        }
        
        // 章节标题和操作
        echo '<div class="outline-title-wrapper">';
        echo '<span class="outline-title">' . htmlspecialchars($chapter['title']) . '</span>';
        echo '<div class="outline-actions">';
        echo '<a href="chapter_edit.php?id=' . $chapter['id'] . '" class="outline-action-btn" title="编辑"><img src="../img/bj.png" alt="编辑" style="width: 16px; height: 16px;"></a>';
        echo '<a href="chapter_add.php?project_id=' . $project_id . '&parent_id=' . $chapter['id'] . '" class="outline-action-btn" title="添加子章节"><img src="../img/j.png" alt="添加子章节" style="width: 16px; height: 16px;"></a>';
        echo '<a href="' . chapterUrl($project_slug, $chapter['slug']) . '" target="_blank" class="outline-action-btn" title="预览"><img src="../img/yl.png" alt="预览" style="width: 16px; height: 16px;"></a>';
        echo '<a href="chapter_delete.php?id=' . $chapter['id'] . '" class="outline-action-btn outline-action-danger" title="删除" onclick="return confirm(\'确定要删除这个章节吗？删除后无法恢复。\')"><img src="../img/sc.png" alt="删除" style="width: 16px; height: 16px;"></a>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
        
        // 子章节
        if ($has_children) {
            echo '<div class="outline-children">';
            renderChapters($chapter['children'], $db, $project_id, $project_slug, $level + 1);
            echo '</div>';
        }
        
        echo '</div>';
    }
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文档管理 - <?php echo htmlspecialchars($project['name']); ?> - <?php echo SITE_NAME; ?></title>
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
        /* 文档大纲样式 */
        .chapter-outline-tree {
            background: white;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .outline-item {
            margin-bottom: 2px;
        }
        .outline-item-content {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 4px;
            transition: background 0.2s;
            position: relative;
        }
        .outline-item-content:hover {
            background: #f5f5f5;
        }
        .outline-item-content:hover .outline-actions {
            opacity: 1;
        }
        .outline-indent-lines {
            display: flex;
            align-items: center;
            margin-right: 8px;
        }
        .outline-indent-line {
            width: 1px;
            height: 100%;
            background: #e0e0e0;
            margin-right: 20px;
        }
        .outline-toggle {
            background: none;
            border: none;
            cursor: pointer;
            padding: 2px 6px;
            font-size: 10px;
            color: #666;
            transition: transform 0.2s;
            margin-right: 4px;
            flex-shrink: 0;
        }
        .outline-toggle.expanded {
            transform: rotate(90deg);
        }
        .outline-spacer {
            width: 18px;
            flex-shrink: 0;
        }
        .outline-title-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-width: 0;
        }
        .outline-title {
            font-size: 14px;
            color: #333;
            font-weight: 500;
            flex: 1;
            min-width: 0;
        }
        .outline-actions {
            display: flex;
            gap: 4px;
            opacity: 0;
            transition: opacity 0.2s;
            flex-shrink: 0;
            margin-left: 8px;
        }
        .outline-action-btn {
            padding: 4px 6px;
            text-decoration: none;
            font-size: 12px;
            border-radius: 3px;
            transition: background 0.2s;
            display: inline-block;
        }
        .outline-action-btn:hover {
            background: #e0e0e0;
        }
        .outline-action-danger:hover {
            background: #fee;
        }
        .outline-children {
            margin-left: 20px;
            display: none;
        }
        .outline-children.expanded {
            display: block;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 5px;
            color: #999;
        }
        .empty-state a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }
        .empty-state a:hover {
            text-decoration: underline;
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
                <h2>文档管理: <?php echo htmlspecialchars($project['name']); ?></h2>
                <div>
                    <a href="project_edit.php?id=<?php echo $project_id; ?>" class="btn btn-secondary">返回项目</a>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div style="background: #fee; color: #c33; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="project-tabs">
                <a href="project_edit.php?id=<?php echo $project_id; ?>">项目信息</a>
                <a href="chapter_list.php?project_id=<?php echo $project_id; ?>" class="active">文档管理</a>
            </div>
            
            <div style="background: white; padding: 30px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0;">章节列表</h3>
                    <a href="chapter_add.php?project_id=<?php echo $project_id; ?>" class="btn btn-primary">+ 新建章节</a>
                </div>
                
                <?php renderChapters($chapters_tree, $db, $project_id, $project['slug']); ?>
            </div>
        </div>
    </div>
    <script>
        // 默认展开所有章节
        document.addEventListener('DOMContentLoaded', function() {
            const allChildren = document.querySelectorAll('.outline-children');
            const allToggles = document.querySelectorAll('.outline-toggle');
            allChildren.forEach(child => {
                child.classList.add('expanded');
            });
            allToggles.forEach(toggle => {
                toggle.classList.add('expanded');
            });
        });
        
        function toggleOutlineItem(button) {
            const outlineItem = button.closest('.outline-item');
            const children = outlineItem.querySelector('.outline-children');
            if (children) {
                children.classList.toggle('expanded');
                button.classList.toggle('expanded');
            }
        }
    </script>
</body>
</html>


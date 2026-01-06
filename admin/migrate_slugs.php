<?php
/**
 * 作者：走小散
 * 微信公众号：走小散
 * 
 * 迁移脚本：更新所有章节的slug为层级格式
 * 使用方法：在浏览器中访问 admin/migrate_slugs.php
 * 
 * 注意：执行此脚本前请备份数据库！
 */

require_once '../config/config.php';
require_once 'check_login.php';

$db = Database::getInstance()->getConnection();
$error = '';
$success = '';
$updated_count = 0;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    try {
        $db->beginTransaction();
        
        // 获取所有项目
        $stmt = $db->query("SELECT * FROM projects ORDER BY id ASC");
        $projects = $stmt->fetchAll();
        
        foreach ($projects as $project) {
            // 获取项目的根级别章节（parent_id = 0）
            $stmt = $db->prepare("SELECT * FROM chapters WHERE project_id = ? AND parent_id = 0 ORDER BY `order` ASC, id ASC");
            $stmt->execute([$project['id']]);
            $root_chapters = $stmt->fetchAll();
            
            // 处理每个根级别章节（递归处理子章节）
            foreach ($root_chapters as $chapter) {
                updateChapterSlug($db, $chapter, $project['slug'], 0, $updated_count, $errors);
            }
        }
        
        $db->commit();
        $success = "成功更新 {$updated_count} 个章节的slug！";
    } catch (Exception $e) {
        $db->rollBack();
        $error = "更新失败: " . $e->getMessage();
    }
}

/**
 * 递归更新章节slug
 * @param PDO $db 数据库连接
 * @param array $chapter 章节信息
 * @param string $project_slug 项目slug
 * @param int $parent_id 父章节ID
 * @param int &$updated_count 更新计数器（引用传递）
 * @param array &$errors 错误数组（引用传递）
 */
function updateChapterSlug($db, $chapter, $project_slug, $parent_id, &$updated_count, &$errors) {
    $new_slug = '';
    
    if ($parent_id == 0) {
        // 根级别章节：项目slug-序号
        // 按照order和id排序，找到当前章节在同级章节中的位置
        $stmt = $db->prepare("SELECT id FROM chapters WHERE project_id = ? AND parent_id = 0 ORDER BY `order` ASC, id ASC");
        $stmt->execute([$chapter['project_id']]);
        $siblings = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $counter = array_search($chapter['id'], $siblings) + 1;
        $new_slug = $project_slug . '-' . str_pad($counter, 3, '0', STR_PAD_LEFT);
    } else {
        // 子章节：父章节slug-序号
        // 获取父章节的slug
        $stmt = $db->prepare("SELECT slug FROM chapters WHERE id = ?");
        $stmt->execute([$parent_id]);
        $parent_chapter = $stmt->fetch();
        
        if (!$parent_chapter) {
            $errors[] = "章节 ID {$chapter['id']} 的父章节 ID {$parent_id} 不存在";
            return;
        }
        
        // 按照order和id排序，找到当前章节在同级子章节中的位置
        $stmt = $db->prepare("SELECT id FROM chapters WHERE project_id = ? AND parent_id = ? ORDER BY `order` ASC, id ASC");
        $stmt->execute([$chapter['project_id'], $parent_id]);
        $siblings = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $counter = array_search($chapter['id'], $siblings) + 1;
        $new_slug = $parent_chapter['slug'] . '-' . str_pad($counter, 3, '0', STR_PAD_LEFT);
    }
    
    // 检查slug是否已存在（排除当前章节）
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM chapters WHERE project_id = ? AND slug = ? AND id != ?");
    $stmt->execute([$chapter['project_id'], $new_slug, $chapter['id']]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        // 如果slug已存在，添加后缀
        $original_slug = $new_slug;
        $suffix = 1;
        while (true) {
            $new_slug = $original_slug . '-' . $suffix;
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM chapters WHERE project_id = ? AND slug = ? AND id != ?");
            $stmt->execute([$chapter['project_id'], $new_slug, $chapter['id']]);
            $result = $stmt->fetch();
            if ($result['count'] == 0) {
                break;
            }
            $suffix++;
        }
    }
    
    // 更新slug
    try {
        $stmt = $db->prepare("UPDATE chapters SET slug = ? WHERE id = ?");
        $stmt->execute([$new_slug, $chapter['id']]);
        $updated_count++;
    } catch (Exception $e) {
        $errors[] = "更新章节 ID {$chapter['id']} 失败: " . $e->getMessage();
    }
    
    // 递归处理子章节
    $stmt = $db->prepare("SELECT * FROM chapters WHERE project_id = ? AND parent_id = ? ORDER BY `order` ASC, id ASC");
    $stmt->execute([$chapter['project_id'], $chapter['id']]);
    $children = $stmt->fetchAll();
    
    foreach ($children as $child) {
        updateChapterSlug($db, $child, $project_slug, $chapter['id'], $updated_count, $errors);
    }
}

// 获取所有章节的当前slug信息（用于预览）
$preview_data = [];
try {
    $stmt = $db->query("SELECT c.*, p.slug as project_slug, p.name as project_name FROM chapters c JOIN projects p ON c.project_id = p.id ORDER BY c.project_id ASC, c.parent_id ASC, c.`order` ASC, c.id ASC");
    $all_chapters = $stmt->fetchAll();
    
    foreach ($all_chapters as $chapter) {
        if (!isset($preview_data[$chapter['project_id']])) {
            $preview_data[$chapter['project_id']] = [
                'project_name' => $chapter['project_name'],
                'project_slug' => $chapter['project_slug'],
                'chapters' => []
            ];
        }
        $preview_data[$chapter['project_id']]['chapters'][] = $chapter;
    }
} catch (Exception $e) {
    $error = "获取章节信息失败: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>迁移章节Slug - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/admin.css">
    <style>
        .migrate-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .warning-box h3 {
            margin-top: 0;
            color: #856404;
        }
        .preview-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .preview-table th,
        .preview-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .preview-table th {
            background: #f5f5f5;
            font-weight: 600;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .success-box {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            color: #155724;
        }
        .error-box {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            color: #721c24;
        }
        .error-list {
            margin-top: 10px;
        }
        .error-list li {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>迁移章节Slug</h1>
        <a href="index.php" class="btn btn-secondary">返回</a>
    </div>
    
    <div class="migrate-container">
        <div class="warning-box">
            <h3>⚠️ 警告</h3>
            <ul>
                <li>此操作将更新所有章节的slug为层级格式（如：项目slug-001-001）</li>
                <li><strong>请务必在执行前备份数据库！</strong></li>
                <li>执行后，旧的URL将失效，需要更新所有外部链接</li>
                <li>此操作不可逆，请谨慎操作</li>
            </ul>
        </div>
        
        <?php if ($success): ?>
            <div class="success-box">
                <h3>✅ 成功</h3>
                <p><?php echo htmlspecialchars($success); ?></p>
                <?php if (!empty($errors)): ?>
                    <div class="error-list">
                        <h4>部分错误：</h4>
                        <ul>
                            <?php foreach ($errors as $err): ?>
                                <li><?php echo htmlspecialchars($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error-box">
                <h3>❌ 错误</h3>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>
        
        <h2>当前章节Slug预览</h2>
        <?php foreach ($preview_data as $project_id => $project_data): ?>
            <h3>项目：<?php echo htmlspecialchars($project_data['project_name']); ?> (<?php echo htmlspecialchars($project_data['project_slug']); ?>)</h3>
            <table class="preview-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>标题</th>
                        <th>当前Slug</th>
                        <th>父章节ID</th>
                        <th>排序</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($project_data['chapters'] as $chapter): ?>
                        <tr>
                            <td><?php echo $chapter['id']; ?></td>
                            <td><?php echo htmlspecialchars($chapter['title']); ?></td>
                            <td><code><?php echo htmlspecialchars($chapter['slug']); ?></code></td>
                            <td><?php echo $chapter['parent_id']; ?></td>
                            <td><?php echo $chapter['order']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>
        
        <form method="POST" onsubmit="return confirm('确定要执行迁移吗？请确保已备份数据库！');">
            <button type="submit" name="confirm" value="1" class="btn-danger">执行迁移</button>
        </form>
    </div>
</body>
</html>


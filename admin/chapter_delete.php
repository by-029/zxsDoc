<?php
/**
 * 作者：走小散
 * 微信公众号：走小散
 */
require_once '../config/config.php';
require_once 'check_login.php';

$db = Database::getInstance()->getConnection();
$chapter_id = $_GET['id'] ?? 0;

if ($chapter_id) {
    // 获取章节信息
    $stmt = $db->prepare("SELECT project_id FROM chapters WHERE id = ?");
    $stmt->execute([$chapter_id]);
    $chapter = $stmt->fetch();
    
    if ($chapter) {
        // 删除子章节
        function deleteChildren($db, $parent_id) {
            $stmt = $db->prepare("SELECT id FROM chapters WHERE parent_id = ?");
            $stmt->execute([$parent_id]);
            $children = $stmt->fetchAll();
            
            foreach ($children as $child) {
                deleteChildren($db, $child['id']);
                $stmt = $db->prepare("DELETE FROM chapters WHERE id = ?");
                $stmt->execute([$child['id']]);
            }
        }
        
        deleteChildren($db, $chapter_id);
        
        // 删除章节本身
        $stmt = $db->prepare("DELETE FROM chapters WHERE id = ?");
        $stmt->execute([$chapter_id]);
        
        header('Location: project_edit.php?id=' . $chapter['project_id']);
        exit;
    }
}

header('Location: index.php');
exit;


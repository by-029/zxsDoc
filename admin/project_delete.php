<?php
/**
 * 作者：走小散
 * 微信公众号：走小散
 */
require_once '../config/config.php';
require_once 'check_login.php';

$db = Database::getInstance()->getConnection();
$project_id = $_GET['id'] ?? 0;

if ($project_id) {
    // 删除项目下的所有章节
    $stmt = $db->prepare("DELETE FROM chapters WHERE project_id = ?");
    $stmt->execute([$project_id]);
    
    // 删除项目
    $stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
}

header('Location: index.php');
exit;


<?php
/**
 * 作者：走小散
 * 微信公众号：走小散
 */
/**
 * 自动生成URL标识（slug）
 * 格式：层级slug-序号（三位数）
 * - 根级别章节：project-slug-001
 * - 子章节：parent-slug-001
 * - 子章节的子章节：parent-slug-001-001
 * @param PDO $db 数据库连接
 * @param string $table 表名（'chapters' 或 'projects'）
 * @param int|null $project_id 项目ID（仅用于章节）
 * @param int|null $parent_id 父章节ID（仅用于章节，0表示根级别）
 */
function generateSlug($db, $table = 'chapters', $project_id = null, $parent_id = null) {
    $base_slug_prefix = '';
    $counter_query_condition = '';
    $unique_check_condition = '';
    $params = [];

    if ($table === 'chapters' && $project_id !== null) {
        // 获取项目slug作为基础前缀
        $stmt_project = $db->prepare("SELECT slug FROM projects WHERE id = ?");
        $stmt_project->execute([$project_id]);
        $project_slug_data = $stmt_project->fetch();
        $project_slug = $project_slug_data ? $project_slug_data['slug'] : '';

        if ($parent_id !== null && $parent_id > 0) {
            // 有父章节：获取父章节的slug作为前缀
            $stmt_parent = $db->prepare("SELECT slug FROM chapters WHERE id = ?");
            $stmt_parent->execute([$parent_id]);
            $parent_slug_data = $stmt_parent->fetch();
            $parent_slug = $parent_slug_data ? $parent_slug_data['slug'] : '';
            $base_slug_prefix = $parent_slug;
            
            // 查找同父章节下已有的子章节数量（用于生成序号）
            $stmt_count = $db->prepare("SELECT COUNT(*) as count FROM chapters WHERE project_id = ? AND parent_id = ?");
            $stmt_count->execute([$project_id, $parent_id]);
            $count_result = $stmt_count->fetch();
            $counter = ($count_result['count'] ?? 0) + 1;
            
            $counter_query_condition = " AND project_id = ? AND parent_id = ?";
            $unique_check_condition = " AND project_id = ? AND parent_id = ?";
            $params = [$project_id, $parent_id];
        } else {
            // 根级别章节：使用项目slug作为前缀
            $base_slug_prefix = $project_slug;
            
            // 查找同项目下根级别章节的数量（用于生成序号）
            $stmt_count = $db->prepare("SELECT COUNT(*) as count FROM chapters WHERE project_id = ? AND parent_id = 0");
            $stmt_count->execute([$project_id]);
            $count_result = $stmt_count->fetch();
            $counter = ($count_result['count'] ?? 0) + 1;
            
            $counter_query_condition = " AND project_id = ? AND parent_id = 0";
            $unique_check_condition = " AND project_id = ? AND parent_id = 0";
            $params = [$project_id];
        }
    } else if ($table === 'projects') {
        // 项目本身，使用日期作为前缀
        $base_slug_prefix = date('Ymd');
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM projects WHERE slug LIKE ?");
        $like_pattern = $base_slug_prefix . '-%';
        $stmt->execute([$like_pattern]);
        $result = $stmt->fetch();
        $counter = ($result['count'] ?? 0) + 1;
        
        $counter_query_condition = "";
        $unique_check_condition = "";
        $params = [];
    } else {
        // Fallback or error
        return uniqid('slug-');
    }

    // 生成slug：前缀-序号（三位数）
    $slug = $base_slug_prefix . '-' . str_pad($counter, 3, '0', STR_PAD_LEFT);

    // 确保slug唯一
    $original_slug = $slug;
    $suffix = 1;
    while (true) {
        if ($table === 'chapters' && $project_id !== null) {
            $stmt_check_unique = $db->prepare("SELECT COUNT(*) as count FROM chapters WHERE slug = ? $unique_check_condition");
            $check_params = array_merge([$slug], $params);
            $stmt_check_unique->execute($check_params);
        } else if ($table === 'projects') {
            $stmt_check_unique = $db->prepare("SELECT COUNT(*) as count FROM projects WHERE slug = ?");
            $stmt_check_unique->execute([$slug]);
        } else {
            break;
        }
        
        $result_check_unique = $stmt_check_unique->fetch();
        if ($result_check_unique['count'] == 0) {
            break;
        }
        $slug = $original_slug . '-' . $suffix;
        $suffix++;
    }
    
    return $slug;
}

/**
 * 确保slug唯一性（如果已存在，添加数字后缀）
 */
function ensureUniqueSlug($db, $slug, $table, $id = null) {
    $original_slug = $slug;
    $counter = 1;
    
    while (true) {
        if ($id) {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM $table WHERE slug = ? AND id != ?");
            $stmt->execute([$slug, $id]);
        } else {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM $table WHERE slug = ?");
            $stmt->execute([$slug]);
        }
        
        $result = $stmt->fetch();
        if ($result['count'] == 0) {
            break;
        }
        
        $slug = $original_slug . '-' . $counter;
        $counter++;
    }
    
    return $slug;
}


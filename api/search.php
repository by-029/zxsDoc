<?php
/**
 * 作者：走小散
 * 微信公众号：走小散
 */
// 在文件最开始就关闭所有错误输出
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 0);

// 开启输出缓冲并立即清理
if (ob_get_level() > 0) {
    ob_end_clean();
}
ob_start();

// 设置JSON响应头
header('Content-Type: application/json; charset=utf-8');

// 清理所有输出
ob_clean();

// 使用输出缓冲捕获所有可能的输出
ob_start();

require_once '../config/config.php';

// 清理require_once可能产生的输出
$output = ob_get_clean();
if (!empty($output)) {
    // 如果有输出，清理掉
    ob_clean();
}

// 再次确保错误显示关闭
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 0);

// 输入验证和清理
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$project_slug = isset($_GET['project']) ? trim($_GET['project']) : '';

// 限制输入长度
if (strlen($query) > 200) {
    $query = substr($query, 0, 200);
}
if (strlen($project_slug) > 100) {
    $project_slug = substr($project_slug, 0, 100);
}

// 验证project_slug格式（只允许字母、数字、连字符）
if (!empty($project_slug) && !preg_match('/^[a-z0-9\-]+$/', $project_slug)) {
    ob_clean();
    echo json_encode(['results' => []], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit;
}

if (empty($query)) {
    ob_clean();
    echo json_encode(['results' => []], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['results' => [], 'error' => 'Database connection failed'], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit;
} catch (Throwable $e) {
    ob_clean();
    echo json_encode(['results' => [], 'error' => 'Database connection failed'], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit;
}

$is_global_search = empty($project_slug);

if ($is_global_search) {
    // 全站搜索：搜索所有项目的所有章节
    try {
        $stmt = $db->query("SELECT c.id, c.title, c.slug, c.parent_id, c.content, c.html_content, c.project_id, p.slug as project_slug, p.name as project_name FROM chapters c INNER JOIN projects p ON c.project_id = p.id ORDER BY c.project_id ASC, c.`order` ASC, c.id ASC");
        $all_chapters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        ob_clean();
        echo json_encode(['results' => [], 'error' => 'Query failed'], JSON_UNESCAPED_UNICODE);
        ob_end_flush();
        exit;
    } catch (Exception $e) {
        ob_clean();
        echo json_encode(['results' => [], 'error' => 'Error occurred'], JSON_UNESCAPED_UNICODE);
        ob_end_flush();
        exit;
    }
} else {
    // 项目内搜索：只搜索当前项目
    try {
        $stmt = $db->prepare("SELECT * FROM projects WHERE slug = ?");
        $stmt->execute([$project_slug]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$project) {
            ob_clean();
            echo json_encode(['results' => []], JSON_UNESCAPED_UNICODE);
            ob_end_flush();
            exit;
        }

        // 获取所有章节（包括内容）
        $stmt = $db->prepare("SELECT id, title, slug, parent_id, content, html_content, project_id FROM chapters WHERE project_id = ? ORDER BY `order` ASC, id ASC");
        $stmt->execute([$project['id']]);
        $all_chapters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Search API error: ' . $e->getMessage());
        ob_clean();
        echo json_encode(['results' => [], 'error' => 'Query failed'], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Exception $e) {
        error_log('Search API error: ' . $e->getMessage());
        ob_clean();
        echo json_encode(['results' => [], 'error' => 'Error occurred'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 为项目内搜索添加项目信息
    foreach ($all_chapters as &$chapter) {
        $chapter['project_slug'] = $project_slug;
        $chapter['project_name'] = $project['name'];
    }
}

// 构建章节路径映射
function buildChapterPath($chapters, $parent_map = [], $is_global = false) {
    $result = [];
    foreach ($chapters as $chapter) {
        $path = [$chapter['title']];
        $parent_id = $chapter['parent_id'];
        $project_id = $chapter['project_id'];
        
        // 构建父章节路径
        while ($parent_id > 0) {
            $found = false;
            foreach ($parent_map as $pid => $pchapter) {
                if ($pchapter['id'] == $parent_id && $pchapter['project_id'] == $project_id) {
                    array_unshift($path, $pchapter['title']);
                    $parent_id = $pchapter['parent_id'];
                    $found = true;
                    break;
                }
            }
            if (!$found) break;
        }
        
        $path_str = implode(' > ', $path);
        if ($is_global && isset($chapter['project_name'])) {
            $path_str = $chapter['project_name'] . ' > ' . $path_str;
        }
        
        $result_item = [
            'id' => $chapter['id'],
            'title' => $chapter['title'],
            'slug' => $chapter['slug'],
            'project_slug' => $chapter['project_slug'] ?? '',
            'path' => $path_str,
            'full_path' => $path
        ];
        
        // 如果存在项目名称，添加到结果中
        if (isset($chapter['project_name'])) {
            $result_item['project_name'] = $chapter['project_name'];
        }
        
        $result[] = $result_item;
    }
    return $result;
}

// 创建父章节映射（按项目分组）
$parent_map = [];
foreach ($all_chapters as $chapter) {
    $project_id = $chapter['project_id'];
    if (!isset($parent_map[$project_id])) {
        $parent_map[$project_id] = [];
    }
    $parent_map[$project_id][$chapter['id']] = $chapter;
}

// 构建路径
$chapters_with_path = [];
foreach ($all_chapters as $chapter) {
    $project_id = $chapter['project_id'];
    $project_parent_map = $parent_map[$project_id] ?? [];
    // 确保传递完整的章节数据（包括project_name）
    $chapter_with_path = buildChapterPath([$chapter], $project_parent_map, $is_global_search);
    if (!empty($chapter_with_path)) {
        $chapters_with_path[] = $chapter_with_path[0];
    }
}

// 搜索
$query_lower = mb_strtolower($query, 'UTF-8');
$results = [];

foreach ($chapters_with_path as $chapter) {
    $title_lower = mb_strtolower($chapter['title'], 'UTF-8');
    $path_lower = mb_strtolower($chapter['path'], 'UTF-8');
    
    // 搜索标题和路径
    $match_title = mb_strpos($title_lower, $query_lower) !== false;
    $match_path = mb_strpos($path_lower, $query_lower) !== false;
    
    // 搜索内容
    $match_content = false;
    $chapter_id = $chapter['id'];
    // 从原始章节数据中查找对应的章节
    foreach ($all_chapters as $original_chapter) {
        if ($original_chapter['id'] == $chapter_id) {
            if (isset($original_chapter['content']) && !empty(trim($original_chapter['content']))) {
                $content_lower = mb_strtolower(strip_tags($original_chapter['content']), 'UTF-8');
                $match_content = mb_strpos($content_lower, $query_lower) !== false;
            }
            if (!$match_content && isset($original_chapter['html_content']) && !empty(trim($original_chapter['html_content']))) {
                $html_content_lower = mb_strtolower(strip_tags($original_chapter['html_content']), 'UTF-8');
                $match_content = mb_strpos($html_content_lower, $query_lower) !== false;
            }
            break;
        }
    }
    
    if ($match_title || $match_path || $match_content) {
        $results[] = $chapter;
    }
}

// 限制结果数量
$results = array_slice($results, 0, 20);

// 确保输出缓冲区干净
ob_clean();
$json_output = json_encode(['results' => $results], JSON_UNESCAPED_UNICODE);
if ($json_output === false) {
    ob_clean();
    echo json_encode(['results' => [], 'error' => 'JSON encoding failed'], JSON_UNESCAPED_UNICODE);
} else {
    echo $json_output;
}
ob_end_flush();
exit;


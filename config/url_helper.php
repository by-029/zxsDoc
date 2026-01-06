<?php
/**
 * 作者：走小散
 * 微信公众号：走小散
 */
/**
 * URL生成辅助函数
 */

/**
 * 生成项目URL（伪静态）
 * @param string $project_slug 项目标识
 * @return string 项目URL
 */
function projectUrl($project_slug) {
    if (empty($project_slug)) {
        return BASE_URL;
    }
    // 移除BASE_URL末尾的斜杠，避免双斜杠
    $base = rtrim(BASE_URL, '/');
    return $base . '/' . $project_slug;
}

/**
 * 生成章节URL（伪静态）
 * 使用章节的完整slug（已包含层级信息，如：project-slug-001-001）
 * @param string $project_slug 项目标识
 * @param string $chapter_slug 章节标识（已包含层级信息）
 * @return string 章节URL
 */
function chapterUrl($project_slug, $chapter_slug) {
    if (empty($project_slug)) {
        return BASE_URL;
    }
    if (empty($chapter_slug)) {
        return projectUrl($project_slug);
    }
    
    // 移除BASE_URL末尾的斜杠，避免双斜杠
    $base = rtrim(BASE_URL, '/');
    return $base . '/' . $project_slug . '/' . $chapter_slug;
}

/**
 * 生成首页URL
 * @return string 首页URL
 */
function homeUrl() {
    return BASE_URL;
}


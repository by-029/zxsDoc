        <?php
/**
 * 作者：走小散
 * 微信公众号：走小散
 */
require_once 'config/config.php';

try {
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    die("数据库连接失败，请检查 config/database.php 中的配置");
}

// 支持伪静态URL和查询参数两种方式
// 伪静态格式：/project-slug 或 /project-slug/chapter-slug
// chapter-slug格式：project-slug-001-001（已包含层级信息）
// 查询参数格式：?project=xxx&chapter=xxx
$project_slug = $_GET['project'] ?? '';
$chapter_slug = $_GET['chapter'] ?? '';

// 如果chapter参数为空字符串（来自重写规则），设置为null
if ($chapter_slug === '') {
    $chapter_slug = null;
}

// 检查表是否存在
try {
    $db->query("SELECT 1 FROM projects LIMIT 1");
} catch (PDOException $e) {
    // 表不存在，显示安装提示
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>数据库未安装 - <?php echo SITE_NAME; ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Hiragino Sans GB', 'Microsoft YaHei', sans-serif;
                background: #f5f5f5;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                padding: 20px;
            }
            .install-prompt {
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                max-width: 600px;
                width: 100%;
            }
            h1 {
                color: #e74c3c;
                margin-bottom: 20px;
                font-size: 24px;
            }
            p {
                color: #666;
                line-height: 1.8;
                margin-bottom: 15px;
            }
            .code-block {
                background: #f8f9fa;
                border: 1px solid #e9ecef;
                border-radius: 5px;
                padding: 15px;
                margin: 15px 0;
                font-family: 'Courier New', monospace;
                font-size: 14px;
                overflow-x: auto;
            }
            .btn {
                display: inline-block;
                padding: 12px 24px;
                background: #3498db;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin-top: 20px;
                transition: background 0.3s;
            }
            .btn:hover {
                background: #2980b9;
            }
            .warning {
                background: #fff3cd;
                border: 1px solid #ffc107;
                color: #856404;
                padding: 15px;
                border-radius: 5px;
                margin: 15px 0;
            }
        </style>
    </head>
    <body>
        <div class="install-prompt">
            <h1>⚠️ 数据库未安装</h1>
            <p>检测到数据库表不存在，请先安装数据库。</p>
            
            <div class="warning">
                <strong>当前数据库：</strong><?php echo DB_NAME; ?><br>
                请确保已正确配置数据库连接信息（config/database.php）
            </div>
            
            <h2>安装步骤：</h2>
            <ol style="margin-left: 20px; line-height: 2;">
                <li>登录 MySQL 数据库</li>
                <li>选择数据库：<code><?php echo DB_NAME; ?></code></li>
                <li>执行安装脚本 <code>install/install.sql</code></li>
            </ol>
            
            <h3>方法一：使用命令行（推荐）</h3>
            <div class="code-block">
mysql -u <?php echo DB_USER; ?> -p <?php echo DB_NAME; ?> &lt; install/install_tables.sql
            </div>
            
            <h3>方法二：使用 phpMyAdmin</h3>
            <ol style="margin-left: 20px; line-height: 2; margin-bottom: 20px;">
                <li>登录 phpMyAdmin</li>
                <li>选择数据库 <code><?php echo DB_NAME; ?></code></li>
                <li>点击"SQL"标签</li>
                <li>复制下面框中的 SQL 代码并执行，或点击"下载安装脚本"按钮下载文件后导入</li>
            </ol>
            
            <p style="margin-top: 20px;">
                <a href="install/install_tables.sql" class="btn" download>下载安装脚本</a>
                <a href="admin/login.php" class="btn" style="background: #95a5a6; margin-left: 10px;">前往后台</a>
            </p>
            
            <h3 style="margin-top: 30px;">SQL 安装代码：</h3>
            <div class="code-block" style="max-height: 400px; overflow-y: auto;">
<?php
$sql_file = __DIR__ . '/install/install_tables.sql';
if (file_exists($sql_file)) {
    $sql_content = file_get_contents($sql_file);
    echo htmlspecialchars($sql_content);
} else {
    echo '-- 安装文件不存在，请检查 install/install_tables.sql 文件';
}
?>
            </div>
            
            <p style="margin-top: 30px; font-size: 12px; color: #999;">
                安装完成后，刷新此页面即可正常使用。
            </p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 获取系统设置
function getSetting($db, $key, $default = '') {
    try {
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch(PDOException $e) {
        return $default;
    }
}

// 获取网站名称和Logo
$site_name_setting = getSetting($db, 'site_name', '');
$site_logo_setting = getSetting($db, 'site_logo', '');
$display_site_name = !empty($site_name_setting) ? $site_name_setting : SITE_NAME;

// 获取项目信息
$project = null;
if ($project_slug) {
    $stmt = $db->prepare("SELECT * FROM projects WHERE slug = ?");
    $stmt->execute([$project_slug]);
    $project = $stmt->fetch();
}

// 如果没有指定项目，获取所有项目列表（用于首页显示）
$all_projects = [];
if (!$project) {
    try {
        $stmt = $db->query("SELECT * FROM projects ORDER BY id ASC");
        $all_projects = $stmt->fetchAll();
    } catch (PDOException $e) {
        // 查询失败，可能表不存在（但前面已经检查过，这里不应该到达）
        die("数据库错误，请检查数据库表是否已正确安装");
    }
}

// 获取章节树形结构
function getChaptersTree($db, $project_id, $parent_id = 0) {
    $stmt = $db->prepare("SELECT * FROM chapters WHERE project_id = ? AND parent_id = ? ORDER BY `order` ASC, id ASC");
    $stmt->execute([$project_id, $parent_id]);
    $chapters = $stmt->fetchAll();
    
    foreach ($chapters as &$chapter) {
        $chapter['children'] = getChaptersTree($db, $project_id, $chapter['id']);
    }
    
    return $chapters;
}

// 检查章节树中是否有活动的章节
function hasActiveChild($chapters, $current_slug) {
    foreach ($chapters as $ch) {
        if ($ch['slug'] === $current_slug) return true;
        if (!empty($ch['children']) && hasActiveChild($ch['children'], $current_slug)) return true;
    }
    return false;
}

// 只有当有项目时才获取章节树
$chapters_tree = [];
if ($project) {
    $chapters_tree = getChaptersTree($db, $project['id']);
}

// 获取当前章节（只有当有项目时才获取）
// 语雀模式：只获取实际可访问的子章节，父章节不能直接访问
$current_chapter = null;
if ($project) {
    if ($chapter_slug) {
        // 使用章节slug直接查找（slug已包含层级信息，全局唯一）
        $stmt = $db->prepare("SELECT * FROM chapters WHERE project_id = ? AND slug = ? LIMIT 1");
        $stmt->execute([$project['id'], $chapter_slug]);
        $current_chapter = $stmt->fetch();
        
        // 语雀模式：如果找到的章节是父章节（有子章节），重定向到第一个子章节
        // 但如果是子章节（没有子章节），则直接显示，不重定向
        if ($current_chapter) {
            // 检查这个章节是否有子章节（使用COUNT更高效）
            $stmt_check = $db->prepare("SELECT COUNT(*) as count FROM chapters WHERE project_id = ? AND parent_id = ?");
            $stmt_check->execute([$project['id'], $current_chapter['id']]);
            $has_children = $stmt_check->fetch()['count'] > 0;
            
            // 检查章节是否有内容
            $has_content = !empty(trim($current_chapter['content'] ?? '')) || !empty(trim($current_chapter['html_content'] ?? ''));
            
            // 只有父章节且没有内容时才重定向到第一个子章节
            // 如果父章节有内容，直接显示父章节的内容
            if ($has_children && !$has_content) {
                // 获取第一个子章节
                $stmt_first_child = $db->prepare("SELECT * FROM chapters WHERE project_id = ? AND parent_id = ? ORDER BY `order` ASC, id ASC LIMIT 1");
                $stmt_first_child->execute([$project['id'], $current_chapter['id']]);
                $first_child = $stmt_first_child->fetch();
                
                // 只有当第一个子章节存在且slug不同时才重定向（避免循环重定向）
                if ($first_child && $first_child['slug'] !== $chapter_slug) {
                    header('Location: ' . chapterUrl($project_slug, $first_child['slug']));
                    exit;
                }
            }
            // 如果有内容或没有子章节，说明是实际文档章节，直接显示，不重定向
        }
    } else {
        // 如果没有指定章节，获取第一个有内容的章节
        $first_chapter = findFirstContentChapterInTree($chapters_tree);
        if ($first_chapter) {
            $current_chapter = $first_chapter;
        }
    }
}

// 在整个章节树中查找第一个有内容的章节
function findFirstContentChapterInTree($chapters) {
    if (empty($chapters)) {
        return null;
    }
    
    foreach ($chapters as $chapter) {
        // 检查当前章节是否有内容
        $content_trimmed = isset($chapter['content']) ? trim($chapter['content']) : '';
        $html_content_trimmed = isset($chapter['html_content']) ? trim($chapter['html_content']) : '';
        $has_content = !empty($content_trimmed) || !empty($html_content_trimmed);
        
        // 如果当前章节有内容，直接返回
        if ($has_content) {
            return $chapter;
        }
        
        // 如果当前章节有子章节，递归查找子章节
        if (!empty($chapter['children'])) {
            $first_content_child = findFirstContentChapterInTree($chapter['children']);
            if ($first_content_child) {
                return $first_content_child;
            }
        }
    }
    
    return null;
}

function findFirstChapter($chapters) {
    if (empty($chapters)) {
        return null;
    }
    // 遍历所有章节，找到第一个实际可访问的章节（不是父章节）
    foreach ($chapters as $chapter) {
        // 如果这个章节有子章节，递归查找第一个子章节
        if (!empty($chapter['children'])) {
            $first_child = findFirstChapter($chapter['children']);
            if ($first_child) {
                return $first_child;
            }
        } else {
            // 如果没有子章节，这就是第一个可访问的章节
            return $chapter;
        }
    }
    return null;
}

// 查找指定章节的第一个有内容的子章节（用于导航）
function findFirstContentChapter($chapters_tree, $parent_id) {
    foreach ($chapters_tree as $chapter) {
        if ($chapter['id'] == $parent_id) {
            // 找到了父章节，查找它的第一个有内容的子章节
            if (!empty($chapter['children'])) {
                return findFirstContentChapterInChildren($chapter['children']);
            }
            return null;
        }
        if (!empty($chapter['children'])) {
            $found = findFirstContentChapter($chapter['children'], $parent_id);
            if ($found) {
                return $found;
            }
        }
    }
    return null;
}

// 在子章节中查找第一个有内容的章节
function findFirstContentChapterInChildren($children) {
    foreach ($children as $chapter) {
        $content_trimmed = isset($chapter['content']) ? trim($chapter['content']) : '';
        $html_content_trimmed = isset($chapter['html_content']) ? trim($chapter['html_content']) : '';
        $has_content = !empty($content_trimmed) || !empty($html_content_trimmed);
        
        if ($has_content) {
            return $chapter;
        }
        
        if (!empty($chapter['children'])) {
            $found = findFirstContentChapterInChildren($chapter['children']);
            if ($found) {
                return $found;
            }
        }
    }
    return null;
}

function getAllChaptersFlat($chapters) {
    $result = [];
    foreach ($chapters as $chapter) {
        // 检查章节是否有内容
        $content_trimmed = isset($chapter['content']) ? trim($chapter['content']) : '';
        $html_content_trimmed = isset($chapter['html_content']) ? trim($chapter['html_content']) : '';
        $has_content = !empty($content_trimmed) || !empty($html_content_trimmed);
        
        // 检查章节是否有子章节
        $has_children = !empty($chapter['children']);
        
        // 添加章节到导航列表的条件：
        // 1. 章节有内容（无论是否有子章节）
        // 2. 或者章节没有子章节（即使没有内容，也是可访问的章节）
        if ($has_content || !$has_children) {
            $result[] = $chapter;
        }
        
        // 无论父分类是否有内容，都递归处理子章节（保持层级顺序）
        if ($has_children) {
            $result = array_merge($result, getAllChaptersFlat($chapter['children']));
        }
    }
    return $result;
}

function getNextChapter($current_chapter, $chapters_tree) {
    if (!$current_chapter || empty($chapters_tree)) {
        return null;
    }
    
    $all_chapters = getAllChaptersFlat($chapters_tree);
    $current_index = -1;
    
    foreach ($all_chapters as $index => $chapter) {
        if ($chapter['id'] == $current_chapter['id']) {
            $current_index = $index;
            break;
        }
    }
    
    if ($current_index >= 0 && $current_index < count($all_chapters) - 1) {
        return $all_chapters[$current_index + 1];
    }
    
    return null;
}

// 清理 Editor.md 生成的 HTML（移除不需要的样式类和包装）
function cleanEditorMDHTML($html) {
    if (empty($html)) {
        return '';
    }
    
    // 移除所有 Editor.md 特有的包装 div
    $html = preg_replace('/<div[^>]*class="[^"]*markdown-body[^"]*"[^>]*>/i', '', $html);
    $html = preg_replace('/<div[^>]*class="[^"]*editormd-preview[^"]*"[^>]*>/i', '', $html);
    $html = preg_replace('/<div[^>]*class="[^"]*editormd-preview-container[^"]*"[^>]*>/i', '', $html);
    $html = preg_replace('/<div[^>]*class="[^"]*editormd[^"]*"[^>]*>/i', '', $html);
    
    // 移除所有结束的 div 标签（可能有多层包装）
    // 但保留内容中的 div
    // 简单处理：移除开头和结尾的 div 标签
    $html = preg_replace('/^<div[^>]*>/i', '', $html);
    $html = preg_replace('/<\/div>\s*$/i', '', $html);
    
    // 移除所有 style 属性（Editor.md 可能添加了内联样式）
    $html = preg_replace('/\s*style="[^"]*"/i', '', $html);
    
    // 移除所有 class 属性中的 Editor.md 相关类
    $html = preg_replace('/\s*class="[^"]*markdown-body[^"]*"/i', '', $html);
    $html = preg_replace('/\s*class="[^"]*editormd[^"]*"/i', '', $html);
    
    // 清理多余的空白和换行
    $html = trim($html);
    
    return $html;
}

// 生成目录锚点并处理HTML标题
function extractHeadings($html) {
    $headings = [];
    $counter = 0;
    
    // 先清理 HTML
    $html = cleanEditorMDHTML($html);
    
    // 为标题添加ID
    $html = preg_replace_callback('/<h([1-6])[^>]*>(.*?)<\/h[1-6]>/i', function($matches) use (&$headings, &$counter) {
        $level = (int)$matches[1];
        $text = strip_tags($matches[2]);
        $text_clean = trim($text);
        
        // 生成ID（使用标题文本的MD5和计数器）
        $id = 'heading-' . md5($text_clean . $counter) . '-' . $counter;
        $counter++;
        
        $headings[] = [
            'level' => $level,
            'text' => $text_clean,
            'id' => $id
        ];
        
        return '<h' . $level . ' id="' . $id . '">' . $matches[2] . '</h' . $level . '>';
    }, $html);
    
    return ['html' => $html, 'headings' => $headings];
}

$toc_headings = [];
$processed_html = '';
if ($current_chapter) {
    if (!empty($current_chapter['html_content'])) {
        $result = extractHeadings($current_chapter['html_content']);
        $toc_headings = $result['headings'];
        $processed_html = $result['html'];
    } else if (!empty($current_chapter['content'])) {
        require_once 'libs/Parsedown.php';
        $Parsedown = new Parsedown();
        $raw_html = $Parsedown->text($current_chapter['content']);
        $result = extractHeadings($raw_html);
        $toc_headings = $result['headings'];
        $processed_html = $result['html'];
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_chapter ? htmlspecialchars($current_chapter['title']) . ' - ' : ''; ?><?php echo $project ? htmlspecialchars($project['name']) . ' - ' : ''; ?><?php echo htmlspecialchars($display_site_name); ?></title>
    <link rel="stylesheet" href="/assets/style.css?v=<?php echo time(); ?>">
    <script>
        // 在页面加载前设置主题，避免闪烁
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            // 等待DOM加载后设置开关状态和图标
            document.addEventListener('DOMContentLoaded', function() {
                const themeToggle = document.getElementById('themeToggle');
                if (themeToggle) {
                    themeToggle.setAttribute('data-theme', savedTheme);
                }
                updateThemeIcon(savedTheme);
            });
        })();
        
        // 更新主题图标的辅助函数
        function updateThemeIcon(theme) {
            const icon = document.getElementById('themeIcon');
            if (icon) {
                // as.png是暗色模式，qs.png是浅色模式
                const iconPath = theme === 'dark' ? '/img/as.png' : '/img/qs.png';
                icon.src = iconPath;
                icon.alt = theme === 'dark' ? '浅色模式' : '暗色模式';
            }
        }
        
        // 主题切换函数（必须在按钮渲染前定义）
        function toggleTheme() {
            const themeToggle = document.getElementById('themeToggle');
            if (!themeToggle) {
                return;
            }
            
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // 更新开关状态和图标
            themeToggle.setAttribute('data-theme', newTheme);
            updateThemeIcon(newTheme);
        }
    </script>
</head>
<body>
    <!-- 顶部导航栏 -->
    <header class="site-header">
        <div class="header-container">
            <div class="header-left">
                <?php if ($project): ?>
                <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="菜单">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <?php endif; ?>
                <a href="<?php echo homeUrl(); ?>" class="logo">
                    <?php if (!empty($site_logo_setting) && file_exists($site_logo_setting)): ?>
                        <img src="/<?php echo htmlspecialchars($site_logo_setting); ?>" alt="<?php echo htmlspecialchars($display_site_name); ?>">
                    <?php else: ?>
                        <?php echo htmlspecialchars($project['name'] ?? $display_site_name); ?>
                    <?php endif; ?>
                </a>
                <div class="search-box">
                    <input type="text" placeholder="搜索一下" class="search-input">
                </div>
            </div>
            <nav class="header-nav">
                <?php
                // 获取导航菜单
                try {
                    $nav_stmt = $db->query("SELECT * FROM nav_menus ORDER BY `order` ASC, id ASC");
                    $nav_menus = $nav_stmt->fetchAll();
                    
                    if (!empty($nav_menus)) {
                        foreach ($nav_menus as $nav_menu) {
                            echo '<a href="' . htmlspecialchars($nav_menu['url']) . '">' . htmlspecialchars($nav_menu['title']) . '</a>';
                        }
                    } else {
                        // 如果没有菜单，显示默认菜单
                        echo '<a href="#">首页</a>';
                        echo '<a href="#">星宿UI小程序</a>';
                        echo '<a href="#">灵沐小程序</a>';
                        echo '<a href="#">博客网</a>';
                        echo '<a href="#">关注公众号</a>';
                        echo '<a href="#">体验小程序</a>';
                    }
                } catch (PDOException $e) {
                    // 如果表不存在，显示默认菜单
                    echo '<a href="#">首页</a>';
                    echo '<a href="#">星宿UI小程序</a>';
                    echo '<a href="#">灵沐小程序</a>';
                    echo '<a href="#">博客网</a>';
                    echo '<a href="#">关注公众号</a>';
                    echo '<a href="#">体验小程序</a>';
                }
                ?>
            </nav>
            <div class="header-right">
                <?php if ($project && !empty($toc_headings)): ?>
                <button class="mobile-toc-btn" id="mobileTocBtn" aria-label="目录">
                    <span>On this page</span>
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 4.5L6 7.5L9 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <?php endif; ?>
                <button class="theme-toggle-switch" id="themeToggle" title="主题切换" onclick="toggleTheme(); return false;" type="button">
                    <span class="theme-toggle-track">
                        <span class="theme-toggle-handle">
                            <img id="themeIcon" src="/img/qs.png" alt="主题切换" class="theme-icon">
                        </span>
                    </span>
                </button>
                <?php
                // 获取名片数据
                $cards = [];
                try {
                    $db->exec("CREATE TABLE IF NOT EXISTS `cards` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `icon_path` varchar(255) NOT NULL COMMENT '图标路径',
                      `link_url` varchar(500) DEFAULT '' COMMENT '跳转链接',
                      `is_popup` tinyint(1) DEFAULT 0 COMMENT '是否弹窗展示 0=跳转 1=弹窗',
                      `order` int(11) DEFAULT 0 COMMENT '排序',
                      `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                      `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                    $cards_stmt = $db->query("SELECT * FROM cards ORDER BY `order` ASC, id ASC");
                    $cards = $cards_stmt->fetchAll();
                } catch (PDOException $e) {
                    $cards = [];
                }
                
                // 显示名片图标（在主题切换开关后面）
                if (!empty($cards)) {
                    // 移动端展开按钮
                    echo '<button class="mobile-cards-toggle" id="mobileCardsToggle" aria-label="名片" style="display: none;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>';
                    
                    // 名片容器（桌面端直接显示，移动端隐藏）
                    echo '<div class="cards-container desktop-cards">';
                    foreach ($cards as $card) {
                        $icon_path = $card['icon_path'] ?? '';
                        if (!empty($icon_path)) {
                            $link_url = htmlspecialchars($card['link_url'] ?? '');
                            $is_popup = intval($card['is_popup'] ?? 0);
                            $icon_path_display = htmlspecialchars($icon_path);
                            
                            if ($is_popup) {
                                echo '<button type="button" class="icon-btn card-icon-btn" title="' . htmlspecialchars($link_url) . '" data-link="' . htmlspecialchars($link_url) . '" data-popup="1">';
                                echo '<img src="/' . $icon_path_display . '" alt="名片图标" style="width: 20px; height: 20px; object-fit: contain;">';
                                echo '</button>';
                            } else {
                                echo '<a href="' . htmlspecialchars($link_url) . '" class="icon-btn card-icon-btn" title="' . htmlspecialchars($link_url) . '" target="_blank">';
                                echo '<img src="/' . $icon_path_display . '" alt="名片图标" style="width: 20px; height: 20px; object-fit: contain;">';
                                echo '</a>';
                            }
                        }
                    }
                    echo '</div>';
                    
                    // 移动端下拉面板
                    echo '<div class="mobile-cards-panel" id="mobileCardsPanel">';
                    echo '<div class="mobile-cards-content">';
                    foreach ($cards as $card) {
                        $icon_path = $card['icon_path'] ?? '';
                        if (!empty($icon_path)) {
                            $link_url = htmlspecialchars($card['link_url'] ?? '');
                            $is_popup = intval($card['is_popup'] ?? 0);
                            $icon_path_display = htmlspecialchars($icon_path);
                            
                            if ($is_popup) {
                                echo '<button type="button" class="mobile-card-item card-icon-btn" title="' . htmlspecialchars($link_url) . '" data-link="' . htmlspecialchars($link_url) . '" data-popup="1">';
                                echo '<img src="/' . $icon_path_display . '" alt="名片图标">';
                                echo '</button>';
                            } else {
                                echo '<a href="' . htmlspecialchars($link_url) . '" class="mobile-card-item card-icon-btn" title="' . htmlspecialchars($link_url) . '" target="_blank">';
                                echo '<img src="/' . $icon_path_display . '" alt="名片图标">';
                                echo '</a>';
                            }
                        }
                    }
                    echo '</div>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </header>

    <div class="main-container">
        <?php if ($project): ?>
        <!-- 左侧导航栏（只显示项目时显示） -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <aside class="sidebar" id="sidebar">
            <nav class="sidebar-nav">
                <?php
                // 语雀模式：检查子章节中是否有当前活动的章节（用于展开父节点）
                // 使用ID匹配而不是slug，避免slug重复问题
                function hasActiveChildInTree($chapters, $current_id) {
                    if ($current_id === null) {
                        return false;
                    }
                    foreach ($chapters as $chapter) {
                        $chapter_id = isset($chapter['id']) ? (int)$chapter['id'] : 0;
                        if ($chapter_id === $current_id) {
                            return true;
                        }
                        if (!empty($chapter['children']) && hasActiveChildInTree($chapter['children'], $current_id)) {
                            return true;
                        }
                    }
                    return false;
                }
                
                function renderNavTree($chapters, $project_slug, $current_slug, $level = 0, $parent_path = '', $current_id = null) {
                    foreach ($chapters as $chapter) {
                        $chapter_slug = isset($chapter['slug']) ? trim($chapter['slug']) : '';
                        $chapter_id = isset($chapter['id']) ? (int)$chapter['id'] : 0;
                        
                        if ($current_id !== null) {
                            $is_active = ($chapter_id === $current_id);
                        } else {
                            $is_active = ($current_slug !== null && $current_slug !== '' && $chapter_slug === $current_slug && $chapter_slug !== '');
                        }
                        
                        $has_children = !empty($chapter['children']);
                        $current_path = $parent_path . '-' . $chapter['id'];
                        
                        $content_trimmed = isset($chapter['content']) ? trim($chapter['content']) : '';
                        $html_content_trimmed = isset($chapter['html_content']) ? trim($chapter['html_content']) : '';
                        $has_content = !empty($content_trimmed) || !empty($html_content_trimmed);
                        
                        $should_expand = $has_children;
                        
                        echo '<div class="nav-item' . ($is_active ? ' active' : '') . '" data-path="' . $current_path . '">';
                        echo '<div class="nav-item-header">';
                        
                        if ($has_children) {
                            if ($has_content) {
                                $should_expand = true;
                                echo '<button class="nav-toggle expanded" onclick="toggleNavItem(this); return false;">▶</button>';
                                $link_url = chapterUrl($project_slug, $chapter['slug']);
                                echo '<a href="' . $link_url . '" class="nav-link nav-group-title" style="cursor: pointer;">' . htmlspecialchars($chapter['title']) . '</a>';
                            } else {
                                echo '<button class="nav-toggle' . ($should_expand ? ' expanded' : '') . '" onclick="toggleNavItem(this); return false;">▶</button>';
                                echo '<span class="nav-link nav-group-title" onclick="toggleNavItemByTitle(this, event); return false;" style="cursor: pointer;">' . htmlspecialchars($chapter['title']) . '</span>';
                            }
                        } else {
                            echo '<span class="nav-spacer"></span>';
                            $link_url = chapterUrl($project_slug, $chapter['slug']);
                            echo '<a href="' . $link_url . '" class="nav-link">';
                            echo htmlspecialchars($chapter['title']);
                            echo '</a>';
                        }
                        
                        echo '</div>';
                        
                        if ($has_children) {
                            $force_expand = $has_content ? true : $should_expand;
                            echo '<div class="nav-children' . ($force_expand ? ' expanded' : '') . '">';
                            renderNavTree($chapter['children'], $project_slug, $current_slug, $level + 1, $current_path, $current_id);
                            echo '</div>';
                        }
                        echo '</div>';
                    }
                }
                if (!empty($chapters_tree)) {
                    $nav_current_slug = null;
                    $nav_current_id = null;
                    if ($current_chapter && isset($current_chapter['slug']) && isset($current_chapter['id'])) {
                        $nav_current_slug = trim($current_chapter['slug']);
                        $nav_current_id = (int)$current_chapter['id'];
                    }
                    renderNavTree($chapters_tree, $project_slug, $nav_current_slug, 0, '', $nav_current_id);
                }
                ?>
            </nav>
            <?php
            // 显示版权信息
            $copyright_info = getSetting($db, 'copyright_info', '');
            if (!empty($copyright_info)):
            ?>
            <div class="copyright-info">
                <?php echo $copyright_info; ?>
            </div>
            <?php endif; ?>
        </aside>
        <?php endif; ?>

        <!-- 主内容区 -->
        <main class="content">
            <?php if (!$project): ?>
                <div class="project-list-page">
                    <h1 class="doc-title"><?php echo htmlspecialchars($display_site_name); ?></h1>
                    <?php if (empty($all_projects)): ?>
                        <div class="empty-state">
                            <p>暂无项目</p>
                        </div>
                    <?php else: ?>
                        <div class="project-grid">
                            <?php foreach ($all_projects as $proj): ?>
                                <div class="project-card">
                                    <h2><a href="<?php echo projectUrl($proj['slug']); ?>"><?php echo htmlspecialchars($proj['name']); ?></a></h2>
                                    <?php if (!empty($proj['description'])): ?>
                                        <p><?php echo htmlspecialchars($proj['description']); ?></p>
                                    <?php else: ?>
                                        <p style="opacity: 0.5;">暂无描述</p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif ($current_chapter): ?>
                <article class="doc-content">
                    <h1 class="doc-title"><?php echo htmlspecialchars($current_chapter['title']); ?></h1>
                    <div class="doc-body">
                        <?php 
                        if (!empty($processed_html)) {
                            echo $processed_html;
                        } else if ($current_chapter && !empty($current_chapter['html_content'])) {
                            $cleaned_html = cleanEditorMDHTML($current_chapter['html_content']);
                            echo $cleaned_html;
                        } else if ($current_chapter && !empty($current_chapter['content'])) {
                            require_once 'libs/Parsedown.php';
                            $Parsedown = new Parsedown();
                            echo $Parsedown->text($current_chapter['content']);
                        }
                        ?>
                    </div>
                </article>
            <?php else: ?>
                <div class="empty-state">
                    <p>请选择一个章节</p>
                </div>
            <?php endif; ?>
            
            <?php if ($current_chapter): ?>
                <?php 
                // 从章节树中查找指定章节的完整信息（包括children）
                function findChapterInTree($chapters_tree, $chapter_id) {
                    foreach ($chapters_tree as $chapter) {
                        if ($chapter['id'] == $chapter_id) {
                            return $chapter;
                        }
                        if (!empty($chapter['children'])) {
                            $found = findChapterInTree($chapter['children'], $chapter_id);
                            if ($found) {
                                return $found;
                            }
                        }
                    }
                    return null;
                }
                
                // 生成章节导航URL：如果章节有内容，链接到自己；如果有子章节但没有内容，链接到第一个子章节
                function getChapterNavigationUrl($chapter, $chapters_tree, $project_slug) {
                    $full_chapter = findChapterInTree($chapters_tree, $chapter['id']);
                    if ($full_chapter) {
                        // 检查章节是否有内容（去除空白字符后检查）
                        $content_trimmed = isset($full_chapter['content']) ? trim($full_chapter['content']) : '';
                        $html_content_trimmed = isset($full_chapter['html_content']) ? trim($full_chapter['html_content']) : '';
                        $has_content = !empty($content_trimmed) || !empty($html_content_trimmed);
                        
                        // 如果有内容，直接链接到自己
                        if ($has_content) {
                            return chapterUrl($project_slug, $chapter['slug']);
                        }
                        
                        // 如果没有内容但有子章节，链接到第一个子章节
                        if (!empty($full_chapter['children'])) {
                            return chapterUrl($project_slug, $full_chapter['children'][0]['slug']);
                        }
                    }
                    // 默认链接到自己
                    return chapterUrl($project_slug, $chapter['slug']);
                }
                
                $all_chapters = getAllChaptersFlat($chapters_tree);
                $current_index = -1;
                
                // 查找当前章节在列表中的位置
                foreach ($all_chapters as $index => $chapter) {
                    if ($chapter['id'] == $current_chapter['id']) {
                        $current_index = $index;
                        break;
                    }
                }
                
                // 如果当前章节不在列表中（可能是没有内容的父分类），尝试查找它的第一个有内容的子章节
                if ($current_index == -1) {
                    // 查找当前章节的第一个有内容的子章节
                    $first_content_child = findFirstContentChapter($chapters_tree, $current_chapter['id']);
                    if ($first_content_child) {
                        foreach ($all_chapters as $index => $chapter) {
                            if ($chapter['id'] == $first_content_child['id']) {
                                $current_index = $index;
                                break;
                            }
                        }
                    }
                }
                
                $prev_chapter = ($current_index > 0) ? $all_chapters[$current_index - 1] : null;
                $next_chapter = ($current_index >= 0 && $current_index < count($all_chapters) - 1) ? $all_chapters[$current_index + 1] : null;
                
                if ($prev_chapter || $next_chapter):
                ?>
                <div class="chapter-navigation-fixed">
                    <div class="chapter-navigation-container">
                        <div class="chapter-navigation">
                            <?php if ($prev_chapter): ?>
                                <div class="nav-link-prev">
                                    <a href="<?php echo getChapterNavigationUrl($prev_chapter, $chapters_tree, $project_slug); ?>">← <?php echo htmlspecialchars($prev_chapter['title']); ?></a>
                                </div>
                            <?php else: ?>
                                <div></div>
                            <?php endif; ?>
                            <?php if ($next_chapter): ?>
                                <div class="nav-link-next">
                                    <a href="<?php echo getChapterNavigationUrl($next_chapter, $chapters_tree, $project_slug); ?>"><?php echo htmlspecialchars($next_chapter['title']); ?> →</a>
                                </div>
                            <?php else: ?>
                                <div></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>

        <!-- 移动端目录遮罩层 -->
        <div class="toc-overlay" id="tocOverlay"></div>
        
        <!-- 移动端目录面板 -->
        <div class="mobile-toc-panel" id="mobileTocPanel">
            <div class="mobile-toc-header">
                <h3>On this page</h3>
                <button class="mobile-toc-close" id="mobileTocClose">×</button>
            </div>
            <div class="mobile-toc-content">
                <?php if (!empty($toc_headings)): ?>
                    <ul class="toc-list">
                        <?php foreach ($toc_headings as $heading): ?>
                            <li class="toc-item toc-level-<?php echo $heading['level']; ?>">
                                <a href="#<?php echo htmlspecialchars($heading['id']); ?>"><?php echo htmlspecialchars($heading['text']); ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p style="padding: 16px; color: var(--text-color); opacity: 0.6;">暂无目录</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 右侧目录导航 -->
        <?php if (!empty($toc_headings)): ?>
        <aside class="toc-sidebar">
            <div class="toc-container">
                <h3 class="toc-title">On this page</h3>
                <ul class="toc-list">
                    <?php foreach ($toc_headings as $heading): ?>
                        <li class="toc-item toc-level-<?php echo $heading['level']; ?>">
                            <a href="#<?php echo $heading['id']; ?>"><?php echo htmlspecialchars($heading['text']); ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>
        <?php endif; ?>
    </div>

    <!-- 搜索模态框 -->
    <div id="searchModal" class="search-modal">
        <div class="search-modal-content">
            <div class="search-modal-header">
                <div class="search-input-wrapper">
                    <svg class="search-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="9" cy="9" r="6"></circle>
                        <path d="m17 17-4-4"></path>
                    </svg>
                    <input type="text" id="searchModalInput" class="search-modal-input" placeholder="搜索" autocomplete="off">
                </div>
                <button class="search-close-btn" id="searchCloseBtn">✕</button>
            </div>
            <div class="search-results" id="searchResults">
                <div class="search-empty">输入关键词搜索章节</div>
            </div>
            <div class="search-footer">
                <div class="search-shortcuts">
                    <kbd>↑</kbd><kbd>↓</kbd> <span>用于导航</span>
                    <kbd>↵</kbd> <span>用于选择</span>
                    <kbd>esc</kbd> <span>用于关闭</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 名片弹窗 -->
    <div id="cardPopupModal" class="card-popup-modal">
        <div class="card-popup-overlay" id="cardPopupOverlay"></div>
        <div class="card-popup-content" id="cardPopupContent">
            <button class="card-popup-close" id="cardPopupClose">×</button>
            <div class="card-popup-body" id="cardPopupBody">
                <img id="cardPopupImage" src="" alt="" style="display: none; max-width: 100%; max-height: 80vh; object-fit: contain;">
                <iframe id="cardPopupIframe" src="" frameborder="0" style="width: 100%; height: 100%; border: none; display: none;"></iframe>
            </div>
        </div>
    </div>

    <script src="/assets/main.js?v=<?php echo time(); ?>"></script>
</body>
</html>


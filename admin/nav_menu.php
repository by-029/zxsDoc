<?php
/**
 * 作者：走小散
 * 微信公众号：走小散
 */
require_once '../config/config.php';
require_once 'check_login.php';

$db = Database::getInstance()->getConnection();
$error = '';
$success = '';

// 获取网站名称
function getSetting($db, $key, $default = '') {
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : $default;
}
$display_site_name = getSetting($db, 'site_name', SITE_NAME);

// 获取菜单列表
$stmt = $db->query("SELECT * FROM nav_menus ORDER BY `order` ASC, id ASC");
$menus = $stmt->fetchAll();

// 保存菜单
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_menu') {
        $id = intval($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $url = trim($_POST['url'] ?? '#');
        $order = intval($_POST['order'] ?? 0);
        
        if ($id <= 0) {
            $error = '菜单ID无效';
        } elseif (empty($title)) {
            $error = '菜单名称不能为空';
        } else {
            if ($url !== '#') {
                $url = filter_var($url, FILTER_SANITIZE_URL);
                if (!filter_var($url, FILTER_VALIDATE_URL) && !preg_match('/^[a-zA-Z0-9\/\-_\.]+$/', $url)) {
                    $error = '链接地址格式不正确';
                }
            }
            if (empty($error)) {
                $stmt = $db->prepare("UPDATE nav_menus SET title = ?, url = ?, `order` = ? WHERE id = ?");
                $stmt->execute([$title, $url, $order, $id]);
                $success = '菜单保存成功！';
                
                $stmt = $db->query("SELECT * FROM nav_menus ORDER BY `order` ASC, id ASC");
                $menus = $stmt->fetchAll();
            }
        }
    } elseif ($_POST['action'] === 'add_menu') {
        // 检查菜单数量限制
        $stmt = $db->query("SELECT COUNT(*) as count FROM nav_menus");
        $result = $stmt->fetch();
        $menu_count = intval($result['count'] ?? 0);
        
        if ($menu_count >= 6) {
            $error = '最多只能添加6个菜单，请先删除一些菜单再添加';
        } else {
            $title = trim($_POST['title'] ?? '');
            $url = trim($_POST['url'] ?? '#');
            
            if (empty($title)) {
                $error = '菜单名称不能为空';
            } else {
                if ($url !== '#') {
                    $url = filter_var($url, FILTER_SANITIZE_URL);
                    if (!filter_var($url, FILTER_VALIDATE_URL) && !preg_match('/^[a-zA-Z0-9\/\-_\.]+$/', $url)) {
                        $error = '链接地址格式不正确';
                    }
                }
                if (empty($error)) {
                    $stmt = $db->query("SELECT MAX(`order`) as max_order FROM nav_menus");
                    $result = $stmt->fetch();
                    $order = ($result['max_order'] ?? 0) + 1;
                    
                    $stmt = $db->prepare("INSERT INTO nav_menus (title, url, `order`) VALUES (?, ?, ?)");
                    $stmt->execute([$title, $url, $order]);
                    
                    $success = '菜单添加成功！';
                    header('Location: nav_menu.php');
                    exit;
                }
            }
        }
    } elseif ($_POST['action'] === 'delete_menu') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM nav_menus WHERE id = ?");
            $stmt->execute([$id]);
            $success = '菜单删除成功！';
            header('Location: nav_menu.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>导航菜单设置 - <?php echo htmlspecialchars($display_site_name); ?></title>
    <link rel="stylesheet" href="assets/admin.css">
    <style>
        .menu-list {
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .menu-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 5px;
            margin-bottom: 10px;
            background: #f8f9fa;
        }
        .menu-item:hover {
            background: #e9ecef;
        }
        .menu-drag-handle {
            cursor: move;
            color: #999;
            font-size: 18px;
            padding: 5px;
        }
        .menu-form {
            display: flex;
            flex: 1;
            gap: 10px;
            align-items: center;
        }
        .menu-form input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .menu-order {
            width: 70px;
            padding: 8px 12px 8px 32px !important;
            border: 2px solid #e0e0e0 !important;
            border-radius: 4px;
            font-weight: 500;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%) !important;
            transition: border-color 0.3s, box-shadow 0.3s;
            position: relative;
            flex: 0 0 70px !important;
        }
        .menu-order:focus {
            outline: none;
            border-color: #3498db !important;
            background: #ffffff !important;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.15);
        }
        .menu-order::-webkit-inner-spin-button,
        .menu-order::-webkit-outer-spin-button {
            opacity: 1;
            height: 28px;
            cursor: pointer;
        }
        .menu-order-wrapper {
            position: relative;
            display: inline-block;
        }
        .menu-order-wrapper::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 14px;
            height: 14px;
            background-image: url('../img/xh.png');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            pointer-events: none;
            z-index: 1;
        }
        .btn-sm {
            padding: 8px 15px;
            font-size: 14px;
        }
        .btn-remove {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-remove:hover {
            background: #c0392b;
        }
        .add-menu-form {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .add-menu-form h3 {
            margin-bottom: 15px;
            color: #333;
        }
        .form-row {
            display: flex;
            gap: 15px;
            align-items: flex-end;
        }
        .form-group {
            flex: 1;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
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
                <a href="nav_menu.php" class="active">导航菜单设置</a>
                <a href="change_password.php">修改密码</a>
                <a href="card_management.php">名片管理</a>
            </nav>
        </div>
        
        <div class="admin-content">
            <div class="page-header">
                <h2>导航菜单设置</h2>
                <a href="../index.php" target="_blank" class="btn btn-secondary">预览前台</a>
            </div>
            
            <?php if ($error): ?>
                <div style="background: #fee; color: #c33; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <!-- 添加新菜单表单 -->
            <?php
            $count_stmt = $db->query("SELECT COUNT(*) as count FROM nav_menus");
            $count_result = $count_stmt->fetch();
            $current_count = intval($count_result['count'] ?? 0);
            $can_add = $current_count < 6;
            ?>
            <div class="add-menu-form">
                <h3>添加新菜单 <?php if (!$can_add): ?><span style="color: #e74c3c; font-size: 14px; font-weight: normal;">(已达上限6个)</span><?php endif; ?></h3>
                <?php if ($can_add): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="add_menu">
                    <div class="form-row">
                        <div class="form-group">
                            <label>菜单名称 *</label>
                            <input type="text" name="title" required placeholder="例如：首页">
                        </div>
                        <div class="form-group">
                            <label>跳转链接 *</label>
                            <input type="text" name="url" required placeholder="例如：/ 或 https://example.com" value="#">
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary">添加菜单</button>
                        </div>
                    </div>
                </form>
                <?php else: ?>
                <p style="color: #999; margin: 0;">已达到最大菜单数量限制（6个），请先删除一些菜单再添加新菜单。</p>
                <?php endif; ?>
            </div>
            
            <!-- 菜单列表 -->
            <div class="menu-list">
                <h3 style="margin-bottom: 20px;">菜单列表</h3>
                
                <?php if (empty($menus)): ?>
                    <div class="empty-state">
                        <p>还没有菜单，请添加第一个菜单</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($menus as $menu): ?>
                        <div class="menu-item">
                            <span class="menu-drag-handle">☰</span>
                            <form method="POST" class="menu-form">
                                <input type="hidden" name="action" value="update_menu">
                                <input type="hidden" name="id" value="<?php echo $menu['id']; ?>">
                                <input type="text" name="title" 
                                       value="<?php echo htmlspecialchars($menu['title']); ?>" 
                                       placeholder="菜单名称" required>
                                <input type="text" name="url" 
                                       value="<?php echo htmlspecialchars($menu['url']); ?>" 
                                       placeholder="跳转链接" required>
                                <span class="menu-order-wrapper">
                                    <input type="number" name="order" 
                                           value="<?php echo $menu['order']; ?>" 
                                           class="menu-order" min="0" step="1">
                                </span>
                                <button type="submit" class="btn btn-sm btn-primary">保存</button>
                                <button type="button" class="btn-remove" onclick="if(confirm('确定要删除这个菜单吗？')) { document.getElementById('delete_form_<?php echo $menu['id']; ?>').submit(); }">删除</button>
                            </form>
                            <form method="POST" id="delete_form_<?php echo $menu['id']; ?>" style="display: none;">
                                <input type="hidden" name="action" value="delete_menu">
                                <input type="hidden" name="id" value="<?php echo $menu['id']; ?>">
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>


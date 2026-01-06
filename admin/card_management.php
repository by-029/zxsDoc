<?php
/**
 * 作者：走小散
 * 微信公众号：走小散
 */
require_once '../config/config.php';
require_once '../config/security.php';
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

// 创建名片表
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
} catch(PDOException $e) {
    // 表已存在，忽略错误
}

// 获取所有名片
$stmt = $db->query("SELECT * FROM cards ORDER BY `order` ASC, id ASC");
$cards = $stmt->fetchAll();

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'save_all') {
            // 保存所有名片
            $card_data = $_POST['cards'] ?? [];
            
            // 检查数量限制
            $existing_count = count($cards);
            $new_count = 0;
            foreach ($card_data as $card) {
                if (!empty($card['icon_path']) || !empty($_FILES['icons']['name'][$card['index'] ?? 0])) {
                    $new_count++;
                }
            }
            
            if ($new_count > 5) {
                $error = '最多只能添加5个图标';
            } else {
                // 先处理上传的图标
                $upload_dir = '../uploads/cards/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // 删除所有现有名片
                $db->exec("DELETE FROM cards");
                
                // 处理每个名片
                foreach ($card_data as $index => $card) {
                    $icon_path = $card['icon_path'] ?? '';
                    $link_url = trim($card['link_url'] ?? '');
                    $is_popup = isset($card['is_popup']) ? 1 : 0;
                    $order = intval($card['order'] ?? $index);
                    
                    // 如果有新上传的图标
                    if (isset($_FILES['icons']['name'][$index]) && $_FILES['icons']['error'][$index] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['icons']['name'][$index],
                            'type' => $_FILES['icons']['type'][$index],
                            'tmp_name' => $_FILES['icons']['tmp_name'][$index],
                            'error' => $_FILES['icons']['error'][$index],
                            'size' => $_FILES['icons']['size'][$index]
                        ];
                        
                        $allowed_types = ['image/png', 'image/jpeg', 'image/jpg'];
                        $max_size = 2 * 1024 * 1024;
                        
                        $validation = validateFileUpload($file, $allowed_types, $max_size);
                        if (!$validation['success']) {
                            $error = $validation['error'];
                            continue;
                        }
                        
                        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        $allowed_ext = ['png', 'jpg', 'jpeg'];
                        if (!in_array($extension, $allowed_ext)) {
                            $error = '只支持 PNG、JPEG、JPG 格式的图片';
                            continue;
                        }
                        
                        if (!empty($icon_path) && file_exists('../' . $icon_path)) {
                            @unlink('../' . $icon_path);
                        }
                        
                        $filename = 'card_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
                        $upload_path = $upload_dir . $filename;
                        
                        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                            $icon_path = 'uploads/cards/' . $filename;
                        } else {
                            $error = '图标上传失败，请检查目录权限';
                            continue;
                        }
                    }
                    
                    // 如果有图标路径，保存到数据库
                    if (!empty($icon_path)) {
                        if (!empty($link_url)) {
                            $link_url = filter_var($link_url, FILTER_SANITIZE_URL);
                            if (!filter_var($link_url, FILTER_VALIDATE_URL) && !preg_match('/^[a-zA-Z0-9\/\-_\.]+$/', $link_url)) {
                                $error = '链接地址格式不正确';
                                continue;
                            }
                        }
                        $stmt = $db->prepare("INSERT INTO cards (icon_path, link_url, is_popup, `order`) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$icon_path, $link_url, $is_popup, $order]);
                    }
                }
                
                if (empty($error)) {
                    $success = '名片保存成功！';
                    // 重新获取名片列表
                    $stmt = $db->query("SELECT * FROM cards ORDER BY `order` ASC, id ASC");
                    $cards = $stmt->fetchAll();
                }
            }
        } elseif ($_POST['action'] === 'delete_card') {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                // 获取图标路径并删除文件
                $stmt = $db->prepare("SELECT icon_path FROM cards WHERE id = ?");
                $stmt->execute([$id]);
                $card = $stmt->fetch();
                if ($card && !empty($card['icon_path']) && file_exists('../' . $card['icon_path'])) {
                    @unlink('../' . $card['icon_path']);
                }
                
                // 删除数据库记录
                $stmt = $db->prepare("DELETE FROM cards WHERE id = ?");
                $stmt->execute([$id]);
                $success = '名片删除成功！';
                header('Location: card_management.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>名片管理 - <?php echo htmlspecialchars($display_site_name); ?></title>
    <link rel="stylesheet" href="assets/admin.css">
    <style>
        .card-management-form {
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .card-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 20px;
            background: #f8f9fa;
        }
        
        .card-item:hover {
            background: #e9ecef;
        }
        
        .icon-upload-area {
            width: 120px;
            height: 120px;
            border: 2px dashed #ddd;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #fff;
            position: relative;
            flex-shrink: 0;
        }
        
        .icon-upload-area img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .icon-upload-area .upload-placeholder {
            text-align: center;
            color: #999;
            font-size: 12px;
            padding: 10px;
        }
        
        .icon-upload-area input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        
        .card-form-fields {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .form-row {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .form-row input[type="text"] {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-row input[type="text"]:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }
        
        .checkbox-wrapper input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .checkbox-wrapper label {
            cursor: pointer;
            font-size: 14px;
            color: #333;
        }
        
        .help-text {
            color: #e74c3c;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            align-self: flex-start;
        }
        
        .btn-delete:hover {
            background: #c0392b;
        }
        
        .btn-save-all {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            float: right;
        }
        
        .btn-save-all:hover {
            background: #2980b9;
        }
        
        .add-card-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .add-card-btn:hover {
            background: #229954;
        }
        
        .add-card-btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
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
                <a href="card_management.php" class="active">名片管理</a>
            </nav>
        </div>
        
        <div class="admin-content">
            <div class="page-header">
                <h2>名片管理</h2>
            </div>
            
            <?php if ($error): ?>
                <div style="background: #fee; color: #c33; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div style="background: #efe; color: #3c3; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <div class="card-management-form">
                <form method="POST" enctype="multipart/form-data" id="cardForm">
                    <input type="hidden" name="action" value="save_all">
                    
                    <div id="cardsContainer">
                        <?php 
                        $card_count = count($cards);
                        for ($i = 0; $i < max(1, $card_count); $i++): 
                            $card = $cards[$i] ?? null;
                        ?>
                            <div class="card-item" data-index="<?php echo $i; ?>">
                                <div class="icon-upload-area">
                                    <?php if ($card && !empty($card['icon_path']) && file_exists('../' . $card['icon_path'])): ?>
                                        <img src="../<?php echo htmlspecialchars($card['icon_path']); ?>" alt="图标预览" id="preview_<?php echo $i; ?>">
                                    <?php else: ?>
                                        <div class="upload-placeholder" id="placeholder_<?php echo $i; ?>">
                                            点击上传<br>图标
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" name="icons[<?php echo $i; ?>]" accept="image/png,image/jpeg,image/jpg" onchange="previewIcon(this, <?php echo $i; ?>)">
                                    <input type="hidden" name="cards[<?php echo $i; ?>][icon_path]" value="<?php echo $card ? htmlspecialchars($card['icon_path']) : ''; ?>">
                                    <input type="hidden" name="cards[<?php echo $i; ?>][index]" value="<?php echo $i; ?>">
                                    <input type="hidden" name="cards[<?php echo $i; ?>][order]" value="<?php echo $i; ?>">
                                </div>
                                
                                <div class="card-form-fields">
                                    <div class="form-row">
                                        <input type="text" name="cards[<?php echo $i; ?>][link_url]" placeholder="跳转链接" value="<?php echo $card ? htmlspecialchars($card['link_url']) : ''; ?>">
                                        <div class="checkbox-wrapper">
                                            <input type="checkbox" name="cards[<?php echo $i; ?>][is_popup]" id="popup_<?php echo $i; ?>" value="1" <?php echo ($card && $card['is_popup']) ? 'checked' : ''; ?>>
                                            <label for="popup_<?php echo $i; ?>">弹窗展示</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($card): ?>
                                    <button type="button" class="btn-delete" onclick="deleteCard(<?php echo $card['id']; ?>)">删除</button>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                    
                    <div style="clear: both; margin-top: 20px;">
                        <button type="button" class="add-card-btn" id="addCardBtn" onclick="addCard()" <?php echo $card_count >= 5 ? 'disabled' : ''; ?>>+ 添加名片</button>
                        <button type="submit" class="btn-save-all">保存全部</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        let cardIndex = <?php echo max(1, $card_count); ?>;
        const maxCards = 5;
        
        function previewIcon(input, index) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('preview_' + index);
                    const placeholder = document.getElementById('placeholder_' + index);
                    if (preview) {
                        preview.src = e.target.result;
                    } else {
                        const img = document.createElement('img');
                        img.id = 'preview_' + index;
                        img.src = e.target.result;
                        img.alt = '图标预览';
                        if (placeholder) {
                            placeholder.parentNode.replaceChild(img, placeholder);
                        }
                    }
                };
                reader.readAsDataURL(file);
            }
        }
        
        function addCard() {
            const container = document.getElementById('cardsContainer');
            const cardCount = container.children.length;
            
            if (cardCount >= maxCards) {
                alert('最多只能添加' + maxCards + '个图标');
                return;
            }
            
            const newCard = document.createElement('div');
            newCard.className = 'card-item';
            newCard.setAttribute('data-index', cardIndex);
            newCard.innerHTML = `
                <div class="icon-upload-area">
                    <div class="upload-placeholder" id="placeholder_${cardIndex}">
                        点击上传<br>图标
                    </div>
                    <input type="file" name="icons[${cardIndex}]" accept="image/png,image/jpeg,image/jpg" onchange="previewIcon(this, ${cardIndex})">
                    <input type="hidden" name="cards[${cardIndex}][icon_path]" value="">
                    <input type="hidden" name="cards[${cardIndex}][index]" value="${cardIndex}">
                    <input type="hidden" name="cards[${cardIndex}][order]" value="${cardIndex}">
                </div>
                
                <div class="card-form-fields">
                    <div class="form-row">
                        <input type="text" name="cards[${cardIndex}][link_url]" placeholder="跳转链接" value="">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" name="cards[${cardIndex}][is_popup]" id="popup_${cardIndex}" value="1">
                            <label for="popup_${cardIndex}">弹窗展示</label>
                        </div>
                    </div>
                </div>
                
                <button type="button" class="btn-delete" onclick="removeCard(this)">删除</button>
            `;
            
            container.appendChild(newCard);
            cardIndex++;
            
            // 更新添加按钮状态
            updateAddButton();
        }
        
        function removeCard(btn) {
            if (confirm('确定要删除这个名片吗？')) {
                btn.closest('.card-item').remove();
                updateAddButton();
            }
        }
        
        function deleteCard(id) {
            if (confirm('确定要删除这个名片吗？')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_card">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        
        function updateAddButton() {
            const container = document.getElementById('cardsContainer');
            const cardCount = container.children.length;
            const addBtn = document.getElementById('addCardBtn');
            if (addBtn) {
                addBtn.disabled = cardCount >= maxCards;
            }
        }
        
    </script>
</body>
</html>


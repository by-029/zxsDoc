<?php
/**
 * 作者：走小散
 * 微信公众号：走小散
 */
require_once '../config/config.php';
require_once 'check_login.php';
require_once 'functions.php';

$db = Database::getInstance()->getConnection();
$chapter_id = $_GET['id'] ?? 0;
$error = '';
$success = '';

$stmt = $db->prepare("SELECT c.*, p.slug as project_slug, p.name as project_name, p.id as project_id FROM chapters c LEFT JOIN projects p ON c.project_id = p.id WHERE c.id = ?");
$stmt->execute([$chapter_id]);
$chapter = $stmt->fetch();

if (!$chapter) {
    header('Location: index.php');
    exit;
}

// 获取网站名称
function getSetting($db, $key, $default = '') {
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : $default;
}
$display_site_name = getSetting($db, 'site_name', SITE_NAME);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $html_content = $_POST['html_content'] ?? '';
    $order = intval($_POST['order'] ?? 0);
    
    if (empty($title)) {
        $error = '标题不能为空';
    } else {
        $slug = $chapter['slug'];
        
            try {
            if (empty($html_content) && !empty($content)) {
                require_once '../libs/Parsedown.php';
                $Parsedown = new Parsedown();
                $html_content = $Parsedown->text($content);
            }
                
                $stmt = $db->prepare("UPDATE chapters SET title = ?, slug = ?, content = ?, html_content = ?, `order` = ? WHERE id = ?");
                $stmt->execute([$title, $slug, $content, $html_content, $order, $chapter_id]);
            
            header('Location: chapter_list.php?project_id=' . $chapter['project_id']);
            exit;
            } catch(PDOException $e) {
                error_log("Chapter update failed: " . $e->getMessage());
                $error = '保存失败，请稍后重试';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑章节 - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/admin.css">
    <link rel="stylesheet" href="assets/custom-styles.css">
    <link rel="stylesheet" href="assets/editormd/editormd.min.css">
    <style>
        .editor-wrapper {
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .editor-wrapper .editormd {
            min-height: 500px;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        /* Editor.md 对话框遮罩层 - 最高优先级 */
        body > .editormd-image-dialog-overlay,
        body > .editormd-link-dialog-overlay,
        body > .editormd-html-entities-dialog-overlay,
        body > .editormd-table-dialog-overlay,
        html body .editormd-image-dialog-overlay,
        html body .editormd-link-dialog-overlay,
        html body .editormd-html-entities-dialog-overlay,
        html body .editormd-table-dialog-overlay {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            min-width: 100vw !important;
            min-height: 100vh !important;
            max-width: 100vw !important;
            max-height: 100vh !important;
            background: rgba(0, 0, 0, 0.5) !important;
            background-color: rgba(0, 0, 0, 0.5) !important;
            z-index: 999999 !important;
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: center !important;
            margin: 0 !important;
            padding: 0 !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
        }
        
        /* Editor.md 对话框样式 - 最高优先级 */
        body > .editormd-image-dialog-overlay > .editormd-image-dialog,
        body > .editormd-link-dialog-overlay > .editormd-link-dialog,
        body > .editormd-html-entities-dialog-overlay > .editormd-html-entities-dialog,
        body > .editormd-table-dialog-overlay > .editormd-table-dialog,
        html body .editormd-image-dialog-overlay .editormd-image-dialog,
        html body .editormd-link-dialog-overlay .editormd-link-dialog,
        html body .editormd-html-entities-dialog-overlay .editormd-html-entities-dialog,
        html body .editormd-table-dialog-overlay .editormd-table-dialog {
            background: white !important;
            background-color: white !important;
            padding: 20px !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
            z-index: 1000000 !important;
            min-width: 400px !important;
            max-width: 600px !important;
            width: auto !important;
            position: static !important;
            display: block !important;
            margin: 0 !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            flex-shrink: 0 !important;
            box-sizing: border-box !important;
            transform: none !important;
            left: auto !important;
            right: auto !important;
            top: auto !important;
            bottom: auto !important;
        }
        
        .editormd-image-dialog form,
        .editormd-link-dialog form,
        .editormd-table-dialog form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .editormd-image-dialog label,
        .editormd-link-dialog label,
        .editormd-table-dialog label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        
        .editormd-image-dialog input[type="text"],
        .editormd-link-dialog input[type="text"],
        .editormd-table-dialog input[type="text"],
        .editormd-table-dialog input[type="number"] {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        .editormd-image-dialog input[type="text"]:focus,
        .editormd-link-dialog input[type="text"]:focus,
        .editormd-table-dialog input[type="text"]:focus,
        .editormd-table-dialog input[type="number"]:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        
        .editormd-image-dialog button[type="submit"],
        .editormd-link-dialog button[type="submit"],
        .editormd-table-dialog button[type="submit"] {
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }
        
        .editormd-image-dialog button[type="submit"]:hover,
        .editormd-link-dialog button[type="submit"]:hover,
        .editormd-table-dialog button[type="submit"]:hover {
            background: #2980b9;
        }
        
        .editormd-html-entities-dialog .editormd-html-entities-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .editormd-html-entities-dialog button {
            padding: 8px 12px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            text-align: left;
        }
        
        .editormd-html-entities-dialog button:hover {
            background: #e9ecef;
            border-color: #3498db;
        }
        
        body {
            overflow-x: hidden;
        }
        
        /* 确保对话框遮罩层覆盖整个屏幕并居中 */
        body > .editormd-image-dialog-overlay,
        body > .editormd-link-dialog-overlay,
        body > .editormd-html-entities-dialog-overlay,
        body > .editormd-table-dialog-overlay {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            background: rgba(0, 0, 0, 0.5) !important;
            z-index: 99999 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            margin: 0 !important;
            padding: 0 !important;
            box-sizing: border-box !important;
        }
        
        /* 确保对话框在遮罩层中居中 */
        body > .editormd-image-dialog-overlay > .editormd-image-dialog,
        body > .editormd-link-dialog-overlay > .editormd-link-dialog,
        body > .editormd-html-entities-dialog-overlay > .editormd-html-entities-dialog,
        body > .editormd-table-dialog-overlay > .editormd-table-dialog {
            position: static !important;
            margin: 0 !important;
            transform: none !important;
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
                <h2>编辑章节: <?php echo htmlspecialchars($chapter['title']); ?></h2>
                <div>
                    <a href="<?php echo chapterUrl($chapter['project_slug'] ?? '', $chapter['slug']); ?>" target="_blank" class="btn btn-secondary">预览</a>
                    <a href="project_edit.php?id=<?php echo $chapter['project_id']; ?>" class="btn btn-secondary">返回项目</a>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div style="background: #fee; color: #c33; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <div class="editor-wrapper">
                <form method="POST" id="chapterForm">
                    <div class="form-group">
                        <label>章节标题 *</label>
                        <input type="text" name="title" required value="<?php echo htmlspecialchars($chapter['title']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>URL标识</label>
                        <div style="padding: 10px; background: #f5f5f5; border-radius: 5px;">
                            <a href="<?php echo chapterUrl($chapter['project_slug'] ?? '', $chapter['slug']); ?>" target="_blank" style="color: #3498db; text-decoration: none; font-weight: 500;">
                                <?php echo htmlspecialchars($chapter['slug']); ?>
                                <span style="margin-left: 8px; font-size: 12px;">↗</span>
                            </a>
                        </div>
                        <small style="color: #999; margin-top: 5px; display: block;">点击链接可查看前端页面，修改章节标题时URL标识会自动更新</small>
                    </div>
                    
                    <div class="form-group">
                        <label>排序序号</label>
                        <div class="order-input-wrapper">
                            <input type="number" name="order" value="<?php echo $chapter['order']; ?>" min="0" step="1" class="order-input-field" id="orderInput">
                        </div>
                        <small class="order-input-hint">数字越小越靠前，相同数字按创建时间排序</small>
                    </div>
                    
                    <div class="form-group">
                        <label>内容 (Markdown)</label>
                        <div id="contentEditor">
                            <textarea name="content" style="display:none;"><?php echo htmlspecialchars($chapter['content'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-primary">保存章节</button>
                        <a href="project_edit.php?id=<?php echo $chapter['project_id']; ?>" class="btn btn-secondary">取消</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="assets/editormd/editormd.min.js"></script>
    <script src="custom_emoji_dialog.js"></script>
    <script>
        $(function() {
            var editor = editormd("contentEditor", {
                width: "100%",
                height: 600,
                path: "assets/editormd/lib/",
                placeholder: "在此输入Markdown格式的内容...",
                toolbarIcons: function() {
                    return ["undo", "redo", "|",
                        "bold", "del", "italic", "quote", "|",
                        "h1", "h2", "h3", "h4", "h5", "h6", "|",
                        "list-ul", "list-ol", "hr", "|",
                        "link", "image", "code", "preformatted-text", "code-block", "|",
                        "table", "datetime", "emoji", "html-entities", "pagebreak", "|",
                        "goto-line", "watch", "preview", "fullscreen", "clear", "search", "|",
                        "help", "info"
                    ];
                },
                toolbarIconsClass: {
                    undo: "fa-undo",
                    redo: "fa-repeat",
                    bold: "fa-bold",
                    del: "fa-strikethrough",
                    italic: "fa-italic",
                    quote: "fa-quote-left",
                    h1: "fa-header",
                    h2: "fa-header",
                    h3: "fa-header",
                    h4: "fa-header",
                    h5: "fa-header",
                    h6: "fa-header",
                    "list-ul": "fa-list-ul",
                    "list-ol": "fa-list-ol",
                    hr: "fa-minus",
                    link: "fa-link",
                    image: "fa-image",
                    code: "fa-code",
                    "preformatted-text": "fa-file-text-o",
                    "code-block": "fa-file-code-o",
                    table: "fa-table",
                    datetime: "fa-clock-o",
                    emoji: "fa-smile-o",
                    "html-entities": "fa-at",
                    pagebreak: "fa-newspaper-o",
                    "goto-line": "fa-arrows-alt",
                    watch: "fa-eye",
                    preview: "fa-eye-slash",
                    fullscreen: "fa-arrows-alt",
                    clear: "fa-eraser",
                    search: "fa-search",
                    help: "fa-question-circle",
                    info: "fa-info-circle"
                },
                lang: {
                    toolbar: {
                        undo: "撤销",
                        redo: "重做",
                        bold: "粗体",
                        del: "删除线",
                        italic: "斜体",
                        quote: "引用",
                        h1: "标题1",
                        h2: "标题2",
                        h3: "标题3",
                        h4: "标题4",
                        h5: "标题5",
                        h6: "标题6",
                        "list-ul": "无序列表",
                        "list-ol": "有序列表",
                        hr: "横线",
                        link: "链接",
                        image: "图片",
                        code: "行内代码",
                        "preformatted-text": "预格式化文本",
                        "code-block": "代码块",
                        table: "表格",
                        datetime: "日期时间",
                        emoji: "Emoji表情",
                        "html-entities": "HTML实体",
                        pagebreak: "分页符",
                        "goto-line": "跳转到行",
                        watch: "关闭实时预览",
                        preview: "全窗口预览",
                        fullscreen: "全屏",
                        clear: "清空",
                        search: "搜索/替换",
                        help: "帮助",
                        info: "关于Editor.md"
                    }
                },
                saveHTMLToTextarea: true,
                imageUpload: false,
                imageFormats: ["jpg", "jpeg", "gif", "png", "bmp", "webp"],
                imageUploadURL: "",
                watch: true,
                emoji: true,
                tex: false,
                flowChart: false,
                sequenceDiagram: false,
                toolbarHandlers: {
                    emoji: function() {
                        if (window.customEmojiDialogInstance) {
                            window.customEmojiDialogInstance.show();
                        }
                        return false;
                    },
                    image: function() {
                        var cm = this.cm;
                        var editor = this;
                        
                        var imageLang = {
                            url: "图片地址",
                            link: "链接地址",
                            alt: "图片描述",
                            uploadButton: "上传图片",
                            imageURLEmpty: "图片地址不能为空。"
                        };
                        
                        $(".editormd-image-dialog-overlay").remove();
                        var overlay = $("<div class=\"editormd-image-dialog-overlay\"></div>");
                        var imageDialog = $("<div class=\"editormd-image-dialog\">").css("display", "block");
                        var form = $("<form class=\"editormd-form\">");
                        
                        form.append($("<label>").text(imageLang.url));
                        form.append($("<input>").attr("type", "text").attr("data-url", "").attr("placeholder", imageLang.url));
                        form.append($("<label>").text(imageLang.link));
                        form.append($("<input>").attr("type", "text").attr("data-link", "").attr("placeholder", imageLang.link));
                        form.append($("<label>").text(imageLang.alt));
                        form.append($("<input>").attr("type", "text").attr("data-alt", "").attr("placeholder", imageLang.alt));
                        form.append($("<button>").attr("type", "submit").text(imageLang.uploadButton));
                        
                        imageDialog.append(form);
                        overlay.append(imageDialog);
                        $("body").append(overlay);
                        
                        var dialog = imageDialog;
                        
                        dialog.find("form").submit(function(e) {
                            e.preventDefault();
                            var url = dialog.find("input[data-url]").val();
                            var link = dialog.find("input[data-link]").val();
                            var alt = dialog.find("input[data-alt]").val();
                            if (url === "") {
                                alert(imageLang.imageURLEmpty);
                                return false;
                            }
                            var imageMarkdown = "![" + alt + "](" + url + ")";
                            if (link !== "") {
                                imageMarkdown = "[![" + alt + "](" + url + ")](" + link + ")";
                            }
                            cm.replaceSelection(imageMarkdown);
                            overlay.remove();
                            return false;
                        });
                        
                        dialog.find("input[data-url]").val("");
                        dialog.find("input[data-link]").val("");
                        dialog.find("input[data-alt]").val("");
                        
                        overlay.on("click", function(e) {
                            if ($(e.target).is(".editormd-image-dialog-overlay")) {
                                overlay.remove();
                            }
                        });
                        
                        dialog.find("input[data-url]").focus();
                        return false;
                    },
                    link: function() {
                        var cm = this.cm;
                        var selectedText = cm.getSelection();
                        
                        var linkLang = {
                            url: "链接地址",
                            text: "链接文本",
                            urlEmpty: "链接地址不能为空。",
                            textEmpty: "链接文本不能为空。"
                        };
                        
                        $(".editormd-link-dialog-overlay").remove();
                        var overlay = $("<div class=\"editormd-link-dialog-overlay\"></div>").css({
                            "position": "fixed",
                            "top": "0",
                            "left": "0",
                            "right": "0",
                            "bottom": "0",
                            "width": "100vw",
                            "height": "100vh",
                            "background": "rgba(0, 0, 0, 0.5)",
                            "z-index": "99999",
                            "display": "flex",
                            "align-items": "center",
                            "justify-content": "center",
                            "margin": "0",
                            "padding": "0"
                        });
                        var linkDialog = $("<div class=\"editormd-link-dialog\">").css("display", "block");
                        var form = $("<form class=\"editormd-form\">");
                        
                        form.append($("<label>").text(linkLang.url));
                        form.append($("<input>").attr("type", "text").attr("data-url", "").attr("placeholder", linkLang.url));
                        form.append($("<label>").text(linkLang.text));
                        form.append($("<input>").attr("type", "text").attr("data-text", "").attr("placeholder", linkLang.text).val(selectedText));
                        form.append($("<button>").attr("type", "submit").text("确定"));
                        
                        linkDialog.append(form);
                        overlay.append(linkDialog);
                        $("body").append(overlay);
                        
                        var dialog = linkDialog;
                        
                        dialog.find("form").submit(function(e) {
                            e.preventDefault();
                            var url = dialog.find("input[data-url]").val();
                            var text = dialog.find("input[data-text]").val();
                            if (url === "") {
                                alert(linkLang.urlEmpty);
                                return false;
                            }
                            if (text === "") {
                                alert(linkLang.textEmpty);
                                return false;
                            }
                            var linkMarkdown = "[" + text + "](" + url + ")";
                            cm.replaceSelection(linkMarkdown);
                            overlay.remove();
                            return false;
                        });
                        
                        dialog.find("input[data-url]").val("");
                        
                        overlay.on("click", function(e) {
                            if ($(e.target).is(".editormd-link-dialog-overlay")) {
                                overlay.remove();
                            }
                        });
                        
                        dialog.find("input[data-url]").focus();
                        return false;
                    },
                    table: function() {
                        var cm = this.cm;
                        
                        var tableLang = {
                            rows: "行数",
                            cols: "列数",
                            rowsEmpty: "行数不能为空。",
                            colsEmpty: "列数不能为空。"
                        };
                        
                        $(".editormd-table-dialog-overlay").remove();
                        var overlay = $("<div class=\"editormd-table-dialog-overlay\"></div>").css({
                            "position": "fixed",
                            "top": "0",
                            "left": "0",
                            "right": "0",
                            "bottom": "0",
                            "width": "100vw",
                            "height": "100vh",
                            "background": "rgba(0, 0, 0, 0.5)",
                            "z-index": "99999",
                            "display": "flex",
                            "align-items": "center",
                            "justify-content": "center",
                            "margin": "0",
                            "padding": "0"
                        });
                        var tableDialog = $("<div class=\"editormd-table-dialog\">").css("display", "block");
                        var form = $("<form class=\"editormd-form\">");
                        
                        var rowsLabel = $("<label>").text(tableLang.rows);
                        var rowsInput = $("<input>").attr("type", "number").attr("data-rows", "").attr("min", "1").val(3);
                        var colsLabel = $("<label>").text(tableLang.cols);
                        var colsInput = $("<input>").attr("type", "number").attr("data-cols", "").attr("min", "1").val(3);
                        
                        form.append(rowsLabel);
                        form.append(rowsInput);
                        form.append(colsLabel);
                        form.append(colsInput);
                        form.append($("<button>").attr("type", "submit").text("确定"));
                        
                        tableDialog.append(form);
                        overlay.append(tableDialog);
                        $("body").append(overlay);
                        
                        var dialog = tableDialog;
                        
                        dialog.find("form").submit(function(e) {
                            e.preventDefault();
                            var rows = parseInt(dialog.find("input[data-rows]").val()) || 3;
                            var cols = parseInt(dialog.find("input[data-cols]").val()) || 3;
                            if (rows <= 0) {
                                alert(tableLang.rowsEmpty);
                                return false;
                            }
                            if (cols <= 0) {
                                alert(tableLang.colsEmpty);
                                return false;
                            }
                            var tableMarkdown = "";
                            tableMarkdown += "|";
                            for (var i = 0; i < cols; i++) {
                                tableMarkdown += " Header " + (i + 1) + " |";
                            }
                            tableMarkdown += "\n";
                            tableMarkdown += "|";
                            for (var i = 0; i < cols; i++) {
                                tableMarkdown += " --- |";
                            }
                            tableMarkdown += "\n";
                            for (var r = 0; r < rows; r++) {
                                tableMarkdown += "|";
                                for (var c = 0; c < cols; c++) {
                                    tableMarkdown += " Cell " + (r + 1) + "," + (c + 1) + " |";
                                }
                                tableMarkdown += "\n";
                            }
                            cm.replaceSelection(tableMarkdown);
                            overlay.remove();
                            return false;
                        });
                        
                        dialog.find("input[data-rows]").val(3);
                        dialog.find("input[data-cols]").val(3);
                        
                        overlay.on("click", function(e) {
                            if ($(e.target).is(".editormd-table-dialog-overlay")) {
                                overlay.remove();
                            }
                        });
                        
                        dialog.find("input[data-rows]").focus();
                        return false;
                    },
                    "html-entities": function() {
                        var cm = this.cm;
                        
                        var htmlEntities = {
                            "&nbsp;": " ", "&lt;": "<", "&gt;": ">", "&amp;": "&",
                            "&quot;": "\"", "&apos;": "'", "&copy;": "©", "&reg;": "®",
                            "&trade;": "™", "&times;": "×", "&divide;": "÷", "&hellip;": "…",
                            "&ldquo;": "\u201C", "&rdquo;": "\u201D", "&lsquo;": "\u2018",
                            "&rsquo;": "\u2019", "&mdash;": "—", "&ndash;": "–"
                        };
                        
                        $(".editormd-html-entities-dialog-overlay").remove();
                        var overlay = $("<div class=\"editormd-html-entities-dialog-overlay\"></div>").css({
                            "position": "fixed",
                            "top": "0",
                            "left": "0",
                            "right": "0",
                            "bottom": "0",
                            "width": "100vw",
                            "height": "100vh",
                            "background": "rgba(0, 0, 0, 0.5)",
                            "z-index": "99999",
                            "display": "flex",
                            "align-items": "center",
                            "justify-content": "center",
                            "margin": "0",
                            "padding": "0"
                        });
                        var htmlEntitiesDialog = $("<div class=\"editormd-html-entities-dialog\">").css("display", "block");
                        var list = $("<div class=\"editormd-html-entities-list\">");
                        
                        for (var entity in htmlEntities) {
                            list.append($("<button>").attr("type", "button").attr("data-entity", entity).text(entity + " (" + htmlEntities[entity] + ")"));
                        }
                        
                        htmlEntitiesDialog.append(list);
                        overlay.append(htmlEntitiesDialog);
                        $("body").append(overlay);
                        
                        var dialog = htmlEntitiesDialog;
                        
                        dialog.find("button").on("click", function() {
                            var entity = $(this).attr("data-entity");
                            cm.replaceSelection(entity);
                            overlay.remove();
                        });
                        
                        overlay.on("click", function(e) {
                            if ($(e.target).is(".editormd-html-entities-dialog-overlay")) {
                                overlay.remove();
                            }
                        });
                        
                        overlay.show();
                        return false;
                    },
                    "code-block": function() {
                        // 代码块按钮 - 使用Editor.md默认行为
                        // 插入代码块标记
                        var cm = this.cm;
                        var selectedText = cm.getSelection();
                        var codeBlock = "```\n" + (selectedText || "code") + "\n```";
                        cm.replaceSelection(codeBlock);
                        return false;
                    },
                    "preformatted-text": function() {
                        // 预格式化文本按钮 - 使用Editor.md默认行为
                        var cm = this.cm;
                        var selectedText = cm.getSelection();
                        var preformatted = "    " + (selectedText || "preformatted text");
                        cm.replaceSelection(preformatted);
                        return false;
                    }
                },
                onload: function() {
                    if (typeof initCustomEmojiDialog !== 'undefined') {
                        window.customEmojiDialogInstance = initCustomEmojiDialog(this);
                    }
                    var self = this;
                    setTimeout(function() {
                        // 绑定emoji按钮
                        var emojiBtn = $(self.toolbar).find('.fa-smile-o').parent();
                        if (emojiBtn && emojiBtn.length > 0) {
                            emojiBtn.off('click.editormd').off('click').on('click', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                e.stopImmediatePropagation();
                                if (window.customEmojiDialogInstance) {
                                    window.customEmojiDialogInstance.show();
                                }
                                return false;
                            });
                        }
                        
                        // 绑定image按钮
                        var imageBtn = $(self.toolbar).find('.fa-image').parent();
                        if (imageBtn && imageBtn.length > 0 && self.settings && self.settings.toolbarHandlers && self.settings.toolbarHandlers.image) {
                            imageBtn.off('click.editormd').off('click').on('click', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                e.stopImmediatePropagation();
                                self.settings.toolbarHandlers.image.call(self);
                                return false;
                            });
                        }
                        
                        // 绑定link按钮
                        var linkBtn = $(self.toolbar).find('.fa-link').parent();
                        if (linkBtn && linkBtn.length > 0 && self.settings && self.settings.toolbarHandlers && self.settings.toolbarHandlers.link) {
                            linkBtn.off('click.editormd').off('click').on('click', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                e.stopImmediatePropagation();
                                self.settings.toolbarHandlers.link.call(self);
                                return false;
                            });
                        }
                        
                        // 绑定table按钮
                        var tableBtn = $(self.toolbar).find('.fa-table').parent();
                        if (tableBtn && tableBtn.length > 0 && self.settings && self.settings.toolbarHandlers && self.settings.toolbarHandlers.table) {
                            tableBtn.off('click.editormd').off('click').on('click', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                e.stopImmediatePropagation();
                                self.settings.toolbarHandlers.table.call(self);
                                return false;
                            });
                        }
                        
                        // 绑定html-entities按钮
                        var htmlEntitiesBtn = $(self.toolbar).find('.fa-at').parent();
                        if (htmlEntitiesBtn && htmlEntitiesBtn.length > 0 && self.settings && self.settings.toolbarHandlers && self.settings.toolbarHandlers['html-entities']) {
                            htmlEntitiesBtn.off('click.editormd').off('click').on('click', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                e.stopImmediatePropagation();
                                self.settings.toolbarHandlers['html-entities'].call(self);
                                return false;
                            });
                        }
                        
                        // 绑定code-block按钮
                        var codeBlockBtn = $(self.toolbar).find('.fa-file-code-o').parent();
                        if (codeBlockBtn && codeBlockBtn.length > 0 && self.settings && self.settings.toolbarHandlers && self.settings.toolbarHandlers['code-block']) {
                            codeBlockBtn.off('click.editormd').off('click').on('click', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                e.stopImmediatePropagation();
                                self.settings.toolbarHandlers['code-block'].call(self);
                                return false;
                            });
                        }
                        
                        // 绑定preformatted-text按钮
                        var preformattedBtn = $(self.toolbar).find('.fa-file-text-o').parent();
                        if (preformattedBtn && preformattedBtn.length > 0 && self.settings && self.settings.toolbarHandlers && self.settings.toolbarHandlers['preformatted-text']) {
                            preformattedBtn.off('click.editormd').off('click').on('click', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                e.stopImmediatePropagation();
                                self.settings.toolbarHandlers['preformatted-text'].call(self);
                                return false;
                            });
                        }
                    }, 200);
                }
            });
            
            
            // 清理Markdown中的HTML标签片段
            function cleanMarkdown(markdown) {
                if (!markdown) return markdown;
                
                // 移除HTML属性片段（如 " class="reference-link">" 这样的残留）
                // 只匹配在行首或空格后出现的属性片段
                markdown = markdown.replace(/\s*" class="[^"]*"\s*>/g, '');
                markdown = markdown.replace(/\s*" id="[^"]*"\s*>/g, '');
                markdown = markdown.replace(/\s*" style="[^"]*"\s*>/g, '');
                markdown = markdown.replace(/\s*"[a-zA-Z-]+="[^"]*"\s*>/g, '');
                
                // 移除单独的 class="xxx"> 这样的片段（不在HTML标签内的）
                markdown = markdown.replace(/\s+class="[^"]*"\s*>/g, '');
                markdown = markdown.replace(/\s+id="[^"]*"\s*>/g, '');
                markdown = markdown.replace(/\s+style="[^"]*"\s*>/g, '');
                
                // 移除单独的 > 符号（可能是标签残留，但不在代码块中）
                // 使用负向前瞻确保不在代码块内
                markdown = markdown.replace(/^(\s*)>\s*$/gm, '$1');
                
                return markdown;
            }
            
            // 表单提交前，确保获取最新的 Markdown 和 HTML 内容
            $("#chapterForm").on("submit", function() {
                if (editor) {
                    var markdown = editor.getMarkdown();
                    var html = editor.getHTML();
                    
                    // 清理Markdown中的HTML标签片段
                    markdown = cleanMarkdown(markdown);
                    
                    // 更新 textarea 的值
                    $("textarea[name='content']").val(markdown);
                    
                    // 添加隐藏字段保存 HTML
                    if ($("input[name='html_content']").length === 0) {
                        $(this).append('<input type="hidden" name="html_content" value="">');
                    }
                    $("input[name='html_content']").val(html);
                }
            });
        });
    </script>
</body>
</html>


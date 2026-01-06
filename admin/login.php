<?php
/**
 * 作者：走小散
 * 微信公众号：走小散
 */
require_once '../config/config.php';

// 如果已登录，跳转到管理首页
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

// 登录失败次数限制
$login_attempts_key = 'login_attempts_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
$max_attempts = 5;
$lockout_time = 900; // 15分钟

if (isset($_SESSION[$login_attempts_key])) {
    $attempts = $_SESSION[$login_attempts_key];
    if ($attempts['count'] >= $max_attempts) {
        $elapsed = time() - $attempts['time'];
        if ($elapsed < $lockout_time) {
            $remaining = ceil(($lockout_time - $elapsed) / 60);
            $error = "登录失败次数过多，请 {$remaining} 分钟后再试";
        } else {
            unset($_SESSION[$login_attempts_key]);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $captcha = trim($_POST['captcha'] ?? '');
    
    // 输入验证
    if (empty($username) || empty($password) || empty($captcha)) {
        $error = '请填写完整信息';
    } elseif (strlen($username) > 50 || strlen($password) > 100) {
        $error = '输入内容过长';
    } else {
        // 验证验证码
        $session_captcha = $_SESSION['admin_captcha'] ?? '';
        if (empty($captcha) || strtolower($captcha) !== strtolower($session_captcha)) {
            $error = '验证码错误';
            unset($_SESSION['admin_captcha']);
            
            // 记录失败次数
            if (!isset($_SESSION[$login_attempts_key])) {
                $_SESSION[$login_attempts_key] = ['count' => 1, 'time' => time()];
            } else {
                $_SESSION[$login_attempts_key]['count']++;
                $_SESSION[$login_attempts_key]['time'] = time();
            }
        } else {
            unset($_SESSION['admin_captcha']);
            
            require_once '../config/database.php';
            $db = Database::getInstance()->getConnection();
            
            // 确保settings表存在
            try {
                $db->exec("CREATE TABLE IF NOT EXISTS `settings` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `setting_key` varchar(100) NOT NULL COMMENT '设置键名',
                  `setting_value` text COMMENT '设置值',
                  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `setting_key` (`setting_key`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            } catch(PDOException $e) {
                // 表已存在，忽略错误
            }
            
            function getSetting($db, $key, $default = '') {
                $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
                $stmt->execute([$key]);
                $result = $stmt->fetch();
                return $result ? $result['setting_value'] : $default;
            }
            
            $saved_password = getSetting($db, 'admin_password', '');
            $admin_password = !empty($saved_password) ? $saved_password : ADMIN_PASSWORD;
            
            // 使用hash_equals防止时序攻击
            if ($username === ADMIN_USERNAME && hash_equals($admin_password, $password)) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                unset($_SESSION[$login_attempts_key]);
                header('Location: index.php');
                exit;
            } else {
                $error = '用户名或密码错误';
                
                // 记录失败次数
                if (!isset($_SESSION[$login_attempts_key])) {
                    $_SESSION[$login_attempts_key] = ['count' => 1, 'time' => time()];
                } else {
                    $_SESSION[$login_attempts_key]['count']++;
                    $_SESSION[$login_attempts_key]['time'] = time();
                }
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
    <title>管理员登录 - <?php echo SITE_NAME; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Hiragino Sans GB', 'Microsoft YaHei', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 80px;
            position: relative;
            overflow: hidden;
        }
        .bg-layer {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            transition: opacity 1s ease-in-out;
            z-index: 0;
        }
        .bg-layer.current {
            opacity: 1;
            z-index: 0;
        }
        .bg-layer.next {
            opacity: 0;
            z-index: -1;
        }
        .bg-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.3);
            z-index: 1;
            pointer-events: none;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 2;
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        .captcha-group {
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }
        .captcha-input {
            flex: 1;
        }
        .captcha-image {
            cursor: pointer;
            border: 1px solid #ddd;
            border-radius: 5px;
            height: 40px;
            width: 120px;
            object-fit: contain;
            background: #f5f5f5;
        }
        .captcha-image:hover {
            opacity: 0.8;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #5568d3;
        }
        .error {
            color: #e74c3c;
            margin-bottom: 15px;
            padding: 10px;
            background: #fee;
            border-radius: 5px;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            body {
                justify-content: center;
                padding-right: 0;
                padding: 20px;
            }
            .login-container {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>管理员登录</h1>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>用户名</label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label>密码</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>验证码</label>
                <div class="captcha-group">
                    <input type="text" name="captcha" class="captcha-input" required placeholder="请输入验证码" maxlength="4">
                    <img src="captcha.php?t=<?php echo time(); ?>" alt="验证码" class="captcha-image" id="captchaImage" onclick="refreshCaptcha()" title="点击刷新验证码">
                </div>
            </div>
            <button type="submit">登录</button>
        </form>
    </div>
    
    <div class="bg-overlay"></div>
    
    <script>
        // 背景图每10秒刷新一次
        let bgImageUrl = 'https://img.8845.top/good';
        let currentLayer = 0;
        let layers = [];
        
        // 创建背景层
        function createBgLayer() {
            const layer = document.createElement('div');
            layer.className = 'bg-layer';
            document.body.appendChild(layer);
            layers.push(layer);
            return layer;
        }
        
        // 预加载背景图
        function preloadBackground(callback) {
            const img = new Image();
            const url = bgImageUrl + '?t=' + new Date().getTime();
            
            img.onload = function() {
                if (callback) callback(url);
            };
            
            img.onerror = function() {
                // 如果加载失败，使用默认背景
                if (callback) callback('linear-gradient(135deg, #667eea 0%, #764ba2 100%)');
            };
            
            img.src = url;
        }
        
        // 更新背景（带淡入淡出效果）
        function updateBackground() {
            preloadBackground(function(url) {
                // 获取当前层和下一层
                const currentBgLayer = layers[currentLayer] || createBgLayer();
                currentLayer = (currentLayer + 1) % 2;
                const nextBgLayer = layers[currentLayer] || createBgLayer();
                
                // 设置下一层背景
                nextBgLayer.style.backgroundImage = 'url(' + url + ')';
                nextBgLayer.className = 'bg-layer next';
                
                // 延迟一点时间确保图片已渲染，然后淡入
                setTimeout(function() {
                    nextBgLayer.className = 'bg-layer current';
                    currentBgLayer.className = 'bg-layer next';
                }, 50);
            });
        }
        
        // 初始化：创建两个背景层
        createBgLayer();
        createBgLayer();
        
        // 初始设置背景
        updateBackground();
        
        // 每10秒刷新背景
        setInterval(updateBackground, 10000);
        
        // 刷新验证码
        function refreshCaptcha() {
            var img = document.getElementById('captchaImage');
            if (img) {
                img.src = 'captcha.php?t=' + new Date().getTime();
            }
        }
    </script>
</body>
</html>

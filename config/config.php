<?php
/**
 * 作者：走小散
 * 微信公众号：走小散
 */
// 应用配置
define('SITE_NAME', '文档管理系统');
define('BASE_URL', '/');
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin123'); // 实际生产环境应该使用哈希密码

// 会话配置
session_start();

// 安全头部设置
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// 如果使用HTTPS，添加以下头部
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 错误报告（生产环境关闭）
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// 引入数据库配置
require_once __DIR__ . '/database.php';

// 引入URL辅助函数
require_once __DIR__ . '/url_helper.php';


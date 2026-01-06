<?php
/**
 * 作者：走小散
 * 微信公众号：走小散
 */
// 检查登录状态
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}


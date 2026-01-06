<?php
/**
 * 作者：走小散
 * 微信公众号：走小散
 */
session_start();
session_destroy();
header('Location: login.php');
exit;


<?php
/**
 * 作者：走小散
 * 微信公众号：走小散
 */
if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    function sanitizeInput($input, $type = 'string') {
        if (is_array($input)) {
            return array_map(function($item) use ($type) {
                return sanitizeInput($item, $type);
            }, $input);
        }
        
        switch ($type) {
            case 'int':
                return intval($input);
            case 'float':
                return floatval($input);
            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
            case 'string':
            default:
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }
    }
    
    function validateFileUpload($file, $allowed_types = [], $max_size = 2097152) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => '文件上传错误'];
        }
        
        $file_type = $file['type'];
        $file_size = $file['size'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        $allowed_extensions = [];
        $mime_to_ext = [
            'image/png' => 'png',
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/jpg' => ['jpg', 'jpeg'],
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg'
        ];
        
        foreach ($allowed_types as $mime) {
            if (isset($mime_to_ext[$mime])) {
                if (is_array($mime_to_ext[$mime])) {
                    $allowed_extensions = array_merge($allowed_extensions, $mime_to_ext[$mime]);
                } else {
                    $allowed_extensions[] = $mime_to_ext[$mime];
                }
            }
        }
        $allowed_extensions = array_unique($allowed_extensions);
        
        if (!in_array($file_type, $allowed_types)) {
            return ['success' => false, 'error' => '不支持的文件类型'];
        }
        
        if (!in_array($file_ext, $allowed_extensions)) {
            return ['success' => false, 'error' => '文件扩展名与类型不匹配'];
        }
        
        if ($file_size > $max_size) {
            return ['success' => false, 'error' => '文件大小超过限制'];
        }
        
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detected_mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($detected_mime, $allowed_types)) {
                return ['success' => false, 'error' => '文件MIME类型验证失败'];
            }
        }
        
        return ['success' => true];
    }
}


<?php
/**
 * 作者：走小散
 * 微信公众号：走小散
 */
function getCustomEmojiFolders() {
    $emojiDir = __DIR__ . '/../assets/emoji';
    $folders = [];
    
    if (!is_dir($emojiDir)) {
        return $folders;
    }
    
    $excludeDirs = ['36x36', 'github', '.', '..'];
    $items = scandir($emojiDir);
    
    foreach ($items as $item) {
        $fullPath = $emojiDir . '/' . $item;
        if (is_dir($fullPath) && !in_array($item, $excludeDirs)) {
            $images = [];
            $files = scandir($fullPath);
            
            $scanDir = function($dir, $relativePath = '') use (&$scanDir, &$images, $item) {
                $files = scandir($dir);
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') continue;
                    
                    $filePath = $dir . '/' . $file;
                    $currentRelativePath = $relativePath ? $relativePath . '/' . $file : $file;
                    
                    if (is_dir($filePath)) {
                        $scanDir($filePath, $currentRelativePath);
                    } elseif (is_file($filePath)) {
                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        if (in_array($ext, ['png', 'gif', 'jpg', 'jpeg'])) {
                            $images[] = [
                                'name' => $file,
                                'path' => '../assets/emoji/' . $item . '/' . $currentRelativePath,
                                'display' => pathinfo($file, PATHINFO_FILENAME)
                            ];
                        }
                    }
                }
            };
            
            $scanDir($fullPath);
            
            if (!empty($images)) {
                $folders[] = [
                    'name' => $item,
                    'images' => $images
                ];
            }
        }
    }
    
    return $folders;
}

header('Content-Type: application/json');
echo json_encode(getCustomEmojiFolders(), JSON_UNESCAPED_UNICODE);


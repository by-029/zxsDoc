<?php
// 简化的Markdown解析器（生产环境建议使用完整的Parsedown库）
// 这里提供一个基本实现，实际项目中应该使用 composer require erusev/parsedown

if (!class_exists('Parsedown')) {
    class Parsedown {
        public function text($text) {
            // 基本的Markdown转HTML转换
            $html = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            
            // 标题
            $html = preg_replace('/^### (.*$)/m', '<h3>$1</h3>', $html);
            $html = preg_replace('/^## (.*$)/m', '<h2>$1</h2>', $html);
            $html = preg_replace('/^# (.*$)/m', '<h1>$1</h1>', $html);
            
            // 粗体和斜体
            $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);
            $html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $html);
            
            // 链接
            $html = preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', '<a href="$2">$1</a>', $html);
            
            // 代码块
            $html = preg_replace('/```([^`]+)```/s', '<pre><code>$1</code></pre>', $html);
            $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);
            
            // 列表
            $html = preg_replace('/^\* (.*$)/m', '<li>$1</li>', $html);
            $html = preg_replace('/^- (.*$)/m', '<li>$1</li>', $html);
            $html = preg_replace('/(<li>.*<\/li>\n?)+/s', '<ul>$0</ul>', $html);
            
            // 段落
            $html = preg_replace('/\n\n/', '</p><p>', $html);
            $html = '<p>' . $html . '</p>';
            $html = preg_replace('/<p>\s*<(h[1-6]|ul|pre)/', '<$2', $html);
            $html = preg_replace('/(<\/h[1-6]|<\/ul>|<\/pre>)\s*<\/p>/', '$1', $html);
            
            return $html;
        }
    }
}


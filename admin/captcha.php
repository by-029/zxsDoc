<?php
/**
 * 作者：走小散
 * 微信公众号：走小散
 */
session_start();

// 创建验证码图片
$width = 120;
$height = 40;
$img = imagecreatetruecolor($width, $height);

// 背景色（浅灰色）
$bg_color = imagecolorallocate($img, 245, 245, 245);
imagefill($img, 0, 0, $bg_color);

// 生成4位随机数字验证码
$code = '';
for ($i = 0; $i < 4; $i++) {
    $code .= rand(0, 9);
}

// 保存验证码到session
$_SESSION['admin_captcha'] = $code;

// 文字颜色（深灰色）
$text_color = imagecolorallocate($img, 51, 51, 51);

// 在图片上绘制验证码
$x = 15;
$y = 28;

for ($i = 0; $i < 4; $i++) {
    $char = $code[$i];
    $char_color = imagecolorallocate($img, rand(0, 100), rand(0, 100), rand(0, 100));
    $x_offset = $x + $i * 25 + rand(-3, 3);
    $y_offset = $y + rand(-5, 5);
    imagestring($img, 5, $x_offset, $y_offset, $char, $char_color);
}

// 添加干扰线
for ($i = 0; $i < 5; $i++) {
    $line_color = imagecolorallocate($img, rand(200, 255), rand(200, 255), rand(200, 255));
    imageline($img, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $line_color);
}

// 添加干扰点
for ($i = 0; $i < 50; $i++) {
    $pixel_color = imagecolorallocate($img, rand(200, 255), rand(200, 255), rand(200, 255));
    imagesetpixel($img, rand(0, $width), rand(0, $height), $pixel_color);
}

// 输出图片
header('Content-Type: image/png');
header('Cache-Control: no-cache, must-revalidate');
imagepng($img);
imagedestroy($img);
?>


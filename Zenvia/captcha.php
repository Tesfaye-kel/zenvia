<?php
session_start();

// Generate random CAPTCHA code
function generateCaptchaCode($length = 6) {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

// Create and output CAPTCHA image
function createCaptchaImage() {
    $code = generateCaptchaCode();
    $_SESSION['captcha_code'] = $code;
    
    // Create image
    $width = 150;
    $height = 50;
    $image = imagecreate($width, $height);
    
    // Colors
    $bg_color = imagecolorallocate($image, 255, 255, 255);
    $text_color = imagecolorallocate($image, 0, 0, 0);
    $line_color = imagecolorallocate($image, 200, 200, 200);
    $noise_color = imagecolorallocate($image, 180, 180, 180);
    
    // Add random lines
    for ($i = 0; $i < 5; $i++) {
        imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $line_color);
    }
    
    // Add random noise points
    for ($i = 0; $i < 100; $i++) {
        imagesetpixel($image, rand(0, $width), rand(0, $height), $noise_color);
    }
    
    // Add text
    $font_size = 5;
    $x = 15;
    $y = 12;
    imagestring($image, $font_size, $x, $y, $code, $text_color);
    
    // Output image
    header('Content-Type: image/png');
    imagepng($image);
    imagedestroy($image);
}

// Validate CAPTCHA
function validateCaptcha($user_input) {
    if (!isset($_SESSION['captcha_code'])) {
        return false;
    }
    $is_valid = strtoupper($user_input) === strtoupper($_SESSION['captcha_code']);
    // Regenerate CAPTCHA after validation attempt
    unset($_SESSION['captcha_code']);
    return $is_valid;
}

// If called directly, generate the image
if (!isset($_SESSION)) {
    session_start();
}
createCaptchaImage();
?>

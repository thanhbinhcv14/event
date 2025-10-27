<?php
// Create a simple placeholder image for locations without images
$width = 200;
$height = 120;

// Create image
$image = imagecreate($width, $height);

// Define colors
$bg_color = imagecolorallocate($image, 240, 240, 240); // Light gray background
$text_color = imagecolorallocate($image, 100, 100, 100); // Dark gray text
$border_color = imagecolorallocate($image, 200, 200, 200); // Light border

// Fill background
imagefill($image, 0, 0, $bg_color);

// Draw border
imagerectangle($image, 0, 0, $width-1, $height-1, $border_color);

// Add text
$text = "No Image";
$font_size = 3;
$text_width = imagefontwidth($font_size) * strlen($text);
$text_height = imagefontheight($font_size);
$x = ($width - $text_width) / 2;
$y = ($height - $text_height) / 2;

imagestring($image, $font_size, $x, $y, $text, $text_color);

// Output image
header('Content-Type: image/jpeg');
imagejpeg($image);
imagedestroy($image);
?>

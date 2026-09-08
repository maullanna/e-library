<?php
/**
 * Basic thumbnail generator without SLiMS dependencies
 */

// Get parameters
$filename = isset($_GET['filename']) ? urldecode($_GET['filename']) : '';
$width = isset($_GET['width']) ? (int)$_GET['width'] : 120;

// Basic validation
if (empty($filename)) {
    http_response_code(404);
    exit('File not found');
}

// Get full path (assuming we're in lib/minigalnano/)
$fullPath = __DIR__ . '/../../' . $filename;

// Check if file exists
if (!file_exists($fullPath)) {
    http_response_code(404);
    exit('File not found');
}

// Get image info
$imageInfo = getimagesize($fullPath);
if (!$imageInfo) {
    http_response_code(500);
    exit('Invalid image file');
}

$originalWidth = $imageInfo[0];
$originalHeight = $imageInfo[1];
$mimeType = $imageInfo['mime'];

// Calculate new height
$height = round(($width / $originalWidth) * $originalHeight);

// Set headers
header('Content-Type: ' . $mimeType);
header('Cache-Control: max-age=86400');

// Create image resource
$source = null;
switch ($mimeType) {
    case 'image/jpeg':
        $source = imagecreatefromjpeg($fullPath);
        break;
    case 'image/png':
        $source = imagecreatefrompng($fullPath);
        break;
    case 'image/gif':
        $source = imagecreatefromgif($fullPath);
        break;
    default:
        http_response_code(500);
        exit('Unsupported image type');
}

if (!$source) {
    http_response_code(500);
    exit('Failed to create image resource');
}

// Create thumbnail
$thumbnail = imagecreatetruecolor($width, $height);

// Preserve transparency for PNG
if ($mimeType == 'image/png') {
    imagealphablending($thumbnail, false);
    imagesavealpha($thumbnail, true);
    $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
    imagefilledrectangle($thumbnail, 0, 0, $width, $height, $transparent);
}

// Resize image
imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);

// Output image
switch ($mimeType) {
    case 'image/jpeg':
        imagejpeg($thumbnail, null, 90);
        break;
    case 'image/png':
        imagepng($thumbnail, null, 9);
        break;
    case 'image/gif':
        imagegif($thumbnail);
        break;
}

// Clean up
imagedestroy($source);
imagedestroy($thumbnail);

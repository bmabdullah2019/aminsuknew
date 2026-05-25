<?php

namespace App\Helpers;

/**
 * Native GD-based image processing helper.
 * Replaces Intervention Image to avoid fileinfo extension dependency issues.
 */
class ImageHelper
{
    /**
     * Process an uploaded image: resize (maintaining aspect ratio) and save as WebP.
     *
     * @param  string  $sourcePath  The real path of the uploaded file.
     * @param  string  $savePath  The destination path to save the WebP image.
     * @param  int  $quality  WebP quality (0-100).
     */
    public static function processAndSaveWebp(string $sourcePath, string $savePath, int $quality = 90): bool
    {
        $imageInfo = @\getimagesize($sourcePath);
        if ($imageInfo === false) {
            return false;
        }

        $mime = $imageInfo['mime'];
        $sourceImage = self::createImageFromFile($sourcePath, $mime);

        if ($sourceImage === false) {
            return false;
        }

        // Ensure the directory exists
        $dir = \dirname($savePath);
        if (! \is_dir($dir)) {
            \mkdir($dir, 0755, true);
        }

        // Save as WebP
        if (! \function_exists('imagewebp')) {
            \imagedestroy($sourceImage);

            return false;
        }

        $result = \imagewebp($sourceImage, $savePath, $quality);
        \imagedestroy($sourceImage);

        return $result;
    }

    /**
     * Process an uploaded image with resize: resize (maintaining aspect ratio) and save as WebP.
     *
     * @param  string  $sourcePath  The real path of the uploaded file.
     * @param  string  $savePath  The destination path to save the WebP image.
     * @param  int|null  $maxWidth  Max width to resize to (null to auto-calculate from height).
     * @param  int|null  $maxHeight  Max height to resize to (null to auto-calculate from width).
     * @param  int  $quality  WebP quality (0-100).
     */
    public static function resizeAndSaveWebp(string $sourcePath, string $savePath, ?int $maxWidth = null, ?int $maxHeight = null, int $quality = 90): bool
    {
        if (! \function_exists('imagecreatetruecolor')) {
            return false;
        }

        $imageInfo = @\getimagesize($sourcePath);
        if ($imageInfo === false) {
            return false;
        }

        $mime = $imageInfo['mime'];
        $origWidth = $imageInfo[0];
        $origHeight = $imageInfo[1];

        $sourceImage = self::createImageFromFile($sourcePath, $mime);
        if ($sourceImage === false) {
            return false;
        }

        // Calculate new dimensions maintaining aspect ratio
        $newWidth = $origWidth;
        $newHeight = $origHeight;

        if ($maxWidth !== null && $maxHeight !== null) {
            // Fit within both dimensions
            $ratioW = $maxWidth / $origWidth;
            $ratioH = $maxHeight / $origHeight;
            $ratio = \min($ratioW, $ratioH);
            $newWidth = (int) \round($origWidth * $ratio);
            $newHeight = (int) \round($origHeight * $ratio);
        } elseif ($maxWidth !== null) {
            $ratio = $maxWidth / $origWidth;
            $newWidth = $maxWidth;
            $newHeight = (int) \round($origHeight * $ratio);
        } elseif ($maxHeight !== null) {
            $ratio = $maxHeight / $origHeight;
            $newHeight = $maxHeight;
            $newWidth = (int) \round($origWidth * $ratio);
        }

        // Create resized image
        $resizedImage = \imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG/WebP
        \imagealphablending($resizedImage, false);
        \imagesavealpha($resizedImage, true);

        \imagecopyresampled(
            $resizedImage,
            $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $origWidth, $origHeight
        );

        // Ensure directory exists
        $dir = \dirname($savePath);
        if (! \is_dir($dir)) {
            \mkdir($dir, 0755, true);
        }

        // Save as WebP
        if (! \function_exists('imagewebp')) {
            \imagedestroy($sourceImage);
            \imagedestroy($resizedImage);

            return false;
        }

        $result = \imagewebp($resizedImage, $savePath, $quality);

        \imagedestroy($sourceImage);
        \imagedestroy($resizedImage);

        return $result;
    }

    /**
     * Save an image as JPG format.
     *
     * @param  string  $sourcePath  The real path of the source file.
     * @param  string  $savePath  The destination path to save the JPG image.
     * @param  int  $quality  JPG quality (0-100).
     */
    public static function saveAsJpg(string $sourcePath, string $savePath, int $quality = 90): bool
    {
        if (! \function_exists('imagecreatetruecolor')) {
            return false;
        }

        $imageInfo = @\getimagesize($sourcePath);
        if ($imageInfo === false) {
            return false;
        }

        $mime = $imageInfo['mime'];
        $sourceImage = self::createImageFromFile($sourcePath, $mime);

        if ($sourceImage === false) {
            return false;
        }

        // Ensure the directory exists
        $dir = \dirname($savePath);
        if (! \is_dir($dir)) {
            \mkdir($dir, 0755, true);
        }

        // For JPG, handle transparency by filling with white background
        $width = \imagesx($sourceImage);
        $height = \imagesy($sourceImage);
        $jpgImage = \imagecreatetruecolor($width, $height);
        $white = \imagecolorallocate($jpgImage, 255, 255, 255);
        \imagefill($jpgImage, 0, 0, $white);
        \imagecopy($jpgImage, $sourceImage, 0, 0, 0, 0, $width, $height);

        if (! \function_exists('imagejpeg')) {
            \imagedestroy($sourceImage);
            \imagedestroy($jpgImage);

            return false;
        }

        $result = \imagejpeg($jpgImage, $savePath, $quality);

        \imagedestroy($sourceImage);
        \imagedestroy($jpgImage);

        return $result;
    }

    /**
     * Create a GD image resource from a file path based on MIME type.
     *
     * @return \GdImage|false
     */
    private static function createImageFromFile(string $path, string $mime)
    {
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                return \function_exists('imagecreatefromjpeg') ? \imagecreatefromjpeg($path) : false;
            case 'image/png':
                if (! \function_exists('imagecreatefrompng')) {
                    return false;
                }
                $img = \imagecreatefrompng($path);
                if ($img !== false) {
                    \imagealphablending($img, true);
                    \imagesavealpha($img, true);
                }

                return $img;
            case 'image/gif':
                return \function_exists('imagecreatefromgif') ? \imagecreatefromgif($path) : false;
            case 'image/webp':
                return \function_exists('imagecreatefromwebp') ? \imagecreatefromwebp($path) : false;
            case 'image/bmp':
            case 'image/x-ms-bmp':
                return \function_exists('imagecreatefrombmp') ? \imagecreatefrombmp($path) : false;
            default:
                return false;
        }
    }
}

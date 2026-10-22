<?php
/**
 * Featured image preparation. Port of extract_news ImageService (which used
 * Intervention Image on GD): the image is fitted, centered and letterboxed
 * on a 700x400 #005A8C canvas, saved as JPEG (q85, retried at q60 above
 * 1 MB). The result waits in uploads/amnp-queue/ until both language posts
 * are published, then it is imported into each site's media library.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AMNP_Image
{
    public static function queueDir(): string
    {
        switch_to_blog(AMNP_Config::int('FR_BLOG_ID'));
        $uploads = wp_upload_dir(null, false);
        restore_current_blog();

        $dir = trailingslashit($uploads['basedir']) . 'amnp-queue';
        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
            // Never list or serve the queue directly.
            @file_put_contents($dir . '/index.php', "<?php\n// Silence is golden.\n");
        }
        return $dir;
    }

    /** Download an image URL and prepare it; returns the queued file path or null. */
    public function downloadAndOptimizeImage(string $imageUrl, string $newsTitle): ?string
    {
        $response = wp_remote_get($imageUrl, [
            'timeout'             => 30,
            'limit_response_size' => 20 * MB_IN_BYTES,
        ]);

        if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
            AMNP_Logger::warning("Failed to download image: {$imageUrl}", [
                'error' => is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_response_code($response),
            ]);
            return null;
        }

        $tempPath = wp_tempnam('amnp-image');
        file_put_contents($tempPath, wp_remote_retrieve_body($response));
        $result = $this->processImage($tempPath, $newsTitle);
        @unlink($tempPath);

        return $result;
    }

    /** Prepare an email attachment already saved on disk by php-imap. */
    public function processAttachmentImage(string $filePath, string $newsTitle): ?string
    {
        if (!file_exists($filePath)) {
            AMNP_Logger::warning("Image file not found: {$filePath}");
            return null;
        }
        return $this->processImage($filePath, $newsTitle);
    }

    public function isValidImageUrl(string $url): bool
    {
        $path = (string) wp_parse_url($url, PHP_URL_PATH);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) && filter_var($url, FILTER_VALIDATE_URL);
    }

    private function processImage(string $filePath, string $newsTitle): ?string
    {
        $width   = AMNP_Config::int('IMAGE_WIDTH');
        $height  = AMNP_Config::int('IMAGE_HEIGHT');
        $maxSize = AMNP_Config::int('IMAGE_MAX_SIZE');

        try {
            $data = file_get_contents($filePath);
            $source = $data !== false ? @imagecreatefromstring($data) : false;
            if (!$source) {
                throw new \RuntimeException('Unsupported or corrupt image');
            }
            $source = $this->applyExifOrientation($source, $filePath);

            $origWidth = imagesx($source);
            $origHeight = imagesy($source);
            if ($origWidth <= 0 || $origHeight <= 0) {
                throw new \RuntimeException('Invalid image dimensions');
            }

            $canvas = imagecreatetruecolor($width, $height);
            [$r, $g, $b] = $this->hexToRgb(AMNP_Config::str('IMAGE_BACKGROUND_COLOR'));
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, $r, $g, $b));

            // Up- or downscale so one axis fills the canvas without overflowing.
            $scale = min($width / $origWidth, $height / $origHeight);
            $newWidth = max(1, min($width, (int) round($origWidth * $scale)));
            $newHeight = max(1, min($height, (int) round($origHeight * $scale)));
            $x = (int) (($width - $newWidth) / 2);
            $y = (int) (($height - $newHeight) / 2);

            imagealphablending($canvas, true);
            imagecopyresampled($canvas, $source, $x, $y, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
            imagedestroy($source);

            $target = $this->targetPath($newsTitle, 'jpg');
            imagejpeg($canvas, $target, AMNP_Config::int('IMAGE_QUALITY'));
            clearstatcache(true, $target);

            if (filesize($target) > $maxSize) {
                AMNP_Logger::warning('Image size exceeds limit: ' . filesize($target) . " > {$maxSize}");
                imagejpeg($canvas, $target, 60);
                clearstatcache(true, $target);
                if (filesize($target) > $maxSize) {
                    imagedestroy($canvas);
                    @unlink($target);
                    return null;
                }
            }

            imagedestroy($canvas);
            return $target;
        } catch (\Throwable $e) {
            AMNP_Logger::error('Error in image processing: ' . $e->getMessage());
            return $this->storeOriginalImage($filePath, $newsTitle);
        }
    }

    private function storeOriginalImage(string $filePath, string $newsTitle): ?string
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($extension === '' || in_array($extension, ['bin', 'tmp'], true)) {
            $extension = match (mime_content_type($filePath) ?: '') {
                'image/png'  => 'png',
                'image/webp' => 'webp',
                'image/gif'  => 'gif',
                default      => 'jpg',
            };
        }

        $target = $this->targetPath($newsTitle, $extension);
        return @copy($filePath, $target) ? $target : null;
    }

    private function targetPath(string $newsTitle, string $extension): string
    {
        $slug = substr(sanitize_title($newsTitle), 0, 100);
        $slug = $slug !== '' ? $slug : 'news';
        return trailingslashit(self::queueDir()) . "{$slug}-" . uniqid() . ".{$extension}";
    }

    /** Intervention auto-oriented JPEGs from EXIF; GD does not. */
    private function applyExifOrientation(\GdImage $image, string $filePath): \GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $image;
        }
        $exif = @exif_read_data($filePath);
        $orientation = (int) ($exif['Orientation'] ?? 1);

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };
        if ($rotated instanceof \GdImage) {
            imagedestroy($image);
            return $rotated;
        }
        return $image;
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (!preg_match('/^[0-9a-f]{6}$/i', $hex)) {
            $hex = '005A8C';
        }
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }
}

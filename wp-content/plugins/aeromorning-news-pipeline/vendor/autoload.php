<?php
// Bundled php-imap/php-imap 5.0.1 (MIT) — same library extract_news used.
// Scoped autoloader: only resolves PhpImap\*, never touches other plugins.
if (!defined('ABSPATH')) {
    exit;
}
spl_autoload_register(static function (string $class): void {
    if (strpos($class, 'PhpImap\\') !== 0) {
        return;
    }
    $file = __DIR__ . '/php-imap/' . str_replace('\\', '/', $class) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

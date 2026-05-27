<?php

declare(strict_types=1);

spl_autoload_register(static function (string $symbol): void {
    $baseName = basename(str_replace('\\', '/', $symbol));
    $filePath = __DIR__ . '/' . $baseName . '.php';

    if (is_file($filePath)) {
        require_once $filePath;
    }
});

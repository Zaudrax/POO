<?php

declare(strict_types=1);

spl_autoload_register(static function (string $symbol): void {
    // Cache construit une seule fois pour éviter de rescanner le disque a chaque classe.
    static $classMap = null;

    if ($classMap === null) {
        $classMap = [];
        // On autorise les classes dans src (nouvelle structure) et a la racine (retro-compatibilite).
        $directories = [
            __DIR__ . '/src',
            __DIR__,
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                // Convention actuelle: NomClasse <=> NomClasse.php
                $baseName = $file->getBasename('.php');
                $classMap[$baseName] = $file->getPathname();
            }
        }
    }

    // Si un namespace est passe, on conserve seulement le nom final de la classe.
    $baseName = basename(str_replace('\\', '/', $symbol));
    if (isset($classMap[$baseName]) && is_file($classMap[$baseName])) {
        require_once $classMap[$baseName];
    }
});

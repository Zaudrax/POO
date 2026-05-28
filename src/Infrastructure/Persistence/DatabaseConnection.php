<?php

declare(strict_types=1);

final class DatabaseConnection
{
    private static ?DatabaseConnection $instance = null;

    private PDO $pdo;

    private function __construct(string $databaseFile)
    {
        $directory = dirname($databaseFile);
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
                throw new RuntimeException('Unable to create database directory: ' . $directory);
            }
        }

        $dsn = 'sqlite:' . $databaseFile;

        $this->pdo = new PDO($dsn);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public static function getInstance(?string $databaseFile = null): DatabaseConnection
    {
        if (self::$instance === null) {
            if ($databaseFile === null || trim($databaseFile) === '') {
                throw new InvalidArgumentException('The database path is required for first initialization.');
            }

            self::$instance = new self($databaseFile);
        }

        return self::$instance;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}

<?php
declare(strict_types=1);

interface ContactableInterface
{
    public function getEmail(): string;

    public function getPhone(): string;
}

<?php

declare(strict_types=1);

interface TextSearchableInterface
{
    public function matchesText(string $term): bool;
}

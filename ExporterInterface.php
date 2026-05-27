<?php

declare(strict_types=1);

interface ExporterInterface
{
    /**
     * @param array<int|string, mixed> $items
     */
    public function export(array $items): string;
}

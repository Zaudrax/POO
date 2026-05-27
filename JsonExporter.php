<?php

declare(strict_types=1);

class JsonExporter implements ExporterInterface
{
    public function export(array $items): string
    {
        return (string) json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}

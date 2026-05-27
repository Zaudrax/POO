<?php

declare(strict_types=1);

// PRINCIPE D — Dependency Inversion : JsonExporter implémente l'interface ExporterInterface
// Le code appelant n'a plus besoin de connaître JsonExporter, il connaît juste l'interface.
class JsonExporter implements ExporterInterface
{
    public function export(array $items): string
    {
        return (string) json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}

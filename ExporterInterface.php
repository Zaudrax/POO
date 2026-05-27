<?php

declare(strict_types=1);

/**
 * PRINCIPE D — Dependency Inversion Principle
 *
 * Avant : index.php dépendait directement de JsonExporter (classe concrète).
 *         Si on voulait passer au CsvExporter, il fallait modifier la fonction.
 *
 * Après : index.php dépend de cette interface (abstraction).
 *         On peut injecter n'importe quel exporter sans toucher au code appelant.
 *
 * Règle : les modules de haut niveau (index.php) ne doivent pas dépendre
 *         des modules de bas niveau (JsonExporter). Les deux doivent dépendre
 *         d'une abstraction (ExporterInterface).
 */
interface ExporterInterface
{
    /**
     * @param array<int|string, mixed> $items
     */
    public function export(array $items): string;
}

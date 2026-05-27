<?php

declare(strict_types=1);

/**
 * PRINCIPE D — Dependency Inversion Principle
 *
 * Avant : index.php instanciait directement new JsonDataRepository(...)
 *         et appelait ses méthodes. Le code de haut niveau était couplé
 *         à une implémentation concrète (JSON).
 *
 * Après : index.php dépend de cette interface. On pourrait demain créer
 *         un SqlDataRepository ou un InMemoryRepository sans toucher
 *         à aucun code appelant.
 */
interface DataRepositoryInterface
{
    /**
     * Charge les données et retourne les propriétaires, biens et loyers.
     *
     * @return array{
     *   owners: array<int, Proprietaire>,
     *   biens: array<int, BienImmobilier>,
     *   loyers: array<int, float>
     * }
     */
    public function load(): array;

    /**
     * @param array<int, Proprietaire> $owners
     * @param array<int, BienImmobilier> $biens
     * @param array<int, float|int> $loyers
     */
    public function save(array $owners, array $biens, array $loyers = []): void;
}

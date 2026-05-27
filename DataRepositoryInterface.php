<?php

declare(strict_types=1);

interface DataRepositoryInterface
{
    /**
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

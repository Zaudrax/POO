<?php

declare(strict_types=1);

interface BienTypeHandlerInterface
{
    public function supports(string $type): bool;

    /**
     * @param array<string, mixed> $data    Données brutes du fichier JSON
     * @param BienStatut           $statut  Statut déjà converti depuis l'enum
     * @param Proprietaire|null    $owner   Propriétaire déjà hydraté (peut être null)
     */
    public function hydrate(array $data, BienStatut $statut, ?Proprietaire $owner): BienImmobilier;

    /**
     * @return array<string, mixed>
     */
    public function serializeSpecificFields(BienImmobilier $bien): array;
}

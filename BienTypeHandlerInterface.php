<?php

declare(strict_types=1);

/**
 * PRINCIPE O — Open/Closed Principle
 *
 * Avant : JsonDataRepository avait des blocs if/elseif pour chaque type de bien :
 *
 *   if ($type === 'appartement') { ... }
 *   elseif ($type === 'maison') { ... }
 *
 * Problème : pour ajouter un type "Villa", on devait MODIFIER ce code existant.
 * C'est une violation de l'OCP : le code n'est pas fermé à la modification.
 *
 * Après : chaque type de bien a son propre handler qui implémente cette interface.
 * Le PropertyHydrator reçoit une liste de handlers et les interroge tour à tour.
 * Ajouter un type "Villa" = créer un VillaHandler, RIEN d'autre à modifier.
 *
 * → Ouvert à l'extension (nouveaux handlers), fermé à la modification.
 */
interface BienTypeHandlerInterface
{
    /**
     * Indique si ce handler sait gérer le type de bien donné.
     * Ex: 'appartement', 'maison', 'villa'...
     */
    public function supports(string $type): bool;

    /**
     * Construit un objet BienImmobilier depuis les données brutes du JSON.
     *
     * @param array<string, mixed> $data    Données brutes du fichier JSON
     * @param BienStatut           $statut  Statut déjà converti depuis l'enum
     * @param Proprietaire|null    $owner   Propriétaire déjà hydraté (peut être null)
     */
    public function hydrate(array $data, BienStatut $statut, ?Proprietaire $owner): BienImmobilier;

    /**
     * Retourne le tableau des champs spécifiques au type pour la sérialisation JSON.
     * Les champs communs (id, city, price...) sont gérés par PropertyHydrator.
     *
     * @return array<string, mixed>
     */
    public function serializeSpecificFields(BienImmobilier $bien): array;
}

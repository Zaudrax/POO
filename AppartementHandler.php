<?php

declare(strict_types=1);

/**
 * PRINCIPE O — Open/Closed Principle
 *
 * Handler responsable uniquement du type "Appartement".
 * Il sait comment construire un Appartement depuis les données JSON,
 * et comment sérialiser les champs spécifiques d'un Appartement.
 *
 * Si demain Appartement gagne un nouveau champ, on modifie SEULEMENT ce handler.
 * Le reste du code (PropertyHydrator, JsonDataRepository) n'est pas touché.
 */
final class AppartementHandler implements BienTypeHandlerInterface
{
    public function supports(string $type): bool
    {
        return $type === 'appartement';
    }

    public function hydrate(array $data, BienStatut $statut, ?Proprietaire $owner): BienImmobilier
    {
        $floorRaw = $data['floor'] ?? 0;
        $floor = is_int($floorRaw) ? $floorRaw : (string) $floorRaw;

        return new Appartement(
            (int) ($data['id'] ?? 0),
            (string) ($data['city'] ?? ''),
            (float) ($data['price'] ?? 0),
            (float) ($data['area'] ?? 0),
            (int) ($data['rooms'] ?? 0),
            $floor,
            (bool) ($data['hasElevator'] ?? false),
            (bool) ($data['isFurnished'] ?? false),
            $statut,
            $owner
        );
    }

    public function serializeSpecificFields(BienImmobilier $bien): array
    {
        // La vérification instanceof ici est locale et isolée dans CE handler.
        // C'est le seul endroit du code où on sait qu'on manipule un Appartement.
        if (!$bien instanceof Appartement) {
            return [];
        }

        return [
            'rooms'       => $bien->getRooms(),
            'floor'       => $bien->getFloor(),
            'hasElevator' => $bien->getHasElevator(),
            'isFurnished' => $bien->getIsFurnished(),
        ];
    }
}

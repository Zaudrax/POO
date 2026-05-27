<?php

declare(strict_types=1);

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

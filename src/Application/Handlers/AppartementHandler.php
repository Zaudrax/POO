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
        $payload = $data;
        $payload['status'] = $statut;

        return BienFactory::createAppartement($payload, $owner);
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

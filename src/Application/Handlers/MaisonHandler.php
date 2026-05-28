<?php

declare(strict_types=1);

final class MaisonHandler implements BienTypeHandlerInterface
{
    public function supports(string $type): bool
    {
        return $type === 'maison';
    }

    public function hydrate(array $data, BienStatut $statut, ?Proprietaire $owner): BienImmobilier
    {
        $payload = $data;
        $payload['status'] = $statut;

        return BienFactory::createMaison($payload, $owner);
    }

    public function serializeSpecificFields(BienImmobilier $bien): array
    {
        if (!$bien instanceof Maison) {
            return [];
        }

        return [
            'bedrooms' => $bien->getBedrooms(),
        ];
    }
}

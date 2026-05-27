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
        return new Maison(
            (int) ($data['id'] ?? 0),
            (string) ($data['city'] ?? ''),
            (float) ($data['price'] ?? 0),
            (float) ($data['area'] ?? 0),
            (int) ($data['bedrooms'] ?? 0),
            $statut,
            $owner
        );
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

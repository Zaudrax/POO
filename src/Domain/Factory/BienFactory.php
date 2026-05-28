<?php

declare(strict_types=1);

final class BienFactory
{
    /**
     * @param array<string, mixed> $data
     */
    public static function createFromArray(array $data, ?Proprietaire $owner = null): BienImmobilier
    {
        $type = strtolower((string) ($data['type'] ?? ''));

        return match ($type) {
            'appartement' => self::createAppartement($data, $owner),
            'maison' => self::createMaison($data, $owner),
            default => throw new InvalidArgumentException('Unknown property type: ' . $type),
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function createAppartement(array $data, ?Proprietaire $owner = null): Appartement
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
            self::resolveStatut($data),
            $owner
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function createMaison(array $data, ?Proprietaire $owner = null): Maison
    {
        return new Maison(
            (int) ($data['id'] ?? 0),
            (string) ($data['city'] ?? ''),
            (float) ($data['price'] ?? 0),
            (float) ($data['area'] ?? 0),
            (int) ($data['bedrooms'] ?? 0),
            self::resolveStatut($data),
            $owner
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function resolveStatut(array $data): BienStatut
    {
        $rawStatut = $data['status'] ?? BienStatut::DISPONIBLE->value;

        if ($rawStatut instanceof BienStatut) {
            return $rawStatut;
        }

        return BienStatut::from((string) $rawStatut);
    }
}

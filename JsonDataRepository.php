<?php

declare(strict_types=1);

final class JsonDataRepository
{
    public function __construct(private string $filePath)
    {
    }

    /**
     * @return array{
     *   owners: array<int, Proprietaire>,
     *   biens: array<int, BienImmobilier>,
     *   loyers: array<int, float>
     * }
     */
    public function load(): array
    {
        if (!is_file($this->filePath)) {
            throw new RuntimeException('JSON database file not found: ' . $this->filePath);
        }

        $rawContent = file_get_contents($this->filePath);

        if ($rawContent === false) {
            throw new RuntimeException('Unable to read JSON database file: ' . $this->filePath);
        }

        $data = json_decode($rawContent, true, 512, JSON_THROW_ON_ERROR);

        $ownersData = is_array($data['owners'] ?? null) ? $data['owners'] : [];
        $propertiesData = is_array($data['properties'] ?? null) ? $data['properties'] : [];

        $ownersById = [];

        foreach ($ownersData as $ownerData) {
            if (!is_array($ownerData)) {
                continue;
            }

            $owner = new Proprietaire(
                (int) ($ownerData['id'] ?? 0),
                (string) ($ownerData['lastName'] ?? ''),
                (string) ($ownerData['firstName'] ?? ''),
                (string) ($ownerData['email'] ?? ''),
                (string) ($ownerData['phone'] ?? ''),
                (string) ($ownerData['address'] ?? '')
            );

            $ownersById[$owner->id] = $owner;
        }

        $biens = [];
        $loyers = [];

        foreach ($propertiesData as $propertyData) {
            if (!is_array($propertyData)) {
                continue;
            }

            $propertyType = strtolower((string) ($propertyData['type'] ?? ''));
            $propertyId = (int) ($propertyData['id'] ?? 0);
            $ownerId = (int) ($propertyData['ownerId'] ?? 0);
            $owner = $ownersById[$ownerId] ?? null;
            $status = BienStatut::from((string) ($propertyData['status'] ?? BienStatut::DISPONIBLE->value));

            if ($propertyType === 'appartement') {
                $floorRaw = $propertyData['floor'] ?? 0;
                $floor = is_int($floorRaw) ? $floorRaw : (string) $floorRaw;

                $bien = new Appartement(
                    $propertyId,
                    (string) ($propertyData['city'] ?? ''),
                    (float) ($propertyData['price'] ?? 0),
                    (float) ($propertyData['area'] ?? 0),
                    (int) ($propertyData['rooms'] ?? 0),
                    $floor,
                    (bool) ($propertyData['hasElevator'] ?? false),
                    (bool) ($propertyData['isFurnished'] ?? false),
                    $status,
                    $owner
                );
            } elseif ($propertyType === 'maison') {
                $bien = new Maison(
                    $propertyId,
                    (string) ($propertyData['city'] ?? ''),
                    (float) ($propertyData['price'] ?? 0),
                    (float) ($propertyData['area'] ?? 0),
                    (int) ($propertyData['bedrooms'] ?? 0),
                    $status,
                    $owner
                );
            } else {
                throw new RuntimeException('Unknown property type in JSON: ' . $propertyType);
            }

            $biens[] = $bien;

            if (isset($propertyData['monthlyRent']) && is_numeric($propertyData['monthlyRent'])) {
                $loyers[$propertyId] = (float) $propertyData['monthlyRent'];
            }
        }

        return [
            'owners' => array_values($ownersById),
            'biens' => $biens,
            'loyers' => $loyers,
        ];
    }

    /**
     * @param array<int, Proprietaire> $owners
     * @param array<int, BienImmobilier> $biens
     * @param array<int, float|int> $loyers
     */
    public function save(array $owners, array $biens, array $loyers = []): void
    {
        $ownersData = [];

        foreach ($owners as $owner) {
            $ownersData[] = [
                'id' => $owner->id,
                'lastName' => $owner->getLastName(),
                'firstName' => $owner->getFirstName(),
                'email' => $owner->getEmail(),
                'phone' => $owner->getPhone(),
                'address' => $owner->getAddress(),
            ];
        }

        $propertiesData = [];

        foreach ($biens as $bien) {
            $property = [
                'type' => strtolower($bien instanceof Appartement ? 'appartement' : 'maison'),
                'id' => $bien->getId(),
                'city' => $bien->getCity(),
                'price' => $bien->getPrice(),
                'area' => $bien->getArea(),
                'status' => $bien->getStatut()->value,
                'ownerId' => $bien->getProprietaire()?->id,
            ];

            if ($bien instanceof Appartement) {
                $property['rooms'] = $bien->getRooms();
                $property['floor'] = $bien->getFloor();
                $property['hasElevator'] = $bien->getHasElevator();
                $property['isFurnished'] = $bien->getIsFurnished();
            }

            if ($bien instanceof Maison) {
                $property['bedrooms'] = $bien->getBedrooms();
            }

            if (array_key_exists($bien->getId(), $loyers)) {
                $property['monthlyRent'] = (float) $loyers[$bien->getId()];
            }

            $propertiesData[] = $property;
        }

        $payload = [
            'owners' => $ownersData,
            'properties' => $propertiesData,
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        if ($json === false) {
            throw new RuntimeException('Unable to encode JSON payload.');
        }

        $bytesWritten = file_put_contents($this->filePath, $json . PHP_EOL);

        if ($bytesWritten === false) {
            throw new RuntimeException('Unable to write JSON database file: ' . $this->filePath);
        }
    }
}

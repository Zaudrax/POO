<?php

declare(strict_types=1);

final class SqliteDataRepository implements DataRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
        private PropertyHydrator $hydrator
    ) {
        $this->createSchema();
    }

    public function isEmpty(): bool
    {
        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM owners')->fetchColumn();

        return $count === 0;
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
        $ownersById = [];

        $ownersStmt = $this->pdo->query(
            'SELECT id, last_name, first_name, email, phone, address FROM owners ORDER BY id'
        );

        $ownersRows = $ownersStmt !== false ? $ownersStmt->fetchAll(PDO::FETCH_ASSOC) : [];

        foreach ($ownersRows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $owner = $this->hydrator->hydrateOwner([
                'id' => (int) ($row['id'] ?? 0),
                'lastName' => (string) ($row['last_name'] ?? ''),
                'firstName' => (string) ($row['first_name'] ?? ''),
                'email' => (string) ($row['email'] ?? ''),
                'phone' => (string) ($row['phone'] ?? ''),
                'address' => (string) ($row['address'] ?? ''),
            ]);

            $ownersById[$owner->id] = $owner;
        }

        $biens = [];
        $loyers = [];

        $propertiesStmt = $this->pdo->query(
            'SELECT id, type, city, price, area, status, owner_id, rooms, floor, has_elevator, is_furnished, bedrooms, monthly_rent
             FROM properties
             ORDER BY id'
        );

        $propertiesRows = $propertiesStmt !== false ? $propertiesStmt->fetchAll(PDO::FETCH_ASSOC) : [];

        foreach ($propertiesRows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $result = $this->hydrator->hydrateProperty([
                'id' => (int) ($row['id'] ?? 0),
                'type' => (string) ($row['type'] ?? ''),
                'city' => (string) ($row['city'] ?? ''),
                'price' => (float) ($row['price'] ?? 0),
                'area' => (float) ($row['area'] ?? 0),
                'status' => (string) ($row['status'] ?? BienStatut::DISPONIBLE->value),
                'ownerId' => (int) ($row['owner_id'] ?? 0),
                'rooms' => (int) ($row['rooms'] ?? 0),
                'floor' => (string) ($row['floor'] ?? ''),
                'hasElevator' => ((int) ($row['has_elevator'] ?? 0)) === 1,
                'isFurnished' => ((int) ($row['is_furnished'] ?? 0)) === 1,
                'bedrooms' => (int) ($row['bedrooms'] ?? 0),
                'monthlyRent' => $row['monthly_rent'] !== null ? (float) $row['monthly_rent'] : null,
            ], $ownersById);

            $biens[] = $result['bien'];

            if ($result['loyer'] !== null) {
                $loyers[$result['bien']->getId()] = $result['loyer'];
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
        $this->pdo->beginTransaction();

        try {
            $this->pdo->exec('DELETE FROM properties');
            $this->pdo->exec('DELETE FROM owners');

            $ownerStmt = $this->pdo->prepare(
                'INSERT INTO owners (id, last_name, first_name, email, phone, address)
                 VALUES (:id, :last_name, :first_name, :email, :phone, :address)'
            );

            if ($ownerStmt === false) {
                throw new RuntimeException('Unable to prepare owners insert statement.');
            }

            foreach ($owners as $owner) {
                $ownerStmt->execute([
                    ':id' => $owner->id,
                    ':last_name' => $owner->getLastName(),
                    ':first_name' => $owner->getFirstName(),
                    ':email' => $owner->getEmail(),
                    ':phone' => $owner->getPhone(),
                    ':address' => $owner->getAddress(),
                ]);
            }

            $propertyStmt = $this->pdo->prepare(
                'INSERT INTO properties (
                    id, type, city, price, area, status, owner_id,
                    rooms, floor, has_elevator, is_furnished, bedrooms, monthly_rent
                 ) VALUES (
                    :id, :type, :city, :price, :area, :status, :owner_id,
                    :rooms, :floor, :has_elevator, :is_furnished, :bedrooms, :monthly_rent
                 )'
            );

            if ($propertyStmt === false) {
                throw new RuntimeException('Unable to prepare properties insert statement.');
            }

            foreach ($biens as $bien) {
                $serialized = $this->hydrator->serializeProperty($bien, $loyers);

                $propertyStmt->execute([
                    ':id' => (int) ($serialized['id'] ?? 0),
                    ':type' => (string) ($serialized['type'] ?? ''),
                    ':city' => (string) ($serialized['city'] ?? ''),
                    ':price' => (float) ($serialized['price'] ?? 0),
                    ':area' => (float) ($serialized['area'] ?? 0),
                    ':status' => (string) ($serialized['status'] ?? BienStatut::DISPONIBLE->value),
                    ':owner_id' => $serialized['ownerId'] !== null ? (int) $serialized['ownerId'] : null,
                    ':rooms' => array_key_exists('rooms', $serialized) ? (int) $serialized['rooms'] : null,
                    ':floor' => array_key_exists('floor', $serialized) ? (string) $serialized['floor'] : null,
                    ':has_elevator' => array_key_exists('hasElevator', $serialized) && (bool) $serialized['hasElevator'] ? 1 : 0,
                    ':is_furnished' => array_key_exists('isFurnished', $serialized) && (bool) $serialized['isFurnished'] ? 1 : 0,
                    ':bedrooms' => array_key_exists('bedrooms', $serialized) ? (int) $serialized['bedrooms'] : null,
                    ':monthly_rent' => array_key_exists('monthlyRent', $serialized)
                        ? (float) $serialized['monthlyRent']
                        : null,
                ]);
            }

            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    private function createSchema(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS owners (
                id INTEGER PRIMARY KEY,
                last_name TEXT NOT NULL,
                first_name TEXT NOT NULL,
                email TEXT NOT NULL,
                phone TEXT NOT NULL,
                address TEXT NOT NULL
            )'
        );

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS properties (
                id INTEGER PRIMARY KEY,
                type TEXT NOT NULL,
                city TEXT NOT NULL,
                price REAL NOT NULL,
                area REAL NOT NULL,
                status TEXT NOT NULL,
                owner_id INTEGER NULL,
                rooms INTEGER NULL,
                floor TEXT NULL,
                has_elevator INTEGER NOT NULL DEFAULT 0,
                is_furnished INTEGER NOT NULL DEFAULT 0,
                bedrooms INTEGER NULL,
                monthly_rent REAL NULL,
                FOREIGN KEY(owner_id) REFERENCES owners(id)
            )'
        );
    }
}

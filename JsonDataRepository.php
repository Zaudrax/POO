<?php

declare(strict_types=1);

final class JsonDataRepository implements DataRepositoryInterface
{
    /**
     * @param string           $filePath
     * @param PropertyHydrator $hydrator
     */
    public function __construct(
        private string $filePath,
        private PropertyHydrator $hydrator
    ) {
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

        $ownersData     = is_array($data['owners'] ?? null) ? $data['owners'] : [];
        $propertiesData = is_array($data['properties'] ?? null) ? $data['properties'] : [];


        $ownersById = [];

        foreach ($ownersData as $ownerData) {
            if (!is_array($ownerData)) {
                continue;
            }

            $owner = $this->hydrator->hydrateOwner($ownerData);
            $ownersById[$owner->id] = $owner;
        }

        $biens  = [];
        $loyers = [];

        foreach ($propertiesData as $propertyData) {
            if (!is_array($propertyData)) {
                continue;
            }

            $result = $this->hydrator->hydrateProperty($propertyData, $ownersById);
            $biens[] = $result['bien'];

            if ($result['loyer'] !== null) {
                $loyers[$result['bien']->getId()] = $result['loyer'];
            }
        }

        return [
            'owners' => array_values($ownersById),
            'biens'  => $biens,
            'loyers' => $loyers,
        ];
    }

    /**
     * @param array<int, Proprietaire>  $owners
     * @param array<int, BienImmobilier> $biens
     * @param array<int, float|int>     $loyers
     */
    public function save(array $owners, array $biens, array $loyers = []): void
    {

        $ownersData = [];
        foreach ($owners as $owner) {
            $ownersData[] = $this->hydrator->serializeOwner($owner);
        }

        $propertiesData = [];
        foreach ($biens as $bien) {
            $propertiesData[] = $this->hydrator->serializeProperty($bien, $loyers);
        }

        $payload = [
            'owners'     => $ownersData,
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

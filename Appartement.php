<?php
declare(strict_types=1);

require_once __DIR__ . '/BienImmobilier.php';

class Appartement extends BienImmobilier
{
    private int $rooms;
    private int|string $floor;
    private bool $hasElevator;
    private bool $isFurnished;

    public function __construct(
        int $id,
        string $city,
        int|float $price,
        int|float $area,
        int $rooms,
        int|string $floor,
        bool $hasElevator,
        bool $isFurnished,
        BienStatut $statut = BienStatut::DISPONIBLE,
        ?Proprietaire $proprietaire = null
    ) {
        parent::__construct($id, $city, $price, $area, $statut, $proprietaire);
        $this->setRooms($rooms);
        $this->setFloor($floor);
        $this->setHasElevator($hasElevator);
        $this->setIsFurnished($isFurnished);
    }

    public function getRooms(): int
    {
        return $this->rooms;
    }

    public function setRooms(int $rooms): void
    {
        if ($rooms <= 0) {
            throw new InvalidArgumentException('Rooms must be an integer greater than 0.');
        }

        $this->rooms = $rooms;
    }

    public function getFloor(): int|string
    {
        return $this->floor;
    }

    public function setFloor(int|string $floor): void
    {
        if (is_string($floor) && trim($floor) === '') {
            throw new InvalidArgumentException('Floor cannot be empty.');
        }

        $this->floor = $floor;
    }

    public function getHasElevator(): bool
    {
        return $this->hasElevator;
    }

    public function setHasElevator(bool $hasElevator): void
    {
        $this->hasElevator = $hasElevator;
    }

    public function getIsFurnished(): bool
    {
        return $this->isFurnished;
    }

    public function setIsFurnished(bool $isFurnished): void
    {
        $this->isFurnished = $isFurnished;
    }

    protected function getPropertyType(): string
    {
        return 'Appartement';
    }

    public function getFullSummary(): string
    {
        $elevatorText = $this->hasElevator ? 'oui' : 'non';
        $furnishedText = $this->isFurnished ? 'oui' : 'non';
        $ownerName = $this->getProprietaire()?->getFullName() ?? 'aucun';

        return "Type: Appartement"
            . ", id: " . $this->getId()
            . ", ville: " . $this->getCity()
            . ", prix: " . $this->getPrice() . " EUR"
            . ", surface: " . $this->getArea() . " m2"
            . ", statut: " . $this->getStatut()->value
            . ", proprietaire: " . $ownerName
            . ", pieces: " . $this->rooms
            . ", etage: " . $this->floor
            . ", ascenseur: " . $elevatorText
            . ", meuble: " . $furnishedText;
    }

    public function toExportArray(): array
    {
        $data = parent::toExportArray();
        $data['rooms'] = $this->getRooms();
        $data['floor'] = $this->getFloor();
        $data['hasElevator'] = $this->getHasElevator();
        $data['isFurnished'] = $this->getIsFurnished();

        return $data;
    }
}

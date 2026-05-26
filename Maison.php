<?php
declare(strict_types=1);

require_once __DIR__ . '/BienImmobilier.php';

class Maison extends BienImmobilier
{
    private int $bedrooms;

    public function __construct(
        int $id,
        string $city,
        int|float $price,
        int|float $area,
        int $bedrooms,
        BienStatut $statut = BienStatut::DISPONIBLE,
        ?Proprietaire $proprietaire = null
    ) {
        parent::__construct($id, $city, $price, $area, $statut, $proprietaire);
        $this->setBedrooms($bedrooms);
    }

    public function getBedrooms(): int
    {
        return $this->bedrooms;
    }

    public function setBedrooms(int $bedrooms): void
    {
        if ($bedrooms <= 0) {
            throw new InvalidArgumentException('Bedrooms must be an integer greater than 0.');
        }

        $this->bedrooms = $bedrooms;
    }

    public function calculatePricePerSquareMeter(): float
    {
        return $this->getPrice() / $this->getArea();
    }

    protected function getPropertyType(): string
    {
        return 'Maison';
    }

    public function getFullSummary(): string
    {
        $ownerName = $this->getProprietaire()?->getFullName() ?? 'aucun';

        return "Type: Maison"
            . ", id: " . $this->getId()
            . ", ville: " . $this->getCity()
            . ", prix: " . $this->getPrice() . " EUR"
            . ", surface: " . $this->getArea() . " m2"
            . ", statut: " . $this->getStatut()->value
            . ", proprietaire: " . $ownerName
            . ", chambres: " . $this->bedrooms
            . ", prix au m2: " . round($this->calculatePricePerSquareMeter(), 2) . " EUR";
    }

    public function toExportArray(): array
    {
        $data = parent::toExportArray();
        $data['bedrooms'] = $this->getBedrooms();

        return $data;
    }
}

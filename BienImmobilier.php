<?php

declare(strict_types=1);

abstract class BienImmobilier implements ExportableInterface, IdentifiableInterface, TextSearchableInterface, JsonSerializable
{
    private string $city;
    private float $price;
    private float $area;
    private BienStatut $statut;
    private ?Proprietaire $proprietaire = null;

    public function __construct(
        public readonly int $id,
        string $city,
        int|float $price,
        int|float $area,
        BienStatut $statut = BienStatut::DISPONIBLE,
        ?Proprietaire $proprietaire = null
    ) {
        if ($this->id <= 0) {
            throw new InvalidArgumentException('Property id must be greater than 0.');
        }

        $this->setCity($city);
        $this->setPrice($price);
        $this->setArea($area);
        $this->setStatut($statut);
        $this->setProprietaire($proprietaire);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $normalizedCity = trim($city);

        if ($normalizedCity === '') {
            throw new InvalidArgumentException('City cannot be empty.');
        }

        $this->city = ucwords(strtolower($normalizedCity));
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(int|float $price): void
    {
        $priceValue = (float) $price;

        if ($priceValue <= 0) {
            throw new InvalidArgumentException('Price must be greater than 0.');
        }

        if ($priceValue > 100000000) {
            throw new InvalidArgumentException('Price is unrealistically high.');
        }

        $this->price = $priceValue;
    }

    public function getArea(): float
    {
        return $this->area;
    }

    public function setArea(int|float $area): void
    {
        if ($area <= 0) {
            throw new InvalidArgumentException('Area must be greater than 0.');
        }

        $this->area = (float) $area;
    }

    public function getStatut(): BienStatut
    {
        return $this->statut;
    }

    public function setStatut(BienStatut $statut): void
    {
        $this->statut = $statut;
    }

    public function getProprietaire(): ?Proprietaire
    {
        return $this->proprietaire;
    }

    public function setProprietaire(?Proprietaire $proprietaire): void
    {
        $this->proprietaire = $proprietaire;

        if ($proprietaire !== null && !$proprietaire->hasBienId($this->id)) {
            $proprietaire->addBien($this);
        }
    }

    public function calculateProfitability(int|float $monthlyRent): float
    {
        $monthlyRent = (float) $monthlyRent;

        if ($monthlyRent < 0) {
            throw new InvalidArgumentException('Monthly rent must be a non-negative number.');
        }

        return (($monthlyRent * 12) / $this->price) * 100;
    }

    public function matchesText(string $term): bool
    {
        $term = trim($term);

        if ($term === '') {
            return true;
        }

        $ownerName = $this->proprietaire?->getFullName() ?? '';

        return stripos($this->city, $term) !== false || stripos($ownerName, $term) !== false;
    }

    abstract public function getFullSummary(): string;

    abstract protected function getPropertyType(): string;

    public function toExportArray(): array
    {
        return [
            'id' => $this->getId(),
            'type' => $this->getPropertyType(),
            'city' => $this->getCity(),
            'price' => $this->getPrice(),
            'area' => $this->getArea(),
            'status' => $this->getStatut()->value,
            'owner' => $this->getProprietaire()?->getFullName(),
            'ownerEmail' => $this->getProprietaire()?->getEmail(),
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toExportArray();
    }
}

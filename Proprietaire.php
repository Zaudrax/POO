<?php
declare(strict_types=1);

require_once __DIR__ . '/ContactableInterface.php';

final class Proprietaire implements ContactableInterface, JsonSerializable
{
    /** @var array<int, BienImmobilier> */
    private array $biens = [];

    public function __construct(
        public readonly int $id,
        private string $lastName,
        private string $firstName,
        private string $email,
        private string $phone,
        private string $address
    ) {
        if ($this->id <= 0) {
            throw new InvalidArgumentException('Owner id must be greater than 0.');
        }

        $this->setLastName($lastName);
        $this->setFirstName($firstName);
        $this->setEmail($email);
        $this->setPhone($phone);
        $this->setAddress($address);
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $lastName = trim($lastName);

        if ($lastName == '') {
            throw new InvalidArgumentException('Last name cannot be empty.');
        }

        $this->lastName = strtoupper($lastName);
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $firstName = trim($firstName);

        if ($firstName == '') {
            throw new InvalidArgumentException('First name cannot be empty.');
        }

        $this->firstName = ucwords(strtolower($firstName));
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $email = trim($email);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Email is invalid.');
        }

        $this->email = strtolower($email);
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $phone = trim($phone);

        if ($phone == '') {
            throw new InvalidArgumentException('Phone cannot be empty.');
        }

        $this->phone = $phone;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): void
    {
        $address = trim($address);

        if ($address == '') {
            throw new InvalidArgumentException('Address cannot be empty.');
        }

        $this->address = $address;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function hasBienId(int $bienId): bool
    {
        foreach ($this->biens as $bien) {
            if ($bien->getId() === $bienId) {
                return true;
            }
        }

        return false;
    }

    public function addBien(BienImmobilier $bien): void
    {
        if (!$this->hasBienId($bien->getId())) {
            $this->biens[] = $bien;
        }

        if ($bien->getProprietaire() !== $this) {
            $bien->setProprietaire($this);
        }
    }

    /** @return array<int, BienImmobilier> */
    public function getBiens(): array
    {
        return $this->biens;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id,
            'lastName' => $this->lastName,
            'firstName' => $this->firstName,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
        ];
    }
}

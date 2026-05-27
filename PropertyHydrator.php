<?php

declare(strict_types=1);

final class PropertyHydrator
{
    /**
     * @param array<BienTypeHandlerInterface> $handlers
     */
    public function __construct(private array $handlers)
    {
    }

    // HYDRATATION : tableaux JSON → objets PHP

    /**
     * @param array<string, mixed> $data
     */
    public function hydrateOwner(array $data): Proprietaire
    {
        return new Proprietaire(
            (int) ($data['id'] ?? 0),
            (string) ($data['lastName'] ?? ''),
            (string) ($data['firstName'] ?? ''),
            (string) ($data['email'] ?? ''),
            (string) ($data['phone'] ?? ''),
            (string) ($data['address'] ?? '')
        );
    }

    /**
     * @param array<string, mixed>      $data
     * @param array<int, Proprietaire>  $ownersById
     * @return array{bien: BienImmobilier, loyer: float|null}
     */
    public function hydrateProperty(array $data, array $ownersById): array
    {
        $type    = strtolower((string) ($data['type'] ?? ''));
        $statut  = BienStatut::from((string) ($data['status'] ?? BienStatut::DISPONIBLE->value));
        $ownerId = (int) ($data['ownerId'] ?? 0);
        $owner   = $ownersById[$ownerId] ?? null;

        // On cherche le handler qui supporte ce type (OCP en action)
        foreach ($this->handlers as $handler) {
            if ($handler->supports($type)) {
                $bien  = $handler->hydrate($data, $statut, $owner);
                $loyer = isset($data['monthlyRent']) && is_numeric($data['monthlyRent'])
                    ? (float) $data['monthlyRent']
                    : null;

                return ['bien' => $bien, 'loyer' => $loyer];
            }
        }

        throw new RuntimeException('Aucun handler trouvé pour le type de bien : ' . $type);
    }

    // SÉRIALISATION : objets PHP → tableaux JSON

    /**
     * @return array<string, mixed>
     */
    public function serializeOwner(Proprietaire $owner): array
    {
        return [
            'id'        => $owner->id,
            'lastName'  => $owner->getLastName(),
            'firstName' => $owner->getFirstName(),
            'email'     => $owner->getEmail(),
            'phone'     => $owner->getPhone(),
            'address'   => $owner->getAddress(),
        ];
    }

    /**
     * @param array<int, float|int> $loyers
     * @return array<string, mixed>
     */
    public function serializeProperty(BienImmobilier $bien, array $loyers): array
    {
        $type = strtolower(
            str_replace('BienImmobilier', '', get_class($bien))
        );

        $typeName = 'inconnu';

        foreach ($this->handlers as $handler) {
            if ($handler->serializeSpecificFields($bien) !== [] || $handler->supports($type)) {
                // On utilise le type retourné par la méthode supports()
                $typeName = $type;
                break;
            }
        }

        $data = [
            'type'    => $type,
            'id'      => $bien->getId(),
            'city'    => $bien->getCity(),
            'price'   => $bien->getPrice(),
            'area'    => $bien->getArea(),
            'status'  => $bien->getStatut()->value,
            'ownerId' => $bien->getProprietaire()?->id,
        ];

        foreach ($this->handlers as $handler) {
            $specific = $handler->serializeSpecificFields($bien);

            if ($specific !== []) {
                $data = array_merge($data, $specific);
                break;
            }
        }

        if (array_key_exists($bien->getId(), $loyers)) {
            $data['monthlyRent'] = (float) $loyers[$bien->getId()];
        }

        return $data;
    }
}

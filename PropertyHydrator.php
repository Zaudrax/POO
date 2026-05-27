<?php

declare(strict_types=1);

/**
 * PRINCIPE S — Single Responsibility Principle
 *
 * Avant : JsonDataRepository avait 3 responsabilités mélangées :
 *   1. Lire/écrire un fichier (I/O disque)
 *   2. Transformer des tableaux JSON en objets PHP (hydratation)
 *   3. Transformer des objets PHP en tableaux JSON (sérialisation)
 *
 * Problème : si le format JSON change, on touche à la même classe que
 * si la logique de construction d'un Appartement change. C'est 2 raisons
 * différentes de modifier la même classe → violation du SRP.
 *
 * Après : PropertyHydrator a UNE seule responsabilité = le mapping
 * entre données brutes (tableaux) et objets du domaine.
 * JsonDataRepository se limite à l'I/O (lire/écrire le fichier).
 *
 * PRINCIPE O — Open/Closed Principle (via BienTypeHandlerInterface)
 *
 * PropertyHydrator reçoit une liste de handlers (BienTypeHandlerInterface[]).
 * Il ne connaît aucun type concret. Pour ajouter un type "Villa" :
 *   → Créer VillaHandler (implements BienTypeHandlerInterface)
 *   → L'injecter dans le constructeur de PropertyHydrator
 *   → AUCUNE modification de ce fichier ni de JsonDataRepository
 */
final class PropertyHydrator
{
    /**
     * @param array<BienTypeHandlerInterface> $handlers Liste des handlers de types
     */
    public function __construct(private array $handlers)
    {
    }

    // -------------------------------------------------------------------------
    // HYDRATATION : tableaux JSON → objets PHP
    // -------------------------------------------------------------------------

    /**
     * Construit un objet Proprietaire depuis ses données brutes JSON.
     *
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
     * Construit un objet BienImmobilier depuis ses données brutes JSON.
     * Délègue la construction au handler correspondant au type du bien.
     *
     * @param array<string, mixed>      $data       Données brutes de la propriété
     * @param array<int, Proprietaire>  $ownersById Index des propriétaires par id
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

    // -------------------------------------------------------------------------
    // SÉRIALISATION : objets PHP → tableaux JSON
    // -------------------------------------------------------------------------

    /**
     * Convertit un Proprietaire en tableau pour l'encodage JSON.
     *
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
     * Convertit un BienImmobilier en tableau pour l'encodage JSON.
     * Les champs spécifiques au type sont ajoutés via le handler (OCP).
     *
     * @param array<int, float|int> $loyers
     * @return array<string, mixed>
     */
    public function serializeProperty(BienImmobilier $bien, array $loyers): array
    {
        // Champs communs à tous les types de biens
        $type = strtolower(
            str_replace('BienImmobilier', '', get_class($bien))
        );

        // On détermine le nom du type via le handler (OCP en action)
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

        // Ajout des champs spécifiques au type via le handler (OCP)
        foreach ($this->handlers as $handler) {
            $specific = $handler->serializeSpecificFields($bien);

            if ($specific !== []) {
                $data = array_merge($data, $specific);
                break;
            }
        }

        // Loyer mensuel si défini
        if (array_key_exists($bien->getId(), $loyers)) {
            $data['monthlyRent'] = (float) $loyers[$bien->getId()];
        }

        return $data;
    }
}

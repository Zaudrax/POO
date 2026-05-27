<?php

declare(strict_types=1);

/**
 * PRINCIPE I — Interface Segregation Principle
 *
 * Avant : ContactableInterface imposait à toutes les classes implémentantes
 *         de fournir getEmail() ET getPhone() en même temps.
 *
 * Problème : si un système n'a besoin que de l'email (ex: envoi d'une newsletter),
 *            il doit quand même dépendre d'une interface qui lui impose getPhone().
 *            C'est une dépendance inutile = violation de l'ISP.
 *
 * Après : on découpe en deux interfaces minimales indépendantes.
 *         Un client qui n'a besoin que de l'email dépend de EmailContactableInterface.
 *         Un client qui n'a besoin que du téléphone dépend de PhoneContactableInterface.
 *         ContactableInterface reste disponible pour ceux qui ont besoin des deux.
 *
 * Règle ISP : "Les clients ne doivent pas être forcés de dépendre d'interfaces
 *              dont ils n'utilisent pas les méthodes."
 */

/** Contrat minimal pour tout objet joignable par email uniquement. */
interface EmailContactableInterface
{
    public function getEmail(): string;
}

/** Contrat minimal pour tout objet joignable par téléphone uniquement. */
interface PhoneContactableInterface
{
    public function getPhone(): string;
}

/**
 * ContactableInterface compose les deux interfaces pour rétrocompatibilité.
 *
 * Proprietaire implements ContactableInterface → toujours valide.
 * Mais on peut maintenant type-hinter plus finement :
 *
 *   function envoyerEmail(EmailContactableInterface $contact): void
 *   function appelerTelephone(PhoneContactableInterface $contact): void
 *   function contacterComplet(ContactableInterface $contact): void
 */
interface ContactableInterface extends EmailContactableInterface, PhoneContactableInterface
{
}
